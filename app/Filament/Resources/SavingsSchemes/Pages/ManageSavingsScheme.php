<?php

namespace App\Filament\Resources\SavingsSchemes\Pages;

use App\Filament\Resources\SavingsSchemes\SavingsSchemeResource;
use App\Models\SavingsScheme;
use App\Models\SchemeInstallment;
use App\Models\SchemePeriod;
use App\Models\SchemeSeat;
use App\Models\User;
use App\Services\NepaliDateService;
use App\Services\SavingsSchemeService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Grid;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class ManageSavingsScheme extends Page
{
    use InteractsWithRecord;

    protected static string $resource = SavingsSchemeResource::class;

    protected string $view = 'filament.resources.savings-schemes.pages.manage-savings-scheme';

    public int $selectedPeriodId = 0;

    public function mount(int|string|SavingsScheme $record): void
    {
        $this->record = $record instanceof SavingsScheme ? $record : $this->resolveRecord($record);
        abort_unless(SavingsSchemeResource::canView($this->record), 403);
        $this->refreshSchemeRecord();
        $this->record->loadMissing([
            'organizer.paymentOptions',
            'periods.winningSeat.user.paymentOptions',
            'periods.winnerUser.paymentOptions',
        ]);

        // Auto initialize seats and periods if not yet generated
        if ($this->record->seats()->count() === 0) {
            app(SavingsSchemeService::class)->initializeScheme($this->record);
            $this->record->refresh();
        }

        $currentPeriod = $this->record->periods()->where('period_number', $this->record->current_period)->first()
            ?? $this->record->periods()->first();

        if ($currentPeriod) {
            $this->selectedPeriodId = $currentPeriod->id;
            app(SavingsSchemeService::class)->generatePeriodInstallments($this->record, $currentPeriod);
        }
    }

    public function getTitle(): string|Htmlable
    {
        return "Manage: {$this->record->title}";
    }

    public function setSelectedPeriod(int $periodId): void
    {
        $this->selectedPeriodId = $periodId;
        $period = SchemePeriod::find($periodId);
        if ($period) {
            app(SavingsSchemeService::class)->generatePeriodInstallments($this->record, $period);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('edit_scheme')
                ->label('Edit Scheme')
                ->icon('heroicon-m-pencil-square')
                ->color('gray')
                ->modalHeading('Edit Savings Scheme')
                ->fillForm(function () {
                    $bs = $this->record->start_date_bs ?? ($this->record->start_date ? NepaliDateService::toBs($this->record->start_date) : NepaliDateService::currentBsDate());
                    [$y, $m, $d] = NepaliDateService::explodeBs($bs);

                    return [
                        'title' => $this->record->title,
                        'organizer_id' => $this->record->organizer_id,
                        'status' => $this->record->status,
                        'bs_year' => $y,
                        'bs_month' => $m,
                        'bs_day' => $d,
                        'start_date_bs' => $bs,
                        'start_date' => $this->record->start_date?->format('Y-m-d') ?: NepaliDateService::toAd($bs),
                        'interest_amount' => $this->record->interest_amount,
                        'khaja_kharcha' => $this->record->khaja_kharcha,
                        'description' => $this->record->description,
                    ];
                })
                ->form([
                    TextInput::make('title')
                        ->label('Scheme Title')
                        ->required()
                        ->maxLength(255),

                    Select::make('organizer_id')
                        ->label('Organizer (Holds Seat #1)')
                        ->options(fn () => User::pluck('name', 'id')->toArray())
                        ->searchable()
                        ->required(),

                    Select::make('status')
                        ->label('Status')
                        ->options([
                            'draft' => 'Draft',
                            'open_for_applications' => 'Open for Applications',
                            'active' => 'Active / In Progress',
                            'completed' => 'Completed',
                            'cancelled' => 'Cancelled',
                        ])
                        ->required(),

                    Grid::make(3)
                        ->schema([
                            Select::make('bs_year')
                                ->label('Nepali Year (वर्ष)')
                                ->options(NepaliDateService::getNepaliYears())
                                ->live()
                                ->dehydrated(false)
                                ->afterStateUpdated(function (callable $get, callable $set) {
                                    $y = (int) ($get('bs_year') ?: 2081);
                                    $m = (int) ($get('bs_month') ?: 1);
                                    $d = (int) ($get('bs_day') ?: 1);
                                    $max = NepaliDateService::daysInMonth($m, $y);
                                    if ($d > $max) {
                                        $d = $max;
                                        $set('bs_day', $d);
                                    }
                                    $bs = sprintf('%04d-%02d-%02d', $y, $m, $d);
                                    $set('start_date_bs', $bs);
                                    $set('start_date', NepaliDateService::toAd($bs));
                                })
                                ->required(),

                            Select::make('bs_month')
                                ->label('Nepali Month (महिना)')
                                ->options(NepaliDateService::getNepaliMonths())
                                ->live()
                                ->dehydrated(false)
                                ->afterStateUpdated(function (callable $get, callable $set) {
                                    $y = (int) ($get('bs_year') ?: 2081);
                                    $m = (int) ($get('bs_month') ?: 1);
                                    $d = (int) ($get('bs_day') ?: 1);
                                    $max = NepaliDateService::daysInMonth($m, $y);
                                    if ($d > $max) {
                                        $d = $max;
                                        $set('bs_day', $d);
                                    }
                                    $bs = sprintf('%04d-%02d-%02d', $y, $m, $d);
                                    $set('start_date_bs', $bs);
                                    $set('start_date', NepaliDateService::toAd($bs));
                                })
                                ->required(),

                            Select::make('bs_day')
                                ->label('Nepali Day (गते)')
                                ->options(function (callable $get) {
                                    $m = (int) ($get('bs_month') ?: 1);
                                    $y = (int) ($get('bs_year') ?: 2081);

                                    return NepaliDateService::getNepaliDays($m, $y);
                                })
                                ->live()
                                ->dehydrated(false)
                                ->afterStateUpdated(function (callable $get, callable $set) {
                                    $y = (int) ($get('bs_year') ?: 2081);
                                    $m = (int) ($get('bs_month') ?: 1);
                                    $d = (int) ($get('bs_day') ?: 1);
                                    $bs = sprintf('%04d-%02d-%02d', $y, $m, $d);
                                    $set('start_date_bs', $bs);
                                    $set('start_date', NepaliDateService::toAd($bs));
                                })
                                ->required(),
                        ]),

                    Grid::make(2)
                        ->schema([
                            TextInput::make('start_date_bs')
                                ->label('Nepali Date (BS)')
                                ->readOnly()
                                ->helperText(fn (callable $get) => '🗓️ '.NepaliDateService::formatBs($get('start_date_bs')))
                                ->required(),

                            DatePicker::make('start_date')
                                ->label('Auto-Filled English Date (AD)')
                                ->readOnly()
                                ->helperText('Auto-calculated from selected Nepali date')
                                ->required(),
                        ]),

                    TextInput::make('interest_amount')
                        ->label('Interest per Round')
                        ->numeric()
                        ->prefix('Rs.')
                        ->required(),

                    TextInput::make('khaja_kharcha')
                        ->label('Maslanda Deposit per Round')
                        ->numeric()
                        ->prefix('Rs.')
                        ->required(),

                    Textarea::make('description')
                        ->label('Description / Notes')
                        ->rows(3),
                ])
                ->action(function (array $data): void {
                    $this->record->update($data);
                    Notification::make()
                        ->title('Scheme Updated')
                        ->body('Savings scheme details and Nepali period due dates synchronized.')
                        ->success()
                        ->send();
                    $this->record->refresh();
                }),

            Action::make('declare_winner')
                ->label(fn () => $this->record->current_period === 1
                    ? 'Confirm Period #1 Recipient (Organizer)'
                    : "Assign Recipient (Period #{$this->record->current_period})"
                )
                ->icon(fn () => $this->record->current_period === 1
                    ? 'heroicon-m-check-badge'
                    : 'heroicon-m-check-circle'
                )
                ->color(fn () => $this->record->current_period === 1 ? 'success' : 'warning')
                ->visible(fn () => SavingsSchemeResource::canShield('DeclareWinner')
                    && $this->record->status !== 'completed'
                    && $this->record->current_period <= $this->record->total_periods
                )
                ->modalHeading(fn () => $this->record->current_period === 1
                    ? 'Confirm Period #1 Recipient (Organizer)'
                    : "Assign Recipient for Period #{$this->record->current_period}"
                )
                ->modalDescription(fn () => $this->record->current_period === 1
                    ? 'Period #1 is permanently allocated to Seat #1 (Organizer). Confirming assigns the recipient and auto-settles the organizer seat dues.'
                    : 'Select the disbursement recipient seat for this round. Recipient seat dues will be auto-settled.'
                )
                ->modalSubmitActionLabel(fn () => $this->record->current_period === 1
                    ? 'Confirm Recipient'
                    : 'Assign Recipient'
                )
                ->form([
                    Placeholder::make('organizer_info')
                        ->label('Period #1 Winner')
                        ->content(function () {
                            $seat1 = $this->record->seats()->where('seat_number', 1)->with('user')->first();
                            $name = $seat1?->user?->name ?? ($this->record->organizer?->name ?? 'Organizer');
                            $grossPool = $this->record->total_seats * (float) $this->record->installment_amount;
                            $winnerDues = (float) $this->record->installment_amount;
                            $netPayout = $grossPool - $winnerDues;

                            return new HtmlString("
                                <div class='p-3 bg-amber-500/10 border border-amber-500/20 rounded-lg text-sm text-slate-800 dark:text-slate-200 space-y-2'>
                                    <div class='font-semibold text-amber-600 dark:text-amber-400 text-base flex items-center gap-1.5'>
                                        <span>🏆</span> <span>Seat #1: {$name} (Organizer)</span>
                                    </div>
                                    <div class='text-xs text-slate-500'>
                                        As per scheme rules, the Organizer wins Round #1 in full with Rs. 0 Maslanda Deposit deducted.
                                    </div>
                                    <div class='mt-2 pt-2 border-t border-amber-500/20 grid grid-cols-2 gap-2 text-xs'>
                                        <div><span class='text-slate-500'>Gross Pool:</span> <strong class='text-slate-900 dark:text-white'>Rs. ".number_format($grossPool, 2)."</strong></div>
                                        <div><span class='text-slate-500'>Own Seat Dues:</span> <strong class='text-amber-600'>-Rs. ".number_format($winnerDues, 2)."</strong></div>
                                        <div><span class='text-slate-500'>Maslanda Deposit:</span> <strong class='text-slate-900 dark:text-white'>Rs. 0.00</strong></div>
                                        <div><span class='text-slate-500 font-bold'>Calculated Net Prize:</span> <strong class='text-emerald-600 dark:text-emerald-400 font-mono'>Rs. ".number_format($netPayout, 2).'</strong></div>
                                    </div>
                                </div>
                            ');
                        })
                        ->visible(fn () => $this->record->current_period === 1),

                    Placeholder::make('reservation_info')
                        ->label('Approved Reservation')
                        ->content(function () {
                            $period = $this->record->periods()->where('period_number', $this->record->current_period)->first();
                            $res = $period?->approvedReservation;
                            if (! $res) {
                                return null;
                            }

                            return new HtmlString("
                                <div class='p-3 bg-purple-500/10 border border-purple-500/20 rounded-lg text-sm text-purple-700 dark:text-purple-300 flex items-center justify-between'>
                                    <div class='flex items-center gap-2'>
                                        <span class='text-lg'>🔖</span>
                                        <div>
                                            <div class='font-bold'>Reserved: Seat #{$res->seat?->seat_number} — {$res->user?->name}</div>
                                            <div class='text-xs opacity-80'>Reason: ".e($res->reason ?: 'No reason stated')."</div>
                                        </div>
                                    </div>
                                    <span class='text-xs font-semibold px-2 py-0.5 rounded bg-purple-500/20'>Approved</span>
                                </div>
                            ");
                        })
                        ->visible(function () {
                            if ($this->record->current_period <= 1) {
                                return false;
                            }
                            $period = $this->record->periods()->where('period_number', $this->record->current_period)->first();

                            return (bool) $period?->approvedReservation;
                        }),

                    Select::make('winning_seat_id')
                        ->label('Select Winning Seat')
                        ->options(function () {
                            return $this->record->seats()
                                ->whereNull('won_at_period')
                                ->where('seat_number', '!=', 1)
                                ->with('user')
                                ->get()
                                ->mapWithKeys(fn (SchemeSeat $seat) => [
                                    $seat->id => "Seat #{$seat->seat_number} - ".($seat->user ? $seat->user->name : 'Unassigned'),
                                ])
                                ->toArray();
                        })
                        ->default(function () {
                            $period = $this->record->periods()->where('period_number', $this->record->current_period)->first();

                            return $period?->approvedReservation?->scheme_seat_id;
                        })
                        ->helperText(function () {
                            $period = $this->record->periods()->where('period_number', $this->record->current_period)->first();
                            $res = $period?->approvedReservation;
                            if ($res) {
                                return "★ Pre-selected from Approved Reservation: Seat #{$res->seat?->seat_number} ({$res->user?->name})";
                            }

                            return null;
                        })
                        ->required(fn () => $this->record->current_period > 1)
                        ->visible(fn () => $this->record->current_period > 1)
                        ->live(),

                    Textarea::make('notes')
                        ->label('Declaration Notes')
                        ->placeholder('Optional notes regarding winner selection (e.g. Unanimous choice, raffle, special reservation)')
                        ->nullable(),
                ])
                ->action(function (array $data) {
                    $isPeriodOne = ($this->record->current_period === 1);
                    $period = $this->record->periods()->where('period_number', $this->record->current_period)->firstOrFail();
                    $seat = $isPeriodOne
                        ? $this->record->seats()->where('seat_number', 1)->firstOrFail()
                        : $this->record->seats()->findOrFail($data['winning_seat_id']);

                    app(SavingsSchemeService::class)->declareWinner(
                        $this->record,
                        $period,
                        $seat,
                        $data['notes'] ?? null
                    );

                    Notification::make()
                        ->title($isPeriodOne ? 'Period #1 Winner Confirmed (Organizer)' : "Winner Declared for Period #{$period->period_number}")
                        ->body("Seat #{$seat->seat_number} ({$seat->user?->name}) has been declared as the winner! Net Prize: Rs. ".number_format((float) $period->fresh()->net_payout_amount, 2).'. Payout will auto-clear when installments are collected.')
                        ->success()
                        ->send();

                    $this->record->refresh();

                    $nextPeriod = $this->record->periods()->where('period_number', $this->record->current_period)->first();
                    if ($nextPeriod) {
                        $this->selectedPeriodId = $nextPeriod->id;
                    }
                }),

            Action::make('raffle_draw')
                ->label('Automated Turn Selection')
                ->icon('heroicon-m-sparkles')
                ->color('info')
                ->visible(fn () => SavingsSchemeResource::canShield('DeclareWinner')
                    && $this->record->current_period > 1
                    && $this->record->status !== 'completed'
                    && $this->record->current_period <= $this->record->total_periods
                )
                ->modalHeading(fn () => "Automated Turn Selection for Period #{$this->record->current_period}")
                ->modalDescription('Run an automated turn selection among all remaining eligible seats to allocate the recipient for this round.')
                ->modalSubmitActionLabel('Confirm Recipient')
                ->form([
                    Select::make('winning_seat_id')
                        ->label('Selected Recipient Seat')
                        ->options(function () {
                            return $this->record->seats()
                                ->whereNull('won_at_period')
                                ->where('seat_number', '!=', 1)
                                ->with('user')
                                ->get()
                                ->mapWithKeys(fn (SchemeSeat $seat) => [
                                    $seat->id => "Seat #{$seat->seat_number} - ".($seat->user ? $seat->user->name : 'Unassigned'),
                                ])
                                ->toArray();
                        })
                        ->default(function () {
                            $eligible = $this->record->seats()
                                ->whereNull('won_at_period')
                                ->where('seat_number', '!=', 1)
                                ->pluck('id')
                                ->toArray();

                            return ! empty($eligible) ? $eligible[array_rand($eligible)] : null;
                        })
                        ->helperText('🎲 Candidate randomly picked from all eligible remaining seats. You may re-pick or confirm.')
                        ->required(),

                    Textarea::make('notes')
                        ->label('Notes')
                        ->default('Selected via Lucky Draw / Raffle')
                        ->nullable(),
                ])
                ->action(function (array $data) {
                    $period = $this->record->periods()->where('period_number', $this->record->current_period)->firstOrFail();
                    $seat = $this->record->seats()->findOrFail($data['winning_seat_id']);

                    app(SavingsSchemeService::class)->declareWinner(
                        $this->record,
                        $period,
                        $seat,
                        $data['notes'] ?? 'Selected via Lucky Draw / Raffle'
                    );

                    Notification::make()
                        ->title("🎉 Lucky Draw Winner Declared for Period #{$period->period_number}")
                        ->body("Seat #{$seat->seat_number} ({$seat->user?->name}) won the raffle! Net Prize: Rs. ".number_format((float) $period->fresh()->net_payout_amount, 2).'. Payout will auto-clear when installments are collected.')
                        ->success()
                        ->send();

                    $this->record->refresh();

                    $nextPeriod = $this->record->periods()->where('period_number', $this->record->current_period)->first();
                    if ($nextPeriod) {
                        $this->selectedPeriodId = $nextPeriod->id;
                    }
                }),

            Action::make('reserve_round')
                ->label('Reserve a Round')
                ->icon('heroicon-m-bookmark')
                ->color('gray')
                ->visible(fn () => SavingsSchemeResource::canShield('ManageSeats')
                    && $this->record->status !== 'completed'
                )
                ->modalHeading('Reserve a Round / Period')
                ->modalDescription('Apply for or directly assign a reserved round for a member seat.')
                ->form([
                    Select::make('scheme_period_id')
                        ->label('Select Round / Period')
                        ->options(function () {
                            return $this->record->periods()
                                ->where('period_number', '>', 1)
                                ->where('status', '!=', 'completed')
                                ->get()
                                ->mapWithKeys(fn (SchemePeriod $p) => [
                                    $p->id => "Round #{$p->period_number} — {$p->formatted_due_date_bs}".($p->approvedReservation ? " (Already Reserved: {$p->approvedReservation->user?->name})" : ''),
                                ])
                                ->toArray();
                        })
                        ->default(function () {
                            if ($this->selectedPeriodId > 0) {
                                $selected = $this->record->periods()->find($this->selectedPeriodId);
                                if ($selected && $selected->period_number > 1 && $selected->status !== 'completed') {
                                    return $selected->id;
                                }
                            }

                            return null;
                        })
                        ->required(),

                    Select::make('scheme_seat_id')
                        ->label('Member Seat')
                        ->options(function () {
                            return $this->record->seats()
                                ->where('seat_number', '!=', 1)
                                ->whereNull('won_at_period')
                                ->with('user')
                                ->get()
                                ->mapWithKeys(fn (SchemeSeat $s) => [
                                    $s->id => "Seat #{$s->seat_number} — ".($s->user ? $s->user->name : 'Unassigned'),
                                ])
                                ->toArray();
                        })
                        ->required(),

                    Textarea::make('reason')
                        ->label('Reason / Occasion for Reservation')
                        ->placeholder('e.g. Wedding in Magh, House renovation, Business expense')
                        ->nullable(),

                    Select::make('status')
                        ->label('Reservation Status')
                        ->options([
                            'approved' => 'Approved (Direct Reservation)',
                            'pending' => 'Pending Application',
                        ])
                        ->default('approved')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $period = $this->record->periods()->findOrFail($data['scheme_period_id']);
                    $seat = $this->record->seats()->findOrFail($data['scheme_seat_id']);
                    $user = $seat->user ?? User::findOrFail($this->record->organizer_id);

                    app(SavingsSchemeService::class)->applyPeriodReservation(
                        $this->record,
                        $period,
                        $user,
                        $seat,
                        $data['reason'] ?? null,
                        Auth::user(),
                        directApprove: ($data['status'] === 'approved')
                    );

                    Notification::make()
                        ->title($data['status'] === 'approved' ? 'Round Reserved' : 'Reservation Application Submitted')
                        ->body("Round #{$period->period_number} has been recorded for Seat #{$seat->seat_number} ({$user->name}).")
                        ->success()
                        ->send();

                    $this->record->refresh();
                }),

            Action::make('assign_seat')
                ->label('Allocate / Assign Seats')
                ->icon('heroicon-m-user-plus')
                ->color('primary')
                ->visible(fn () => SavingsSchemeResource::canShield('ManageSeats'))
                ->modalHeading('Allocate Seats to Members')
                ->modalDescription('Add one or more members and enter the number of seats to allocate for each selected member.')
                ->form([
                    Repeater::make('allocations')
                        ->label('Seat Allocations')
                        ->schema([
                            Select::make('user_id')
                                ->label('Member')
                                ->options(fn (): array => User::query()
                                    ->where('name', '!=', 'Super Admin')
                                    ->orderBy('name')
                                    ->get()
                                    ->mapWithKeys(fn (User $user): array => [$user->id => static::formatUserSelectLabel($user)])
                                    ->toArray())
                                ->getSearchResultsUsing(fn (string $search): array => User::query()
                                    ->where('name', '!=', 'Super Admin')
                                    ->where(function ($query) use ($search): void {
                                        $query->where('name', 'like', "%{$search}%")
                                            ->orWhere('username', 'like', "%{$search}%")
                                            ->orWhere('primary_contact_number', 'like', "%{$search}%");
                                    })
                                    ->orderBy('name')
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(fn (User $user): array => [$user->id => static::formatUserSelectLabel($user)])
                                    ->toArray())
                                ->getOptionLabelUsing(fn ($value): ?string => ($user = User::find($value)) ? static::formatUserSelectLabel($user) : null)
                                ->searchable()
                                ->required(),

                            TextInput::make('seat_count')
                                ->label('Number of Seats')
                                ->numeric()
                                ->integer()
                                ->minValue(1)
                                ->maxValue(50)
                                ->default(1)
                                ->required(),
                        ])
                        ->columns(['default' => 1, 'sm' => 2])
                        ->defaultItems(1)
                        ->minItems(1)
                        ->addActionLabel('Add Another Member')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $allocations = (array) ($data['allocations'] ?? []);
                    $service = app(SavingsSchemeService::class);
                    $allocatedCount = 0;
                    $allocatedSeats = [];

                    foreach ($allocations as $allocation) {
                        if (empty($allocation['user_id'])) {
                            continue;
                        }

                        $user = User::findOrFail($allocation['user_id']);
                        $seatCount = max(1, (int) ($allocation['seat_count'] ?? 1));

                        for ($i = 0; $i < $seatCount; $i++) {
                            $seat = $service->allocateSeat($this->record, $user);
                            $allocatedSeats[] = "#{$seat->seat_number} ({$user->name})";
                            $allocatedCount++;
                        }
                    }

                    $seatList = implode(', ', $allocatedSeats);
                    Notification::make()
                        ->title($allocatedCount === 1 ? 'Seat Allocated' : "{$allocatedCount} Seats Allocated")
                        ->body("Allocated {$seatList}. Total rounds: {$this->record->fresh()->total_periods}.")
                        ->success()
                        ->send();

                    $this->record->refresh();
                }),

            Action::make('settle_winner_payout')
                ->label('Settle Recipient Disbursement')
                ->icon('heroicon-m-banknotes')
                ->color('success')
                ->visible(fn () => SavingsSchemeResource::canShield('DeclareWinner')
                    && $this->record->periods()->where('status', 'completed')->where('payout_status', '!=', 'paid')->exists()
                )
                ->modalHeading('Settle Remaining Winner Balance')
                ->modalDescription('Record a payment towards the winner net payout balance.')
                ->form([
                    Select::make('scheme_period_id')
                        ->label('Select Completed Round')
                        ->options(function () {
                            return $this->record->periods()
                                ->where('status', 'completed')
                                ->where('payout_status', '!=', 'paid')
                                ->get()
                                ->mapWithKeys(fn (SchemePeriod $p) => [
                                    $p->id => "Round #{$p->period_number} — Winner: {$p->winnerUser?->name} (Balance: Rs. ".number_format($p->winner_balance_remaining, 2).')',
                                ])
                                ->toArray();
                        })
                        ->required()
                        ->live(),

                    TextInput::make('amount')
                        ->label('Settlement Amount (Rs.)')
                        ->numeric()
                        ->required(),

                    Grid::make(['default' => 1, 'sm' => 3])
                        ->schema([
                            Select::make('settlement_bs_year')
                                ->label('Nepali Year (वर्ष)')
                                ->options(NepaliDateService::getNepaliYears())
                                ->default(fn () => (int) substr(NepaliDateService::currentBsDate(), 0, 4))
                                ->live()
                                ->dehydrated(false)
                                ->afterStateUpdated(function (callable $get, callable $set) {
                                    $y = (int) ($get('settlement_bs_year') ?: 2081);
                                    $m = (int) ($get('settlement_bs_month') ?: 1);
                                    $d = (int) ($get('settlement_bs_day') ?: 1);
                                    $max = NepaliDateService::daysInMonth($m, $y);
                                    if ($d > $max) {
                                        $d = $max;
                                        $set('settlement_bs_day', $d);
                                    }
                                    $bs = sprintf('%04d-%02d-%02d', $y, $m, $d);
                                    $set('paid_at', NepaliDateService::toAd($bs));
                                })
                                ->required(),

                            Select::make('settlement_bs_month')
                                ->label('Nepali Month (महिना)')
                                ->options(NepaliDateService::getNepaliMonths())
                                ->default(fn () => (int) substr(NepaliDateService::currentBsDate(), 5, 2))
                                ->live()
                                ->dehydrated(false)
                                ->afterStateUpdated(function (callable $get, callable $set) {
                                    $y = (int) ($get('settlement_bs_year') ?: 2081);
                                    $m = (int) ($get('settlement_bs_month') ?: 1);
                                    $d = (int) ($get('settlement_bs_day') ?: 1);
                                    $max = NepaliDateService::daysInMonth($m, $y);
                                    if ($d > $max) {
                                        $d = $max;
                                        $set('settlement_bs_day', $d);
                                    }
                                    $bs = sprintf('%04d-%02d-%02d', $y, $m, $d);
                                    $set('paid_at', NepaliDateService::toAd($bs));
                                })
                                ->required(),

                            Select::make('settlement_bs_day')
                                ->label('Nepali Day (गते)')
                                ->options(function (callable $get) {
                                    $m = (int) ($get('settlement_bs_month') ?: 1);
                                    $y = (int) ($get('settlement_bs_year') ?: 2081);

                                    return NepaliDateService::getNepaliDays($m, $y);
                                })
                                ->default(fn () => (int) substr(NepaliDateService::currentBsDate(), 8, 2))
                                ->live()
                                ->dehydrated(false)
                                ->afterStateUpdated(function (callable $get, callable $set) {
                                    $y = (int) ($get('settlement_bs_year') ?: 2081);
                                    $m = (int) ($get('settlement_bs_month') ?: 1);
                                    $d = (int) ($get('settlement_bs_day') ?: 1);
                                    $bs = sprintf('%04d-%02d-%02d', $y, $m, $d);
                                    $set('paid_at', NepaliDateService::toAd($bs));
                                })
                                ->required(),
                        ]),

                    DatePicker::make('paid_at')
                        ->label('Settlement Date (AD Auto-Converted)')
                        ->default(now()->format('Y-m-d'))
                        ->readOnly()
                        ->helperText(fn (callable $get) => '🗓️ Nepali: '.NepaliDateService::formatBs($get('paid_at') ? NepaliDateService::toBs($get('paid_at')) : NepaliDateService::currentBsDate()))
                        ->required(),

                    Select::make('payout_method')
                        ->label('Payment Method')
                        ->options([
                            'Cash' => 'Cash',
                            'eSewa' => 'eSewa (Digital Wallet)',
                            'Khalti' => 'Khalti (Digital Wallet)',
                            'Bank Transfer' => 'Bank Transfer (A/C)',
                            'Other' => 'Other / Cheque',
                        ])
                        ->default('Cash')
                        ->required(),

                    TextInput::make('payout_reference')
                        ->label('Transaction Reference / ID')
                        ->placeholder('e.g. eSewa Txn ID, Bank UTR')
                        ->nullable(),

                    Textarea::make('notes')
                        ->label('Settlement Notes')
                        ->nullable(),
                ])
                ->action(function (array $data) {
                    $period = $this->record->periods()->findOrFail($data['scheme_period_id']);

                    app(SavingsSchemeService::class)->settleWinnerPayout(
                        $period,
                        (float) $data['amount'],
                        $data['payout_method'] ?? 'Cash',
                        $data['payout_reference'] ?? null,
                        $data['notes'] ?? null,
                        $data['paid_at'] ?? now()
                    );

                    Notification::make()
                        ->title('Payout Settlement Recorded')
                        ->body('Rs. '.number_format((float) $data['amount'], 2)." paid to {$period->winnerUser?->name} via {$data['payout_method']}.")
                        ->success()
                        ->send();

                    $this->record->refresh();
                }),
        ];
    }

    public ?int $viewPeriodId = null;

    public ?int $selectedInstallmentId = null;

    public ?int $selectedInstallmentUserId = null;

    public ?int $selectedInstallmentPeriodId = null;

    public float|string $installmentPaymentAmount = 0;

    public string $installmentPaidAt = '';

    public int $installmentPaidBsYear = 2083;

    public int $installmentPaidBsMonth = 1;

    public int $installmentPaidBsDay = 1;

    public string $installmentPaidAtBsString = '';

    public string $installmentPaymentMethod = 'Cash';

    public ?string $installmentPaymentReference = null;

    public ?string $installmentNotes = null;

    public function openPeriodDetails(int $periodId): void
    {
        $this->viewPeriodId = $periodId;
    }

    public function closePeriodDetails(): void
    {
        $this->viewPeriodId = null;
    }

    public function refreshSchemeRecord(): void
    {
        $this->record->refresh();
        $this->record->load([
            'organizer',
            'seats.user',
            'periods.winningSeat.user',
            'periods.winnerUser',
            'periods.reservations.user',
            'periods.reservations.seat',
            'periods.installments.seat',
            'periods.installments.user',
        ]);
    }

    public function refreshPeriodInstallments(?int $periodId = null): void
    {
        $service = app(SavingsSchemeService::class);

        if ($periodId !== null) {
            $period = $this->record->periods()->find($periodId);
            if ($period) {
                $service->generatePeriodInstallments($this->record, $period);
                Notification::make()
                    ->title("Round #{$period->period_number} Installments Refreshed")
                    ->body('Missing installment records have been loaded.')
                    ->success()
                    ->send();
            }
        } else {
            $service->ensurePeriodsMatchSeats($this->record);
            foreach ($this->record->periods as $p) {
                $service->generatePeriodInstallments($this->record, $p);
            }
            Notification::make()
                ->title('All Round Installments Refreshed')
                ->body('All missing round and installment records have been loaded.')
                ->success()
                ->send();
        }

        $this->refreshSchemeRecord();
    }

    public function promptUserInstallmentPayment(int $userId, int $periodId): void
    {
        $installments = SchemeInstallment::query()
            ->where('scheme_period_id', $periodId)
            ->where('user_id', $userId)
            ->whereNotIn('status', ['paid', 'auto_settled_winner'])
            ->get();

        $remaining = round((float) $installments->sum(fn (SchemeInstallment $inst): float => $inst->remaining_due), 2);

        $this->selectedInstallmentId = $installments->first()?->id;
        $this->selectedInstallmentUserId = $userId;
        $this->selectedInstallmentPeriodId = $periodId;
        $this->installmentPaymentAmount = $remaining;

        $currentBs = NepaliDateService::currentBsDate();
        [$y, $m, $d] = NepaliDateService::explodeBs($currentBs);
        $this->installmentPaidBsYear = $y;
        $this->installmentPaidBsMonth = $m;
        $this->installmentPaidBsDay = $d;
        $this->installmentPaidAtBsString = $currentBs;
        $this->installmentPaidAt = NepaliDateService::toAd($currentBs);

        $this->installmentPaymentMethod = 'Cash';
        $this->installmentPaymentReference = null;
        $this->installmentNotes = null;
    }

    public function updatedInstallmentPaidBsYear(): void
    {
        $this->syncInstallmentBsDate();
    }

    public function updatedInstallmentPaidBsMonth(): void
    {
        $this->syncInstallmentBsDate();
    }

    public function updatedInstallmentPaidBsDay(): void
    {
        $this->syncInstallmentBsDate();
    }

    protected function syncInstallmentBsDate(): void
    {
        $maxDays = NepaliDateService::daysInMonth($this->installmentPaidBsMonth, $this->installmentPaidBsYear);
        if ($this->installmentPaidBsDay > $maxDays) {
            $this->installmentPaidBsDay = $maxDays;
        }

        $this->installmentPaidAtBsString = sprintf('%04d-%02d-%02d', $this->installmentPaidBsYear, $this->installmentPaidBsMonth, $this->installmentPaidBsDay);
        $this->installmentPaidAt = NepaliDateService::toAd($this->installmentPaidAtBsString);
    }

    public function promptMarkInstallmentPaid(int $installmentId): void
    {
        $installment = SchemeInstallment::findOrFail($installmentId);
        $this->promptUserInstallmentPayment($installment->user_id, $installment->scheme_period_id);
    }

    public function cancelInstallmentPrompt(): void
    {
        $this->selectedInstallmentId = null;
        $this->selectedInstallmentUserId = null;
        $this->selectedInstallmentPeriodId = null;
    }

    public function confirmMarkInstallmentPaid(): void
    {
        if (! $this->selectedInstallmentUserId || ! $this->selectedInstallmentPeriodId) {
            return;
        }

        if (! SavingsSchemeResource::canShield('ManageInstallments')) {
            Notification::make()
                ->title('Unauthorized')
                ->body('You do not have permission to manage installments.')
                ->danger()
                ->send();

            return;
        }

        $amount = (float) $this->installmentPaymentAmount;
        if ($amount <= 0) {
            Notification::make()
                ->title('Invalid Amount')
                ->body('Settlement amount must be greater than zero.')
                ->danger()
                ->send();

            return;
        }

        $period = SchemePeriod::findOrFail($this->selectedInstallmentPeriodId);
        $notes = $this->installmentPaymentReference
            ? trim("Ref: {$this->installmentPaymentReference}. {$this->installmentNotes}")
            : $this->installmentNotes;

        $result = app(SavingsSchemeService::class)->settleUserPeriodInstallments(
            scheme: $this->record,
            period: $period,
            userId: $this->selectedInstallmentUserId,
            amount: $amount,
            method: $this->installmentPaymentMethod,
            paidAt: $this->installmentPaidAt ?: now()->toDateString(),
            verifiedBy: Auth::id(),
            notes: $notes,
        );

        if ($result['auto_completed_round']) {
            Notification::make()
                ->title('Installment Marked as Paid')
                ->body('Rs. '.number_format($result['applied'], 2)." recorded. All installments for Period #{$period->period_number} are settled; winner payout completed and next round started.")
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Installment Marked as Paid')
                ->body('Rs. '.number_format($result['applied'], 2)." settled via {$this->installmentPaymentMethod}.")
                ->success()
                ->send();
        }

        $this->cancelInstallmentPrompt();
        $this->refreshSchemeRecord();
    }

    public function markInstallmentPaid(int $installmentId, string $method = 'Cash'): void
    {
        $installment = SchemeInstallment::findOrFail($installmentId);
        $this->promptUserInstallmentPayment($installment->user_id, $installment->scheme_period_id);
        $this->installmentPaymentAmount = $installment->remaining_due;
        $this->installmentPaymentMethod = $method;
        $this->confirmMarkInstallmentPaid();
    }

    public function recordWinnerPayout(int $periodId, float $amount, string $method = 'Cash', ?string $reference = null, ?string $notes = null): void
    {
        if (! SavingsSchemeResource::canShield('DeclareWinner')) {
            Notification::make()->title('Unauthorized')->danger()->send();

            return;
        }

        $period = SchemePeriod::findOrFail($periodId);
        app(SavingsSchemeService::class)->settleWinnerPayout($period, $amount, $method, $reference, $notes);

        Notification::make()
            ->title('Payout Recorded')
            ->body('Rs. '.number_format($amount, 2)." paid to {$period->winnerUser?->name} via {$method}.")
            ->success()
            ->send();

        $this->refreshSchemeRecord();
    }

    public ?int $declaringWinnerPeriodId = null;

    public ?int $declaringWinnerSeatId = null;

    public ?string $declaringWinnerNotes = null;

    public function openDeclareWinnerModal(int $periodId): void
    {
        $this->declaringWinnerPeriodId = $periodId;
        $period = $this->record->periods()->find($periodId);
        $this->declaringWinnerSeatId = $period?->approvedReservation?->scheme_seat_id;
        $this->declaringWinnerNotes = null;
    }

    public function closeDeclareWinnerModal(): void
    {
        $this->declaringWinnerPeriodId = null;
        $this->declaringWinnerSeatId = null;
        $this->declaringWinnerNotes = null;
    }

    public function confirmDeclareWinner(): void
    {
        if (! SavingsSchemeResource::canShield('DeclareWinner')) {
            Notification::make()->title('Unauthorized to declare winners.')->danger()->send();

            return;
        }

        if (! $this->declaringWinnerPeriodId) {
            return;
        }

        $period = $this->record->periods()->findOrFail($this->declaringWinnerPeriodId);
        $isPeriodOne = ($period->period_number === 1);

        if ($isPeriodOne) {
            $seat = $this->record->seats()->where('seat_number', 1)->firstOrFail();
        } else {
            if (! $this->declaringWinnerSeatId) {
                Notification::make()->title('Please select a winning seat.')->warning()->send();

                return;
            }
            $seat = $this->record->seats()->findOrFail($this->declaringWinnerSeatId);
        }

        app(SavingsSchemeService::class)->declareWinner(
            $this->record,
            $period,
            $seat,
            $this->declaringWinnerNotes
        );

        Notification::make()
            ->title($isPeriodOne ? 'Period #1 Winner Confirmed (Organizer)' : "Winner Declared for Round #{$period->period_number}")
            ->body("Seat #{$seat->seat_number} ({$seat->user?->name}) declared as the winner! Net Prize: Rs. ".number_format((float) $period->fresh()->net_payout_amount, 2).'.')
            ->success()
            ->send();

        $this->closeDeclareWinnerModal();
        $this->refreshSchemeRecord();
    }

    public ?int $reservingPeriodId = null;

    public ?int $reservingSeatId = null;

    public ?string $reservingReason = null;

    public string $reservingStatus = 'approved';

    public function openReserveModal(int $periodId): void
    {
        $this->reservingPeriodId = $periodId;
        $this->reservingSeatId = null;
        $this->reservingReason = null;
        $this->reservingStatus = 'approved';
    }

    public function closeReserveModal(): void
    {
        $this->reservingPeriodId = null;
        $this->reservingSeatId = null;
        $this->reservingReason = null;
    }

    public function confirmReserve(): void
    {
        if (! $this->reservingPeriodId || ! $this->reservingSeatId) {
            Notification::make()->title('Please select a member seat to reserve.')->warning()->send();

            return;
        }

        $period = $this->record->periods()->findOrFail($this->reservingPeriodId);
        $seat = $this->record->seats()->findOrFail($this->reservingSeatId);
        $user = $seat->user ?? User::findOrFail($this->record->organizer_id);

        app(SavingsSchemeService::class)->applyPeriodReservation(
            $this->record,
            $period,
            $user,
            $seat,
            $this->reservingReason,
            Auth::user(),
            directApprove: ($this->reservingStatus === 'approved')
        );

        Notification::make()
            ->title($this->reservingStatus === 'approved' ? 'Round Reserved' : 'Reservation Application Submitted')
            ->body("Round #{$period->period_number} reserved for Seat #{$seat->seat_number} ({$user->name}).")
            ->success()
            ->send();

        $this->closeReserveModal();
        $this->refreshSchemeRecord();
    }

    public ?int $validatingPeriodId = null;

    public ?string $validationNotes = null;

    public function openValidateRound(int $periodId): void
    {
        $this->validatingPeriodId = $periodId;
        $period = $this->record->periods()->find($periodId);
        $this->validationNotes = $period?->validation_notes;
    }

    public function closeValidateRound(): void
    {
        $this->validatingPeriodId = null;
        $this->validationNotes = null;
    }

    public function confirmValidateRound(): void
    {
        if (! $this->validatingPeriodId) {
            return;
        }

        if (! SavingsSchemeResource::canShield('DeclareWinner') && ! Auth::user()?->hasRole('super_admin')) {
            Notification::make()->title('Unauthorized to validate rounds.')->danger()->send();

            return;
        }

        $period = $this->record->periods()->findOrFail($this->validatingPeriodId);
        $period->update([
            'is_validated' => true,
            'validated_at' => now(),
            'validated_by' => Auth::id(),
            'validation_notes' => $this->validationNotes,
        ]);

        Notification::make()
            ->title('Round Validated')
            ->body("Round #{$period->period_number} has been officially reconciled and validated.")
            ->success()
            ->send();

        $this->closeValidateRound();
        $this->refreshSchemeRecord();
    }

    public function toggleRevokeValidation(int $periodId): void
    {
        if (! SavingsSchemeResource::canShield('DeclareWinner') && ! Auth::user()?->hasRole('super_admin')) {
            Notification::make()->title('Unauthorized')->danger()->send();

            return;
        }

        $period = $this->record->periods()->findOrFail($periodId);
        $period->update([
            'is_validated' => false,
            'validated_at' => null,
            'validated_by' => null,
        ]);

        Notification::make()
            ->title('Validation Revoked')
            ->body("Round #{$period->period_number} validation has been revoked.")
            ->info()
            ->send();

        $this->refreshSchemeRecord();
    }

    public static function formatUserSelectLabel(User $user): string
    {
        return filled($user->username)
            ? "{$user->name} ({$user->username})"
            : $user->name;
    }
}
