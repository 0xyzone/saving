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
        $this->record->load(['seats.user', 'periods.winningSeat.user', 'periods.winnerUser']);

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
                        ->required(fn () => $this->record->current_period > 1)
                        ->visible(fn () => $this->record->current_period > 1)
                        ->live(),

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
                        $data['payout_notes'] ?? null
                    );

                    Notification::make()
                        ->title($isPeriodOne ? 'Period #1 Payout Settled for Organizer' : "Winner Declared for Period #{$period->period_number}")
                        ->body("Seat #{$seat->seat_number} ({$seat->user?->name}) awarded Net Payout: Rs. ".number_format((float) $period->fresh()->net_payout_amount, 2))
                        ->success()
                        ->send();

                    $this->record->refresh();

                    $nextPeriod = $this->record->periods()->where('period_number', $this->record->current_period)->first();
                    if ($nextPeriod) {
                        $this->selectedPeriodId = $nextPeriod->id;
                    }
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
        ];
    }

    public function markInstallmentPaid(int $installmentId, string $method = 'Cash'): void
    {
        if (! SavingsSchemeResource::canShield('ManageInstallments')) {
            Notification::make()
                ->title('Unauthorized')
                ->body('You do not have permission to manage installments.')
                ->danger()
                ->send();

            return;
        }

        $installment = SchemeInstallment::findOrFail($installmentId);
        $installment->update([
            'status' => 'paid',
            'payment_method' => $method,
            'paid_at' => now(),
            'verified_by' => auth()->id(),
        ]);

        Notification::make()
            ->title('Installment Marked as Paid')
            ->body("Seat #{$installment->seat->seat_number} marked as Paid.")
            ->success()
            ->send();
    }
}
