<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\SavingsScheme;
use App\Models\SchemeInstallment;
use App\Models\SchemePeriod;
use App\Models\SchemeSeat;
use Filament\Actions\Action;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class MyOngoingSchemeSeats extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'My Ongoing Saving Schemes & Seats';

    public static function canView(): bool
    {
        return auth()->user()?->hasOngoingSchemeSeats() ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                SchemeSeat::query()
                    ->with(['scheme.organizer', 'scheme.periods', 'installments.period'])
                    ->where('user_id', auth()->id())
                    ->whereHas('scheme', fn ($query) => $query->whereIn('status', SavingsScheme::ONGOING_STATUSES))
                    ->orderBy('savings_scheme_id')
                    ->orderBy('seat_number')
            )
            ->columns([
                Stack::make([
                    Split::make([
                        Stack::make([
                            TextColumn::make('scheme.title')
                                ->weight('bold')
                                ->size('lg')
                                ->searchable(),

                            TextColumn::make('scheme.organizer.name')
                                ->icon('heroicon-m-user-circle')
                                ->iconColor('primary')
                                ->formatStateUsing(fn (?string $state): string => 'Organizer: '.($state ?? 'N/A'))
                                ->color('gray')
                                ->size('sm'),
                        ])->space(1),

                        Stack::make([
                            TextColumn::make('seat_number')
                                ->badge()
                                ->color('primary')
                                ->formatStateUsing(fn ($state): string => "Seat #{$state}"),

                            TextColumn::make('scheme.status')
                                ->badge()
                                ->color(fn (string $state): string => match ($state) {
                                    'active' => 'success',
                                    'open_for_applications' => 'info',
                                    default => 'gray',
                                })
                                ->formatStateUsing(fn (string $state): string => ucwords(str_replace('_', ' ', $state))),
                        ])->space(1)->grow(false)->alignment('end'),
                    ])->extraAttributes(['class' => 'items-start justify-between gap-3']),

                    Panel::make([
                        Stack::make([
                            Split::make([
                                TextColumn::make('current_period_info')
                                    ->icon('heroicon-m-calendar-days')
                                    ->state(function (SchemeSeat $record): string {
                                        $scheme = $record->scheme;

                                        return "Running: Period {$scheme->current_period} of {$scheme->total_periods} ({$scheme->frequency})";
                                    })
                                    ->description(function (SchemeSeat $record): ?string {
                                        $period = $this->resolveCurrentPeriod($record);

                                        if (! $period) {
                                            return null;
                                        }

                                        $ad = $period->due_date ? $period->due_date->format('M d, Y').' AD' : '';

                                        return "Due: {$period->formatted_due_date_bs}".($ad ? " ({$ad})" : '');
                                    })
                                    ->size('xs'),
                            ]),

                            Split::make([
                                TextColumn::make('current_payment_status')
                                    ->badge()
                                    ->state(function (SchemeSeat $record): string {
                                        $installment = $this->resolveCurrentInstallment($record);

                                        if (! $installment) {
                                            return 'Not Generated';
                                        }

                                        return ucfirst($installment->status).' — Rs. '.number_format((float) $installment->total_due, 2);
                                    })
                                    ->color(function (SchemeSeat $record): string {
                                        $installment = $this->resolveCurrentInstallment($record);

                                        return match ($installment?->status) {
                                            'paid' => 'success',
                                            'pending' => 'warning',
                                            'waived' => 'info',
                                            default => 'gray',
                                        };
                                    }),

                                TextColumn::make('win_status')
                                    ->badge()
                                    ->state(fn (SchemeSeat $record): string => $record->won_at_period
                                        ? "Won Round #{$record->won_at_period}"
                                        : 'Eligible to Win')
                                    ->color(fn (SchemeSeat $record): string => $record->won_at_period ? 'success' : 'gray')
                                    ->grow(false),
                            ])->extraAttributes(['class' => 'items-center justify-between gap-2']),
                        ])->space(2),
                    ]),
                ])->space(3),
            ])
            ->contentGrid([
                'default' => 1,
                'md' => 2,
                'xl' => 3,
            ])
            ->recordActions([
                Action::make('view_details')
                    ->label('View Details')
                    ->icon('heroicon-m-eye')
                    ->color('primary')
                    ->slideOver()
                    ->modalWidth('3xl')
                    ->modalHeading(fn (SchemeSeat $record): string => "{$record->scheme->title} — Seat #{$record->seat_number}")
                    ->modalContent(fn (SchemeSeat $record) => view('filament.widgets.seat-details-modal', [
                        'seat' => $record->loadMissing(['scheme.organizer', 'scheme.periods', 'installments.period']),
                        'currentPeriod' => $this->resolveCurrentPeriod($record),
                        'currentInstallment' => $this->resolveCurrentInstallment($record),
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
            ]);
    }

    protected function resolveCurrentPeriod(SchemeSeat $seat): ?SchemePeriod
    {
        return $seat->scheme?->periods
            ?->firstWhere('period_number', $seat->scheme->current_period);
    }

    protected function resolveCurrentInstallment(SchemeSeat $seat): ?SchemeInstallment
    {
        $currentPeriod = $this->resolveCurrentPeriod($seat);

        if (! $currentPeriod) {
            return null;
        }

        return $seat->installments->firstWhere('scheme_period_id', $currentPeriod->id);
    }
}
