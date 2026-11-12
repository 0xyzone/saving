<?php

namespace App\Models;

use App\Services\NepaliDateService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SchemePeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'savings_scheme_id',
        'period_number',
        'due_date',
        'due_date_bs',
        'winning_seat_id',
        'winner_user_id',
        'gross_pool_amount',
        'winner_dues_deducted',
        'khaja_kharcha_deducted',
        'net_payout_amount',
        'paid_amount',
        'payout_status',
        'payout_method',
        'payout_reference',
        'paid_at',
        'payout_notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'period_number' => 'integer',
            'due_date' => 'date',
            'paid_at' => 'datetime',
            'gross_pool_amount' => 'decimal:2',
            'winner_dues_deducted' => 'decimal:2',
            'khaja_kharcha_deducted' => 'decimal:2',
            'net_payout_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
        ];
    }

    public function getTotalCollectedAmountAttribute(): float
    {
        return (float) $this->installments->where('status', 'paid')->sum('total_due');
    }

    public function getWinnerBalanceRemainingAttribute(): float
    {
        return max(0.00, (float) $this->net_payout_amount - (float) $this->paid_amount);
    }

    public function getCollectionBalanceRemainingAttribute(): float
    {
        $targetPool = max(0.00, (float) $this->gross_pool_amount - (float) $this->winner_dues_deducted);

        return max(0.00, $targetPool - $this->total_collected_amount);
    }

    public function getFormattedDueDateBsAttribute(): string
    {
        if (filled($this->due_date_bs)) {
            return NepaliDateService::formatBs($this->due_date_bs);
        }

        return $this->due_date ? NepaliDateService::formatBs($this->due_date) : '-';
    }

    public function scheme(): BelongsTo
    {
        return $this->belongsTo(SavingsScheme::class, 'savings_scheme_id');
    }

    public function winningSeat(): BelongsTo
    {
        return $this->belongsTo(SchemeSeat::class, 'winning_seat_id');
    }

    public function winnerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'winner_user_id');
    }

    public function installments(): HasMany
    {
        return $this->hasMany(SchemeInstallment::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(SchemePeriodReservation::class);
    }

    public function approvedReservation(): HasOne
    {
        return $this->hasOne(SchemePeriodReservation::class)->where('status', 'approved');
    }
}
