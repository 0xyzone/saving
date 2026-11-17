<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\SavingsScheme;
use App\Models\SchemeInstallment;
use App\Models\SchemePeriod;
use App\Models\SchemeSeat;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class MemberSchemesOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    public static function canView(): bool
    {
        return Auth::user()?->hasOngoingSchemeSeats() ?? false;
    }

    protected function getStats(): array
    {
        $userId = Auth::id();

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

        $currentPendingInstallments = $currentPeriodInstallments->where('status', 'pending');
        $currentPendingAmount = (float) $currentPendingInstallments->sum('total_due');
        $currentPendingCount = $currentPendingInstallments->count();
        $currentPaidCount = $currentPeriodInstallments->where('status', 'paid')->count();
        $pendingSchemeTitles = $currentPendingInstallments
            ->map(fn (SchemeInstallment $inst): ?string => $inst->scheme ? "{$inst->scheme->title} (Rd #{$inst->period?->period_number})" : null)
            ->filter()
            ->unique()
            ->values();

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
            ? 'Pending: '.$pendingSchemeTitles->implode(', ')
            : ($currentPaidCount > 0 ? "All {$currentPaidCount} paid for current period" : 'No dues generated yet');

        return [
            Stat::make('My Active Seats', $totalSeats)
                ->description("Across {$distinctSchemes} ongoing {$schemeLabel}")
                ->descriptionIcon('heroicon-m-ticket')
                ->url('#my-ongoing-schemes-section')
                ->color('primary'),

            Stat::make('Current Period Dues', 'Rs. '.number_format($currentPendingAmount, 2))
                ->description($duesDescription)
                ->descriptionIcon($currentPendingCount > 0 ? 'heroicon-m-clock' : 'heroicon-m-check-circle')
                ->url('#my-ongoing-schemes-section')
                ->color($currentPendingCount > 0 ? 'warning' : 'success'),

            Stat::make('Total Contributed', 'Rs. '.number_format($totalContributed, 2))
                ->description("Across {$totalPaidCount} paid installments")
                ->descriptionIcon('heroicon-m-banknotes')
                ->url('#my-installment-ledger-section')
                ->color('success'),

            Stat::make('Disbursed Rounds', $wonSeatsCount)
                ->description('Rs. '.number_format($totalPayoutReceived, 2).' disbursement received')
                ->descriptionIcon('heroicon-m-banknotes')
                ->url('#my-disbursed-rounds-section')
                ->color('info'),
        ];
    }
}
