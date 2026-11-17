<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\SavingsScheme;
use App\Models\SchemeInstallment;
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
                TextColumn::make('scheme.title')
                    ->label('Scheme')
                    ->searchable()
                    ->weight('semibold'),

                TextColumn::make('seat.seat_number')
                    ->label('Seat')
                    ->formatStateUsing(fn ($state): string => "Seat #{$state}")
                    ->badge()
                    ->color('gray'),

                TextColumn::make('period.period_number')
                    ->label('Period')
                    ->formatStateUsing(fn ($state): string => "Period #{$state}")
                    ->description(fn (SchemeInstallment $record): ?string => $record->period?->formatted_due_date_bs),

                TextColumn::make('total_due')
                    ->label('Amount')
                    ->formatStateUsing(fn ($state): string => 'Rs. '.number_format((float) $state, 2))
                    ->description(fn (SchemeInstallment $record): ?string => (float) $record->interest_amount > 0
                        ? 'Base Rs. '.number_format((float) $record->base_amount, 2).' + Int Rs. '.number_format((float) $record->interest_amount, 2)
                        : null)
                    ->weight('medium'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'pending' => 'warning',
                        'waived' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                TextColumn::make('paid_at')
                    ->label('Paid On')
                    ->dateTime('M d, Y h:i A')
                    ->placeholder('-'),

                TextColumn::make('payment_method')
                    ->label('Method')
                    ->formatStateUsing(fn (?string $state): string => $state ? ucfirst(str_replace('_', ' ', $state)) : '-')
                    ->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'waived' => 'Waived',
                    ]),
            ])
            ->defaultPaginationPageOption(5);
    }
}
