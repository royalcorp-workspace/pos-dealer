<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\PaymentMethod;

class SyncEspayMethods extends Command
{
    protected $signature = 'espay:sync-methods';
    protected $description = 'Sync payment methods from Espay Merchant Info API';

    public function handle()
    {
        $this->info('Mengambil data merchantinfo dari Espay...');

        $espayUrl = rtrim(config('espay.base_url', 'https://sandbox-api.espay.id/rest/merchant'), '/') . '/merchantinfo';
        
        try {
            $response = Http::asForm()->post($espayUrl, [
                'key' => config('espay.api_key', '')
            ]);

            if ($response->successful() && $response->json('error_code') === '0000') {
                $espayData = $response->json('data') ?? [];
                
                $countNew = 0;
                $countUpdated = 0;

                foreach ($espayData as $espayMethod) {
                    $code = $espayMethod['bankCode']; // User wants bankCode here
                    $name = $espayMethod['productName'];
                    $productCode = $espayMethod['productCode']; // Keep productCode in bank_info
                    
                    // Tentukan tipe
                    $isTransfer = str_contains(strtoupper($productCode), 'ATM') || str_contains(strtoupper($productCode), 'VA') || str_contains(strtoupper($productCode), 'PERMATA');
                    $isCC = str_contains(strtoupper($productCode), 'CREDITCARD');
                    $type = 3; // E-Wallet by default
                    if ($isTransfer) $type = 2; // VA
                    if ($isCC) $type = 5; // Credit Card
                    
                    // Buat dummy instruksi berdasarkan tipe
                    $dummyInstructions = [];
                    if ($type === 2) { // VA
                        $dummyInstructions = [
                            ['title' => 'Transfer ATM', 'steps' => ['Masukkan kartu ATM dan PIN', 'Pilih Menu Transaksi Lainnya > Transfer > Ke Rekening Virtual Account', 'Masukkan nomor Virtual Account', 'Periksa detail dan konfirmasi pembayaran']],
                            ['title' => 'Mobile Banking', 'steps' => ['Buka aplikasi Mobile Banking dan Login', 'Pilih menu Transfer > Virtual Account', 'Masukkan nomor Virtual Account', 'Masukkan PIN untuk konfirmasi pembayaran']],
                            ['title' => 'Internet Banking', 'steps' => ['Login ke Internet Banking', 'Pilih menu Transfer > Virtual Account', 'Masukkan nomor Virtual Account', 'Masukkan token/PIN untuk konfirmasi']]
                        ];
                    } elseif ($type === 3) { // E-Wallet
                        $dummyInstructions = [
                            ['title' => 'Aplikasi E-Wallet', 'steps' => ['Buka notifikasi/aplikasi E-Wallet Anda', 'Periksa detail tagihan yang muncul', 'Klik Bayar', 'Masukkan PIN Anda']]
                        ];
                    } elseif ($type === 4) { // QRIS
                        $dummyInstructions = [
                            ['title' => 'Scan QR', 'steps' => ['Buka aplikasi E-Wallet atau Mobile Banking Anda', 'Pilih menu Scan QR/QRIS', 'Arahkan kamera ke kode QR yang tampil', 'Konfirmasi pembayaran dan masukkan PIN']]
                        ];
                    } elseif ($type === 5) { // CC
                        $dummyInstructions = [
                            ['title' => 'Kartu Kredit', 'steps' => ['Masukkan nomor Kartu Kredit', 'Masukkan masa berlaku (Valid Thru)', 'Masukkan 3 digit CVV di belakang kartu', 'Masukkan kode OTP yang dikirim via SMS']]
                        ];
                    }

                    $method = PaymentMethod::withoutGlobalScope('active')->where('code', $code)->first();
                    
                    if (!$method) {
                        PaymentMethod::create([
                            'code' => $code,
                            'name' => $name,
                            'type' => $type,
                            'provider' => 'espay',
                            'has_charge' => false,
                            'charge_type' => 2, // Fixed
                            'charge_value' => 0,
                            'status' => 1,
                            'bank_info' => ['product_code' => $productCode, 'bank_code' => $code],
                            'instructions' => $dummyInstructions
                        ]);
                        $countNew++;
                    } else {
                        // Jangan overwrite charge karena user/admin yang setting
                        $method->update([
                            'name' => $name,
                            'provider' => 'espay',
                            'bank_info' => ['product_code' => $productCode, 'bank_code' => $code],
                            'instructions' => $method->instructions ?? $dummyInstructions // Jangan timpa jika sudah ada custom
                        ]);
                        $countUpdated++;
                    }
                }
                
                $this->info("Sinkronisasi selesai. Baru: {$countNew}, Diupdate: {$countUpdated}");
            } else {
                $this->error('Gagal mengambil data dari Espay: ' . $response->body());
            }
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
        }
    }
}
