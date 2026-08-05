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

        return (object) [
            'id' => 'ORD-DUMMY-20260619-0001',
            'order_number' => 'ORD-DUMMY-20260619-0001',
            'status' => 5,
            'created_at' => now()->subDays(3),
            'customer' => $customer,
            'items' => $items,
            'total' => 3950000,
            'shipment' => [
                'events' => new Collection([
                    (object) ['status' => 'Received by Courier', 'title' => 'Paket diterima kurir', 'location' => 'DC Cakung', 'description' => 'Paket telah diterima oleh ekspedisi di DC Cakung.', 'date' => now()->subDay()],
                    (object) ['status' => 'In Transit', 'title' => 'Paket transit', 'location' => 'Jakarta Sortir', 'description' => 'Paket sedang dalam proses sortir untuk rute berikutnya.', 'date' => now()->subHours(8)],
                    (object) ['status' => 'In Transit', 'title' => 'Paket dalam perjalanan', 'location' => 'Bekasi, Jawa Barat', 'description' => 'Paket menuju kota tujuan.', 'date' => now()],
                ]),
            ],
        ];
    }

    private function buildShipment(object $order, int $currentStatus): ?array
    {
        if ($currentStatus < 5) {
            return null;
        }

        $createdAt = $order->created_at ?? now();
        $customer = $order->customer ?? null;
        $customerName = $customer->name ?? 'Pelanggan';
        $destination = $customer->city ?? $customer->address ?? 'Alamat penerima';
        $events = new Collection();

        if ($currentStatus === 8) {
            $events->push((object) ['status' => 'Received by Courier', 'title' => 'Paket diterima kurir', 'location' => 'Gudang IMG', 'description' => 'Paket telah diterima oleh ekspedisi.', 'date' => $createdAt->copy()->addDays(2)]);
            $events->push((object) ['status' => 'Returned', 'title' => 'Pesanan dikembalikan', 'location' => 'Gudang Pengembalian', 'description' => 'Pesanan sedang dalam proses pengembalian.', 'date' => now()]);
        } elseif ($currentStatus === 6) {
            $events->push((object) ['status' => 'Received by Courier', 'title' => 'Paket diterima kurir', 'location' => 'Gudang IMG', 'description' => 'Paket telah diterima oleh ekspedisi.', 'date' => $createdAt->copy()->addDays(2)]);
            $events->push((object) ['status' => 'Delivered', 'title' => 'Paket diterima pelanggan', 'location' => $destination, 'description' => 'Paket telah diterima oleh ' . $customerName . '.', 'date' => $createdAt->copy()->addDays(4)]);
        } else {
            $events->push((object) ['status' => 'Received by Courier', 'title' => 'Paket diterima kurir', 'location' => 'Gudang IMG', 'description' => 'Paket telah diterima oleh ekspedisi.', 'date' => $createdAt->copy()->addDays(2)]);
            $events->push((object) ['status' => 'In Transit', 'title' => 'Paket transit', 'location' => 'Jakarta Sortir', 'description' => 'Paket sedang dalam proses sortir untuk rute berikutnya.', 'date' => now()]);
        }

        return [
            'events' => $events,
        ];
    }

    public function index(Request $request)
    {
        $queryOrderId = strtoupper((string) $request->input('order_id', ''));
        $queryEmail = strtolower((string) $request->input('email', ''));
        $selectedOrder = null;

        if ($queryOrderId && $queryEmail) {
            $selectedOrder = Order::query()
                ->where(function ($q) use ($queryOrderId) {
                    $q->where('id', $queryOrderId)
                      ->orWhere('order_number', $queryOrderId);
                })
                ->whereHas('customer', fn($q) => $q->whereRaw('LOWER(email) = ?', [$queryEmail]))
                ->with(['items.product', 'customer', 'courier'])
                ->first();
        } elseif (session()->get('is_logged_in')) {
            $user = session()->get('user', []);
            $customer = Customer::where('email', $user['email'] ?? '')->first();

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
