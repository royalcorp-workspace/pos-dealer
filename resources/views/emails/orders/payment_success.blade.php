<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: sans-serif; line-height: 1.5; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
        .header { background: #000; color: #d4af37; padding: 15px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { padding: 20px; }
        .footer { text-align: center; padding: 15px; font-size: 12px; color: #777; }
        .btn { display: inline-block; padding: 10px 20px; background: #000; color: #d4af37; text-decoration: none; border-radius: 5px; font-weight: bold; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Pembayaran Berhasil</h2>
        </div>
        <div class="content">
            <p>Halo,</p>
            <p>Kabar baik! Kami telah menerima pembayaran untuk pesanan Anda.</p>
            <ul>
                <li><strong>Nomor Pesanan:</strong> {{ $order->order_number }}</li>
                <li><strong>Total Pembayaran:</strong> Rp {{ number_format($order->total, 0, ',', '.') }}</li>
            </ul>
            <p>Saat ini pesanan Anda sedang kami siapkan untuk pengiriman.</p>
            <div style="text-align: center;">
                <a href="{{ url('/track-order?order_id=' . $order->order_number) }}" class="btn">Lacak Pesanan</a>
            </div>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} POS Dealer. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
