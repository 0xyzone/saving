<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\SavingsScheme;
use App\Models\SchemePeriod;
use App\Models\SchemeSeat;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class PortfolioNetGainSimulator extends Widget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'lg' => 1,
    ];

    protected string $view = 'filament.widgets.portfolio-net-gain-simulator';

    public ?int $selectedSchemeId = null;

    /**
     * @var array<int, int|string>
     */
    public array $seatTargetPeriods = [];

    public static function canView(): bool
    {
        return Auth::user()?->hasOngoingSchemeSeats() ?? false;
    }

    public function mount(): void
    {
        $firstScheme = $this->getUserSchemes()->first();

        if ($firstScheme) {
            $this->selectedSchemeId = $firstScheme->id;
            $this->assignDefaultTargetPeriods($firstScheme);
        }
    }

    public function updatedSelectedSchemeId(): void
    {
        $this->seatTargetPeriods = [];
        $scheme = $this->getUserSchemes()->firstWhere('id', (int) $this->selectedSchemeId);

        if ($scheme) {
            $this->assignDefaultTargetPeriods($scheme);
        }
    }

    /**
     * @return Collection<int, SavingsScheme>
     */
    protected function getUserSchemes(): Collection
    {
        $userId = Auth::id();

        if (! $userId) {
            return collect();
        }

        return SavingsScheme::query()
            ->whereIn('status', SavingsScheme::ONGOING_STATUSES)
            ->whereHas('seats', fn ($q) => $q->where('user_id', $userId))
            ->with([
                'seats' => fn ($q) => $q->where('user_id', $userId)->orderBy('seat_number'),
                'periods' => fn ($q) => $q->orderBy('period_number'),
            ])
            ->orderBy('title')
            ->get();
    }

    /**
     * @return Collection<int, SchemePeriod>
     */
    protected function getUpcomingPeriodsForScheme(?SavingsScheme $scheme): Collection
    {
        if (! $scheme) {
            return collect();
        }

        return $scheme->periods
            ->filter(fn (SchemePeriod $period): bool => $period->status === 'upcoming'
                && ! $period->is_validated
                && $period->winning_seat_id === null)
            ->values();
    }

    protected function assignDefaultTargetPeriods(SavingsScheme $scheme): void
    {
        $upcoming = $this->getUpcomingPeriodsForScheme($scheme);

        if ($upcoming->isEmpty()) {
            return;
        }

        $eligibleSeats = $scheme->seats->whereNull('won_at_period')->values();

        foreach ($eligibleSeats as $index => $seat) {
            $targetPeriod = $upcoming->get($index) ?? $upcoming->last();
            if ($targetPeriod) {
                $this->seatTargetPeriods[$seat->id] = $targetPeriod->id;
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $schemes = $this->getUserSchemes();
        $selectedScheme = $schemes->firstWhere('id', (int) $this->selectedSchemeId) ?? $schemes->first();

        if ($selectedScheme && $this->selectedSchemeId !== $selectedScheme->id) {
            $this->selectedSchemeId = $selectedScheme->id;
            $this->assignDefaultTargetPeriods($selectedScheme);
        }

        $upcomingPeriods = $this->getUpcomingPeriodsForScheme($selectedScheme);
        $portfolio = $this->computePortfolio($selectedScheme, $upcomingPeriods);

        return [
            'schemes' => $schemes,
            'selectedScheme' => $selectedScheme,
            'upcomingPeriods' => $upcomingPeriods,
            'portfolio' => $portfolio,
        ];
    }

    /**
     * @param  Collection<int, SchemePeriod>  $upcomingPeriods
     * @return array{
     *     has_data: bool,
     *     total_user_seats: int,
     *     won_seats_count: int,
     *     eligible_seats_count: int,
     *     total_payout: float,
     *     total_outlay: float,
     *     combined_net_gain: float,
     *     seat_rows: array<int, array{
     *         seat_id: int,
     *         seat_number: int,
     *         is_won: bool,
     *         round_number: int|null,
     *         payout: float,
     *         outlay: float,
     *         net_gain: float,
     *     }>,
     * }
     */
    protected function computePortfolio(?SavingsScheme $scheme, Collection $upcomingPeriods): array
    {
        if (! $scheme || $scheme->seats->isEmpty()) {
            return [
                'has_data' => false,
                'total_user_seats' => 0,
                'won_seats_count' => 0,
                'eligible_seats_count' => 0,
                'total_payout' => 0.0,
                'total_outlay' => 0.0,
                'combined_net_gain' => 0.0,
                'seat_rows' => [],
            ];
        }

        $totalSeats = max(1, (int) $scheme->total_seats);
        $totalPeriods = max(1, (int) ($scheme->total_periods ?: $totalSeats));
        $installment = (float) $scheme->installment_amount;
        $interest = (float) $scheme->interest_amount;
        $khajaKharcha = (float) $scheme->khaja_kharcha;

        $periodsByNumber = $scheme->periods->keyBy('period_number');
        $upcomingById = $upcomingPeriods->keyBy('id');
        $fallbackUpcoming = $upcomingPeriods->first();

        $totalPayout = 0.0;
        $totalOutlay = 0.0;
        $wonCount = 0;
        $eligibleCount = 0;
        $seatRows = [];

        /** @var SchemeSeat $seat */
        foreach ($scheme->seats as $seat) {
            $isWon = $seat->won_at_period !== null;

            if ($isWon) {
                $wonCount++;
                $roundNumber = (int) $seat->won_at_period;
                /** @var SchemePeriod|null $wonPeriod */
                $wonPeriod = $periodsByNumber->get($roundNumber);

                if ($wonPeriod && (float) $wonPeriod->gross_pool_amount > 0) {
                    $payout = max(0.0, (float) $wonPeriod->gross_pool_amount - (float) $wonPeriod->khaja_kharcha);
                } else {
                    $pastNonOrg = max(0, $roundNumber - 2);
                    $grossPool = ($totalSeats * $installment) + ($pastNonOrg * $interest);
                    $maslanda = ($roundNumber === 1) ? 0.0 : $khajaKharcha;
                    $payout = max(0.0, $grossPool - $maslanda);
                }

                $futureInterest = ($seat->seat_number !== 1)
                    ? (max(0, $totalPeriods - $roundNumber) * $interest)
                    : 0.0;
                $outlay = ($totalPeriods * $installment) + $futureInterest;
            } else {
                $eligibleCount++;
                $selectedId = isset($this->seatTargetPeriods[$seat->id]) ? (int) $this->seatTargetPeriods[$seat->id] : null;
                /** @var SchemePeriod|null $targetPeriod */
                $targetPeriod = ($selectedId ? $upcomingById->get($selectedId) : null) ?? $fallbackUpcoming;

                if ($targetPeriod) {
                    $this->seatTargetPeriods[$seat->id] = $targetPeriod->id;
                    $roundNumber = (int) $targetPeriod->period_number;
                    $pastNonOrg = max(0, $roundNumber - 2);
                    $grossPool = ($totalSeats * $installment) + ($pastNonOrg * $interest);
                    $maslanda = ($roundNumber === 1) ? 0.0 : $khajaKharcha;
                    $payout = max(0.0, $grossPool - $maslanda);
                    $futureInterest = ($seat->seat_number !== 1)
                        ? (max(0, $totalPeriods - $roundNumber) * $interest)
                        : 0.0;
                    $outlay = ($totalPeriods * $installment) + $futureInterest;
                } else {
                    $roundNumber = null;
                    $payout = 0.0;
                    $outlay = $totalPeriods * $installment;
                }
            }

            $netGain = $payout - $outlay;
            $totalPayout += $payout;
            $totalOutlay += $outlay;

            $seatRows[] = [
                'seat_id' => $seat->id,
                'seat_number' => (int) $seat->seat_number,
                'is_won' => $isWon,
                'round_number' => $roundNumber,
                'payout' => $payout,
                'outlay' => $outlay,
                'net_gain' => $netGain,
            ];
        }

        return [
            'has_data' => true,
            'total_user_seats' => $scheme->seats->count(),
            'won_seats_count' => $wonCount,
            'eligible_seats_count' => $eligibleCount,
            'total_payout' => $totalPayout,
            'total_outlay' => $totalOutlay,
            'combined_net_gain' => $totalPayout - $totalOutlay,
            'seat_rows' => $seatRows,
        ];
    }
}
