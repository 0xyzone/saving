<?php

namespace App\Models;

use App\Services\NepaliDateService;
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
        'start_date_bs',
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

            if (filled($scheme->start_date_bs) && empty($scheme->start_date)) {
                $scheme->start_date = NepaliDateService::toAd($scheme->start_date_bs);
            } elseif (filled($scheme->start_date) && empty($scheme->start_date_bs)) {
                $scheme->start_date_bs = NepaliDateService::toBs($scheme->start_date);
            }
        });

        static::updating(function (SavingsScheme $scheme): void {
            if ($scheme->isDirty('start_date_bs') && filled($scheme->start_date_bs)) {
                $scheme->start_date = NepaliDateService::toAd($scheme->start_date_bs);
            } elseif ($scheme->isDirty('start_date') && filled($scheme->start_date)) {
                $scheme->start_date_bs = NepaliDateService::toBs($scheme->start_date);
            }
        });
    }

    public function getFormattedStartDateBsAttribute(): string
    {
        if (filled($this->start_date_bs)) {
            return NepaliDateService::formatBs($this->start_date_bs);
        }

        return $this->start_date ? NepaliDateService::formatBs($this->start_date) : '-';
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

    public function reservations(): HasMany
    {
        return $this->hasMany(SchemePeriodReservation::class);
    }
}
