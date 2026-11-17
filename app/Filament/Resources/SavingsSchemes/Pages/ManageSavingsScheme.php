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
        $this->record->load([
            'organizer',
            'seats.user',
            'periods.winningSeat.user',
            'periods.winnerUser',
            'periods.reservations.user',
            'periods.reservations.seat',
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
                    ? 'Settle Period #1 Payout (Organizer)'
                    : "Declare Winner (Period #{$this->record->current_period})"
                )
                ->icon(fn () => $this->record->current_period === 1
                    ? 'heroicon-m-check-badge'
                    : 'heroicon-m-trophy'
                )
                ->color(fn () => $this->record->current_period === 1 ? 'success' : 'warning')
                ->visible(fn () => SavingsSchemeResource::canShield('DeclareWinner')
                    && $this->record->status !== 'completed'
                    && $this->record->current_period <= $this->record->total_periods
                )
                ->modalHeading(fn () => $this->record->current_period === 1
                    ? 'Settle Period #1 Payout (Organizer)'
                    : "Declare Winner for Period #{$this->record->current_period}"
                )
                ->modalDescription(fn () => $this->record->current_period === 1
                    ? 'Period #1 is permanently allocated to Seat #1 (Organizer). Confirm to record the initial full payout (with no Maslanda Deposit deduction) and auto-settle the organizer seat dues.'
                    : 'Select the winning seat for this round. Winner seat dues and Maslanda Deposit will be calculated and settled automatically.'
                )
                ->modalSubmitActionLabel(fn () => $this->record->current_period === 1
                    ? 'Confirm & Settle Payout'
                    : 'Declare Winner'
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
                                        <div><span class='text-slate-500 font-bold'>Net Payout:</span> <strong class='text-emerald-600 dark:text-emerald-400 font-mono'>Rs. ".number_format($netPayout, 2).'</strong></div>
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

                    Select::make('payout_method')
                        ->label('Payout / Settlement Method')
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
                        ->label('Payment Reference / Txn ID')
                        ->placeholder('e.g. eSewa Txn ID, Bank UTR, Receipt #')
                        ->nullable(),

                    TextInput::make('paid_amount')
                        ->label('Initial Amount Paid (Leave empty for full payout)')
                        ->numeric()
                        ->placeholder('Full net prize amount')
                        ->nullable(),

                    Textarea::make('payout_notes')
                        ->label('Payout Notes / Receipt Info')
                        ->placeholder('e.g. Paid in cash, or bank transaction reference')
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
                        $data['payout_notes'] ?? null,
                        $data['payout_method'] ?? 'Cash',
                        $data['payout_reference'] ?? null,
                        filled($data['paid_amount'] ?? null) ? (float) $data['paid_amount'] : null
                    );

                    Notification::make()
                        ->title($isPeriodOne ? 'Period #1 Payout Settled for Organizer' : "Winner Declared for Period #{$period->period_number}")
                        ->body("Seat #{$seat->seat_number} ({$seat->user?->name}) awarded Net Payout: Rs. ".number_format((float) $period->fresh()->net_payout_amount, 2).' via '.($data['payout_method'] ?? 'Cash'))
                        ->success()
                        ->send();

                    $this->record->refresh();

                    $nextPeriod = $this->record->periods()->where('period_number', $this->record->current_period)->first();
                    if ($nextPeriod) {
                        $this->selectedPeriodId = $nextPeriod->id;
                    }
                }),

            Action::make('raffle_draw')
                ->label('Lucky Draw (Raffle)')
                ->icon('heroicon-m-sparkles')
                ->color('info')
                ->visible(fn () => SavingsSchemeResource::canShield('DeclareWinner')
                    && $this->record->current_period > 1
                    && $this->record->status !== 'completed'
                    && $this->record->current_period <= $this->record->total_periods
                )
                ->modalHeading(fn () => "🎉 Lucky Draw / Raffle for Period #{$this->record->current_period}")
                ->modalDescription('Run an automated lucky draw among all remaining eligible seats to pick the winner for this round.')
                ->modalSubmitActionLabel('Confirm Raffle Winner & Settle Payout')
                ->form([
                    Select::make('winning_seat_id')
                        ->label('Raffle Winner Candidate')
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

                    Select::make('payout_method')
                        ->label('Payout / Settlement Method')
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
                        ->label('Payment Reference / Txn ID')
                        ->placeholder('e.g. eSewa Txn ID, Bank UTR, Receipt #')
                        ->nullable(),

                    TextInput::make('paid_amount')
                        ->label('Initial Amount Paid (Leave empty for full payout)')
                        ->numeric()
                        ->placeholder('Full net prize amount')
                        ->nullable(),

                    Textarea::make('payout_notes')
                        ->label('Payout Notes / Receipt Info')
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
                        $data['payout_notes'] ?? 'Selected via Lucky Draw / Raffle',
                        $data['payout_method'] ?? 'Cash',
                        $data['payout_reference'] ?? null,
                        filled($data['paid_amount'] ?? null) ? (float) $data['paid_amount'] : null
                    );

                    Notification::make()
                        ->title("🎉 Lucky Draw Winner Declared for Period #{$period->period_number}")
                        ->body("Seat #{$seat->seat_number} ({$seat->user?->name}) won the raffle! Net Payout: Rs. ".number_format((float) $period->fresh()->net_payout_amount, 2).' via '.($data['payout_method'] ?? 'Cash'))
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
                ->modalDescription('Select one or more members and specify how many seats to allocate per member, auto-expanding the scheme rounds.')
                ->form([
                    Select::make('user_ids')
                        ->label('Select Members')
                        ->multiple()
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
                        ->getOptionLabelsUsing(fn (array $values): array => User::query()
                            ->whereIn('id', $values)
                            ->get()
                            ->mapWithKeys(fn (User $user): array => [$user->id => static::formatUserSelectLabel($user)])
                            ->toArray())
                        ->searchable()
                        ->required(),

                    TextInput::make('seats_per_user')
                        ->label('Number of Seats per Selected User')
                        ->numeric()
                        ->integer()
                        ->minValue(1)
                        ->maxValue(20)
                        ->default(1)
                        ->required(),

                    Select::make('seat_number')
                        ->label('Preferred Seat Number (Optional, single seat only)')
                        ->placeholder('Auto-allocate next available seats')
                        ->options(function () {
                            $available = $this->record->seats()
                                ->where('seat_number', '!=', 1)
                                ->whereNull('user_id')
                                ->pluck('seat_number', 'seat_number')
                                ->toArray();

                            $nextNumber = ((int) $this->record->seats()->max('seat_number')) + 1;
                            if ($nextNumber < 2) {
                                $nextNumber = 2;
                            }

                            return $available + [
                                $nextNumber => "Seat #{$nextNumber} (Next Dynamic Round)",
                            ];
                        })
                        ->helperText('Used only when allocating 1 seat to 1 member; otherwise seats are auto-allocated sequentially.')
                        ->nullable(),
                ])
                ->action(function (array $data) {
                    $userIds = array_values((array) ($data['user_ids'] ?? []));
                    $seatsPerUser = max(1, (int) ($data['seats_per_user'] ?? 1));
                    $preferredSeat = (count($userIds) === 1 && $seatsPerUser === 1 && filled($data['seat_number'] ?? null))
                        ? (int) $data['seat_number']
                        : null;

                    $service = app(SavingsSchemeService::class);
                    $allocatedCount = 0;
                    $allocatedSeats = [];

                    foreach ($userIds as $userId) {
                        $user = User::findOrFail($userId);
                        for ($i = 0; $i < $seatsPerUser; $i++) {
                            $seat = $service->allocateSeat($this->record, $user, $i === 0 ? $preferredSeat : null);
                            $allocatedSeats[] = "#{$seat->seat_number}";
                            $allocatedCount++;
                        }
                    }

                    $seatList = implode(', ', $allocatedSeats);
                    Notification::make()
                        ->title($allocatedCount === 1 ? 'Seat Allocated' : "{$allocatedCount} Seats Allocated")
                        ->body("Allocated seat(s) {$seatList}. Total rounds: {$this->record->fresh()->total_periods}.")
                        ->success()
                        ->send();

                    $this->record->refresh();
                }),

            Action::make('settle_winner_payout')
                ->label('Settle Winner Payout')
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
                        $data['notes'] ?? null
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
        $this->installmentPaidAt = now()->format('Y-m-d');
        $this->installmentPaymentMethod = 'Cash';
        $this->installmentPaymentReference = null;
        $this->installmentNotes = null;
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
        $this->record->refresh();
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

        $this->record->refresh();
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
        $this->record->refresh();
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
        $this->record->refresh();
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

        $this->record->refresh();
    }

    public static function formatUserSelectLabel(User $user): string
    {
        return filled($user->username)
            ? "{$user->name} ({$user->username})"
            : $user->name;
    }
}
