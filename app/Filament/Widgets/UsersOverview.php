<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\UserPaymentOption;
use BezhanSalleh\FilamentShield\Support\Utils;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class UsersOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function isSuperAdmin(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->hasRole(Utils::getSuperAdminName());
    }

    /**
     * @return int|array<string, int|null>
     */
    protected function getColumns(): int|array
    {
        return [
            'default' => 1,
            'md' => $this->isSuperAdmin() ? 3 : 2,
        ];
    }

    protected function getStats(): array
    {
        $stats = [];

        if ($this->isSuperAdmin()) {
            $stats[] = Stat::make('Total Users', User::count())
                ->description('All registered users')
                ->descriptionIcon('heroicon-m-users')
                ->chart([3, 5, 7, 10, 12, User::count()])
                ->color('success');
        }

        $userId = Auth::id();
        $activeCount = UserPaymentOption::where('user_id', $userId)->where('is_active', true)->count();
        $preferredCount = UserPaymentOption::where('user_id', $userId)->where('is_preferred', true)->count();
        $profileUrl = route('filament.panel.pages.my-profile');

        $stats[] = Stat::make('Active Payment Methods', $activeCount)
            ->description('Your active payment accounts (click to manage)')
            ->descriptionIcon('heroicon-m-credit-card')
            ->url($profileUrl)
            ->color('primary');

        $stats[] = Stat::make('Preferred Payment Methods', $preferredCount)
            ->description('Your designated preferred methods (click to manage)')
            ->descriptionIcon('heroicon-m-check-badge')
            ->url($profileUrl)
            ->color('info');

        return $stats;
    }
}
