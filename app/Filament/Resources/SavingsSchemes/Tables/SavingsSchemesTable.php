<?php

namespace App\Filament\Resources\SavingsSchemes\Tables;

use App\Models\SavingsScheme;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SavingsSchemesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Stack::make([
                    // Card Top: Title, Status Badge, and Organizer
                    Split::make([
                        Stack::make([
                            TextColumn::make('title')
                                ->searchable()
                                ->sortable()
                                ->weight('bold')
                                ->size('lg'),

                            TextColumn::make('organizer.name')
                                ->icon('heroicon-m-user-circle')
                                ->iconColor('primary')
                                ->formatStateUsing(fn ($state) => "Organizer: {$state}")
                                ->color('gray')
                                ->size('sm'),
                        ])->space(1),

                        TextColumn::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'active' => 'success',
                                'open_for_applications' => 'info',
                                'completed' => 'primary',
                                'cancelled' => 'danger',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => ucwords(str_replace('_', ' ', $state)))
                            ->grow(false),
                    ])->extraAttributes(['class' => 'items-center justify-between gap-3']),

                    // Card Body: Financial details
                    Panel::make([
                        Stack::make([
                            Split::make([
                                TextColumn::make('installment_amount')
                                    ->icon('heroicon-m-banknotes')
                                    ->label('Installment')
                                    ->formatStateUsing(fn ($state) => 'Rs. '.number_format((float) $state).' / seat')
                                    ->size('xs'),

                                TextColumn::make('interest_amount')
                                    ->icon('heroicon-m-arrow-trending-up')
                                    ->label('Interest')
                                    ->formatStateUsing(fn ($state) => '+Rs. '.number_format((float) $state).' interest')
                                    ->color('warning')
                                    ->size('xs'),

                                TextColumn::make('khaja_kharcha')
                                    ->icon('heroicon-m-receipt-percent')
                                    ->label('Khaja')
                                    ->formatStateUsing(fn ($state) => 'Rs. '.number_format((float) $state).' khaja')
                                    ->color('danger')
                                    ->size('xs'),
                            ])->extraAttributes(['class' => 'justify-between gap-2']),

                            Split::make([
                                TextColumn::make('progress')
                                    ->icon('heroicon-m-calendar-days')
                                    ->state(fn (SavingsScheme $record) => "Period {$record->current_period} of {$record->total_periods} ({$record->frequency})")
                                    ->color('gray')
                                    ->size('xs'),

                                TextColumn::make('start_date')
                                    ->icon('heroicon-m-clock')
                                    ->date('M d, Y')
                                    ->color('gray')
                                    ->size('xs'),
                            ])->extraAttributes(['class' => 'justify-between gap-2']),
                        ])->space(2),
                    ])->collapsible(),
                ])->space(3),
            ])
            ->contentGrid([
                'default' => 1,
                'md' => 2,
                'xl' => 3,
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                Action::make('manage')
                    ->label('Manage Scheme')
                    ->icon('heroicon-m-cog-6-tooth')
                    ->color('primary')
                    ->url(fn (SavingsScheme $record): string => route('filament.panel.resources.savings-schemes.manage', ['record' => $record])),

                EditAction::make()->slideOver(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
