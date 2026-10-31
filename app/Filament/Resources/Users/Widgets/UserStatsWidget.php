<?php

namespace App\Filament\Resources\Users\Widgets;

use App\Models\User;
use App\Models\UserPaymentOption;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UserStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $totalUsers = User::count();
        $usersWithAvatar = User::whereNotNull('avatar_url')->count();
        $usersWithAddress = User::whereNotNull('home_address')->count();
        $totalPaymentMethods = UserPaymentOption::count();

        return [
            Stat::make('Total Users', $totalUsers)
                ->description('Registered accounts')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary'),

            Stat::make('Profiles with Avatar', $usersWithAvatar)
                ->description('Uploaded personal avatars')
                ->descriptionIcon('heroicon-m-photo')
                ->color('success'),

            Stat::make('Payment Accounts Linked', $totalPaymentMethods)
                ->description('Bank / Wallets configured')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('warning'),
        ];
    }
}
