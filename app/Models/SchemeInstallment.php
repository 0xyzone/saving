<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchemeInstallment extends Model
{
    use HasFactory;

    protected $fillable = [
        'savings_scheme_id',
        'scheme_period_id',
        'scheme_seat_id',
        'user_id',
        'base_amount',
        'interest_amount',
        'total_due',
        'status',
        'user_payment_option_id',
        'payment_method',
        'payment_proof_url',
        'paid_at',
        'verified_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'base_amount' => 'decimal:2',
            'interest_amount' => 'decimal:2',
            'total_due' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function scheme(): BelongsTo
    {
        return $this->belongsTo(SavingsScheme::class, 'savings_scheme_id');
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(SchemePeriod::class, 'scheme_period_id');
    }

    public function seat(): BelongsTo
    {
        return $this->belongsTo(SchemeSeat::class, 'scheme_seat_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function paymentOption(): BelongsTo
    {
        return $this->belongsTo(UserPaymentOption::class, 'user_payment_option_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
