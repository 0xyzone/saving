<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchemePeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'savings_scheme_id',
        'period_number',
        'due_date',
        'winning_seat_id',
        'winner_user_id',
        'gross_pool_amount',
        'winner_dues_deducted',
        'khaja_kharcha_deducted',
        'net_payout_amount',
        'payout_status',
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
        ];
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
}
