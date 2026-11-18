<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\InstallmentSettlementRequest;
use App\Services\SavingsSchemeService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class PendingSettlementRequestsWidget extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Pending Installment Settlement Applications';

    public static function canView(): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        return static::baseQuery()->exists();
    }

    protected static function baseQuery(): Builder
    {
        $user = Auth::user();

        $query = InstallmentSettlementRequest::query()
            ->with(['scheme', 'period', 'seat', 'user'])
            ->where('status', InstallmentSettlementRequest::STATUS_PENDING)
            ->latest();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->hasRole('super_admin')) {
            return $query;
        }

        return $query->whereHas('scheme', fn (Builder $q) => $q->where('organizer_id', $user->id));
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(static::baseQuery())
            ->columns([
                TextColumn::make('scheme.title')
                    ->label('Scheme & Round')
                    ->weight('bold')
                    ->description(fn (InstallmentSettlementRequest $record): string => "Round #{$record->period?->period_number}"),

                TextColumn::make('user.name')
                    ->label('Member & Seat')
                    ->description(fn (InstallmentSettlementRequest $record): string => "Seat #{$record->seat?->seat_number}"),

                TextColumn::make('formatted_payment_date_bs')
                    ->label('Payment Date (BS)')
                    ->description(fn (InstallmentSettlementRequest $record): ?string => $record->payment_date?->format('M d, Y').' AD'),

                TextColumn::make('amount')
                    ->label('Amount')
                    ->formatStateUsing(fn ($state): string => 'Rs. '.number_format((float) $state, 2))
                    ->weight('bold')
                    ->color('success'),

                TextColumn::make('payment_method')
                    ->label('Method & Ref')
                    ->badge()
                    ->color('info')
                    ->description(fn (InstallmentSettlementRequest $record): ?string => collect([
                        $record->reference_id ? "Ref: {$record->reference_id}" : null,
                        $record->notes,
                    ])->filter()->implode(' • ') ?: null),
            ])
            ->recordActions([
                Action::make('view_proof')
                    ->label(fn (InstallmentSettlementRequest $record): string => 'Proof ('.count($record->proof_images ?? []).')')
                    ->icon('heroicon-m-photo')
                    ->color('info')
                    ->visible(fn (InstallmentSettlementRequest $record): bool => filled($record->proof_images))
                    ->modalHeading(fn (InstallmentSettlementRequest $record): string => "Payment Proof — {$record->user?->name} (Round #{$record->period?->period_number})")
                    ->modalWidth('3xl')
                    ->modalContent(fn (InstallmentSettlementRequest $record) => view('filament.widgets.settlement-proof-modal', [
                        'request' => $record->loadMissing(['user', 'seat', 'period']),
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),

                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Settlement Application')
                    ->modalDescription(fn (InstallmentSettlementRequest $record): string => 'Verify and record Rs. '.number_format((float) $record->amount, 2)." from {$record->user?->name} (Seat #{$record->seat?->seat_number}, Round #{$record->period?->period_number}).")
                    ->form([
                        Textarea::make('reviewer_notes')
                            ->label('Verification Notes (Optional)')
                            ->rows(2)
                            ->maxLength(500),
                    ])
                    ->action(function (InstallmentSettlementRequest $record, array $data): void {
                        $reviewer = Auth::user();
                        if (! $reviewer) {
                            return;
                        }

                        try {
                            $result = app(SavingsSchemeService::class)->approveInstallmentSettlementRequest(
                                request: $record,
                                reviewer: $reviewer,
                                reviewerNotes: $data['reviewer_notes'] ?? null,
                            );

                            $msg = 'Applied Rs. '.number_format($result['applied'], 2).' to the installment.';
                            if ($result['auto_completed_round']) {
                                $msg .= ' Round completed automatically!';
                            }

                            Notification::make()
                                ->success()
                                ->title('Settlement Approved')
                                ->body($msg)
                                ->send();
                        } catch (InvalidArgumentException $e) {
                            Notification::make()
                                ->danger()
                                ->title('Approval Failed')
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),

                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Reject Settlement Application')
                    ->form([
                        Textarea::make('reviewer_notes')
                            ->label('Reason for Rejection')
                            ->rows(2)
                            ->maxLength(500),
                    ])
                    ->action(function (InstallmentSettlementRequest $record, array $data): void {
                        $reviewer = Auth::user();
                        if (! $reviewer) {
                            return;
                        }

                        try {
                            app(SavingsSchemeService::class)->rejectInstallmentSettlementRequest(
                                request: $record,
                                reviewer: $reviewer,
                                reviewerNotes: $data['reviewer_notes'] ?? null,
                            );

                            Notification::make()
                                ->warning()
                                ->title('Settlement Application Rejected')
                                ->send();
                        } catch (InvalidArgumentException $e) {
                            Notification::make()
                                ->danger()
                                ->title('Rejection Failed')
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),
            ]);
    }
}
