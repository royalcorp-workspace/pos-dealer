<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ExpeditionIntegrationController extends Controller
{
    private array $providers = [
        'sap' => ['name' => 'SAP Internal', 'label' => 'SAP Internal', 'active' => true],
        'jne' => ['name' => 'JNE', 'label' => 'JNE', 'active' => true],
        'jt' => ['name' => 'J&T', 'label' => 'J&T Express', 'active' => true],
        'sicepat' => ['name' => 'SiCepat', 'label' => 'SiCepat', 'active' => true],
        'anteraja' => ['name' => 'AnterAja', 'label' => 'AnterAja', 'active' => true],
    ];

    public function providers(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => collect($this->providers)->map(fn($provider, $code) => [
                'code' => $code,
                'name' => $provider['name'],
                'label' => $provider['label'],
                'active' => $provider['active'],
            ])->values(),
        ]);
    }

    public function tracking(string $provider, string $awb): JsonResponse
    {
        $provider = strtolower($provider);
        if (!isset($this->providers[$provider])) {
            return response()->json([
                'success' => false,
                'message' => 'Provider ekspedisi tidak didukung.',
            ], 404);
        }

        try {
            $raw = $this->fetchTracking($provider, $awb);
            $normalized = $this->normalizeTracking($provider, $awb, $raw);

            return response()->json([
                'success' => true,
                'data' => $normalized,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data tracking dari provider.',
                'error' => $e->getMessage(),
            ], 502);
        }
    }

    public function rates(Request $request): JsonResponse
    {
        $request->validate([
            'origin' => 'required|string',
            'destination' => 'required|string',
            'weight' => 'required|numeric|min:1',
            'providers' => 'nullable|array',
            'providers.*' => 'string|in:' . implode(',', array_keys($this->providers)),
        ]);

        $providers = (array) $request->input('providers', array_keys($this->providers));

        try {
            $rates = [];
            foreach ($providers as $provider) {
                $raw = $this->fetchRates(strtolower($provider), $request->only(['origin', 'destination', 'weight']));
                $rates = array_merge($rates, $this->normalizeRates(strtolower($provider), $raw));
            }

            usort($rates, fn($a, $b) => $a['price'] <=> $b['price']);

            return response()->json([
                'success' => true,
                'data' => [
                    'origin' => $request->input('origin'),
                    'destination' => $request->input('destination'),
                    'weight' => (float) $request->input('weight'),
                    'rates' => $rates,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data ongkir dari provider.',
                'error' => $e->getMessage(),
            ], 502);
        }
    }

    private function fetchTracking(string $provider, string $awb): array
    {
        return match ($provider) {
            'sap' => $this->fetchSapTracking($awb),
            'jne' => $this->fetchJneTracking($awb),
            'jt' => $this->fetchJtTracking($awb),
            'sicepat' => $this->fetchSiCepatTracking($awb),
            'anteraja' => $this->fetchAnterAjaTracking($awb),
            default => [],
        };
    }

    private function fetchRates(string $provider, array $payload): array
    {
        return match ($provider) {
            'sap' => $this->fetchSapRates($payload),
            'jne' => $this->fetchJneRates($payload),
            'jt' => $this->fetchJtRates($payload),
            'sicepat' => $this->fetchSiCepatRates($payload),
            'anteraja' => $this->fetchAnterAjaRates($payload),
            default => [],
        };
    }

    private function fetchSapTracking(string $awb): array
    {
        return [
            'status' => 'SHIPPED',
            'current_location' => 'Gudang SAP Internal',
            'destination' => 'Jakarta Selatan',
            'eta' => now()->addDays(2)->toDateTimeString(),
            'events' => [
                ['time' => now()->toDateTimeString(), 'status' => 'In Transit', 'location' => 'Gudang SAP Internal', 'description' => 'Paket dalam perjalanan menuju alamat penerima.'],
            ],
        ];
    }

    private function fetchSapRates(array $payload): array
    {
        return [
            ['service' => 'SAP Standard', 'price' => 25000, 'eta_min_days' => 2, 'eta_max_days' => 4],
            ['service' => 'SAP Express', 'price' => 45000, 'eta_min_days' => 1, 'eta_max_days' => 2],
        ];
    }

    private function fetchJneTracking(string $awb): array
    {
        return $this->callProvider('jne', 'tracking', compact('awb'));
    }

    private function fetchJneRates(array $payload): array
    {
        return $this->callProvider('jne', 'rates', $payload);
    }

    private function fetchJtTracking(string $awb): array
    {
        return $this->callProvider('jt', 'tracking', compact('awb'));
    }

    private function fetchJtRates(array $payload): array
    {
        return $this->callProvider('jt', 'rates', $payload);
    }

    private function fetchSiCepatTracking(string $awb): array
    {
        return $this->callProvider('sicepat', 'tracking', compact('awb'));
    }

    private function fetchSiCepatRates(array $payload): array
    {
        return $this->callProvider('sicepat', 'rates', $payload);
    }

    private function fetchAnterAjaTracking(string $awb): array
    {
        return $this->callProvider('anteraja', 'tracking', compact('awb'));
    }

    private function fetchAnterAjaRates(array $payload): array
    {
        return $this->callProvider('anteraja', 'rates', $payload);
    }

    private function callProvider(string $provider, string $action, array $payload): array
    {
        $baseUrl = config("services.expeditions.{$provider}.base_url");
        $token = config("services.expeditions.{$provider}.token");

        if (!$baseUrl) {
            return [];
        }

        $response = Http::withToken($token)->get("{$baseUrl}/{$action}", $payload);

        return $response->throw()->json();
    }

    private function normalizeTracking(string $provider, string $awb, array $raw): array
    {
        $events = collect(data_get($raw, 'events', []))->map(function ($event) {
            return [
                'time' => data_get($event, 'time') ?? data_get($event, 'timestamp') ?? data_get($event, 'date'),
                'status' => data_get($event, 'status') ?? data_get($event, 'status_label') ?? data_get($event, 'manifest'),
                'location' => data_get($event, 'location') ?? data_get($event, 'city') ?? data_get($event, 'office'),
                'description' => data_get($event, 'description') ?? data_get($event, 'note') ?? data_get($event, 'manifest_description'),
                'raw' => $event,
            ];
        })->filter()->values()->toArray();

        $latestEvent = last($events) ?? [];

        return [
            'provider' => $provider,
            'provider_name' => $this->providers[$provider]['label'],
            'awb' => $awb,
            'status' => data_get($raw, 'status') ?? data_get($latestEvent, 'status', 'UNKNOWN'),
            'status_label' => data_get($raw, 'status_label') ?? data_get($latestEvent, 'status', 'Unknown'),
            'current_location' => data_get($raw, 'current_location') ?? data_get($latestEvent, 'location'),
            'destination' => data_get($raw, 'destination'),
            'eta' => data_get($raw, 'eta'),
            'events' => $events,
        ];
    }

    private function normalizeRates(string $provider, array $raw): array
    {
        return collect(data_get($raw, 'rates', $raw))->map(function ($rate) use ($provider) {
            return [
                'provider' => $provider,
                'provider_name' => $this->providers[$provider]['label'],
                'courier_code' => data_get($rate, 'courier_code') ?? data_get($rate, 'service_code') ?? $provider,
                'courier_name' => data_get($rate, 'courier_name') ?? data_get($rate, 'service') ?? $this->providers[$provider]['label'],
                'service' => data_get($rate, 'service') ?? data_get($rate, 'service_name'),
                'price' => (float) (data_get($rate, 'price') ?? data_get($rate, 'cost') ?? data_get($rate, 'total') ?? 0),
                'currency' => data_get($rate, 'currency', 'IDR'),
                'eta_min_days' => (int) (data_get($rate, 'eta_min_days') ?? data_get($rate, 'etd_min') ?? 0),
                'eta_max_days' => (int) (data_get($rate, 'eta_max_days') ?? data_get($rate, 'etd_max') ?? 0),
                'raw' => $rate,
            ];
        })->filter(fn($rate) => $rate['price'] > 0)->values()->toArray();
    }
}
