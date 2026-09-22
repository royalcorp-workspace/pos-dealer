<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\Carbon;

class EtaService
{
    /**
     * Parse duration string (e.g. "1 - 2", "1-2 days", "3 hari", "2", "1 - 2 hours") into min and max days.
     *
     * @param string|null $duration
     * @return array{min_days: int, max_days: int, unit: string}
     */
    public static function parseDuration(?string $duration): array
    {
        if (empty($duration)) {
            return ['min_days' => 1, 'max_days' => 2, 'unit' => 'hari'];
        }

        $clean = strtolower(trim($duration));
        $unit = 'hari';
        if (str_contains($clean, 'jam') || str_contains($clean, 'hour')) {
            $unit = 'jam';
            return ['min_days' => 0, 'max_days' => 1, 'unit' => 'jam'];
        }

        // Match patterns like "1 - 2", "1-2", "1 - 3 days", "2-3 hari"
        if (preg_match('/(\d+)\s*(?:-|to|\/)\s*(\d+)/', $clean, $matches)) {
            $min = (int) $matches[1];
            $max = (int) $matches[2];
            return [
                'min_days' => max(1, min($min, $max)),
                'max_days' => max(1, max($min, $max)),
                'unit' => $unit,
            ];
        }

        // Match single number like "1", "2 hari", "1 day"
        if (preg_match('/(\d+)/', $clean, $matches)) {
            $val = (int) $matches[1];
            return [
                'min_days' => max(1, $val),
                'max_days' => max(1, $val),
                'unit' => $unit,
            ];
        }

        return ['min_days' => 1, 'max_days' => 2, 'unit' => 'hari'];
    }

    /**
     * Calculate ETA dates and labels from duration or min/max days.
     *
     * @param string|null $duration
     * @param Carbon|null $fromDate
     * @return array{min_days: int, max_days: int, min_date: Carbon, max_date: Carbon, estimated_at: Carbon, duration: string, formatted_label: string}
     */
    public static function calculateEta(?string $duration, ?Carbon $fromDate = null): array
    {
        $start = $fromDate ? $fromDate->copy() : now();
        $parsed = self::parseDuration($duration);
        
        $minDays = $parsed['min_days'];
        $maxDays = $parsed['max_days'];

        // If order processed after 17:00, count starts from next business day
        if ($start->hour >= 17) {
            $minDays += 1;
            $maxDays += 1;
        }

        $minDate = $start->copy()->addDays($minDays)->startOfDay();
        $maxDate = $start->copy()->addDays($maxDays)->endOfDay();

        $durationLabel = ($minDays === $maxDays) ? "{$minDays} hari" : "{$minDays}-{$maxDays} hari";

        if ($minDate->format('Y-m-d') === $maxDate->format('Y-m-d')) {
            $formattedDate = $minDate->format('d M Y');
        } else {
            $formattedDate = $minDate->format('d M') . ' - ' . $maxDate->format('d M Y');
        }

        $fullLabel = "{$formattedDate} ({$durationLabel})";

        return [
            'min_days' => $minDays,
            'max_days' => $maxDays,
            'min_date' => $minDate,
            'max_date' => $maxDate,
            'estimated_at' => $maxDate,
            'duration' => $durationLabel,
            'formatted_label' => $fullLabel,
        ];
    }
}
