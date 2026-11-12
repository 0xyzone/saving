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
                                Select::make('bs_year')
                                    ->label('Nepali Year (वर्ष)')
                                    ->options(NepaliDateService::getNepaliYears())
                                    ->default(fn () => (int) substr(NepaliDateService::currentBsDate(), 0, 4))
                                    ->live()
                                    ->dehydrated(false)
                                    ->afterStateHydrated(function ($state, callable $set, $record) {
                                        $bs = $record?->start_date_bs ?? ($record?->start_date ? NepaliDateService::toBs($record->start_date) : NepaliDateService::currentBsDate());
                                        [$y] = NepaliDateService::explodeBs($bs);
                                        $set('bs_year', $y);
                                    })
                                    ->afterStateUpdated(function (callable $get, callable $set) {
                                        static::syncNepaliToEnglishDate($get, $set);
                                    })
                                    ->required(),

                                Select::make('bs_month')
                                    ->label('Nepali Month (महिना)')
                                    ->options(NepaliDateService::getNepaliMonths())
                                    ->default(fn () => (int) substr(NepaliDateService::currentBsDate(), 5, 2))
                                    ->live()
                                    ->dehydrated(false)
                                    ->afterStateHydrated(function ($state, callable $set, $record) {
                                        $bs = $record?->start_date_bs ?? ($record?->start_date ? NepaliDateService::toBs($record->start_date) : NepaliDateService::currentBsDate());
                                        [, $m] = NepaliDateService::explodeBs($bs);
                                        $set('bs_month', $m);
                                    })
                                    ->afterStateUpdated(function (callable $get, callable $set) {
                                        static::syncNepaliToEnglishDate($get, $set);
                                    })
                                    ->required(),

                                Select::make('bs_day')
                                    ->label('Nepali Day (गते)')
                                    ->options(function (callable $get) {
                                        $m = (int) ($get('bs_month') ?: 1);
                                        $y = (int) ($get('bs_year') ?: 2081);

                                        return NepaliDateService::getNepaliDays($m, $y);
                                    })
                                    ->default(fn () => (int) substr(NepaliDateService::currentBsDate(), 8, 2))
                                    ->live()
                                    ->dehydrated(false)
                                    ->afterStateHydrated(function ($state, callable $set, $record) {
                                        $bs = $record?->start_date_bs ?? ($record?->start_date ? NepaliDateService::toBs($record->start_date) : NepaliDateService::currentBsDate());
                                        [, , $d] = NepaliDateService::explodeBs($bs);
                                        $set('bs_day', $d);
                                    })
                                    ->afterStateUpdated(function (callable $get, callable $set) {
                                        static::syncNepaliToEnglishDate($get, $set);
                                    })
                                    ->required(),
                            ])
                            ->columnSpanFull(),

                        Grid::make(3)
                            ->schema([
                                TextInput::make('start_date_bs')
                                    ->label('Nepali Date (BS)')
                                    ->default(fn () => NepaliDateService::currentBsDate())
                                    ->readOnly()
                                    ->helperText(fn (callable $get) => '🗓️ '.NepaliDateService::formatBs($get('start_date_bs')))
                                    ->required(),

                                DatePicker::make('start_date')
                                    ->label('Auto-Filled English Date (AD)')
                                    ->default(now())
                                    ->readOnly()
                                    ->helperText('Auto-calculated from selected Nepali date')
                                    ->required(),

                                Select::make('frequency')
                                    ->label('Period Frequency')
                                    ->options([
                                        'monthly' => 'Monthly (Every Nepali BS Month)',
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

    public static function syncNepaliToEnglishDate(callable $get, callable $set): void
    {
        $y = (int) ($get('bs_year') ?: 2081);
        $m = (int) ($get('bs_month') ?: 1);
        $d = (int) ($get('bs_day') ?: 1);

        $maxDays = NepaliDateService::daysInMonth($m, $y);
        if ($d > $maxDays) {
            $d = $maxDays;
            $set('bs_day', $d);
        }

        $bsString = sprintf('%04d-%02d-%02d', $y, $m, $d);
        $set('start_date_bs', $bsString);

        $adString = NepaliDateService::toAd($bsString);
        $set('start_date', $adString);
    }
}
