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

    /**
     * Estimate shipping duration based on origin (Gudang Cimareme, Kab. Bandung Barat)
     * to destination province, city, and courier type.
     *
     * @param string|null $courierType ('toko' or 'expedisi')
     * @param string|null $courierCode ('jne', 'jnt', 'sicepat', 'tiki', 'pos', 'kurir_toko')
     * @param string|null $provinceName
     * @param string|null $cityName
     * @return string
     */
    public static function estimateDuration(
        ?string $courierType = null,
        ?string $courierCode = null,
        ?string $provinceName = null,
        ?string $cityName = null
    ): string {
        $cCode = strtolower(trim((string) $courierCode));
        $cType = strtolower(trim((string) $courierType));
        $prov = trim((string) $provinceName);
        $city = strtolower(trim((string) $cityName));

        // 1. Kurir Toko (Area Lokal Bandung Raya)
        if ($cType === 'toko' || str_contains($cCode, 'toko')) {
            if (str_contains($city, 'bandung barat') || str_contains($city, 'cimahi')) {
                return '1 hari';
            }
            return '1-2 hari';
        }

        // 2. Kurir Ekspedisi berdasarkan jarak geografis dari Gudang Cimareme (KBB, Jawa Barat)
        if (empty($prov)) {
            return '2-3 hari';
        }

        $provLower = strtolower($prov);

        // A. Wilayah Lokal Jawa Barat
        if (str_contains($provLower, 'jawa barat')) {
            if (str_contains($city, 'bandung barat') || str_contains($city, 'cimahi') || str_contains($city, 'kota bandung')) {
                return '1-2 hari';
            }
            return '1-3 hari';
        }

        // B. DKI Jakarta & Banten
        if (str_contains($provLower, 'dki jakarta') || str_contains($provLower, 'jakarta') || str_contains($provLower, 'banten')) {
            return ($cCode === 'pos' || $cCode === 'tiki') ? '2-3 hari' : '1-3 hari';
        }

        // C. Jawa Tengah & DI Yogyakarta
        if (str_contains($provLower, 'jawa tengah') || str_contains($provLower, 'yogyakarta')) {
            return ($cCode === 'pos') ? '2-4 hari' : '2-3 hari';
        }

        // D. Jawa Timur & Bali
        if (str_contains($provLower, 'jawa timur') || str_contains($provLower, 'bali')) {
            return ($cCode === 'pos') ? '3-5 hari' : '2-4 hari';
        }

        // E. Sumatera Bagian Selatan & Tengah (Lampung, Sumsel, Bengkulu, Jambi, Babel, Sumbar)
        if (
            str_contains($provLower, 'lampung') ||
            str_contains($provLower, 'sumatera selatan') ||
            str_contains($provLower, 'bengkulu') ||
            str_contains($provLower, 'jambi') ||
            str_contains($provLower, 'bangka belitung') ||
            str_contains($provLower, 'sumatera barat')
        ) {
            return ($cCode === 'pos') ? '4-6 hari' : '3-5 hari';
        }

        // F. Sumatera Bagian Utara & Riau (Sumut, Riau, Kepri)
        if (
            str_contains($provLower, 'sumatera utara') ||
            str_contains($provLower, 'riau')
        ) {
            return ($cCode === 'pos') ? '4-7 hari' : '3-6 hari';
        }

        // G. Aceh
        if (str_contains($provLower, 'aceh')) {
            return ($cCode === 'pos') ? '5-8 hari' : '4-7 hari';
        }

        // H. Nusa Tenggara Barat (NTB)
        if (str_contains($provLower, 'nusa tenggara barat')) {
            return ($cCode === 'pos') ? '4-6 hari' : '3-5 hari';
        }

        // I. Nusa Tenggara Timur (NTT)
        if (str_contains($provLower, 'nusa tenggara timur')) {
            return ($cCode === 'pos') ? '5-8 hari' : '4-7 hari';
        }

        // J. Kalimantan
        if (str_contains($provLower, 'kalimantan')) {
            if (str_contains($provLower, 'utara')) {
                return ($cCode === 'pos') ? '5-8 hari' : '4-7 hari';
            }
            return ($cCode === 'pos') ? '4-7 hari' : '3-6 hari';
        }

        // K. Sulawesi
        if (str_contains($provLower, 'sulawesi') || str_contains($provLower, 'gorontalo')) {
            if (str_contains($provLower, 'utara') || str_contains($provLower, 'gorontalo')) {
                return ($cCode === 'pos') ? '5-8 hari' : '4-7 hari';
            }
            return ($cCode === 'pos') ? '4-7 hari' : '3-6 hari';
        }

        // L. Maluku & Maluku Utara
        if (str_contains($provLower, 'maluku')) {
            return ($cCode === 'pos') ? '6-9 hari' : '5-8 hari';
        }

        // M. Papua & sekitarnya
        if (str_contains($provLower, 'papua')) {
            return ($cCode === 'pos') ? '7-12 hari' : '6-10 hari';
        }

        // Default Nasional
        return '2-4 hari';
    }
}
