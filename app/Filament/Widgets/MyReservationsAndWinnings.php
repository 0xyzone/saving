<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\SavingsScheme;
use App\Models\SchemePeriod;
use App\Models\SchemePeriodReservation;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

class MyReservationsAndWinnings extends Widget
{
    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.my-reservations-and-winnings';

    public static function canView(): bool
    {
        return auth()->user()?->hasOngoingSchemeSeats() ?? false;
    }

    /**
     * @return array<string, Collection<int, mixed>>
     */
    protected function getViewData(): array
    {
        $userId = auth()->id();

        $winnings = SchemePeriod::query()
            ->with(['scheme', 'winningSeat'])
            ->where('winner_user_id', $userId)
            ->whereHas('scheme', fn ($query) => $query->whereIn('status', SavingsScheme::ONGOING_STATUSES))
            ->orderByDesc('period_number')
            ->get();

        $reservations = SchemePeriodReservation::query()
            ->with(['scheme', 'period', 'seat'])
            ->where('user_id', $userId)
            ->whereHas('scheme', fn ($query) => $query->whereIn('status', SavingsScheme::ONGOING_STATUSES))
            ->latest('id')
            ->get();

        return [
            'winnings' => $winnings,
            'reservations' => $reservations,
        ];
    }
}
