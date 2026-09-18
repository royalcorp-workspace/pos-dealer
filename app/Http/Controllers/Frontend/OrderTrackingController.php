<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Frontend\Customer\Customer;
use App\Models\Frontend\Order\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use stdClass;

class OrderTrackingController extends Controller
{
    private function dummyOrder(): stdClass
    {
        $customer = (object) [
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
        ];

        $items = new Collection([
            (object) [
                'name' => 'Elite Royal Sovereign Springbed',
                'quantity' => 1,
                'unit_price' => 3500000,
                'total' => 3500000,
                'item_notes' => 'Mohon dikirim sebelum jam 14.00.',
                'product' => (object) ['thumbnail_url' => 'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&q=80&w=400&h=300'],
            ],
            (object) [
                'name' => 'Moro Baby Complete Sleep Set',
                'quantity' => 2,
                'unit_price' => 225000,
                'total' => 450000,
                'item_notes' => '',
                'product' => (object) ['thumbnail_url' => 'https://images.unsplash.com/photo-1519689680058-324335c77eba?auto=format&fit=crop&q=80&w=400&h=300'],
            ],
        ]);

        $dummyWaybill = 'WYB-MOCK-888999';
        $dummyPayload1 = [
            'event' => 'order.waybill_id',
            'order_id' => 'biteship-ord-mock123',
            'courier_waybill_id' => $dummyWaybill,
            'courier_tracking_id' => $dummyWaybill,
            'courier_company' => 'sicepat',
            'status' => 'picking_up',
            'location' => 'DC Cakung',
            'note' => 'Waybill terbit / diperbarui: ' . $dummyWaybill,
            'metadata' => [
                'order_number' => 'ORD-DUMMY-20260619-0001',
            ],
        ];
        $dummyPayload2 = [
            'event' => 'order.status',
            'order_id' => 'biteship-ord-mock123',
            'courier_waybill_id' => $dummyWaybill,
            'courier_company' => 'sicepat',
            'status' => 'in_transit',
            'location' => 'Jakarta Sortir',
            'note' => 'Paket sedang dalam proses sortir untuk rute berikutnya.',
            'metadata' => [
                'order_number' => 'ORD-DUMMY-20260619-0001',
            ],
        ];
        $dummyPayload3 = [
            'event' => 'order.status',
            'order_id' => 'biteship-ord-mock123',
            'courier_waybill_id' => $dummyWaybill,
            'courier_company' => 'sicepat',
            'status' => 'delivering',
            'location' => 'Bekasi, Jawa Barat',
            'note' => 'Paket sedang diantar ke alamat tujuan.',
            'metadata' => [
                'order_number' => 'ORD-DUMMY-20260619-0001',
            ],
        ];

        return (object) [
            'id' => 'ORD-DUMMY-20260619-0001',
            'order_number' => 'ORD-DUMMY-20260619-0001',
            'status' => 5,
            'created_at' => now()->subDays(3),
            'customer' => $customer,
            'items' => $items,
            'total' => 3950000,
            'shipment' => [
                'waybill_id' => $dummyWaybill,
                'courier' => 'SiCepat Express',
                'latest_payload' => $dummyPayload3,
                'events' => new Collection([
                    (object) ['status' => 'Received by Courier', 'title' => 'Paket diterima kurir', 'location' => 'DC Cakung', 'description' => 'Paket telah diterima oleh ekspedisi di DC Cakung.', 'date' => now()->subDay(), 'payload' => $dummyPayload1],
                    (object) ['status' => 'In Transit', 'title' => 'Paket transit', 'location' => 'Jakarta Sortir', 'description' => 'Paket sedang dalam proses sortir untuk rute berikutnya.', 'date' => now()->subHours(8), 'payload' => $dummyPayload2],
                    (object) ['status' => 'Delivering', 'title' => 'Paket dalam perjalanan', 'location' => 'Bekasi, Jawa Barat', 'description' => 'Paket sedang diantar menuju alamat penerima.', 'date' => now(), 'payload' => $dummyPayload3],
                ]),
            ],
        ];
    }

