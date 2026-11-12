<?php

namespace App\Filament\Resources\SavingsSchemes\Pages;

use App\Filament\Resources\SavingsSchemes\SavingsSchemeResource;
use App\Models\SavingsScheme;
use App\Models\SchemeInstallment;
use App\Models\SchemePeriod;
use App\Models\SchemeSeat;
use App\Models\User;
use App\Services\SavingsSchemeService;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
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
                    ? 'Period #1 is permanently allocated to Seat #1 (Organizer). Confirm to record the initial full payout (with no Khaja Kharcha deduction) and auto-settle the organizer seat dues.'
                    : 'Select the winning seat for this round. Winner seat dues and Khaja Kharcha will be calculated and settled automatically.'
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
                                        As per Dhukuti scheme rules, the Organizer wins Round #1 in full with Rs. 0 Khaja Kharcha deducted.
                                    </div>
                                    <div class='mt-2 pt-2 border-t border-amber-500/20 grid grid-cols-2 gap-2 text-xs'>
                                        <div><span class='text-slate-500'>Gross Pool:</span> <strong class='text-slate-900 dark:text-white'>Rs. ".number_format($grossPool, 2)."</strong></div>
                                        <div><span class='text-slate-500'>Own Seat Dues:</span> <strong class='text-amber-600'>-Rs. ".number_format($winnerDues, 2)."</strong></div>
                                        <div><span class='text-slate-500'>Khaja Kharcha:</span> <strong class='text-slate-900 dark:text-white'>Rs. 0.00</strong></div>
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
                        auth()->user(),
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
                ->label('Assign Seat')
                ->icon('heroicon-m-user-plus')
                ->color('primary')
                ->visible(fn () => SavingsSchemeResource::canShield('ManageSeats'))
                ->form([
                    Select::make('seat_number')
                        ->label('Seat Number')
                        ->options(fn () => $this->record->seats()
                            ->where('seat_number', '!=', 1)
                            ->whereNull('user_id')
                            ->pluck('seat_number', 'seat_number')
                            ->toArray()
                        )
                        ->required(),

                    Select::make('user_id')
                        ->label('Member')
                        ->options(fn () => User::where('name', '!=', 'Super Admin')->pluck('name', 'id')->toArray())
                        ->searchable()
                        ->required(),
                ])
                ->action(function (array $data) {
                    $user = User::findOrFail($data['user_id']);
                    app(SavingsSchemeService::class)->assignSeat($this->record, (int) $data['seat_number'], $user);

                    Notification::make()
                        ->title('Seat Assigned')
                        ->body("Seat #{$data['seat_number']} has been assigned to {$user->name}.")
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

    public function promptMarkInstallmentPaid(int $installmentId): void
    {
        $this->selectedInstallmentId = $installmentId;
        $this->installmentPaymentMethod = 'Cash';
        $this->installmentPaymentReference = null;
        $this->installmentNotes = null;
    }

    public function cancelInstallmentPrompt(): void
    {
        $this->selectedInstallmentId = null;
    }

    public function confirmMarkInstallmentPaid(): void
    {
        if (! $this->selectedInstallmentId) {
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

        $installment = SchemeInstallment::findOrFail($this->selectedInstallmentId);
        $installment->update([
            'status' => 'paid',
            'payment_method' => $this->installmentPaymentMethod,
            'paid_at' => now(),
            'verified_by' => auth()->id(),
            'notes' => $this->installmentPaymentReference
                ? "Ref: {$this->installmentPaymentReference}. {$this->installmentNotes}"
                : $this->installmentNotes,
        ]);

        Notification::make()
            ->title('Installment Marked as Paid')
            ->body("Seat #{$installment->seat?->seat_number} marked as Paid via {$this->installmentPaymentMethod}.")
            ->success()
            ->send();

        $this->selectedInstallmentId = null;
        $this->record->refresh();
    }

    public function markInstallmentPaid(int $installmentId, string $method = 'Cash'): void
    {
        $this->selectedInstallmentId = $installmentId;
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
}
