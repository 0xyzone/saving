<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Changelog extends Model
{
    use HasFactory;

    protected $fillable = [
        'version',
        'type',
        'title',
        'description',
        'released_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'released_at' => 'date',
        ];
    }

    /**
     * Get the latest application version.
     */
    public static function currentVersion(): string
    {
        return static::query()
            ->latest('released_at')
            ->latest('id')
            ->value('version') ?? 'v1.0.0';
    }
}
