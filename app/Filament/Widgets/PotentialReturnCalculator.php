<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\SavingsScheme;
use App\Models\SchemeInstallment;
use App\Models\SchemePeriod;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class PotentialReturnCalculator extends Widget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.potential-return-calculator';

    public ?int $selectedSchemeId = null;

    public ?int $selectedPeriodId = null;

    public static function canView(): bool
    {
        return Auth::user()?->hasOngoingSchemeSeats() ?? false;
    }

    public function mount(): void
    {
        $schemes = $this->getUserSchemes();
        $firstScheme = $schemes->first();

        if ($firstScheme) {
            $this->selectedSchemeId = $firstScheme->id;
            $this->selectDefaultPeriodForScheme($firstScheme);
        }
    }

    public function updatedSelectedSchemeId(): void
    {
        $scheme = $this->getUserSchemes()->firstWhere('id', (int) $this->selectedSchemeId);
        $this->selectedPeriodId = null;

        if ($scheme) {
            $this->selectDefaultPeriodForScheme($scheme);
        }
    }

    protected function selectDefaultPeriodForScheme(SavingsScheme $scheme): void
    {
        $upcomingPeriod = $this->getUpcomingPeriodsForScheme($scheme)->first();
        $this->selectedPeriodId = $upcomingPeriod?->id;
    }

    /**
     * @return Collection<int, SavingsScheme>
     */
    protected function getUserSchemes(): Collection
    {
        $userId = Auth::id();

        return SavingsScheme::query()
            ->whereIn('status', SavingsScheme::ONGOING_STATUSES)
            ->whereHas('seats', fn ($query) => $query->where('user_id', $userId))
            ->with(['seats' => fn ($query) => $query->where('user_id', $userId), 'periods'])
            ->orderBy('title')
            ->get();
    }

    /**
     * @return Collection<int, SchemePeriod>
     */
    protected function getUpcomingPeriodsForScheme(SavingsScheme $scheme): Collection
    {
        return $scheme->periods
            ->filter(fn (SchemePeriod $period): bool => $period->status === 'upcoming'
                && ! $period->is_validated
                && $period->winning_seat_id === null)
            ->sortBy('period_number')
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $schemes = $this->getUserSchemes();
        $selectedScheme = $schemes->firstWhere('id', (int) $this->selectedSchemeId) ?? $schemes->first();
        $upcomingPeriods = $selectedScheme ? $this->getUpcomingPeriodsForScheme($selectedScheme) : collect();
        $selectedPeriod = $upcomingPeriods->firstWhere('id', (int) $this->selectedPeriodId) ?? $upcomingPeriods->first();

        if ($selectedScheme && ! $this->selectedSchemeId) {
            $this->selectedSchemeId = $selectedScheme->id;
        }
        if ($selectedPeriod && ! $this->selectedPeriodId) {
            $this->selectedPeriodId = $selectedPeriod->id;
        }

        $calculation = $this->computeProjection($selectedScheme, $selectedPeriod);

        return [
            'schemes' => $schemes,
            'selectedScheme' => $selectedScheme,
            'upcomingPeriods' => $upcomingPeriods,
            'selectedPeriod' => $selectedPeriod,
            'projection' => $calculation,
        ];
    }

    /**
     * @return array{
     *     has_data: bool,
     *     can_receive: bool,
     *     total_user_seats: int,
     *     eligible_seats_left: int,
     *     disbursed_seats_count: int,
     *     projected_gross_pool: float,
     *     user_own_dues: float,
     *     maslanda_deposit: float,
     *     net_disbursement: float,
     *     projected_cost_through_round: float,
     *     net_gain: float,
     *     paid_so_far: float,
     * }
     */
    protected function computeProjection(?SavingsScheme $scheme, ?SchemePeriod $period): array
    {
        if (! $scheme || ! $period) {
            return [
                'has_data' => false,
                'can_receive' => false,
                'total_user_seats' => 0,
                'eligible_seats_left' => 0,
                'disbursed_seats_count' => 0,
                'projected_gross_pool' => 0.0,
                'user_own_dues' => 0.0,
                'maslanda_deposit' => 0.0,
                'net_disbursement' => 0.0,
                'projected_cost_through_round' => 0.0,
                'net_gain' => 0.0,
                'paid_so_far' => 0.0,
            ];
        }

        $userId = Auth::id();
        $userSeats = $scheme->seats;
        $totalUserSeats = $userSeats->count();
        $eligibleSeatsLeft = $userSeats->whereNull('won_at_period')->count();
        $disbursedSeatsCount = $totalUserSeats - $eligibleSeatsLeft;

        $roundNumber = $period->period_number;
        $totalSeats = max(1, (int) $scheme->total_seats);
        $installment = (float) $scheme->installment_amount;
        $interest = (float) $scheme->interest_amount;
        $maslanda = ($roundNumber === 1) ? 0.0 : (float) $scheme->khaja_kharcha;

        // Round #1 is always Organizer (0 interest). Rounds 2..(R-1) add 1 past non-organizer interest payer each.
        $pastNonOrgInterestPayers = max(0, $roundNumber - 2);
        $projectedGrossPool = ($totalSeats * $installment) + ($pastNonOrgInterestPayers * $interest);

        // User's own seats dues deducted in Round #R (including interest on any of user's non-organizer seats already disbursed before R)
        $userPriorNonOrgDisbursed = $userSeats
            ->whereNotNull('won_at_period')
            ->where('won_at_period', '<', $roundNumber)
            ->where('seat_number', '!=', 1)
            ->count();

        $userOwnDues = ($totalUserSeats * $installment) + ($userPriorNonOrgDisbursed * $interest);

        $canReceive = $eligibleSeatsLeft > 0;
        $netDisbursement = $canReceive
            ? max(0.0, $projectedGrossPool - $userOwnDues - $maslanda)
            : 0.0;

        // Projected total contributions by user from Round #1 up to Round #(R-1) (since Round #R dues are deducted from netDisbursement)
        $projectedCostThroughRound = 0.0;
        for ($p = 1; $p < $roundNumber; $p++) {
            $priorUserInterestCount = $userSeats
                ->whereNotNull('won_at_period')
                ->where('won_at_period', '<', $p)
                ->where('seat_number', '!=', 1)
                ->count();
            $projectedCostThroughRound += ($totalUserSeats * $installment) + ($priorUserInterestCount * $interest);
        }

        $netGain = $canReceive ? ($netDisbursement - $projectedCostThroughRound) : 0.0;

        $paidSoFar = (float) SchemeInstallment::query()
            ->where('savings_scheme_id', $scheme->id)
            ->where('user_id', $userId)
            ->whereIn('status', ['paid', 'partial', 'auto_settled_winner'])
            ->sum('paid_amount');

        return [
            'has_data' => true,
            'can_receive' => $canReceive,
            'total_user_seats' => $totalUserSeats,
            'eligible_seats_left' => $eligibleSeatsLeft,
            'disbursed_seats_count' => $disbursedSeatsCount,
            'projected_gross_pool' => $projectedGrossPool,
            'user_own_dues' => $userOwnDues,
            'maslanda_deposit' => $maslanda,
            'net_disbursement' => $netDisbursement,
            'projected_cost_through_round' => $projectedCostThroughRound,
            'net_gain' => $netGain,
            'paid_so_far' => $paidSoFar,
        ];
    }
}
