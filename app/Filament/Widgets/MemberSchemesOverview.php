<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\SavingsScheme;
use App\Models\SchemeInstallment;
use App\Models\SchemePeriod;
use App\Models\SchemeSeat;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MemberSchemesOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    public static function canView(): bool
    {
        return auth()->user()?->hasOngoingSchemeSeats() ?? false;
    }

    protected function getStats(): array
    {
        $userId = auth()->id();

        $ongoingSeats = SchemeSeat::query()
            ->with('scheme')
            ->where('user_id', $userId)
            ->whereHas('scheme', fn ($query) => $query->whereIn('status', SavingsScheme::ONGOING_STATUSES))
            ->get();

        $totalSeats = $ongoingSeats->count();
        $distinctSchemes = $ongoingSeats->pluck('savings_scheme_id')->unique()->count();
        $wonSeatsCount = $ongoingSeats->whereNotNull('won_at_period')->count();

        $currentPeriodInstallments = SchemeInstallment::query()
            ->with(['scheme', 'period'])
            ->where('user_id', $userId)
            ->whereHas('scheme', fn ($query) => $query->whereIn('status', SavingsScheme::ONGOING_STATUSES))
            ->get()
            ->filter(fn (SchemeInstallment $installment): bool => $installment->period?->period_number === $installment->scheme?->current_period);

        $currentPendingAmount = (float) $currentPeriodInstallments->where('status', 'pending')->sum('total_due');
        $currentPendingCount = $currentPeriodInstallments->where('status', 'pending')->count();
        $currentPaidCount = $currentPeriodInstallments->where('status', 'paid')->count();

        $paidInstallmentsQuery = SchemeInstallment::query()
            ->where('user_id', $userId)
            ->where('status', 'paid')
            ->whereHas('scheme', fn ($query) => $query->whereIn('status', SavingsScheme::ONGOING_STATUSES));

        $totalContributed = (float) $paidInstallmentsQuery->sum('total_due');
        $totalPaidCount = $paidInstallmentsQuery->count();

        $totalPayoutReceived = (float) SchemePeriod::query()
            ->where('winner_user_id', $userId)
            ->whereHas('scheme', fn ($query) => $query->whereIn('status', SavingsScheme::ONGOING_STATUSES))
            ->sum('paid_amount');

        $schemeLabel = $distinctSchemes === 1 ? 'scheme' : 'schemes';
        $duesDescription = $currentPendingCount > 0
            ? "{$currentPendingCount} pending, {$currentPaidCount} paid this period"
            : ($currentPaidCount > 0 ? "All {$currentPaidCount} paid for current period" : 'No dues generated yet');

        return [
            Stat::make('My Active Seats', $totalSeats)
                ->description("Across {$distinctSchemes} ongoing {$schemeLabel}")
                ->descriptionIcon('heroicon-m-ticket')
                ->color('primary'),

            Stat::make('Current Period Dues', 'Rs. '.number_format($currentPendingAmount, 2))
                ->description($duesDescription)
                ->descriptionIcon($currentPendingCount > 0 ? 'heroicon-m-clock' : 'heroicon-m-check-circle')
                ->color($currentPendingCount > 0 ? 'warning' : 'success'),

            Stat::make('Total Contributed', 'Rs. '.number_format($totalContributed, 2))
                ->description("Across {$totalPaidCount} paid installments")
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Won Rounds', $wonSeatsCount)
                ->description('Rs. '.number_format($totalPayoutReceived, 2).' payout received')
                ->descriptionIcon('heroicon-m-trophy')
                ->color('info'),
        ];
    }
}
