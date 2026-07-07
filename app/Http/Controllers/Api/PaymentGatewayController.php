<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PaymentGatewayController extends Controller
{
    public function methods()
    {
        $methods = config('payment.methods', [
            'bca' => ['name' => 'BCA', 'type' => 'bank_transfer', 'icon' => 'bank'],
            'mandiri' => ['name' => 'Mandiri', 'type' => 'bank_transfer', 'icon' => 'bank'],
            'bni' => ['name' => 'BNI', 'type' => 'bank_transfer', 'icon' => 'bank'],
            'bri' => ['name' => 'BRI', 'type' => 'bank_transfer', 'icon' => 'bank'],
            'gopay' => ['name' => 'GoPay', 'type' => 'ewallet', 'icon' => 'wallet'],
            'ovo' => ['name' => 'OVO', 'type' => 'ewallet', 'icon' => 'wallet'],
            'dana' => ['name' => 'DANA', 'type' => 'ewallet', 'icon' => 'wallet'],
            'shopeepay' => ['name' => 'ShopeePay', 'type' => 'ewallet', 'icon' => 'wallet'],
        ]);

        return response()->json(['methods' => $methods]);
    }

    public function create(Request $request)
    {
        $request->validate([
            'order_id' => 'required|string',
            'payment_method' => 'required|string|in:bca,mandiri,bni,bri,gopay,ovo,dana,shopeepay',
            'amount' => 'required|numeric|min:1000',
            'customer' => 'required|array',
            'items' => 'required|array',
        ]);

        // Placeholder - integrate with actual payment gateway
        $payment = [
            'id' => Str::uuid(),
            'order_id' => $request->order_id,
            'payment_method' => $request->payment_method,
            'amount' => $request->amount,
            'status' => 'pending',
            'reference' => 'PAY-' . date('Ymd') . '-' . rand(1000, 9999),
            'created_at' => now()->toIso8601String(),
        ];

        return response()->json([
            'success' => true,
            'payment' => $payment,
            'redirect_url' => route('payment'),
        ]);
    }

    public function callback(Request $request)
    {
        // Handle webhook from payment gateway
        $payload = $request->validate([
            'reference' => 'required|string',
            'status' => 'required|string|in:pending,success,failed,expired',
            'signature' => 'required|string',
        ]);

        // Verify signature (implement based on gateway docs)
        if (!$this->verifySignature($request->all(), $payload['signature'])) {
            return response()->json(['error' => 'Invalid signature'], 403);
        }

        // Update payment status in database
        // TODO: implement actual payment update logic

        return response()->json(['success' => true]);
    }

    public function status(string $reference)
    {
        // Check payment status
        return response()->json([
            'reference' => $reference,
            'status' => 'pending', // placeholder
        ]);
    }

    private function verifySignature(array $data, string $signature): bool
    {
        // Implement signature verification based on gateway
        return true;
    }
}
