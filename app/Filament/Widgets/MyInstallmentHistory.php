<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\SavingsScheme;
use App\Models\SchemeInstallment;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Facades\Auth;

class MyInstallmentHistory extends TableWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'My Installment Payment Ledger';

    public static function canView(): bool
    {
        return Auth::user()?->hasOngoingSchemeSeats() ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                SchemeInstallment::query()
                    ->with(['scheme', 'period', 'seat'])
                    ->where('user_id', Auth::id())
                    ->whereHas('scheme', fn ($query) => $query->whereIn('status', SavingsScheme::ONGOING_STATUSES))
                    ->latest('id')
            )
            ->columns([
                Stack::make([
                    Split::make([
                        Stack::make([
                            TextColumn::make('scheme.title')
                                ->searchable()
                                ->weight('bold')
                                ->size('md'),

                            TextColumn::make('period_seat_info')
                                ->state(fn (SchemeInstallment $record): string => "Period #{$record->period?->period_number} · Seat #{$record->seat?->seat_number}")
                                ->description(fn (SchemeInstallment $record): ?string => $record->period?->formatted_due_date_bs)
                                ->color('gray')
                                ->size('xs'),
                        ])->space(1),

                        TextColumn::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'paid' => 'success',
                                'partial' => 'info',
                                'pending' => 'warning',
                                'waived' => 'info',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state)))
                            ->grow(false),
                    ])->extraAttributes(['class' => 'items-start justify-between gap-2']),

                    Panel::make([
                        Split::make([
                            TextColumn::make('total_due')
                                ->formatStateUsing(fn ($state): string => 'Due: Rs. '.number_format((float) $state, 2))
                                ->description(fn (SchemeInstallment $record): ?string => (float) $record->paid_amount > 0 && $record->status === 'partial'
                                    ? 'Paid: Rs. '.number_format((float) $record->paid_amount, 2)
                                    : null)
                                ->weight('semibold')
                                ->size('xs'),

                            TextColumn::make('payment_method')
                                ->formatStateUsing(fn (?string $state): string => $state ? ucfirst(str_replace('_', ' ', $state)) : 'Unpaid')
                                ->description(fn (SchemeInstallment $record): ?string => $record->paid_at ? $record->paid_at->format('M d, Y') : null)
                                ->color('gray')
                                ->size('xs')
                                ->grow(false),
                        ])->from('sm')->extraAttributes(['class' => 'items-center justify-between gap-2']),
                    ]),
                ])->space(2),
            ])
            ->contentGrid([
                'default' => 1,
                'md' => 2,
                'xl' => 3,
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'partial' => 'Partial',
                        'paid' => 'Paid',
                        'waived' => 'Waived',
                    ]),
            ])
            ->defaultPaginationPageOption(6);
    }
}
