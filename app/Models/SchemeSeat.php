<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchemeSeat extends Model
{
    use HasFactory;

    protected $fillable = [
        'savings_scheme_id',
        'seat_number',
        'user_id',
        'status',
        'won_at_period',
    ];

    protected function casts(): array
    {
        return [
            'seat_number' => 'integer',
            'won_at_period' => 'integer',
        ];
    }

    public function scheme(): BelongsTo
    {
        return $this->belongsTo(SavingsScheme::class, 'savings_scheme_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function installments(): HasMany
    {
        return $this->hasMany(SchemeInstallment::class);
    }
}
