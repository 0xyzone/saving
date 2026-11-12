<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchemeApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'savings_scheme_id',
        'user_id',
        'requested_seats_count',
        'notes',
        'status',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'requested_seats_count' => 'integer',
            'reviewed_at' => 'datetime',
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

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
