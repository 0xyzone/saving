<?php

declare(strict_types=1);

namespace App\Services;

use Anuzpandey\LaravelNepaliDate\LaravelNepaliDate;
use Carbon\Carbon;
use DateTimeInterface;
use Throwable;

class NepaliDateService
{
    /**
     * Nepali Month Names.
     */
    public const MONTHS = [
        1 => 'Baishakh',
        2 => 'Jestha',
        3 => 'Ashadh',
        4 => 'Shrawan',
        5 => 'Bhadra',
        6 => 'Ashwin',
        7 => 'Kartik',
        8 => 'Mangsir',
        9 => 'Poush',
        10 => 'Magh',
        11 => 'Falgun',
        12 => 'Chaitra',
    ];

    /**
     * Convert an English (AD) date to Nepali (BS) YYYY-MM-DD string.
     */
    public static function toBs(DateTimeInterface|string|null $date): string
    {
        if (blank($date)) {
            return '';
        }

        try {
            $carbon = $date instanceof DateTimeInterface ? $date : Carbon::parse($date);

            return LaravelNepaliDate::from($carbon->format('Y-m-d'))->toNepaliDate();
        } catch (Throwable) {
            return '';
        }
    }

    /**
     * Format an English (AD) date or Nepali (BS) date into a readable string (e.g. '26 Kartik 2083 BS').
     */
    public static function formatBs(DateTimeInterface|string|null $date): string
    {
        if (blank($date)) {
            return '-';
        }

        $bsString = '';

        if (is_string($date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) && (int) substr($date, 0, 4) >= 2000) {
            $bsString = $date;
        } else {
            $bsString = static::toBs($date);
        }

        if (empty($bsString)) {
            return '-';
        }

        $parts = explode('-', $bsString);
        if (count($parts) !== 3) {
            return $bsString;
        }

        [$y, $m, $d] = $parts;
        $monthName = self::MONTHS[(int) $m] ?? $m;

        return "{$d} {$monthName} {$y} BS";
    }

    /**
     * Convert a Nepali (BS) YYYY-MM-DD string to English (AD) YYYY-MM-DD string.
     */
    public static function toAd(string $bsDate): string
    {
        $cleanBs = trim($bsDate);

        if (empty($cleanBs)) {
            return now()->format('Y-m-d');
        }

        try {
            return LaravelNepaliDate::from($cleanBs)->toEnglishDate();
        } catch (Throwable) {
            return now()->format('Y-m-d');
        }
    }

    /**
     * Add Nepali months to a starting BS date string, ensuring valid day capping.
     */
    public static function addNepaliMonths(string $startBsDate, int $monthsToAdd): string
    {
        $parts = explode('-', trim($startBsDate));
        if (count($parts) !== 3) {
            $startBsDate = static::currentBsDate();
            $parts = explode('-', $startBsDate);
        }

        [$y, $m, $d] = array_map('intval', $parts);

        $totalMonths = ($m - 1) + $monthsToAdd;
        $targetYear = $y + intdiv($totalMonths, 12);
        $targetMonth = ($totalMonths % 12) + 1;

        if ($targetMonth <= 0) {
            $targetMonth += 12;
            $targetYear--;
        }

        // Cap day to maximum days in target month
        try {
            $converter = LaravelNepaliDate::from(sprintf('%04d-%02d-01', $targetYear, $targetMonth));
            $maxDays = $converter->daysInMonth($targetMonth, $targetYear);
        } catch (Throwable) {
            $maxDays = 30;
        }

        $targetDay = min($d, $maxDays);

        return sprintf('%04d-%02d-%02d', $targetYear, $targetMonth, $targetDay);
    }

    /**
     * Get the current Nepali date in YYYY-MM-DD.
     */
    public static function currentBsDate(): string
    {
        return static::toBs(now());
    }

    /**
     * Nepali Month Names with Devanagari.
     */
    public const MONTHS_DEV = [
        1 => '01 - Baishakh (वैशाख)',
        2 => '02 - Jestha (जेठ)',
        3 => '03 - Ashadh (असार)',
        4 => '04 - Shrawan (श्रावण)',
        5 => '05 - Bhadra (भाद्र)',
        6 => '06 - Ashwin (असोज)',
        7 => '07 - Kartik (कार्तिक)',
        8 => '08 - Mangsir (मंसिर)',
        9 => '09 - Poush (पुष)',
        10 => '10 - Magh (माघ)',
        11 => '11 - Falgun (फागुन)',
        12 => '12 - Chaitra (चैत)',
    ];

    /**
     * List of selectable Nepali Years (e.g. 2080 to 2095).
     */
    public static function getNepaliYears(): array
    {
        $current = (int) substr(static::currentBsDate(), 0, 4);
        if ($current < 2000) {
            $current = 2081;
        }

        $years = [];
        for ($y = $current - 2; $y <= $current + 10; $y++) {
            $years[$y] = "{$y} BS";
        }

        return $years;
    }

    /**
     * List of selectable Nepali Months.
     */
    public static function getNepaliMonths(): array
    {
        return self::MONTHS_DEV;
    }

    /**
     * List of selectable Nepali Days (1 to 32).
     */
    public static function getNepaliDays(?int $month = null, ?int $year = null): array
    {
        $max = ($month && $year) ? static::daysInMonth($month, $year) : 32;
        $days = [];

        for ($d = 1; $d <= $max; $d++) {
            $days[$d] = sprintf('%02d गते', $d);
        }

        return $days;
    }

    /**
     * Get days in specific Nepali month and year.
     */
    public static function daysInMonth(int $month, int $year): int
    {
        try {
            $converter = LaravelNepaliDate::from(sprintf('%04d-%02d-01', $year, $month));

            return $converter->daysInMonth($month, $year);
        } catch (Throwable) {
            return 30;
        }
    }

    /**
     * Parse a BS date string into [year, month, day] integers.
     *
     * @return array{0: int, 1: int, 2: int}
     */
    public static function explodeBs(?string $bsDate): array
    {
        if (blank($bsDate) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($bsDate))) {
            $bsDate = static::currentBsDate();
        }

        $parts = explode('-', trim($bsDate));

        return [
            (int) ($parts[0] ?? 2081),
            (int) ($parts[1] ?? 1),
            (int) ($parts[2] ?? 1),
        ];
    }
}
