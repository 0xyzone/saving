<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Stack::make([
                    // Card Top: Avatar, Name, and Status Badges
                    Split::make([
                        ImageColumn::make('avatar_url')
                            ->circular()
                            ->disk('public')
                            ->size(46)
                            ->defaultImageUrl(url('https://ui-avatars.com/api/?background=f59e0b&color=fff&name=User'))
                            ->grow(false),

                        Stack::make([
                            TextColumn::make('name')
                                ->searchable()
                                ->sortable()
                                ->weight('bold')
                                ->size('lg'),

                            TextColumn::make('primary_contact_number')
                                ->icon('heroicon-m-phone')
                                ->iconColor('primary')
                                ->searchable()
                                ->copyable()
                                ->color('gray')
                                ->size('sm'),
                        ])->space(1),

                        TextColumn::make('payment_options_count')
                            ->counts('paymentOptions')
                            ->badge()
                            ->color('warning')
                            ->formatStateUsing(fn ($state) => "{$state} Payment".($state == 1 ? '' : 's'))
                            ->grow(false),
                    ])->extraAttributes(['class' => 'items-center justify-between gap-3']),

                    // Card Body: Details Panel for email, secondary number, address
                    Panel::make([
                        Stack::make([
                            Split::make([
                                TextColumn::make('secondary_contact_number')
                                    ->icon('heroicon-m-device-phone-mobile')
                                    ->color('gray')
                                    ->size('xs')
                                    ->placeholder('No secondary phone'),

                                TextColumn::make('email')
                                    ->icon('heroicon-m-envelope')
                                    ->color('gray')
                                    ->size('xs')
                                    ->placeholder('No email registered'),
                            ])->extraAttributes(['class' => 'justify-between gap-2']),

                            TextColumn::make('home_address')
                                ->icon('heroicon-m-map-pin')
                                ->color('gray')
                                ->size('xs')
                                ->limit(55)
                                ->placeholder('No home address recorded'),
                        ])->space(2),
                    ])->collapsible(),

                    // Card Footer: Registration Date
                    TextColumn::make('created_at')
                        ->icon('heroicon-m-calendar')
                        ->label('Joined')
                        ->formatStateUsing(fn ($state) => 'Joined '.$state->format('M d, Y'))
                        ->size('xs')
                        ->color('gray')
                        ->extraAttributes(['class' => 'mt-2 pt-2 border-t border-slate-100 dark:border-slate-800']),
                ])->space(3),
            ])
            ->contentGrid([
                'default' => 1,
                'md' => 2,
                'xl' => 3,
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make()->slideOver(),
                EditAction::make()->slideOver(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
