<?php

namespace App\Models;

use App\Services\ChangelogService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Changelog extends Model
{
    use HasFactory;

    protected $fillable = [
        'version',
        'type',
        'badge',
        'title',
        'description',
        'highlights',
        'released_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'released_at' => 'date',
            'highlights' => 'array',
        ];
    }

    /**
     * Get the current application version.
     */
    public static function currentVersion(): string
    {
        return ChangelogService::getCurrentVersion();
    }
}
