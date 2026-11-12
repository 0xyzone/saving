<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SavingsScheme extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'organizer_id',
        'total_seats',
        'total_periods',
        'installment_amount',
        'interest_amount',
        'khaja_kharcha',
        'frequency',
        'start_date',
        'status',
        'current_period',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'installment_amount' => 'decimal:2',
            'interest_amount' => 'decimal:2',
            'khaja_kharcha' => 'decimal:2',
            'total_seats' => 'integer',
            'total_periods' => 'integer',
            'current_period' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (SavingsScheme $scheme): void {
            if (empty($scheme->total_periods) && ! empty($scheme->total_seats)) {
                $scheme->total_periods = $scheme->total_seats;
            }
        });
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    public function seats(): HasMany
    {
        return $this->hasMany(SchemeSeat::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(SchemeApplication::class);
    }

    public function periods(): HasMany
    {
        return $this->hasMany(SchemePeriod::class)->orderBy('period_number');
    }

    public function installments(): HasMany
    {
        return $this->hasMany(SchemeInstallment::class);
    }
}
