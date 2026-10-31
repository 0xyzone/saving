<?php

namespace App\Services;

use App\Models\Changelog;

class ChangelogService
{
    public const DEFAULT_VERSION = '1.0.0';

    /**
     * Get the current active version string (e.g. "1.0.0").
     */
    public static function getCurrentVersion(): string
    {
        $latest = Changelog::query()
            ->orderByDesc('released_at')
            ->orderByDesc('id')
            ->value('version');

        if ($latest) {
            return ltrim($latest, 'vV');
        }

        return self::DEFAULT_VERSION;
    }

    /**
     * Parse semver components [major, minor, patch] from a version string.
     *
     * @return array{major: int, minor: int, patch: int}
     */
    public static function parseVersion(?string $version = null): array
    {
        $version = $version ?? self::getCurrentVersion();

        if (preg_match('/^v?(\d+)\.(\d+)(?:\.(\d+))?/', $version, $matches)) {
            return [
                'major' => (int) $matches[1],
                'minor' => (int) $matches[2],
                'patch' => isset($matches[3]) ? (int) $matches[3] : 0,
            ];
        }

        return ['major' => 1, 'minor' => 0, 'patch' => 0];
    }

    /**
     * Calculate next semantic version.
     *
     * @param  string  $type  'major', 'minor', or 'macro'/'patch'
     */
    public static function getNextVersion(string $type = 'macro', ?string $currentVersion = null): string
    {
        $parts = self::parseVersion($currentVersion);
        $type = strtolower($type);

        return match ($type) {
            'major' => ($parts['major'] + 1).'.0.0',
            'minor' => $parts['major'].'.'.($parts['minor'] + 1).'.0',
            'macro', 'patch', 'micro' => $parts['major'].'.'.$parts['minor'].'.'.($parts['patch'] + 1),
            default => $parts['major'].'.'.$parts['minor'].'.'.($parts['patch'] + 1),
        };
    }
}
