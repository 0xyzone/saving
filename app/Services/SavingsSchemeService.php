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
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SavingsSchemeService
{
    /**
     * Initialize seats and periods for a newly created savings scheme.
     * Starts with Seat #1 (Organizer) and Period #1, dynamically expanding as seats are allocated.
     */
    public function initializeScheme(SavingsScheme $scheme): void
    {
        DB::transaction(function () use ($scheme): void {
            $initialSeats = max(1, (int) ($scheme->total_seats ?: 1));

            // 1. Create initial seats (Seat #1 is reserved for organizer)
            for ($i = 1; $i <= $initialSeats; $i++) {
                $isOrganizerSeat = ($i === 1);

                SchemeSeat::firstOrCreate(
                    [
                        'savings_scheme_id' => $scheme->id,
                        'seat_number' => $i,
                    ],
                    [
                        'user_id' => $isOrganizerSeat ? $scheme->organizer_id : null,
                        'status' => $isOrganizerSeat ? 'assigned' : 'available',
                    ]
                );
            }

            // 2. Ensure periods match seats count
            $this->ensurePeriodsMatchSeats($scheme);

            // 3. Generate initial installments for Period 1
            $firstPeriod = $scheme->periods()->where('period_number', 1)->first();
            if ($firstPeriod) {
                $this->generatePeriodInstallments($scheme, $firstPeriod);
            }
        });
    }

    /**
     * Allocate a new seat to a user, dynamically auto-creating the seat and expanding rounds/periods.
     */
    public function allocateSeat(SavingsScheme $scheme, User $user, ?int $preferredSeatNumber = null): SchemeSeat
    {
        return DB::transaction(function () use ($scheme, $user, $preferredSeatNumber): SchemeSeat {
            // If scheme has available seats, check if requested or find next available
            $seat = null;
            if ($preferredSeatNumber !== null) {
                $seat = $scheme->seats()->where('seat_number', $preferredSeatNumber)->first();
                if ($seat && $seat->user_id !== null && $seat->user_id !== $user->id) {
                    throw new InvalidArgumentException("Seat #{$preferredSeatNumber} is already assigned to another user.");
                }
            }

            if (! $seat) {
                // Find first unassigned seat if any
                $seat = $scheme->seats()->whereNull('user_id')->orderBy('seat_number')->first();
            }

            if (! $seat) {
                // All current seats are occupied. Dynamically create the next seat.
                $nextSeatNumber = (int) $scheme->seats()->max('seat_number') + 1;
                if ($nextSeatNumber < 1) {
                    $nextSeatNumber = 1;
                }

                $seat = SchemeSeat::create([
                    'savings_scheme_id' => $scheme->id,
                    'seat_number' => $nextSeatNumber,
                    'user_id' => $user->id,
                    'status' => 'assigned',
                ]);
            } else {
                $seat->update([
                    'user_id' => $user->id,
                    'status' => 'assigned',
                ]);
            }

            // Sync total seats count
            $totalSeatsCount = $scheme->seats()->count();
            $scheme->update([
                'total_seats' => $totalSeatsCount,
            ]);

            // Ensure periods are auto-expanded to match the seats count (1 seat = 1 round/period)
            $this->ensurePeriodsMatchSeats($scheme);

            return $seat;
        });
    }

    /**
     * Ensure scheme periods match the total seats (auto-expanding rounds).
     */
    public function ensurePeriodsMatchSeats(SavingsScheme $scheme): void
    {
        $totalSeats = max(1, $scheme->seats()->count());
        $currentPeriodsCount = $scheme->periods()->count();

        if ($currentPeriodsCount < $totalSeats) {
            $startBs = filled($scheme->start_date_bs)
                ? $scheme->start_date_bs
                : NepaliDateService::toBs($scheme->start_date);

            $startDate = Carbon::parse($scheme->start_date ?: NepaliDateService::toAd($startBs));

            for ($p = $currentPeriodsCount + 1; $p <= $totalSeats; $p++) {
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

                SchemePeriod::firstOrCreate(
                    [
                        'savings_scheme_id' => $scheme->id,
                        'period_number' => $p,
                    ],
                    [
                        'due_date' => $dueDate,
                        'due_date_bs' => $dueDateBs,
                        'status' => $p === 1 ? 'in_progress' : 'upcoming',
                    ]
                );
            }
        }

        $scheme->update([
            'total_seats' => $totalSeats,
            'total_periods' => max($scheme->periods()->count(), $totalSeats),
        ]);

        // If there is an active current period, refresh installments for the newly added seat
        $activePeriod = $scheme->periods()->where('period_number', $scheme->current_period)->first();
        if ($activePeriod) {
            $this->generatePeriodInstallments($scheme, $activePeriod);
        }
    }

    /**
     * Assign a specific seat to a user.
     */
    public function assignSeat(SavingsScheme $scheme, int $seatNumber, User $user): SchemeSeat
    {
        return $this->allocateSeat($scheme, $user, $seatNumber);
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
        ?string $payoutMethod = null,
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

            // If a specific paidAmount is provided, record it; otherwise payout starts at 0 until settled or auto-cleared
            $actualPaid = $paidAmount !== null ? (float) $paidAmount : 0.00;
            $payoutStatus = 'pending';
            if ($actualPaid >= $payout['net_payout'] && $payout['net_payout'] > 0) {
                $payoutStatus = 'paid';
            } elseif ($actualPaid > 0) {
                $payoutStatus = 'partial';
            }

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
                'paid_at' => $actualPaid > 0 ? now() : null,
                'payout_notes' => $payoutNotes,
                'status' => 'completed',
            ]);

            // Auto-settle any unpaid installments for the winner's seats in this period
            SchemeInstallment::where('scheme_period_id', $period->id)
                ->where('user_id', $winningSeat->user_id)
                ->whereNotIn('status', ['paid'])
                ->update([
                    'status' => 'auto_settled_winner',
                    'paid_amount' => DB::raw('total_due'),
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

            // If all installments in this period were already settled before declaring winner, auto-clear payout settlement
            $this->checkAndAutoCompletePeriod($scheme, $period);
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
        ?string $notes = null,
        DateTimeInterface|string|null $paidAt = null
    ): void {
        $newPaidAmount = (float) $period->paid_amount + $amount;
        $status = ($newPaidAmount >= (float) $period->net_payout_amount) ? 'paid' : 'partial';
        $paidTimestamp = filled($paidAt) ? Carbon::parse($paidAt) : now();

        $period->update([
            'paid_amount' => $newPaidAmount,
            'payout_status' => $status,
            'payout_method' => $method,
            'payout_reference' => $reference,
            'payout_notes' => $notes ?: $period->payout_notes,
            'paid_at' => $paidTimestamp,
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

    /**
     * Record a partial or full installment settlement for a user across all their seats in a period.
     *
     * @return array{applied: float, fully_settled_period: bool, auto_completed_round: bool}
     */
    public function settleUserPeriodInstallments(
        SavingsScheme $scheme,
        SchemePeriod $period,
        int $userId,
        float $amount,
        string $method = 'Cash',
        ?string $paidAt = null,
        ?int $verifiedBy = null,
        ?string $notes = null,
    ): array {
        return DB::transaction(function () use ($scheme, $period, $userId, $amount, $method, $paidAt, $verifiedBy, $notes): array {
            $installments = SchemeInstallment::query()
                ->where('scheme_period_id', $period->id)
                ->where('user_id', $userId)
                ->whereNotIn('status', ['paid', 'auto_settled_winner'])
                ->orderBy('scheme_seat_id')
                ->get();

            $remainingToApply = max(0.00, $amount);
            $totalApplied = 0.00;
            $settlementTimestamp = filled($paidAt) ? Carbon::parse($paidAt) : now();

            foreach ($installments as $installment) {
                if ($remainingToApply <= 0) {
                    break;
                }

                $seatRemaining = $installment->remaining_due;
                if ($seatRemaining <= 0) {
                    continue;
                }

                $apply = min($remainingToApply, $seatRemaining);
                $newPaidAmount = round((float) $installment->paid_amount + $apply, 2);
                $isSeatPaid = $newPaidAmount >= (float) $installment->total_due;

                $installment->update([
                    'paid_amount' => $newPaidAmount,
                    'status' => $isSeatPaid ? 'paid' : 'partial',
                    'payment_method' => $method,
                    'paid_at' => $settlementTimestamp,
                    'verified_by' => $verifiedBy,
                    'notes' => $notes ?: $installment->notes,
                ]);

                $remainingToApply = round($remainingToApply - $apply, 2);
                $totalApplied = round($totalApplied + $apply, 2);
            }

            if ($totalApplied > 0 && ! in_array($scheme->status, ['active', 'completed'], true)) {
                $scheme->update(['status' => 'active']);
            }

            $candidateSeat = $this->resolveAutoWinnerSeat($scheme, $period);
            $allSettled = $this->areAllRequiredInstallmentsSettled($period, $candidateSeat);

            $autoCompleted = false;
            if ($allSettled) {
                $autoCompleted = $this->checkAndAutoCompletePeriod($scheme, $period);
            }

            return [
                'applied' => $totalApplied,
                'fully_settled_period' => $allSettled,
                'auto_completed_round' => $autoCompleted,
            ];
        });
    }

    /**
     * Automatically mark a period settled and paid out to the winner and advance to the next round
     * once all installments for that period are settled.
     */
    public function checkAndAutoCompletePeriod(SavingsScheme $scheme, SchemePeriod $period): bool
    {
        $period->refresh();
        $scheme->refresh();

        $candidateSeat = $this->resolveAutoWinnerSeat($scheme, $period);

        if (! $this->areAllRequiredInstallmentsSettled($period, $candidateSeat)) {
            return false;
        }

        // Case 1: Winner already declared — mark payout as fully paid and ensure round is completed & advanced
        if ($period->winning_seat_id) {
            if ($period->payout_status !== 'paid' || $period->status !== 'completed') {
                $period->update([
                    'paid_amount' => $period->net_payout_amount,
                    'payout_status' => 'paid',
                    'paid_at' => $period->paid_at ?? now(),
                    'status' => 'completed',
                ]);
            }

            if ($scheme->current_period === $period->period_number && $period->period_number < $scheme->total_periods) {
                $nextPeriodNumber = $period->period_number + 1;
                $scheme->update(['current_period' => $nextPeriodNumber]);
                $nextPeriod = $scheme->periods()->where('period_number', $nextPeriodNumber)->first();
                if ($nextPeriod) {
                    $nextPeriod->update(['status' => 'in_progress']);
                    $this->generatePeriodInstallments($scheme, $nextPeriod);
                }
            } elseif ($period->period_number >= $scheme->total_periods) {
                $scheme->update(['status' => 'completed']);
            }

            return true;
        }

        if ($candidateSeat && $candidateSeat->user_id) {
            $this->declareWinner(
                scheme: $scheme,
                period: $period,
                winningSeat: $candidateSeat,
                payoutNotes: "Automatically settled and paid out upon full collection of Period #{$period->period_number} installments.",
                payoutMethod: 'Auto-Settled',
            );

            return true;
        }

        return false;
    }

    protected function resolveAutoWinnerSeat(SavingsScheme $scheme, SchemePeriod $period): ?SchemeSeat
    {
        if ($period->winning_seat_id) {
            return $period->winningSeat;
        }

        if ($period->period_number === 1) {
            return $scheme->seats()->where('seat_number', 1)->first();
        }

        $approvedReservation = $period->reservations()
            ->where('status', 'approved')
            ->whereNotNull('scheme_seat_id')
            ->first();

        return $approvedReservation?->seat;
    }

    protected function areAllRequiredInstallmentsSettled(SchemePeriod $period, ?SchemeSeat $knownWinnerSeat = null): bool
    {
        $query = SchemeInstallment::query()
            ->where('scheme_period_id', $period->id)
            ->whereNotIn('status', ['paid', 'auto_settled_winner']);

        if ($knownWinnerSeat?->user_id) {
            $query->where('user_id', '!=', $knownWinnerSeat->user_id);
        }

        return ! $query->exists();
    }
}
