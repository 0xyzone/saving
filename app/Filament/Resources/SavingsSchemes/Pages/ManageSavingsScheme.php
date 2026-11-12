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
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class ManageSavingsScheme extends Page
{
    protected static string $resource = SavingsSchemeResource::class;

    protected string $view = 'filament.resources.savings-schemes.pages.manage-savings-scheme';

    public SavingsScheme $record;

    public int $selectedPeriodId = 0;

    public function mount(SavingsScheme $record): void
    {
        $this->record = $record->load(['seats.user', 'periods.winningSeat.user', 'periods.winnerUser']);

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
                ->label('Declare Period Winner')
                ->icon('heroicon-m-trophy')
                ->color('warning')
                ->modalHeading(fn () => "Declare Winner for Period #{$this->record->current_period}")
                ->modalDescription('Select the winning seat for this round. Winner seat dues and Khaja Kharcha will be calculated and settled automatically.')
                ->form([
                    Select::make('winning_seat_id')
                        ->label('Select Winning Seat')
                        ->options(function () {
                            $isPeriodOne = ($this->record->current_period === 1);

                            if ($isPeriodOne) {
                                return $this->record->seats()
                                    ->where('seat_number', 1)
                                    ->with('user')
                                    ->get()
                                    ->mapWithKeys(fn (SchemeSeat $seat) => [
                                        $seat->id => "Seat #1 - {$seat->user?->name} (Organizer)",
                                    ])
                                    ->toArray();
                            }

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
                        ->required()
                        ->live(),

                    Textarea::make('payout_notes')
                        ->label('Payout Notes / Receipt Info')
                        ->placeholder('e.g. Paid in cash, or bank transaction reference')
                        ->nullable(),
                ])
                ->action(function (array $data) {
                    $period = $this->record->periods()->where('period_number', $this->record->current_period)->firstOrFail();
                    $seat = $this->record->seats()->findOrFail($data['winning_seat_id']);

                    app(SavingsSchemeService::class)->declareWinner(
                        $this->record,
                        $period,
                        $seat,
                        $data['payout_notes'] ?? null
                    );

                    Notification::make()
                        ->title("Winner Declared for Period #{$period->period_number}")
                        ->body("Seat #{$seat->seat_number} ({$seat->user?->name}) awarded Net Payout: Rs. ".number_format((float) $period->fresh()->net_payout_amount, 2))
                        ->success()
                        ->send();

                    $this->record->refresh();
                }),

            Action::make('assign_seat')
                ->label('Assign Seat')
                ->icon('heroicon-m-user-plus')
                ->color('primary')
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