    private function buildShipment(object $order, int $currentStatus): ?array
    {
        // 1. Fetch real webhook tracking logs from delivery_logs table
        $logs = \Illuminate\Support\Facades\DB::table('delivery_logs')
            ->where(function ($q) use ($order) {
                if (!empty($order->id)) {
                    $q->where('order_id', $order->id);
                }
                if (!empty($order->order_number)) {
                    $q->orWhereRaw("payload->'metadata'->>'order_number' = ?", [$order->order_number]);
                }
            })
            ->orderBy('created_at', 'asc')
            ->get();

        $delivery = \Illuminate\Support\Facades\DB::table('deliveries')
            ->where('order_id', $order->id ?? null)
            ->first();

        $waybillId = $logs->firstWhere('waybill_id', '!=', null)->waybill_id
            ?? $delivery->tracking_number
            ?? data_get($order->meta, 'waybill_id')
            ?? data_get($order->meta, 'resi')
            ?? null;

        $courierName = $order->courier->name 
            ?? $logs->firstWhere('courier_code', '!=', null)->courier_code 
            ?? 'Kurir Pengiriman';

        if ($logs->isNotEmpty()) {
            $events = new Collection();
            foreach ($logs as $log) {
                $rawPayload = is_string($log->payload) ? json_decode($log->payload, true) : (array) $log->payload;
                $date = $log->created_at ? \Carbon\Carbon::parse($log->created_at) : now();

                $events->push((object) [
                    'status' => $log->status ?? $log->event ?? 'In Transit',
                    'title' => $log->note ?? ('Status: ' . ($log->status ?? $log->event)),
                    'location' => $log->location ?? '-',
                    'description' => $log->note ?? 'Pembaruan dari ekspedisi.',
                    'date' => $date,
                    'event' => $log->event,
                    'waybill_id' => $log->waybill_id,
                    'payload' => $rawPayload,
                ]);
            }

            return [
                'waybill_id' => $waybillId,
                'courier' => $courierName,
                'events' => $events,
                'latest_payload' => $events->last()?->payload ?? null,
            ];
        }

        // Fallback for orders with status >= 4 (Shipped / Delivered)
        if ($currentStatus < 4) {
            return null;
        }

        $createdAt = $order->created_at ? \Carbon\Carbon::parse($order->created_at) : now();
        $customer = $order->customer ?? null;
        $customerName = $customer->name ?? 'Pelanggan';
        $destination = $customer->city ?? $customer->address ?? 'Alamat penerima';
        $events = new Collection();

        $basePayload = [
            'order_id' => $order->id ?? null,
            'order_number' => $order->order_number ?? null,
            'courier_waybill_id' => $waybillId ?: 'MENUNGGU-RESI',
            'courier_company' => $courierName,
            'destination' => $destination,
        ];

        if ($currentStatus === 8 || $currentStatus === 7) {
            $events->push((object) [
                'status' => 'Received by Courier',
                'title' => 'Paket diterima kurir',
                'location' => 'Gudang IMG',
                'description' => 'Paket telah diterima oleh ekspedisi.',
                'date' => $createdAt->copy()->addDays(2),
                'payload' => array_merge($basePayload, ['event' => 'order.status', 'status' => 'picking_up']),
            ]);
            $events->push((object) [
                'status' => 'Returned',
                'title' => 'Pesanan dikembalikan',
                'location' => 'Gudang Pengembalian',
                'description' => 'Pesanan sedang dalam proses pengembalian.',
                'date' => now(),
                'payload' => array_merge($basePayload, ['event' => 'order.status', 'status' => 'returned']),
            ]);
        } elseif ($currentStatus === 6 || $currentStatus === 5) {
            $events->push((object) [
                'status' => 'Received by Courier',
                'title' => 'Paket diterima kurir',
                'location' => 'Gudang IMG',
                'description' => 'Paket telah diterima oleh ekspedisi.',
                'date' => $createdAt->copy()->addDays(2),
                'payload' => array_merge($basePayload, ['event' => 'order.status', 'status' => 'picking_up']),
            ]);
            $events->push((object) [
                'status' => 'Delivered',
                'title' => 'Paket diterima pelanggan',
                'location' => $destination,
                'description' => 'Paket telah diterima oleh ' . $customerName . '.',
                'date' => $createdAt->copy()->addDays(4),
                'payload' => array_merge($basePayload, ['event' => 'order.status', 'status' => 'delivered', 'note' => 'Paket telah diterima oleh ' . $customerName]),
            ]);
        } else {
            $events->push((object) [
                'status' => 'Received by Courier',
                'title' => 'Paket diterima kurir',
                'location' => 'Gudang IMG',
                'description' => 'Paket telah diterima oleh ekspedisi.',
                'date' => $createdAt->copy()->addDays(2),
                'payload' => array_merge($basePayload, ['event' => 'order.status', 'status' => 'picking_up']),
            ]);
            $events->push((object) [
                'status' => 'In Transit',
                'title' => 'Paket transit',
                'location' => 'Jakarta Sortir',
                'description' => 'Paket sedang dalam proses sortir untuk rute berikutnya.',
                'date' => now(),
                'payload' => array_merge($basePayload, ['event' => 'order.status', 'status' => 'in_transit']),
            ]);
        }

        return [
            'waybill_id' => $waybillId,
            'courier' => $courierName,
            'events' => $events,
            'latest_payload' => $events->last()?->payload ?? null,
        ];
    }

