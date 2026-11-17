<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPaymentOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'provider_name',
        'account_number',
        'qr_code_image',
        'is_preferred',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_preferred' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (UserPaymentOption $option): void {
            if ($option->is_preferred && $option->user_id) {
                static::query()
                    ->where('user_id', $option->user_id)
                    ->when($option->exists, fn ($query) => $query->where('id', '!=', $option->id))
                    ->update(['is_preferred' => false]);
            }
        });
    }

    /**
     * @return BelongsTo<User, covariant $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
