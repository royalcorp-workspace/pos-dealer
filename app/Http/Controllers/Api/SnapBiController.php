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
        Log::channel('espay')->info('SNAP BI Inquiry Received', $request->all());

        // virtualAccountNo dari Espay akan berisi order_number (contoh: ORDER0001)
        $virtualAccountNo = $request->input('virtualAccountNo');
        
        $order = Order::with('customer')->where('order_number', $virtualAccountNo)->first();

        if (!$order) {
            return response()->json([
                'responseCode' => '4042300',
                'responseMessage' => 'Order Not Found',
                'virtualAccountData' => new \stdClass()
            ], 404);
        }

        $amount = number_format((float)$order->total, 2, '.', '');

        return response()->json([
            'responseCode' => '2002400',
            'responseMessage' => 'Successful',
            'virtualAccountData' => [
                'partnerServiceId' => $request->input('partnerServiceId', ''),
                'customerNo' => $request->input('customerNo', ''),
                'virtualAccountNo' => $virtualAccountNo,
                'virtualAccountName' => $order->customer->name ?? 'Customer',
                'inquiryStatus' => '00',
                'inquiryReason' => [
                    'english' => 'Success',
                    'indonesia' => 'Sukses'
                ],
                'billDetails' => [
                    [
                        'billCode' => '01',
                        'billName' => 'Payment for Order #' . $order->order_number,
                        'billAmount' => [
                            'value' => $amount,
                            'currency' => 'IDR'
                        ]
                    ]
                ]
            ]
        ], 200);
    }

    public function payment(Request $request)
    {
        Log::channel('espay')->info('SNAP BI Payment Received', $request->all());

        $virtualAccountNo = $request->input('virtualAccountNo');
        
        $order = Order::where('order_number', $virtualAccountNo)->first();

        if (!$order) {
            return response()->json([
                'responseCode' => '4042700',
                'responseMessage' => 'Order Not Found',
                'virtualAccountData' => new \stdClass()
            ], 404);
        }

        if ($order->payment_status !== 2) {
            $order->payment_status = 2; // Paid
            $order->status = Order::STATUS_CONFIRMED;
            $order->save();
        }

        return response()->json([
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
        ], 200);
    }
}