    public function index(Request $request)
    {
        $queryOrderId = trim((string) $request->input('order_id', ''));
        $queryEmail = strtolower(trim((string) $request->input('email', '')));
        $selectedOrder = null;

        if ($queryOrderId && $queryEmail) {
            $selectedOrder = Order::query()
                ->where(function ($q) use ($queryOrderId) {
                    $q->whereRaw('LOWER(id::text) = ?', [strtolower($queryOrderId)])
                      ->orWhereRaw('LOWER(order_number) = ?', [strtolower($queryOrderId)]);
                })
                ->where(function ($q) use ($queryEmail) {
                    $q->whereHas('customer', fn($cq) => $cq->whereRaw('LOWER(email) = ?', [$queryEmail]))
                      ->orWhereRaw("LOWER(meta->'customer'->>'email') = ?", [$queryEmail])
                      ->orWhereRaw("LOWER(meta->'shipping_address'->>'email') = ?", [$queryEmail]);
                })
                ->with(['items.product', 'customer', 'courier'])
                ->first();
        } elseif (session()->get('is_logged_in')) {
            $user = session()->get('user', []);
            $userEmail = strtolower(trim((string) ($user['email'] ?? '')));
            $userId = $user['id'] ?? $user['sub'] ?? null;

            $customer = Customer::query()
                ->when($userId, fn($q) => $q->where('user_id', $userId))
                ->when($userEmail, fn($q) => $q->orWhereRaw('LOWER(email) = ?', [$userEmail]))
                ->first();

            if ($customer) {
                $selectedOrder = Order::query()
                    ->where('customer_id', $customer->id)
                    ->with(['items.product', 'customer', 'courier'])
                    ->latest()
                    ->first();
            }
        }

        if (!$selectedOrder && $request->boolean('dummy')) {
            $selectedOrder = $this->dummyOrder();
        }

        $currentStatus = $selectedOrder ? (int) ($selectedOrder->status ?? 0) : 0;
        if ($currentStatus < 1 || $currentStatus > 8) {
            $currentStatus = 0;
        }

        $shipment = $selectedOrder ? $this->buildShipment($selectedOrder, $currentStatus) : null;

        return view('frontend.order-tracking', [
            'order' => $selectedOrder,
            'shipment' => $shipment,
            'currentStatus' => $currentStatus,
            'orderId' => $queryOrderId,
            'email' => $queryEmail,
        ]);
    }
}
