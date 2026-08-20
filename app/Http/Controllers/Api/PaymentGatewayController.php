<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Controller;
use App\Models\PaymentMethod;
use App\Models\Order\Order; // Assuming Order model exists here or shared
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentGatewayController extends Controller
{
    public function methods()
    {
        $methods = [];
        try {
            $espayUrl = rtrim(config('espay.base_url', 'https://sandbox-api.espay.id/rest/merchant'), '/') . '/merchantinfo';
            $response = \Illuminate\Support\Facades\Http::asForm()->post($espayUrl, [
                'key' => config('espay.api_key', '')
            ]);
            
            if ($response->successful() && $response->json('error_code') === '0000') {
                $espayData = $response->json('data') ?? [];
                foreach ($espayData as $espayMethod) {
                    $code = $espayMethod['productCode'];
                    $isTransfer = str_contains(strtoupper($code), 'ATM') || str_contains(strtoupper($code), 'VA') || str_contains(strtoupper($code), 'CREDITCARD') || str_contains(strtoupper($code), 'PERMATA');
                    
                    $methods[] = [
                        'code' => $code,
                        'name' => $espayMethod['productName'],
                        'type' => $isTransfer ? 'Virtual Account' : 'E-Wallet',
                        'icon' => null,
                    ];
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Gagal mengambil API merchantinfo dari Espay: ' . $e->getMessage());
        }

        // Add manual transfer from DB
        $manual = \App\Models\PaymentMethod::where('code', 'transfer_manual')->first();
        if ($manual) {
            $methods[] = [
                'code' => $manual->code,
                'name' => $manual->name,
                'type' => 'Bank Transfer Manual',
                'icon' => $manual->image,
            ];
        }

        return response()->json(['methods' => $methods]);
    }

    public function create(Request $request)
    {
        $request->validate([
            'order_id' => 'required|string',
            'payment_method' => 'required|string',
            'amount' => 'required|numeric|min:1000',
            'customer' => 'required|array',
            'items' => 'required|array',
        ]);

        $orderId = $request->order_id;
        $amount = number_format((float)$request->amount, 2, '.', '');
        $paymentMethodCode = $request->payment_method;

        // Tidak perlu cek DB karena kita memakai daftar dinamis dari Espay merchantinfo.
        $signatureKey = config('espay.signature_key');
        $commCode = config('espay.merchant_key');
        $rqUuid = Str::uuid()->toString();
        $rqDatetime = date('Y-m-d H:i:s');
        
        $dataToHash = "##{$signatureKey}##{$rqUuid}##{$rqDatetime}##{$orderId}##{$amount}##IDR##{$commCode}##SENDINVOICE##";
        $signature = hash('sha256', strtoupper($dataToHash));

        // Espay API URL
        $baseUrl = rtrim(config('espay.base_url'), '/');
        $espayUrl = $baseUrl . '/api/v1/create-order';

        // Lookup bankCode asli dari Espay berdasarkan productCode yang dipilih
        $espayBankCode = $paymentMethodCode;
        try {
            $infoUrl = rtrim(config('espay.base_url', 'https://sandbox-api.espay.id/rest/merchant'), '/') . '/merchantinfo';
            $infoResp = \Illuminate\Support\Facades\Http::asForm()->post($infoUrl, [
                'key' => config('espay.api_key', '')
            ]);
            if ($infoResp->successful() && $infoResp->json('error_code') === '0000') {
                $espayData = $infoResp->json('data') ?? [];
                $found = collect($espayData)->firstWhere('productCode', $paymentMethodCode);
                if ($found && !empty($found['bankCode'])) {
                    $espayBankCode = $found['bankCode'];
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Gagal lookup bankCode API: ' . $e->getMessage());
        }

        $payload = [
            'rq_uuid' => $rqUuid,
            'rq_datetime' => $rqDatetime,
            'order_id' => $orderId,
            'amount' => $amount,
            'ccy' => 'IDR',
            'comm_code' => $commCode,
            'remark1' => $request->customer['phone'] ?? '00000000000',
            'remark2' => $request->customer['name'] ?? 'Customer',
            'remark3' => $request->customer['email'] ?? '',
            'update' => 'N',
            'bankCode' => $espayBankCode,
            'va_expired' => 1440, // 24 Hours
            'signature' => $signature,
        ];

        try {
            $response = Http::post($espayUrl, $payload);

            if ($response->successful() && $response->json('error_code') === '0000') {
                $paymentData = $response->json();
                
                $payment = [
                    'id' => Str::uuid(),
                    'order_id' => $orderId,
                    'payment_method' => $method->code,
                    'amount' => $amount,
                    'status' => 'pending',
                    'reference' => $paymentData['reference'] ?? 'PAY-' . date('Ymd') . '-' . rand(1000, 9999),
                    'va_number' => $paymentData['va_number'] ?? null,
                    'payment_url' => $paymentData['payment_url'] ?? null,
                    'created_at' => now()->toIso8601String(),
                ];

                // Redirect user to Espay Checkout Page / Payment URL
                return response()->json([
                    'success' => true,
                    'payment' => $payment,
                    'redirect_url' => $paymentData['payment_url'] ?? route('payment'),
                ]);
            }

            Log::channel('espay')->error('Espay Create Order Failed', ['response' => $response->json(), 'payload' => $payload]);

        } catch (\Exception $e) {
            Log::channel('espay')->error('Espay Create Order Exception', ['message' => $e->getMessage()]);
        }
        
        return response()->json([
            'success' => false,
            'message' => 'Gagal menghubungkan ke payment gateway.',
        ], 500);
    }

    public function inquiry(Request $request)
    {
        $payload = $request->all();
        \Illuminate\Support\Facades\Log::channel('espay')->info('Espay Inquiry Received', $payload);

        $orderId = $request->input('order_id');
        
        // Cari order berdasarkan order_number karena Espay menerima order_number
        $order = \App\Models\Frontend\Order::with('customer')->where('order_number', $orderId)->first();
        
        if (!$order) {
            \Illuminate\Support\Facades\Log::channel('espay')->warning('Espay Inquiry Order Not Found', ['order_id' => $orderId]);
            return response()->json([
                'rq_uuid' => $request->input('rq_uuid'),
                'rs_datetime' => date('Y-m-d H:i:s'),
                'error_code' => '0001',
                'error_message' => 'Order not found',
            ]);
        }

        $amount = number_format((float)$order->total, 2, '.', '');
        $signatureKey = config('espay.signature_key');
        $signature = hash('sha256', "{$request->input('comm_code')}{$orderId}{$amount}IDR{$signatureKey}");

        $responsePayload = [
            'rq_uuid' => $request->input('rq_uuid'),
            'rs_datetime' => date('Y-m-d H:i:s'),
            'error_code' => '0000',
            'error_message' => 'Success',
            'signature' => $signature,
            'order_id' => $orderId,
            'amount' => $amount,
            'ccy' => 'IDR',
            'description' => 'Payment for Order #' . $order->order_number,
            'trx_date' => $order->created_at->format('Y-m-d H:i:s'),
            'customer_details' => [
                'firstName' => $order->customer->name ?? 'Customer',
                'lastName' => '',
                'phone' => $order->customer->phone ?? '',
                'email' => $order->customer->email ?? ''
            ]
        ];

        \Illuminate\Support\Facades\Log::channel('espay')->info('Espay Inquiry Response', $responsePayload);
        return response()->json($responsePayload);
    }

    public function callback(Request $request)
    {
        $payload = $request->all();
        \Illuminate\Support\Facades\Log::channel('espay')->info('Espay Callback Received', $payload);
        
        $orderId = $request->input('order_id');
        $amount = $request->input('amount');
        $receivedSignature = $request->input('signature');
        $status = $request->input('status', 'success'); // Usually empty or success/failed depending on docs

        if (!$this->verifySignature('callback', (string)$orderId, (string)$amount, (string)$receivedSignature)) {
            \Illuminate\Support\Facades\Log::channel('espay')->warning('Espay Callback Invalid Signature', $payload);
            
            return response()->json([
                'rs_datetime' => date('Y-m-d H:i:s'),
                'error_code' => '0001',
                'error_message' => 'Invalid signature',
                'order_id' => $orderId,
            ], 403);
        }

        // Update Order Status
        $order = \App\Models\Frontend\Order::where('order_number', $orderId)->first();
        if ($order) {
            // Check if not already paid
            if ($order->payment_status !== 2) {
                // payment_status 2 = Terbayar/Menunggu Verifikasi
                // order status 2 = Confirmed
                $order->update([
                    'payment_status' => 2,
                    'status' => \App\Models\Frontend\Order::STATUS_CONFIRMED,
                ]);
                \Illuminate\Support\Facades\Log::channel('espay')->info("Espay Payment success processed for Order ID: {$orderId}");
            } else {
                \Illuminate\Support\Facades\Log::channel('espay')->info("Espay Payment already processed for Order ID: {$orderId}");
            }
        } else {
            \Illuminate\Support\Facades\Log::channel('espay')->warning("Espay Callback Order not found: {$orderId}");
        }

        return response()->json([
            'rs_datetime' => date('Y-m-d H:i:s'),
            'error_code' => '0000',
            'error_message' => 'Success',
            'order_id' => $orderId,
            'reconcile_id' => $orderId,
            'reconcile_datetime' => date('Y-m-d H:i:s')
        ]);
    }

    public function status(string $reference)
    {
        return response()->json([
            'reference' => $reference,
            'status' => 'pending', 
        ]);
    }

    private function generateSignature(string $type, string $orderId, string $amount): string
    {
        $signatureKey = config('espay.signature_key');
        // Example formula: signatureKey + orderId
        return hash('sha256', "{$signatureKey}{$orderId}");
    }

    private function verifySignature(string $type, string $orderId, string $amount, string $receivedSignature): bool
    {
        $signatureKey = config('espay.signature_key');
        $expectedSignature = hash('sha256', "{$signatureKey}{$orderId}");

        return hash_equals($expectedSignature, $receivedSignature);
    }
}
