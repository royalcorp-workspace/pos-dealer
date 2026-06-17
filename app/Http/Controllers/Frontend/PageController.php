<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Frontend\ProductsCatalog\Brand;
use App\Models\Frontend\ProductsCatalog\ProductCategory;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function brands()
    {
        $brands = Brand::where('deleted', false)
            ->withCount('products')
            ->orderBy('sort_order')
            ->get();
        return view('frontend.brands', compact('brands'));
    }

    public function categories()
    {
        $categories = ProductCategory::where('deleted', false)
            ->whereNull('parent_id')
            ->with('children.children')
            ->orderBy('sort_order')
            ->get();
        return view('frontend.categories', compact('categories'));
    }

    public function promos()
    {
        $promos = [
            [
                'title' => 'Diskon Akhir Bulan',
                'desc' => 'Diskon hingga 50% untuk kasur pilihan.',
                'code' => 'PAYDAY50',
                'expiry' => '3 Hari Lagi',
                'type' => 'disc'
            ],
            [
                'title' => 'Gratis Ongkir Jabodetabek',
                'desc' => 'Tanpa minimum pembelian.',
                'code' => 'FREESONGKIR',
                'expiry' => 'Berlaku Selamanya',
                'type' => 'shipping'
            ],
            [
                'title' => 'Cashback 1 Juta',
                'desc' => 'Minimal transaksi Rp 10.000.000.',
                'code' => 'CASHBACK1M',
                'expiry' => 'Hari Ini',
                'type' => 'cashback'
            ]
        ];

        return view('frontend.promos', compact('promos'));
    }

    public function blog()
    {
        $blogs = [
            [
                'title' => 'Cara Memilih Kasur yang Tepat untuk Tulang Belakang',
                'category' => 'Tips & Trik',
                'date' => '19 Mei 2026'
            ],
            [
                'title' => 'Tanda-tanda Anda Harus Segera Mengganti Bantal',
                'category' => 'Kesehatan',
                'date' => '15 Mei 2026'
            ],
            [
                'title' => 'Mengenal Teknologi Pocket Spring Pada Kasur Premium',
                'category' => 'Informasi Produk',
                'date' => '10 Mei 2026'
            ],
            [
                'title' => 'Membersihkan Noda Membandel di Springbed Anda',
                'category' => 'Perawatan',
                'date' => '05 Mei 2026'
            ]
        ];

        return view('frontend.blog', compact('blogs'));
    }

    public function help()
    {
        $contacts = [
            ['label' => 'Telepon', 'value' => '1500-123', 'icon' => 'phone'],
            ['label' => 'Email', 'value' => 'support@img.co.id', 'icon' => 'mail'],
            ['label' => 'WhatsApp', 'value' => '+62 811-1234-5678', 'icon' => 'message-square'],
        ];

        $faqs = [
            [
                'question' => 'Bagaimana cara mengklaim garansi?',
                'answer' => 'Hubungi layanan pelanggan IMG dengan menyertakan bukti pembelian, foto produk, dan detail keluhan. Tim kami akan memandu proses klaim garansi sesuai ketentuan produk.',
            ],
            [
                'question' => 'Apakah bisa tukar tambah kasur lama?',
                'answer' => 'Program tukar tambah dapat berubah sesuai periode promo. Hubungi IMG melalui WhatsApp atau telepon untuk mengecek ketersediaan program di wilayah Anda.',
            ],
            [
                'question' => 'Apa saja metode pembayaran yang tersedia?',
                'answer' => 'IMG menyediakan pembayaran melalui transfer bank, e-wallet, dan metode pembayaran lainnya sesuai ketersediaan saat checkout.',
            ],
            [
                'question' => 'Berapa lama estimasi pengiriman untuk pesanan saya?',
                'answer' => 'Estimasi pengiriman tergantung lokasi dan ketersediaan produk. Untuk area Jabodetabek, pengiriman biasanya lebih cepat dan dapat dikonfirmasi saat pemesanan.',
            ],
        ];

        return view('frontend.help', compact('contacts', 'faqs'));
    }

    public function error400()
    {
        return response()->view('errors.400', [], 400);
    }

    public function error403()
    {
        return response()->view('errors.403', [], 403);
    }

    public function error404()
    {
        return response()->view('errors.404', [], 404);
    }

    public function error500()
    {
        return response()->view('errors.500', [], 500);
    }
}