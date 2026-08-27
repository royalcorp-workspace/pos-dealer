<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Frontend\Order;

class SnapBiController extends Controller
{
    public function inquiry(Request $request)
    {
        $logMessage = "SNAP BI Inquiry Received\n";
        $logMessage .= "Payload: \n" . json_encode($request->all(), JSON_PRETTY_PRINT);
        Log::channel('espay')->info($logMessage);

        // virtualAccountNo dari Espay akan berisi order_number (contoh: ORDER0001)
        $virtualAccountNo = $request->input('virtualAccountNo');
        
        // Karena tanda strip (-) dihilangkan saat sendinvoice, kita cocokkan order_number tanpa strip
        $order = Order::with('customer')
            ->whereRaw("REPLACE(order_number, '-', '') = ?", [$virtualAccountNo])
            ->first();

        if (!$order) {
            $errResponse = [
                'responseCode' => '4042300',
                'responseMessage' => 'Order Not Found',
                'virtualAccountData' => new \stdClass()
            ];
            $logMessage .= "\nResponse (Error): \n" . json_encode($errResponse, JSON_PRETTY_PRINT);
            Log::channel('espay')->error($logMessage);
            return response()->json($errResponse, 404);
        }

        $amount = number_format((float)$order->total, 2, '.', '');

        $responseData = [
            'responseCode' => '2002400',
            'responseMessage' => 'Success',
            'virtualAccountData' => [
                'partnerServiceId' => $request->input('partnerServiceId', ''),
                'customerNo' => $request->input('customerNo', ''),
                'virtualAccountNo' => $virtualAccountNo,
                'virtualAccountName' => $order->customer->name ?? 'Customer',
                'virtualAccountEmail' => $order->customer->email ?? 'no-email@domain.com',
                'virtualAccountPhone' => $order->customer->phone ?? '0000000000',
                'inquiryRequestId' => $request->input('inquiryRequestId', \Illuminate\Support\Str::uuid()->toString()),
                'totalAmount' => [
                    'value' => $amount,
                    'currency' => 'IDR'
                ],
                'billDetails' => [
                    [
                        'billDescription' => [
                            'english' => 'Invoice No ' . $order->order_number,
                            'indonesia' => 'Tagihan No ' . $order->order_number
                        ]
                    ]
                ],
                'additionalInfo' => [
                    'shippingAddress' => [
                        'firstName' => $order->customer->name ?? 'Customer',
                        'lastName' => '',
                        'address' => $order->customer->address ?? 'Alamat',
                        'city' => '-',
                        'postalCode' => '-',
                        'phoneNumber' => $order->customer->phone ?? '0000000',
                        'countryCode' => 'IDN'
                    ]
                ]
            ]
        ];

        $logMessage .= "\nResponse: \n" . json_encode($responseData, JSON_PRETTY_PRINT);
        Log::channel('espay')->info($logMessage);

        return response()->json($responseData, 200);
    }

    public function payment(Request $request)
    {
        $logMessage = "SNAP BI Payment Received\n";
        $logMessage .= "Payload: \n" . json_encode($request->all(), JSON_PRETTY_PRINT);

        $virtualAccountNo = $request->input('virtualAccountNo');
        
        // Karena tanda strip (-) dihilangkan saat sendinvoice, kita cocokkan order_number tanpa strip
        $order = Order::whereRaw("REPLACE(order_number, '-', '') = ?", [$virtualAccountNo])->first();

        if (!$order) {
            $errResponse = [
                'responseCode' => '4042700',
                'responseMessage' => 'Order Not Found',
                'virtualAccountData' => new \stdClass()
            ];
            $logMessage .= "\nResponse (Error): \n" . json_encode($errResponse, JSON_PRETTY_PRINT);
            Log::channel('espay')->error($logMessage);
            return response()->json($errResponse, 404);
        }

        if ($order->payment_status !== 2) {
            $order->payment_status = 2; // Paid
            $order->status = Order::STATUS_PROCESSING;
            $order->save();

            // Update status Settlement jika ada
            if ($order->settlement_id) {
                $settlement = \App\Models\Settlement::find($order->settlement_id);
                if ($settlement) {
                    $settlement->update([
                        'status' => 'success',
                        'settlement_date' => now(),
                    ]);
                }
            }

            // Catat ke tabel payments
            \App\Models\CreditMemo::create([
                'id' => \Illuminate\Support\Str::uuid()->toString(),
                'credit_memo_number' => 'CM-' . $order->order_number . '-' . rand(100, 999),
                'order_id' => $order->id,
                'gateway' => 'espay',
                'transaction_id' => $request->input('inquiryRequestId') ?? \Illuminate\Support\Str::uuid()->toString(),
                'amount' => $order->total,
                'status' => 'success',
                'payload' => $request->all(),
                'paid_at' => now(),
            ]);

            try {
                $customerEmail = $order->customer->email ?? ($order->meta['customer']['email'] ?? null);
                if ($customerEmail) {
                    \Illuminate\Support\Facades\Mail::to($customerEmail)->send(new \App\Mail\PaymentSuccess($order));
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Gagal mengirim email PaymentSuccess (Snap): ' . $e->getMessage());
            }
        }

        $responseData = [
            'responseCode' => '2002700',
            'responseMessage' => 'Successful',
            'virtualAccountData' => [
                'partnerServiceId' => $request->input('partnerServiceId', ''),
                'customerNo' => $request->input('customerNo', ''),
                'virtualAccountNo' => $virtualAccountNo,
                'virtualAccountName' => $order->customer->name ?? 'Customer',
                'paymentFlagReason' => [
                    'english' => 'Success',
                    'indonesia' => 'Sukses'
                ]
            ]
        ];

        $logMessage .= "\nResponse: \n" . json_encode($responseData, JSON_PRETTY_PRINT);
        Log::channel('espay')->info($logMessage);

        return response()->json($responseData, 200);
    }
}
