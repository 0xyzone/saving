<?php

namespace App\Services;

use App\Models\SavingsScheme;
use App\Models\SchemeApplication;
use App\Models\SchemeInstallment;
use App\Models\SchemePeriod;
use App\Models\SchemeSeat;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SavingsSchemeService
{
    /**
     * Initialize seats and periods for a newly created savings scheme.
     */
    public function initializeScheme(SavingsScheme $scheme): void
    {
        DB::transaction(function () use ($scheme): void {
            // 1. Create seats
            for ($i = 1; $i <= $scheme->total_seats; $i++) {
                $isOrganizerSeat = ($i === 1);

                SchemeSeat::create([
                    'savings_scheme_id' => $scheme->id,
                    'seat_number' => $i,
                    'user_id' => $isOrganizerSeat ? $scheme->organizer_id : null,
                    'status' => $isOrganizerSeat ? 'assigned' : 'available',
                ]);
            }

            // 2. Create periods with due dates based on frequency
            $startDate = Carbon::parse($scheme->start_date);

            for ($p = 1; $p <= $scheme->total_periods; $p++) {
                $dueDate = match ($scheme->frequency) {
                    'daily' => $startDate->copy()->addDays($p - 1),
                    'weekly' => $startDate->copy()->addWeeks($p - 1),
                    'biweekly' => $startDate->copy()->addWeeks(($p - 1) * 2),
                    'monthly' => $startDate->copy()->addMonthsNoOverflow($p - 1),
                    default => $startDate->copy()->addMonthsNoOverflow($p - 1),
                };

                SchemePeriod::create([
                    'savings_scheme_id' => $scheme->id,
                    'period_number' => $p,
                    'due_date' => $dueDate,
                    'status' => $p === 1 ? 'in_progress' : 'upcoming',
                ]);
            }
        });
    }

    /**
     * Assign a specific seat to a user.
     */
    public function assignSeat(SavingsScheme $scheme, int $seatNumber, User $user): SchemeSeat
    {
        $seat = $scheme->seats()->where('seat_number', $seatNumber)->firstOrFail();

        if ($seat->user_id !== null && $seat->user_id !== $user->id) {
            throw new InvalidArgumentException("Seat #{$seatNumber} is already assigned to another user.");
        }

        $seat->update([
            'user_id' => $user->id,
            'status' => 'assigned',
        ]);

        return $seat;
    }

    /**
     * Approve seat application and assign requested seats.
     *
     * @param  array<int>  $seatNumbers
     */
    public function approveApplication(SchemeApplication $application, array $seatNumbers, User $reviewer): void
    {
        DB::transaction(function () use ($application, $seatNumbers, $reviewer): void {
            $scheme = $application->scheme;

            foreach ($seatNumbers as $seatNum) {
                $this->assignSeat($scheme, $seatNum, $application->user);
            }

            $application->update([
                'status' => 'approved',
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
            ]);
        });
    }

    /**
     * Generate or recalculate installments for a given period.
     */
    public function generatePeriodInstallments(SavingsScheme $scheme, SchemePeriod $period): void
    {
        $seats = $scheme->seats()->with('user')->get();

        DB::transaction(function () use ($scheme, $period, $seats): void {
            foreach ($seats as $seat) {
                if (! $seat->user_id) {
                    continue;
                }

                $isOrganizerSeat = ($seat->seat_number === 1);
                $hasWonPrior = ($seat->won_at_period !== null && $seat->won_at_period < $period->period_number);

                // Organizer never pays interest. Other past winners pay fixed interest.
                $interestAmount = (! $isOrganizerSeat && $hasWonPrior)
                    ? (float) $scheme->interest_amount
                    : 0.00;

                $baseAmount = (float) $scheme->installment_amount;
                $totalDue = $baseAmount + $interestAmount;

                SchemeInstallment::updateOrCreate(
                    [
                        'savings_scheme_id' => $scheme->id,
                        'scheme_period_id' => $period->id,
                        'scheme_seat_id' => $seat->id,
                    ],
                    [
                        'user_id' => $seat->user_id,
                        'base_amount' => $baseAmount,
                        'interest_amount' => $interestAmount,
                        'total_due' => $totalDue,
                    ]
                );
            }
        });
    }

    /**
     * Calculate payout preview breakdown for a candidate winning seat.
     *
     * @return array{
     *     gross_pool: float,
     *     winner_dues: float,
     *     khaja_kharcha: float,
     *     net_payout: float
     * }
     */
    public function calculateWinnerPayoutPreview(SavingsScheme $scheme, SchemePeriod $period, SchemeSeat $winningSeat): array
    {
        // Ensure installments are generated
        $this->generatePeriodInstallments($scheme, $period);

        $installments = $period->installments;

        // Gross pool is the sum of total due across all seats
        $grossPool = (float) $installments->sum('total_due');

        // Winner dues is the sum of total due for all seats owned by this winner
        $winnerUserId = $winningSeat->user_id;
        $winnerDues = (float) $installments->where('user_id', $winnerUserId)->sum('total_due');

        // Period 1 (Organizer) has no khaja kharcha deducted. Period 2+ deducts khaja kharcha.
        $khajaKharcha = ($period->period_number === 1)
            ? 0.00
            : (float) $scheme->khaja_kharcha;

        $netPayout = max(0.00, $grossPool - $winnerDues - $khajaKharcha);

        return [
            'gross_pool' => $grossPool,
            'winner_dues' => $winnerDues,
            'khaja_kharcha' => $khajaKharcha,
            'net_payout' => $netPayout,
        ];
    }

    /**
     * Declare the winner for a period and auto-settle the winner's seat dues.
     */
    public function declareWinner(
        SavingsScheme $scheme,
        SchemePeriod $period,
        SchemeSeat $winningSeat,
        ?string $payoutNotes = null
    ): void {
        if ($winningSeat->savings_scheme_id !== $scheme->id) {
            throw new InvalidArgumentException('Seat does not belong to this scheme.');
        }

        if ($period->period_number === 1 && $winningSeat->seat_number !== 1) {
            throw new InvalidArgumentException('Period 1 winner must be Seat #1 (Organizer).');
        }

        if ($period->period_number > 1 && $winningSeat->won_at_period !== null) {
            throw new InvalidArgumentException("Seat #{$winningSeat->seat_number} has already won in Period #{$winningSeat->won_at_period}.");
        }

        DB::transaction(function () use ($scheme, $period, $winningSeat, $payoutNotes): void {
            $payout = $this->calculateWinnerPayoutPreview($scheme, $period, $winningSeat);

            // Update Winning Seat
            $winningSeat->update([
                'status' => 'won',
                'won_at_period' => $period->period_number,
            ]);

            // Update Period Record
            $period->update([
                'winning_seat_id' => $winningSeat->id,
                'winner_user_id' => $winningSeat->user_id,
                'gross_pool_amount' => $payout['gross_pool'],
                'winner_dues_deducted' => $payout['winner_dues'],
                'khaja_kharcha_deducted' => $payout['khaja_kharcha'],
                'net_payout_amount' => $payout['net_payout'],
                'payout_status' => 'paid',
                'paid_at' => now(),
                'payout_notes' => $payoutNotes,
                'status' => 'completed',
            ]);

            // Auto-settle all installments for the winner's seats in this period
            SchemeInstallment::where('scheme_period_id', $period->id)
                ->where('user_id', $winningSeat->user_id)
                ->update([
                    'status' => 'auto_settled_winner',
                    'payment_method' => 'Auto-Settled (Winner Prize)',
                    'paid_at' => now(),
                    'notes' => 'Settled via winning payout deduction for Period #'.$period->period_number,
                ]);

            // Advance scheme current period or mark completed
            if ($period->period_number >= $scheme->total_periods) {
                $scheme->update(['status' => 'completed']);
            } else {
                $nextPeriodNumber = $period->period_number + 1;
                $scheme->update(['current_period' => $nextPeriodNumber]);

                $nextPeriod = $scheme->periods()->where('period_number', $nextPeriodNumber)->first();
                if ($nextPeriod) {
                    $nextPeriod->update(['status' => 'in_progress']);
                    $this->generatePeriodInstallments($scheme, $nextPeriod);
                }
            }
        });
    }
}
