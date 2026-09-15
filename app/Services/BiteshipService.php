<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BiteshipService
{
    private ?string $apiKey;
    private string $baseUrl;
    private array $originConfig;

    public function __construct()
    {
        $this->apiKey = config('services.biteship.api_key') ?: env('BITESHIP_API_KEY');
        $this->baseUrl = rtrim((string) (config('services.biteship.base_url') ?: env('BITESHIP_BASE_URL', 'https://api.biteship.com')), '/');
        $this->originConfig = [
            'area_id' => config('services.biteship.origin_area_id') ?: env('BITESHIP_ORIGIN_AREA_ID'),
            'postal_code' => config('services.biteship.origin_postal_code') ?: env('BITESHIP_ORIGIN_POSTAL_CODE', '40552'),
            'latitude' => config('services.biteship.origin_latitude') ?: env('BITESHIP_ORIGIN_LATITUDE'),
            'longitude' => config('services.biteship.origin_longitude') ?: env('BITESHIP_ORIGIN_LONGITUDE'),
            'address' => config('services.biteship.origin_address') ?: env('BITESHIP_ORIGIN_ADDRESS', 'Jl. Raya Barat, Cimareme, Kec. Ngamprah, Kabupaten Bandung Barat'),
            'contact_name' => config('services.biteship.origin_contact_name') ?: env('BITESHIP_ORIGIN_CONTACT_NAME', 'Admin Gudang IMG'),
            'contact_phone' => config('services.biteship.origin_contact_phone') ?: env('BITESHIP_ORIGIN_CONTACT_PHONE', '081112345678'),
        ];
    }

    /**
     * Check if Biteship integration is active and configured.
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getOriginConfig(): array
    {
        return $this->originConfig;
    }

    /**
     * Check rates from Biteship for couriers.
     *
     * @param array $items Array of cart items
     * @param string|null $destinationPostalCode
     * @param string|null $destinationAreaId
     * @param array|string $couriers Array or comma-separated list of couriers (e.g. ['jne', 'sicepat'])
     * @return array
     */
    public function getRates(
        array $items,
        ?string $destinationPostalCode = null,
        ?string $destinationAreaId = null,
        array|string $couriers = []
    ): array {
        if (!$this->isConfigured()) {
            return [];
        }

        $formattedItems = $this->formatItemsForBiteship($items);
        if (empty($formattedItems)) {
            return [];
        }

        if (is_array($couriers)) {
            $courierList = implode(',', array_filter(array_map('strtolower', $couriers)));
        } else {
            $courierList = strtolower(trim((string) $couriers));
        }

        if (empty($courierList)) {
            $courierList = 'jne,jnt,sicepat,tiki,pos';
        }

        $payload = [
            'couriers' => $courierList,
            'items' => $formattedItems,
        ];

        // Set Origin
        if (!empty($this->originConfig['area_id'])) {
            $payload['origin_area_id'] = $this->originConfig['area_id'];
        } elseif (!empty($this->originConfig['postal_code'])) {
            $payload['origin_postal_code'] = (int) $this->originConfig['postal_code'];
        }

        if (!empty($this->originConfig['latitude']) && !empty($this->originConfig['longitude'])) {
            $payload['origin_latitude'] = (float) $this->originConfig['latitude'];
            $payload['origin_longitude'] = (float) $this->originConfig['longitude'];
        }

        // Set Destination
        if (!empty($destinationAreaId)) {
            $payload['destination_area_id'] = $destinationAreaId;
        } elseif (!empty($destinationPostalCode)) {
            $payload['destination_postal_code'] = (int) $destinationPostalCode;
        } else {
            return [];
        }

        // Cache rates for 10 minutes to conserve API quota and ensure fast responses
        $cacheKey = 'biteship_rates_' . md5(json_encode($payload));
        return Cache::remember($cacheKey, 600, function () use ($payload) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => $this->apiKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])->timeout(8)->post("{$this->baseUrl}/v1/rates/couriers", $payload);

                if ($response->successful()) {
                    $body = $response->json();
                    return $this->normalizeRatesResponse($body['pricing'] ?? []);
                }

                Log::warning('Biteship API getRates error', [
                    'status' => $response->status(),
                    'body' => $response->json() ?? $response->body(),
                    'payload' => $payload,
                ]);
            } catch (\Throwable $e) {
                Log::error('Biteship API Exception: ' . $e->getMessage(), [
                    'payload' => $payload,
                ]);
            }

            return [];
        });
    }

    /**
     * Get the best rate for a specific courier code (e.g. 'jne', 'sicepat', 'jnt', 'pos', 'tiki').
     */
    public function getBestRateForCourier(
        string $courierCode,
        array $items,
        ?string $destinationPostalCode = null,
        ?string $destinationAreaId = null
    ): ?array {
        $cleanCourierCode = strtolower(trim($courierCode));
        $rates = $this->getRates($items, $destinationPostalCode, $destinationAreaId, $cleanCourierCode);

        if (empty($rates)) {
            return null;
        }

        // Filter matching rates for this courier
        $matching = array_filter($rates, function ($rate) use ($cleanCourierCode) {
            return strtolower($rate['courier_code'] ?? '') === $cleanCourierCode
                || strtolower($rate['company'] ?? '') === $cleanCourierCode;
        });

        if (empty($matching)) {
            return null;
        }

        // Prioritize standard/regular services if available, otherwise lowest price
        usort($matching, function ($a, $b) {
            $aIsReg = str_contains(strtolower($a['service_code'] ?? ''), 'reg') || strtolower($a['type'] ?? '') === 'standard';
            $bIsReg = str_contains(strtolower($b['service_code'] ?? ''), 'reg') || strtolower($b['type'] ?? '') === 'standard';

            if ($aIsReg && !$bIsReg) return -1;
            if (!$aIsReg && $bIsReg) return 1;

            return $a['price'] <=> $b['price'];
        });

        return reset($matching) ?: null;
    }

    /**
     * Search areas / maps for auto-resolving destination area_id.
     */
    public function searchAreas(string $input): array
    {
        if (!$this->isConfigured() || empty(trim($input))) {
            return [];
        }

        $cacheKey = 'biteship_areas_' . md5(strtolower(trim($input)));
        return Cache::remember($cacheKey, 86400, function () use ($input) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => $this->apiKey,
                    'Accept' => 'application/json',
                ])->timeout(5)->get("{$this->baseUrl}/v1/maps/areas", [
                    'countries' => 'ID',
                    'input' => trim($input),
                    'type' => 'single',
                ]);

                if ($response->successful()) {
                    $body = $response->json();
                    return $body['areas'] ?? [];
                }
            } catch (\Throwable $e) {
                Log::warning('Biteship searchAreas failed: ' . $e->getMessage());
            }

            return [];
        });
    }

    /**
     * Retrieve tracking info for a waybill.
     */
    public function getTracking(string $waybillId, string $courierCode): array
    {
        if (!$this->isConfigured()) {
            return [];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => $this->apiKey,
                'Accept' => 'application/json',
            ])->timeout(8)->get("{$this->baseUrl}/v1/trackings/{$waybillId}/couriers/" . strtolower($courierCode));

            if ($response->successful()) {
                $body = $response->json();
                return $this->normalizeTrackingResponse($body);
            }
        } catch (\Throwable $e) {
            Log::error('Biteship getTracking failed: ' . $e->getMessage());
        }

        return [];
    }

    /**
     * Create an order (book shipment) in Biteship.
     */
    public function createOrder(array $orderPayload): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'message' => 'Biteship API Key belum dikonfigurasi.'];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(12)->post("{$this->baseUrl}/v1/orders", $orderPayload);

            $body = $response->json() ?? [];
            if ($response->successful() && ($body['success'] ?? false)) {
                return [
                    'success' => true,
                    'data' => $body,
                ];
            }

            return [
                'success' => false,
                'message' => $body['error'] ?? $body['message'] ?? 'Gagal membuat pesanan pengiriman di Biteship.',
                'raw' => $body,
            ];
        } catch (\Throwable $e) {
            Log::error('Biteship createOrder exception: ' . $e->getMessage(), ['payload' => $orderPayload]);
            return [
                'success' => false,
                'message' => 'Terjadi kesalahan sistem saat menghubungi Biteship: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Format cart items into Biteship items specification.
     */
    private function formatItemsForBiteship(array $cart): array
    {
        $items = [];
        foreach ($cart as $item) {
            $quantity = max(1, (int) ($item['quantity'] ?? 1));
            $name = (string) ($item['name'] ?? 'Produk IMG');
            $price = (int) round((float) ($item['sell_price'] ?? 10000));

            // Weight in grams (Biteship expects grams, min 100g)
            $weight = (float) ($item['weight'] ?? 0);
            if ($weight > 0 && $weight < 20) {
                // Stored in kilograms, convert to grams
                $weightGrams = (int) round($weight * 1000);
            } elseif ($weight >= 20) {
                // Likely already grams or large item
                $weightGrams = (int) round($weight);
            } else {
                $weightGrams = 1000; // Default 1kg
            }

            $length = max(5, (int) ($item['length'] ?? 10));
            $width = max(5, (int) ($item['width'] ?? 10));
            $height = max(5, (int) ($item['height'] ?? 10));

            $items[] = [
                'name' => mb_substr($name, 0, 80),
                'description' => mb_substr($name, 0, 80),
                'value' => max(1000, $price),
                'quantity' => $quantity,
                'weight' => max(100, $weightGrams),
                'length' => $length,
                'width' => $width,
                'height' => $height,
            ];
        }

        return $items;
    }

    /**
     * Normalize rates pricing array from Biteship response.
     */
    private function normalizeRatesResponse(array $pricing): array
    {
        $rates = [];
        foreach ($pricing as $p) {
            $price = (int) ($p['price'] ?? 0);
            if ($price <= 0) {
                continue;
            }

            $rates[] = [
                'company' => strtolower($p['company'] ?? $p['courier_code'] ?? ''),
                'courier_code' => strtolower($p['courier_code'] ?? $p['company'] ?? ''),
                'courier_name' => $p['courier_name'] ?? strtoupper($p['company'] ?? ''),
                'service_code' => strtolower($p['courier_service_code'] ?? ''),
                'service_name' => $p['courier_service_name'] ?? 'Reguler',
                'description' => $p['description'] ?? '',
                'duration' => $p['duration'] ?? ($p['shipment_duration_range'] ?? '') . ' ' . ($p['shipment_duration_unit'] ?? 'hari'),
                'price' => $price,
                'type' => $p['type'] ?? $p['service_type'] ?? 'standard',
            ];
        }

        return $rates;
    }

    /**
     * Normalize tracking response from Biteship.
     */
    private function normalizeTrackingResponse(array $raw): array
    {
        $history = $raw['history'] ?? [];
        $events = [];

        foreach ($history as $h) {
            $events[] = [
                'time' => $h['updated_at'] ?? '',
                'status' => $h['status'] ?? 'ON_PROCESS',
                'location' => $h['city'] ?? $h['location'] ?? '',
                'description' => $h['note'] ?? $h['service_type'] ?? '',
            ];
        }

        return [
            'provider' => strtolower($raw['courier']['company'] ?? 'biteship'),
            'provider_name' => $raw['courier']['name'] ?? 'Ekspedisi',
            'waybill_id' => $raw['waybill_id'] ?? '',
            'status' => strtoupper($raw['status'] ?? 'ON_PROCESS'),
            'status_label' => ucwords(str_replace('_', ' ', $raw['status'] ?? 'Dalam Proses')),
            'current_location' => $raw['destination']['address'] ?? '',
            'events' => $events,
            'link' => $raw['link'] ?? null,
        ];
    }
}
