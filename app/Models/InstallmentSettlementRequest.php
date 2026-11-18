<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\NepaliDateService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstallmentSettlementRequest extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    /**
     * @var array<int, string>
     */
    public const PAYMENT_METHODS = [
        'Cash' => 'Cash',
        'Bank Transfer' => 'Bank Transfer',
        'eSewa' => 'eSewa',
        'Khalti' => 'Khalti',
        'ConnectIPS' => 'ConnectIPS',
        'Other' => 'Other',
    ];

    protected $fillable = [
        'savings_scheme_id',
        'scheme_period_id',
        'scheme_seat_id',
        'scheme_installment_id',
        'user_id',
        'amount',
        'payment_method',
        'payment_date',
        'payment_date_bs',
        'reference_id',
        'notes',
        'status',
        'reviewed_by',
        'reviewed_at',
        'reviewer_notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_date' => 'date',
            'reviewed_at' => 'datetime',
        ];
    }

    public function getFormattedPaymentDateBsAttribute(): string
    {
        if (filled($this->payment_date_bs)) {
            return NepaliDateService::formatBs($this->payment_date_bs);
        }

        return $this->payment_date ? NepaliDateService::formatBs($this->payment_date) : '-';
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

    public function installment(): BelongsTo
    {
        return $this->belongsTo(SchemeInstallment::class, 'scheme_installment_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
