<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\InstallmentSettlementRequest;
use App\Models\SavingsScheme;
use App\Models\SchemeInstallment;
use App\Models\SchemePeriod;
use App\Models\SchemeSeat;
use App\Services\NepaliDateService;
use App\Services\SavingsSchemeService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class MyOngoingSchemeSeats extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'My Ongoing Saving Schemes & Seats';

    public static function canView(): bool
    {
        return Auth::user()?->hasOngoingSchemeSeats() ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                SchemeSeat::query()
                    ->with(['scheme.organizer', 'scheme.periods', 'installments.period', 'settlementRequests.period', 'settlementRequests.reviewer'])
                    ->where('user_id', Auth::id())
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
                                        ? "Disbursed Rd #{$record->won_at_period}"
                                        : 'Active Turn')
                                    ->color(fn (SchemeSeat $record): string => $record->won_at_period ? 'success' : 'gray')
                                    ->grow(false),
                            ])->extraAttributes(['class' => 'items-center justify-between gap-2']),

                            TextColumn::make('settlement_application_status')
                                ->badge()
                                ->icon(function (SchemeSeat $record): ?string {
                                    $pending = $record->settlementRequests->where('status', InstallmentSettlementRequest::STATUS_PENDING)->sortByDesc('id')->first();
                                    if ($pending) {
                                        return 'heroicon-m-clock';
                                    }

                                    $latest = $record->settlementRequests->sortByDesc('id')->first();

                                    return match ($latest?->status) {
                                        InstallmentSettlementRequest::STATUS_APPROVED => 'heroicon-m-check-circle',
                                        InstallmentSettlementRequest::STATUS_REJECTED => 'heroicon-m-x-circle',
                                        default => null,
                                    };
                                })
                                ->state(function (SchemeSeat $record): ?string {
                                    $pendingCount = $record->settlementRequests
                                        ->where('status', InstallmentSettlementRequest::STATUS_PENDING)
                                        ->count();

                                    if ($pendingCount > 0) {
                                        $latestPending = $record->settlementRequests
                                            ->where('status', InstallmentSettlementRequest::STATUS_PENDING)
                                            ->sortByDesc('id')
                                            ->first();
                                        $rd = $latestPending?->period?->period_number ?? '?';
                                        $amt = number_format((float) ($latestPending?->amount ?? 0), 2);

                                        return $pendingCount > 1
                                            ? "Submitted for Review ({$pendingCount} pending)"
                                            : "Submitted for Review: Rd #{$rd} (Rs. {$amt})";
                                    }

                                    $latest = $record->settlementRequests->sortByDesc('id')->first();
                                    if (! $latest) {
                                        return null;
                                    }

                                    $rd = $latest->period?->period_number ?? '?';

                                    return 'Last Request: '.ucfirst($latest->status)." (Rd #{$rd})";
                                })
                                ->color(function (SchemeSeat $record): string {
                                    if ($record->settlementRequests->where('status', InstallmentSettlementRequest::STATUS_PENDING)->isNotEmpty()) {
                                        return 'warning';
                                    }

                                    $latest = $record->settlementRequests->sortByDesc('id')->first();

                                    return match ($latest?->status) {
                                        InstallmentSettlementRequest::STATUS_APPROVED => 'success',
                                        InstallmentSettlementRequest::STATUS_REJECTED => 'danger',
                                        default => 'gray',
                                    };
                                })
                                ->visible(fn (?SchemeSeat $record): bool => ($record?->settlementRequests?->isNotEmpty()) ?? false),
                        ])->space(2),
                    ]),
                ])->space(3),
            ])
            ->extraAttributes(['id' => 'my-ongoing-schemes-section'])
            ->contentGrid([
                'default' => 1,
                'md' => 2,
                'xl' => 3,
            ])
            ->recordActions([
                Action::make('apply_settlement')
                    ->label('Apply Settlement')
                    ->icon('heroicon-m-banknotes')
                    ->color('success')
                    ->visible(fn (SchemeSeat $record): bool => $this->eligibleInstallmentsForSeat($record)->isNotEmpty())
                    ->modalHeading(fn (SchemeSeat $record): string => "Apply Settlement — {$record->scheme->title} (Seat #{$record->seat_number})")
                    ->modalDescription('Submit your installment payment details for organizer verification.')
                    ->modalWidth('lg')
                    ->form(function (SchemeSeat $record): array {
                        $eligible = $this->eligibleInstallmentsForSeat($record);
                        /** @var SchemeInstallment|null $defaultInstallment */
                        $defaultInstallment = $eligible->first();
                        [$defaultYear, $defaultMonth, $defaultDay] = NepaliDateService::explodeBs(NepaliDateService::currentBsDate());

                        $options = $eligible->mapWithKeys(function (SchemeInstallment $inst): array {
                            $roundNum = $inst->period?->period_number ?? '?';
                            $remaining = number_format($inst->remaining_due, 2);

                            return [$inst->id => "Round #{$roundNum} — Remaining Due: Rs. {$remaining}"];
                        })->all();

                        return [
                            Select::make('scheme_installment_id')
                                ->label('Installment / Round')
                                ->options($options)
                                ->default($defaultInstallment?->id)
                                ->required()
                                ->live()
                                ->afterStateUpdated(function (Set $set, $state) use ($eligible): void {
                                    $selected = $eligible->firstWhere('id', (int) $state);
                                    if ($selected) {
                                        $set('amount', $selected->remaining_due);
                                    }
                                }),

                            Grid::make(3)
                                ->schema([
                                    Select::make('bs_year')
                                        ->label('Nepali Year (BS)')
                                        ->options(NepaliDateService::getNepaliYears())
                                        ->default($defaultYear)
                                        ->required()
                                        ->live(),

                                    Select::make('bs_month')
                                        ->label('Nepali Month')
                                        ->options(NepaliDateService::getNepaliMonths())
                                        ->default($defaultMonth)
                                        ->required()
                                        ->live(),

                                    Select::make('bs_day')
                                        ->label('Nepali Day')
                                        ->options(fn (Get $get): array => NepaliDateService::getNepaliDays(
                                            (int) ($get('bs_month') ?: $defaultMonth),
                                            (int) ($get('bs_year') ?: $defaultYear),
                                        ))
                                        ->default($defaultDay)
                                        ->required()
                                        ->live()
                                        ->helperText(function (Get $get) use ($defaultYear, $defaultMonth, $defaultDay): string {
                                            $y = (int) ($get('bs_year') ?: $defaultYear);
                                            $m = (int) ($get('bs_month') ?: $defaultMonth);
                                            $d = min((int) ($get('bs_day') ?: $defaultDay), NepaliDateService::daysInMonth($m, $y));

                                            return '🗓️ '.NepaliDateService::formatBs(sprintf('%04d-%02d-%02d', $y, $m, $d));
                                        }),
                                ]),

                            TextInput::make('amount')
                                ->label('Settlement Amount (Rs.)')
                                ->numeric()
                                ->required()
                                ->minValue(1)
                                ->default($defaultInstallment?->remaining_due ?? 0),

                            Select::make('payment_method')
                                ->label('Payment Method')
                                ->options(InstallmentSettlementRequest::PAYMENT_METHODS)
                                ->default('Cash')
                                ->required(),

                            TextInput::make('reference_id')
                                ->label('Transaction Reference / ID')
                                ->placeholder('e.g. eSewa Txn ID, Bank Ref')
                                ->maxLength(120),

                            Textarea::make('notes')
                                ->label('Notes')
                                ->rows(2)
                                ->maxLength(500),
                        ];
                    })
                    ->action(function (SchemeSeat $record, array $data): void {
                        $user = Auth::user();
                        $installment = $this->eligibleInstallmentsForSeat($record)
                            ->firstWhere('id', (int) ($data['scheme_installment_id'] ?? 0));

                        if (! $user || ! $installment) {
                            Notification::make()
                                ->danger()
                                ->title('Invalid Installment Selected')
                                ->send();

                            return;
                        }

                        $y = (int) $data['bs_year'];
                        $m = (int) $data['bs_month'];
                        $d = min((int) $data['bs_day'], NepaliDateService::daysInMonth($m, $y));
                        $bsDate = sprintf('%04d-%02d-%02d', $y, $m, $d);

                        try {
                            app(SavingsSchemeService::class)->submitInstallmentSettlementRequest(
                                installment: $installment,
                                user: $user,
                                amount: (float) $data['amount'],
                                paymentMethod: (string) $data['payment_method'],
                                paymentDateBs: $bsDate,
                                referenceId: $data['reference_id'] ?? null,
                                notes: $data['notes'] ?? null,
                            );

                            Notification::make()
                                ->success()
                                ->title('Settlement Application Submitted')
                                ->body("Your settlement request for Round #{$installment->period?->period_number} has been sent to the organizer for verification.")
                                ->send();
                        } catch (InvalidArgumentException $e) {
                            Notification::make()
                                ->danger()
                                ->title('Could Not Submit Request')
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),

                Action::make('settlement_pending')
                    ->label('Pending Verification')
                    ->icon('heroicon-m-clock')
                    ->color('warning')
                    ->disabled()
                    ->visible(fn (SchemeSeat $record): bool => $this->eligibleInstallmentsForSeat($record)->isEmpty()
                        && $record->settlementRequests->where('status', InstallmentSettlementRequest::STATUS_PENDING)->isNotEmpty()),

                Action::make('view_details')
                    ->label('View Details')
                    ->icon('heroicon-m-eye')
                    ->color('primary')
                    ->slideOver()
                    ->modalWidth('7xl')
                    ->modalHeading(fn (SchemeSeat $record): string => "{$record->scheme->title} — Seat #{$record->seat_number}")
                    ->modalContent(fn (SchemeSeat $record) => view('filament.widgets.seat-details-modal', [
                        'seat' => $record->loadMissing(['scheme.organizer', 'scheme.periods', 'installments.period', 'settlementRequests.period', 'settlementRequests.reviewer']),
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

    /**
     * @return Collection<int, SchemeInstallment>
     */
    protected function eligibleInstallmentsForSeat(SchemeSeat $seat): Collection
    {
        $seat->loadMissing(['installments.period', 'settlementRequests']);

        $pendingInstallmentIds = $seat->settlementRequests
            ->where('status', InstallmentSettlementRequest::STATUS_PENDING)
            ->pluck('scheme_installment_id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        return $seat->installments
            ->filter(fn (SchemeInstallment $inst): bool => $inst->remaining_due > 0
                && ! in_array($inst->status, ['paid', 'waived', 'auto_settled_winner'], true)
                && ! in_array((int) $inst->id, $pendingInstallmentIds, true))
            ->sortBy(fn (SchemeInstallment $inst): int => (int) ($inst->period?->period_number ?? 0))
            ->values();
    }
}
