<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ChangelogService
{
    public const DEFAULT_VERSION = '1.0.0';

    /**
     * Get path to the CHANGELOG.md file.
     */
    public static function getChangelogPath(): string
    {
        return base_path('CHANGELOG.md');
    }

    /**
     * Get raw markdown content of CHANGELOG.md.
     */
    public static function getRawContent(): string
    {
        $path = self::getChangelogPath();

        return File::exists($path) ? File::get($path) : '';
    }

    /**
     * Parse all release entries from CHANGELOG.md.
     *
     * @return array<int, array{
     *     version: string,
     *     date: ?string,
     *     type: string,
     *     badge: string,
     *     title: string,
     *     description: ?string,
     *     highlights: array<int, string>,
     *     raw_body: string,
     *     html: string
     * }>
     */
    public static function getReleases(): array
    {
        $content = self::getRawContent();

        if (trim($content) === '') {
            return [];
        }

        // Match version headers: ## [1.0.5] - 2026-10-31 or ## 1.0.5 - 2026-10-31 or ## [v1.0.5]
        $pattern = '/^##\s+\[?v?([0-9]+\.[0-9]+(?:\.[0-9]+)?(?:-[0-9A-Za-z.-]+)?)\]?(?:\s*-\s*([0-9]{4}-[0-9]{2}-[0-9]{2}|[A-Za-z]+ \d{1,2},? \d{4}|[^\n\r]+))?/m';

        if (! preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            return [];
        }

        $releases = [];
        $totalMatches = count($matches[0]);

        for ($i = 0; $i < $totalMatches; $i++) {
            $version = $matches[1][$i][0];
            $date = isset($matches[2][$i][0]) ? trim($matches[2][$i][0]) : null;

            $startPos = $matches[0][$i][1] + strlen($matches[0][$i][0]);
            $endPos = ($i + 1 < $totalMatches) ? $matches[0][$i + 1][1] : strlen($content);

            $sectionBody = trim(substr($content, $startPos, $endPos - $startPos));

            // Extract highlights and type
            $highlights = [];
            $descriptionLines = [];
            $lines = explode("\n", $sectionBody);
            $primarySectionType = null;

            foreach ($lines as $line) {
                $trimmed = trim($line);

                if (preg_match('/^###\s+(.+)$/', $trimmed, $headingMatch)) {
                    if ($primarySectionType === null) {
                        $primarySectionType = strtolower(trim($headingMatch[1]));
                    }

                    continue;
                }

                if (preg_match('/^[-*+]\s+(.+)$/', $trimmed, $bulletMatch)) {
                    $highlights[] = trim($bulletMatch[1]);
                } elseif ($trimmed !== '' && ! str_starts_with($trimmed, '#')) {
                    $descriptionLines[] = $trimmed;
                }
            }

            // Determine release type (major, minor, macro)
            $type = self::classifyVersionType($version, $primarySectionType);
            $badge = ucfirst($primarySectionType ?: $type);

            $title = ! empty($descriptionLines)
                ? $descriptionLines[0]
                : (! empty($highlights) ? $highlights[0] : "Release v{$version}");

            $description = count($descriptionLines) > 1
                ? implode(' ', array_slice($descriptionLines, 1))
                : (count($descriptionLines) === 1 && ! empty($highlights) ? $descriptionLines[0] : null);

            $html = Str::markdown($sectionBody);

            $releases[] = [
                'version' => $version,
                'date' => $date,
                'type' => $type,
                'badge' => $badge,
                'title' => $title,
                'description' => $description,
                'highlights' => $highlights,
                'raw_body' => $sectionBody,
                'html' => $html,
            ];
        }

        return $releases;
    }

    /**
     * Classify version type as major, minor, or macro (patch).
     */
    protected static function classifyVersionType(string $version, ?string $section = null): string
    {
        if ($section === 'breaking' || $section === 'deprecated' || $section === 'major') {
            return 'major';
        }

        if ($section === 'added' || $section === 'features' || $section === 'minor') {
            return 'minor';
        }

        $parts = self::parseVersion($version);

        if ($parts['patch'] > 0) {
            return 'macro';
        }

        if ($parts['minor'] > 0) {
            return 'minor';
        }

        return 'major';
    }

    /**
     * Get the current active version string (e.g. "1.0.5").
     */
    public static function getCurrentVersion(): string
    {
        $releases = self::getReleases();

        if (! empty($releases)) {
            return ltrim($releases[0]['version'], 'vV');
        }

        // Fallback: search for first version pattern in CHANGELOG.md
        $content = self::getRawContent();
        if (preg_match('/##\s+\[?v?([0-9]+\.[0-9]+(?:\.[0-9]+)?)/', $content, $m)) {
            return $m[1];
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
