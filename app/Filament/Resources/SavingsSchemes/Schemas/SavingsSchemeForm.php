<?php

namespace App\Filament\Resources\SavingsSchemes\Schemas;

use App\Models\User;
use App\Services\NepaliDateService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SavingsSchemeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Scheme Overview')
                    ->description('General identity and organizer configuration')
                    ->schema([
                        TextInput::make('title')
                            ->label('Scheme Title')
                            ->placeholder('e.g. 20-Month Gold Savings Committee')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Select::make('organizer_id')
                            ->label('Organizer (Holds Seat #1)')
                            ->options(fn () => User::pluck('name', 'id')->toArray())
                            ->default(fn () => auth()->id())
                            ->searchable()
                            ->required(),

                        Select::make('status')
                            ->label('Scheme Status')
                            ->options([
                                'draft' => 'Draft (Configuring)',
                                'open_for_applications' => 'Open for Applications',
                                'active' => 'Active / In Progress',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('draft')
                            ->required(),

                        Grid::make(3)
                            ->schema([
                                DatePicker::make('start_date')
                                    ->label('Start Date (Calendar Picker)')
                                    ->native(false)
                                    ->default(now())
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if ($state) {
                                            $set('start_date_bs', NepaliDateService::toBs($state));
                                        }
                                    })
                                    ->helperText(function (callable $get) {
                                        $ad = $get('start_date');
                                        if (! $ad) {
                                            return null;
                                        }

                                        return '🗓️ Nepali: '.NepaliDateService::formatBs($ad);
                                    })
                                    ->required(),

                                TextInput::make('start_date_bs')
                                    ->label('Nepali Date (Bikram Sambat BS)')
                                    ->placeholder('e.g. 2083-08-01')
                                    ->default(fn () => NepaliDateService::currentBsDate())
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if ($state && preg_match('/^\d{4}-\d{2}-\d{2}$/', $state)) {
                                            $set('start_date', NepaliDateService::toAd($state));
                                        }
                                    })
                                    ->helperText(fn ($state) => $state ? NepaliDateService::formatBs($state) : null)
                                    ->required()
                                    ->rules(['regex:/^\d{4}-\d{2}-\d{2}$/']),

                                Select::make('frequency')
                                    ->label('Period Frequency')
                                    ->options([
                                        'monthly' => 'Monthly (Nepali BS Months)',
                                        'biweekly' => 'Bi-Weekly',
                                        'weekly' => 'Weekly',
                                        'daily' => 'Daily',
                                    ])
                                    ->default('monthly')
                                    ->required(),
                            ])
                            ->columnSpanFull(),

                        Textarea::make('description')
                            ->label('Terms & Description')
                            ->rows(3)
                            ->nullable()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Financial & Period Settings')
                    ->description('Seats, installments, interest rates, and khaja kharcha rules')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('total_seats')
                                    ->label('Total Seats / Periods')
                                    ->numeric()
                                    ->default(20)
                                    ->minValue(2)
                                    ->maxValue(100)
                                    ->required()
                                    ->helperText('1 seat = 1 period. Seat #1 is reserved for the Organizer.'),

                                TextInput::make('installment_amount')
                                    ->label('Installment Amount Per Seat')
                                    ->numeric()
                                    ->prefix('Rs.')
                                    ->placeholder('10000')
                                    ->required()
                                    ->helperText('Base contribution due each period per seat.'),

                                TextInput::make('interest_amount')
                                    ->label('Past Winner Fixed Interest')
                                    ->numeric()
                                    ->prefix('Rs.')
                                    ->default(500)
                                    ->required()
                                    ->helperText('Fixed cash interest added to each past winner’s subsequent installments.'),

                                TextInput::make('khaja_kharcha')
                                    ->label('Khaja Kharcha (Snack Expense)')
                                    ->numeric()
                                    ->prefix('Rs.')
                                    ->default(1000)
                                    ->required()
                                    ->helperText('Deducted from winner payout from Period 2 onwards (Rs. 0 deduction in Period 1).'),
                            ]),
                    ]),
            ]);
    }
}
