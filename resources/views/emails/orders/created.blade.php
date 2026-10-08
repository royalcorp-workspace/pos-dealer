<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Berhasil Dibuat - {{ $order->order_number }}</title>
    <style>
        body { margin: 0; padding: 0; background-color: #f8fafc; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased; color: #2b1d12; }
        .wrapper { width: 100%; table-layout: fixed; background-color: #f8fafc; padding: 30px 0; }
        .main-card { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #e5e7eb; box-shadow: 0 4px 12px rgba(0,0,0,0.04); }
        .header { background: #2b1d12; padding: 28px 24px; text-align: center; }
        .content { padding: 32px 28px; }
        .badge { display: inline-block; padding: 6px 14px; background: #fdfbf7; border: 1px solid #c09d6b; color: #ad8a58; border-radius: 9999px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
        .title { font-size: 22px; font-weight: 800; color: #2b1d12; margin: 16px 0 8px; line-height: 1.3; }
        .detail-card { background: #fafafa; border: 1px solid #e4e4e7; border-radius: 8px; padding: 18px 20px; margin: 20px 0; }
        .detail-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px dashed #e4e4e7; font-size: 14px; }
        .detail-row:last-child { border-bottom: none; padding-bottom: 0; }
        .btn { display: inline-block; padding: 14px 28px; background: #2b1d12; color: #c09d6b !important; text-decoration: none; border-radius: 8px; font-weight: 700; font-size: 14px; letter-spacing: 0.5px; margin-top: 15px; }
        .footer { background: #fdfbf7; padding: 24px 20px 30px; text-align: center; border-top: 1px solid #f2ebd9; }
        .brand-pill { font-size: 10px; font-weight: 700; color: #2b1d12; background: #ffffff; border: 1px solid #e5e7eb; border-radius: 4px; padding: 3px 8px; display: inline-block; margin: 2px; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="main-card">
            <!-- Header Brand -->
            <div class="header">
                <table cellpadding="0" cellspacing="0" border="0" style="margin: 0 auto;">
                    <tr>
                        <td style="vertical-align: middle; padding-right: 12px; border-right: 1px solid rgba(192, 157, 107, 0.4);">
                            <span style="font-family: Georgia, serif; font-size: 30px; font-weight: 900; color: #c09d6b; line-height: 1;">IMG</span>
                        </td>
                        <td style="vertical-align: middle; padding-left: 12px; text-align: left;">
                            <span style="font-size: 10px; font-weight: 700; color: #fdfbf7; letter-spacing: 2px; text-transform: uppercase; line-height: 1.3; display: block;">
                                INTERNATIONAL<br><span style="color: #c09d6b;">MATTRESS GALLERY</span>
                            </span>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Content Area -->
            <div class="content">
                <div style="text-align: center; margin-bottom: 20px;">
                    <span class="badge">📦 Pesanan Diterima</span>
                    <h1 class="title">Terima Kasih Atas Pesanan Anda</h1>
                    <p style="font-size: 14px; color: #71717a; margin: 0;">Kami sedang menantikan pembayaran Anda untuk segera memproses pengiriman produk.</p>
                </div>

                @php
                    $meta = $order->meta ?? [];
                    $appliedVouchers = $meta['applied_vouchers'] ?? [];

                    $shippingSubsidy = (float)($order->shipping_cost_subsidy ?? ($meta['shipping_voucher_discount'] ?? 0));
                    $productVoucherDisc = (float)($meta['product_voucher_discount'] ?? 0);
                    $voucherNominal = (float)($order->voucher_nominal ?? 0);
                    $orderDiscount = (float)($order->discount ?? 0);

                    if ($productVoucherDisc <= 0) {
                        if ($voucherNominal > 0 && $shippingSubsidy > 0) {
                            $productVoucherDisc = max(0, $voucherNominal - $shippingSubsidy);
                        } elseif ($voucherNominal > 0) {
                            $productVoucherDisc = $voucherNominal;
                        } elseif ($orderDiscount > 0) {
                            $productVoucherDisc = $orderDiscount;
                        }
                    }

                    $productVoucherCode = null;
                    $shippingVoucherCode = null;

                    if (!empty($appliedVouchers) && is_array($appliedVouchers)) {
                        foreach ($appliedVouchers as $av) {
                            $vData = $av['voucher'] ?? $av;
                            $vType = strtolower($vData['type'] ?? $vData['discount_type'] ?? '');
                            $vCode = strtoupper($vData['code'] ?? '');
                            if (str_contains($vType, 'shipping') || str_contains($vType, 'ongkir') || str_contains($vCode, 'ONGKIR')) {
                                $shippingVoucherCode = $vCode;
                            } else {
                                $productVoucherCode = $vCode;
                            }
                        }
                    }

                    $rawOrderVoucherCode = $order->voucher?->code ?? ($meta['voucher_code'] ?? null);
                    if (!empty($rawOrderVoucherCode)) {
                        $rawUpper = strtoupper($rawOrderVoucherCode);
                        if (str_contains($rawUpper, 'ONGKIR') || ($order->voucher && str_contains(strtolower($order->voucher->type ?? ''), 'shipping'))) {
                            if (!$shippingVoucherCode) $shippingVoucherCode = $rawUpper;
                        } else {
                            if (!$productVoucherCode) $productVoucherCode = $rawUpper;
                        }
                    }

                    $hasProductVoucher = ($productVoucherDisc > 0) || !empty($productVoucherCode);
                    $hasShippingVoucher = ($shippingSubsidy > 0) || !empty($shippingVoucherCode);

                    $courierName = $order->courier?->name ?? ($meta['courier']['name'] ?? ($meta['courier'] ?? null));
                    $courierService = $meta['shipping_service_name'] ?? ($meta['shipping_service_code'] ?? null);
                    $shippingAddress = $meta['shipping_address'] ?? null;

                    $totalOriginalPrice = 0;
                    if ($order->items && $order->items->isNotEmpty()) {
                        foreach ($order->items as $i) {
                            $basePrice = (float)($i->meta['original_price'] ?? $i->meta['base_price'] ?? $i->sell_price ?? $i->unit_price ?? 0);
                            if ($basePrice <= 0) {
                                $basePrice = (float)($i->total / max(1, (int)$i->quantity));
                            }
                            $totalOriginalPrice += ($basePrice * (int)$i->quantity);
                        }
                    }
                    if ($totalOriginalPrice < (float)$order->subtotal) {
                        $totalOriginalPrice = (float)$order->subtotal;
                    }

                    $totalProductDiscount = max(0, $totalOriginalPrice - (float)$order->subtotal);
                    $totalHemat = $totalProductDiscount + $productVoucherDisc + $shippingSubsidy;
                    $finalShippingCost = max(0, (float)$order->shipping_cost - $shippingSubsidy);
                @endphp

                <!-- Order Summary Box -->
                <div class="detail-card">
                    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="font-size: 13px;">
                        <tr>
                            <td style="padding: 6px 0; color: #71717a;">Nomor Pesanan:</td>
                            <td style="padding: 6px 0; text-align: right; font-weight: 700; color: #2b1d12;">#{{ $order->order_number }}</td>
                        </tr>
                        @if(!empty($order->created_at))
                        <tr>
                            <td style="padding: 6px 0; color: #71717a; border-top: 1px dashed #e4e4e7;">Waktu Pemesanan:</td>
                            <td style="padding: 6px 0; text-align: right; color: #2b1d12; border-top: 1px dashed #e4e4e7;">
                                {{ \Carbon\Carbon::parse($order->created_at)->translatedFormat('d F Y, H:i') }} WIB
                            </td>
                        </tr>
                        @endif
                        @if(!empty($order->payment_method))
                        <tr>
                            <td style="padding: 6px 0; color: #71717a; border-top: 1px dashed #e4e4e7;">Metode Pembayaran:</td>
                            <td style="padding: 6px 0; text-align: right; font-weight: 600; color: #2b1d12; border-top: 1px dashed #e4e4e7;">
                                {{ $order->payment_method_name ?? strtoupper($order->payment_method) }}
                            </td>
                        </tr>
                        @endif

                        <!-- Rincian Produk & Harga Asli -->
                        @if($order->items && $order->items->isNotEmpty())
                        <tr>
                            <td colspan="2" style="padding: 14px 0 6px; font-weight: 700; color: #2b1d12; border-top: 1px solid #e4e4e7;">
                                📦 Rincian Produk:
                            </td>
                        </tr>
                        @foreach($order->items as $item)
                        @php
                            $itemBase = (float)($item->meta['original_price'] ?? $item->meta['base_price'] ?? $item->sell_price ?? $item->unit_price ?? 0);
                            $itemUnitFinal = (float)($item->total / max(1, (int)$item->quantity));
                            if ($itemBase <= 0) {
                                $itemBase = $itemUnitFinal;
                            }
                            $hasItemDisc = $itemBase > $itemUnitFinal;
                        @endphp
                        <tr>
                            <td style="padding: 6px 0; color: #52525b; line-height: 1.4;">
                                <div style="font-weight: 600; color: #2b1d12;">{{ $item->name }}</div>
                                <div style="font-size: 11px; margin-top: 2px;">
                                    <span style="color: #71717a;">Qty: {{ $item->quantity }} &times;</span>
                                    @if($hasItemDisc)
                                        <span style="text-decoration: line-through; color: #a1a1aa; margin-left: 2px;">Rp {{ number_format($itemBase, 0, ',', '.') }}</span>
                                        <span style="color: #2b1d12; font-weight: 700; margin-left: 2px;">Rp {{ number_format($itemUnitFinal, 0, ',', '.') }}</span>
                                        @if((float)$item->discount_percent > 0)
                                            <span style="background: #fee2e2; color: #dc2626; font-size: 10px; font-weight: 700; padding: 1px 4px; border-radius: 3px; margin-left: 3px;">-{{ (int)$item->discount_percent }}%</span>
                                        @endif
                                    @else
                                        <span style="color: #2b1d12; font-weight: 600; margin-left: 2px;">Rp {{ number_format($itemUnitFinal, 0, ',', '.') }}</span>
                                    @endif
                                </div>
                            </td>
                            <td style="padding: 6px 0; text-align: right; font-weight: 600; color: #2b1d12; vertical-align: top;">
                                Rp {{ number_format($item->total, 0, ',', '.') }}
                            </td>
                        </tr>
                        @endforeach
                        @endif

                        <!-- Rincian Biaya, Diskon, & Voucher -->
                        <tr>
                            <td colspan="2" style="padding: 14px 0 6px; font-weight: 700; color: #2b1d12; border-top: 1px solid #e4e4e7;">
                                💰 Rincian Pembayaran &amp; Promo:
                            </td>
                        </tr>
                        <!-- 1. Total Harga Asli Produk -->
                        <tr>
                            <td style="padding: 6px 0; color: #52525b;">
                                <div><strong>Harga Asli Produk</strong></div>
                                <div style="font-size: 11px; color: #a1a1aa;">Total harga katalog sebelum diskon &amp; promo</div>
                            </td>
                            <td style="padding: 6px 0; text-align: right; font-weight: 600; color: #2b1d12; vertical-align: top;">
                                Rp {{ number_format($totalOriginalPrice, 0, ',', '.') }}
                            </td>
                        </tr>

                        <!-- 2. Potongan Diskon Produk Langsung -->
                        @if($totalProductDiscount > 0)
                        <tr>
                            <td style="padding: 6px 0; color: #dc2626; border-top: 1px dashed #f4f4f5;">
                                <div><strong>Diskon Promo Produk</strong></div>
                                <div style="font-size: 11px; color: #ef4444;">Potongan langsung promo katalog produk</div>
                            </td>
                            <td style="padding: 6px 0; text-align: right; font-weight: 700; color: #dc2626; border-top: 1px dashed #f4f4f5; vertical-align: top;">
                                - Rp {{ number_format($totalProductDiscount, 0, ',', '.') }}
                            </td>
                        </tr>
                        @endif

                        <!-- 3. Subtotal Belanja -->
                        <tr>
                            <td style="padding: 6px 0; color: #52525b; border-top: 1px dashed #f4f4f5;">
                                <strong>Subtotal Produk:</strong>
                            </td>
                            <td style="padding: 6px 0; text-align: right; font-weight: 600; color: #2b1d12; border-top: 1px dashed #f4f4f5;">
                                Rp {{ number_format($order->subtotal, 0, ',', '.') }}
                            </td>
                        </tr>

                        <!-- 4. Voucher Belanja (Memakai Voucher Apa & Berapa Diskonnya) -->
                        <tr>
                            <td style="padding: 6px 0; border-top: 1px dashed #f4f4f5;">
                                <div><strong>🏷️ Voucher Belanja:</strong></div>
                                @if($hasProductVoucher)
                                    <div style="margin-top: 2px;">
                                        <span style="font-weight: 700; color: #15803d; background: #dcfce7; padding: 2px 7px; border-radius: 4px; font-size: 11px; letter-spacing: 0.5px;">{{ $productVoucherCode ?: 'VOUCHER DIGUNAKAN' }}</span>
                                        <span style="font-size: 11px; color: #16a34a; margin-left: 3px;">(Diskon berhasil diterapkan)</span>
                                    </div>
                                @else
                                    <div style="font-size: 11px; color: #a1a1aa; font-style: italic;">Tidak menggunakan voucher belanja</div>
                                @endif
                            </td>
                            <td style="padding: 6px 0; text-align: right; border-top: 1px dashed #f4f4f5; vertical-align: top;">
                                @if($hasProductVoucher)
                                    <span style="font-weight: 700; color: #15803d;">- Rp {{ number_format($productVoucherDisc, 0, ',', '.') }}</span>
                                @else
                                    <span style="color: #a1a1aa; font-style: italic;">Rp 0</span>
                                @endif
                            </td>
                        </tr>

                        <!-- 5. Ongkos Kirim Asli -->
                        <tr>
                            <td style="padding: 6px 0; color: #52525b; border-top: 1px dashed #f4f4f5;">
                                <div><strong>Ongkos Kirim:</strong></div>
                                <div style="font-size: 11px; color: #71717a;">Kurir: {{ $courierName ? "{$courierName}" . ($courierService ? " ({$courierService})" : "") : 'Kurir Standar' }}</div>
                            </td>
                            <td style="padding: 6px 0; text-align: right; font-weight: 600; color: #2b1d12; border-top: 1px dashed #f4f4f5; vertical-align: top;">
                                Rp {{ number_format($order->shipping_cost, 0, ',', '.') }}
                            </td>
                        </tr>

                        <!-- 6. Voucher Ongkir (Memakai Voucher Ongkir atau Tidak) -->
                        <tr>
                            <td style="padding: 6px 0; border-top: 1px dashed #f4f4f5;">
                                <div><strong>🚚 Voucher Ongkir:</strong></div>
                                @if($hasShippingVoucher)
                                    <div style="margin-top: 2px;">
                                        <span style="font-weight: 700; color: #15803d; background: #dcfce7; padding: 2px 7px; border-radius: 4px; font-size: 11px; letter-spacing: 0.5px;">{{ $shippingVoucherCode ? $shippingVoucherCode : 'VOUCHER ONGKIR DIGUNAKAN' }}</span>
                                        <span style="font-size: 11px; color: #16a34a; margin-left: 3px;">(Subsidi ongkir diterapkan)</span>
                                    </div>
                                @else
                                    <div style="font-size: 11px; color: #a1a1aa; font-style: italic;">Tidak menggunakan voucher ongkir</div>
                                @endif
                            </td>
                            <td style="padding: 6px 0; text-align: right; border-top: 1px dashed #f4f4f5; vertical-align: top;">
                                @if($hasShippingVoucher)
                                    <span style="font-weight: 700; color: #15803d;">- Rp {{ number_format($shippingSubsidy, 0, ',', '.') }}</span>
                                @else
                                    <span style="color: #a1a1aa; font-style: italic;">Rp 0</span>
                                @endif
                            </td>
                        </tr>

                        <!-- 7. Biaya Layanan / Transaksi jika ada -->
                        @if((float)$order->transaction_fee > 0)
                        <tr>
                            <td style="padding: 6px 0; color: #52525b; border-top: 1px dashed #f4f4f5;">Biaya Layanan / Transaksi:</td>
                            <td style="padding: 6px 0; text-align: right; font-weight: 600; color: #2b1d12; border-top: 1px dashed #f4f4f5;">
                                Rp {{ number_format($order->transaction_fee, 0, ',', '.') }}
                            </td>
                        </tr>
                        @endif

                        <!-- 8. Grand Total -->
                        <tr>
                            <td style="padding: 12px 0 4px; color: #2b1d12; font-weight: 800; font-size: 15px; border-top: 2px solid #2b1d12;">
                                Total Tagihan:
                            </td>
                            <td style="padding: 12px 0 4px; text-align: right; font-weight: 900; color: #ad8a58; font-size: 18px; border-top: 2px solid #2b1d12;">
                                Rp {{ number_format($order->total, 0, ',', '.') }}
                            </td>
                        </tr>

                        <!-- 9. Total Penghematan jika ada -->
                        @if($totalHemat > 0)
                        <tr>
                            <td colspan="2" style="padding: 10px 0 0;">
                                <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 10px 14px; text-align: center;">
                                    <div style="font-size: 13px; font-weight: 800; color: #15803d;">
                                        🎉 Total Penghematan Anda: Rp {{ number_format($totalHemat, 0, ',', '.') }}
                                    </div>
                                    <div style="font-size: 11px; color: #16a34a; margin-top: 3px;">
                                        (Diskon Produk: Rp {{ number_format($totalProductDiscount, 0, ',', '.') }}
                                        @if($hasProductVoucher) + Voucher Belanja: Rp {{ number_format($productVoucherDisc, 0, ',', '.') }} @endif
                                        @if($hasShippingVoucher) + Subsidi Ongkir: Rp {{ number_format($shippingSubsidy, 0, ',', '.') }} @endif)
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @endif
                    </table>
                </div>

                <!-- Informasi Alamat Penerima -->
                @if(!empty($shippingAddress['recipient_name']) || !empty($shippingAddress['address']))
                <div style="background: #fafafa; border: 1px solid #e4e4e7; border-radius: 8px; padding: 14px 18px; margin: 15px 0; font-size: 12px; color: #52525b; line-height: 1.5;">
                    <div style="font-weight: 700; color: #2b1d12; margin-bottom: 4px; font-size: 13px;">📍 Alamat Penerima:</div>
                    <div style="font-weight: 600; color: #2b1d12;">{{ $shippingAddress['recipient_name'] ?? $order->customer?->name }} ({{ $shippingAddress['phone'] ?? $order->customer?->phone ?? '-' }})</div>
                    <div>{{ $shippingAddress['address'] ?? '' }}</div>
                    <div>{{ implode(', ', array_filter([$shippingAddress['sub_district'] ?? null, $shippingAddress['city'] ?? null, $shippingAddress['province'] ?? null, $shippingAddress['postal_code'] ?? null])) }}</div>
                </div>
                @endif

                <div style="background-color: #fdfbf7; border-left: 4px solid #c09d6b; border-radius: 4px; padding: 12px 16px; margin: 20px 0; font-size: 13px; color: #71717a;">
                    <strong style="color: #2b1d12;">Langkah Selanjutnya:</strong> Selesaikan pembayaran Anda sebelum batas waktu yang ditentukan agar ketersediaan kasur impian Anda tetap terjamin.
                </div>

                <div style="text-align: center; margin: 25px 0 10px;">
                    <a href="{{ url('/track-order?order_id=' . $order->order_number) }}" class="btn">
                        🔍 Lacak Status Pesanan
                    </a>
                </div>
            </div>

            <!-- Footer & Official Brand Partners -->
            <div class="footer">
                @php
                    $officialBrandLogos = \App\Models\Frontend\ProductsCatalog\Brand::where('status', 1)
                        ->where('deleted', false)
                        ->whereNotNull('logo')
                        ->where('logo', '!=', '')
                        ->orderBy('sort_order', 'asc')
                        ->get();
                @endphp
                @if($officialBrandLogos->isNotEmpty())
                <div style="margin-bottom: 20px; padding: 14px 10px; background-color: #ffffff; border: 1px solid #f2ebd9; border-radius: 8px; text-align: center;">
                    <p style="font-size: 11px; font-weight: 700; color: #ad8a58; letter-spacing: 1.5px; text-transform: uppercase; margin: 0 0 10px;">
                        ★ Official Brand Partners ★
                    </p>
                    <table cellpadding="0" cellspacing="0" border="0" style="margin: 0 auto;">
                        <tr>
                            @foreach($officialBrandLogos as $ob)
                            <td style="padding: 4px 8px; vertical-align: middle; text-align: center;">
                                <img src="{{ cms_asset($ob->logo) }}" alt="{{ $ob->name }}" height="26" style="max-height: 26px; max-width: 65px; height: auto; width: auto; object-fit: contain; display: inline-block;" />
                            </td>
                            @endforeach
                        </tr>
                    </table>
                </div>
                @endif

                <p style="font-size: 12px; line-height: 1.6; color: #71717a; margin: 0 0 8px;">
                    <strong>IMG (International Mattress Gallery)</strong><br>
                    Kualitas tidur terbaik untuk kenyamanan istirahat Anda dan keluarga.
                </p>
                <p style="font-size: 11px; color: #a1a1aa; margin: 0;">
                    &copy; {{ date('Y') }} IMG Mattress Gallery. Seluruh hak cipta dilindungi.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
