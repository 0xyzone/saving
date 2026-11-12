<?php

namespace App\Services;

use App\Models\SavingsScheme;
use App\Models\SchemeApplication;
use App\Models\SchemeInstallment;
use App\Models\SchemePeriod;
use App\Models\SchemePeriodReservation;
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

            // 2. Create periods with due dates based on frequency (supporting Nepali calendar)
            $startBs = filled($scheme->start_date_bs)
                ? $scheme->start_date_bs
                : NepaliDateService::toBs($scheme->start_date);

            $startDate = Carbon::parse($scheme->start_date);

            for ($p = 1; $p <= $scheme->total_periods; $p++) {
                if ($scheme->frequency === 'monthly') {
                    $dueDateBs = NepaliDateService::addNepaliMonths($startBs, $p - 1);
                    $dueDate = Carbon::parse(NepaliDateService::toAd($dueDateBs));
                } else {
                    $dueDate = match ($scheme->frequency) {
                        'daily' => $startDate->copy()->addDays($p - 1),
                        'weekly' => $startDate->copy()->addWeeks($p - 1),
                        'biweekly' => $startDate->copy()->addWeeks(($p - 1) * 2),
                        default => $startDate->copy()->addMonthsNoOverflow($p - 1),
                    };
                    $dueDateBs = NepaliDateService::toBs($dueDate);
                }

                SchemePeriod::create([
                    'savings_scheme_id' => $scheme->id,
                    'period_number' => $p,
                    'due_date' => $dueDate,
                    'due_date_bs' => $dueDateBs,
                    'status' => $p === 1 ? 'in_progress' : 'upcoming',
                ]);
            }

            // 3. Generate initial installments for Period 1
            $firstPeriod = $scheme->periods()->where('period_number', 1)->first();
            if ($firstPeriod) {
                $this->generatePeriodInstallments($scheme, $firstPeriod);
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
     * Recalculate and synchronize due dates for all periods of a scheme based on Nepali calendar.
     */
    public function syncPeriodDueDates(SavingsScheme $scheme): void
    {
        $startBs = filled($scheme->start_date_bs)
            ? $scheme->start_date_bs
            : NepaliDateService::toBs($scheme->start_date);

        if (blank($startBs)) {
            return;
        }

        $periods = $scheme->periods()->orderBy('period_number')->get();

        foreach ($periods as $period) {
            $p = $period->period_number;

            if ($scheme->frequency === 'monthly') {
                $dueDateBs = NepaliDateService::addNepaliMonths($startBs, $p - 1);
                $dueDate = Carbon::parse(NepaliDateService::toAd($dueDateBs));
            } else {
                $startDate = Carbon::parse($scheme->start_date ?: NepaliDateService::toAd($startBs));
                $dueDate = match ($scheme->frequency) {
                    'daily' => $startDate->copy()->addDays($p - 1),
                    'weekly' => $startDate->copy()->addWeeks($p - 1),
                    'biweekly' => $startDate->copy()->addWeeks(($p - 1) * 2),
                    default => $startDate->copy()->addMonthsNoOverflow($p - 1),
                };
                $dueDateBs = NepaliDateService::toBs($dueDate);
            }

            $period->update([
                'due_date_bs' => $dueDateBs,
                'due_date' => $dueDate,
            ]);
        }
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
        ?string $payoutNotes = null,
        string $payoutMethod = 'Cash',
        ?string $payoutReference = null,
        ?float $paidAmount = null
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

        DB::transaction(function () use ($scheme, $period, $winningSeat, $payoutNotes, $payoutMethod, $payoutReference, $paidAmount): void {
            $payout = $this->calculateWinnerPayoutPreview($scheme, $period, $winningSeat);
            $actualPaid = $paidAmount !== null ? $paidAmount : $payout['net_payout'];
            $payoutStatus = ($actualPaid >= $payout['net_payout']) ? 'paid' : 'partial';

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
                'paid_amount' => $actualPaid,
                'payout_status' => $payoutStatus,
                'payout_method' => $payoutMethod,
                'payout_reference' => $payoutReference,
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

    /**
     * Settle or record an additional payout payment to the period winner.
     */
    public function settleWinnerPayout(
        SchemePeriod $period,
        float $amount,
        string $method = 'Cash',
        ?string $reference = null,
        ?string $notes = null
    ): void {
        $newPaidAmount = (float) $period->paid_amount + $amount;
        $status = ($newPaidAmount >= (float) $period->net_payout_amount) ? 'paid' : 'partial';

        $period->update([
            'paid_amount' => $newPaidAmount,
            'payout_status' => $status,
            'payout_method' => $method,
            'payout_reference' => $reference,
            'payout_notes' => $notes ?: $period->payout_notes,
            'paid_at' => now(),
        ]);
    }

    /**
     * Create a period reservation request or direct reservation.
     */
    public function applyPeriodReservation(
        SavingsScheme $scheme,
        SchemePeriod $period,
        User $user,
        ?SchemeSeat $seat,
        ?string $reason,
        User $requestedBy,
        bool $directApprove = false
    ): SchemePeriodReservation {
        return SchemePeriodReservation::create([
            'savings_scheme_id' => $scheme->id,
            'scheme_period_id' => $period->id,
            'scheme_seat_id' => $seat?->id,
            'user_id' => $user->id,
            'requested_by_user_id' => $requestedBy->id,
            'reason' => $reason,
            'status' => $directApprove ? 'approved' : 'pending',
            'reviewed_by' => $directApprove ? $requestedBy->id : null,
            'reviewed_at' => $directApprove ? now() : null,
        ]);
    }

    /**
     * Approve a period reservation.
     */
    public function approvePeriodReservation(SchemePeriodReservation $reservation, User $reviewer): void
    {
        $reservation->update([
            'status' => 'approved',
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ]);
    }

    /**
     * Reject a period reservation.
     */
    public function rejectPeriodReservation(SchemePeriodReservation $reservation, User $reviewer, ?string $adminNotes = null): void
    {
        $reservation->update([
            'status' => 'rejected',
            'admin_notes' => $adminNotes,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ]);
    }
}
