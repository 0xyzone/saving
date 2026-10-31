<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\UserPaymentOption;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UsersOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Users', User::count())
                ->description('All registered users')
                ->descriptionIcon('heroicon-m-users')
                ->chart([3, 5, 7, 10, 12, User::count()])
                ->color('success'),

            Stat::make('Active Payment Methods', UserPaymentOption::where('is_active', true)->count())
                ->description('Total active payment accounts')
                ->descriptionIcon('heroicon-m-credit-card')
                ->color('primary'),

            Stat::make('Preferred Payment Methods', UserPaymentOption::where('is_preferred', true)->count())
                ->description('Total designated preferred methods')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('info'),
        ];
    }
}
