<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchemePeriodReservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'savings_scheme_id',
        'scheme_period_id',
        'scheme_seat_id',
        'user_id',
        'requested_by_user_id',
        'reason',
        'status',
        'admin_notes',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
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
        return $this->belongsTo(User::class, 'user_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
