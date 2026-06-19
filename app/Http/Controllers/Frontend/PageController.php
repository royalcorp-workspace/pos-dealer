<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Frontend\ProductsCatalog\Brand;
use App\Models\Frontend\ProductsCatalog\Product;
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

    public function verifyEmail()
    {
        return view('frontend.verify-email');
    }

    public function resendVerification()
    {
        return response()->json(['message' => 'Verification email resent']);
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

    public function sitemap()
    {
        $urls = [
            [
                'loc' => route('home'),
                'lastmod' => now()->toIso8601String(),
                'changefreq' => 'daily',
                'priority' => '1.0',
            ],
            [
                'loc' => route('about'),
                'lastmod' => now()->toIso8601String(),
                'changefreq' => 'monthly',
                'priority' => '0.6',
            ],
            [
                'loc' => route('categories'),
                'lastmod' => now()->toIso8601String(),
                'changefreq' => 'weekly',
                'priority' => '0.9',
            ],
            [
                'loc' => route('brands'),
                'lastmod' => now()->toIso8601String(),
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ],
            [
                'loc' => route('terms'),
                'lastmod' => now()->toIso8601String(),
                'changefreq' => 'monthly',
                'priority' => '0.5',
            ],
            [
                'loc' => route('privacy'),
                'lastmod' => now()->toIso8601String(),
                'changefreq' => 'monthly',
                'priority' => '0.5',
            ],
            [
                'loc' => route('returns'),
                'lastmod' => now()->toIso8601String(),
                'changefreq' => 'monthly',
                'priority' => '0.5',
            ],
            [
                'loc' => route('warranty'),
                'lastmod' => now()->toIso8601String(),
                'changefreq' => 'monthly',
                'priority' => '0.5',
            ],
            [
                'loc' => route('promos'),
                'lastmod' => now()->toIso8601String(),
                'changefreq' => 'daily',
                'priority' => '0.8',
            ],
            [
                'loc' => route('blog'),
                'lastmod' => now()->toIso8601String(),
                'changefreq' => 'weekly',
                'priority' => '0.7',
            ],
            [
                'loc' => route('help'),
                'lastmod' => now()->toIso8601String(),
                'changefreq' => 'monthly',
                'priority' => '0.6',
            ],
        ];

        ProductCategory::where('deleted', false)
            ->whereNotNull('slug')
            ->orderBy('sort_order')
            ->pluck('slug')
            ->each(function ($slug) use (&$urls) {
                $urls[] = [
                    'loc' => route('category.show', $slug),
                    'lastmod' => now()->toIso8601String(),
                    'changefreq' => 'weekly',
                    'priority' => '0.8',
                ];
            });

        Brand::where('deleted', false)
            ->whereNotNull('slug')
            ->orderBy('sort_order')
            ->pluck('slug')
            ->each(function ($slug) use (&$urls) {
                $urls[] = [
                    'loc' => route('brands.show', $slug),
                    'lastmod' => now()->toIso8601String(),
                    'changefreq' => 'weekly',
                    'priority' => '0.8',
                ];
            });

        Product::where('deleted', false)
            ->whereNotNull('slug')
            ->orderBy('sort_order')
            ->pluck('slug')
            ->each(function ($slug) use (&$urls) {
                $urls[] = [
                    'loc' => route('products.show', $slug),
                    'lastmod' => now()->toIso8601String(),
                    'changefreq' => 'weekly',
                    'priority' => '0.9',
                ];
            });

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

        foreach ($urls as $url) {
            $xml .= '  <url>' . PHP_EOL;
            $xml .= '    <loc>' . e($url['loc']) . '</loc>' . PHP_EOL;
            $xml .= '    <lastmod>' . e($url['lastmod']) . '</lastmod>' . PHP_EOL;
            $xml .= '    <changefreq>' . e($url['changefreq']) . '</changefreq>' . PHP_EOL;
            $xml .= '    <priority>' . e($url['priority']) . '</priority>' . PHP_EOL;
            $xml .= '  </url>' . PHP_EOL;
        }

        $xml .= '</urlset>' . PHP_EOL;

        return response($xml, 200)->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function robots()
    {
        $robots = "User-agent: *" . PHP_EOL;
        $robots .= "Disallow: /checkout" . PHP_EOL;
        $robots .= "Disallow: /payment" . PHP_EOL;
        $robots .= "Disallow: /dashboard" . PHP_EOL;
        $robots .= "Disallow: /forgot-password" . PHP_EOL;
        $robots .= "Disallow: /reset-password" . PHP_EOL;
        $robots .= "Disallow: /password-otp-sent" . PHP_EOL;
        $robots .= "Disallow: /email/verify" . PHP_EOL;
        $robots .= "Disallow: /auth/" . PHP_EOL;
        $robots .= "Allow: /" . PHP_EOL . PHP_EOL;
        $robots .= "Sitemap: " . url('/sitemap.xml') . PHP_EOL;

        return response($robots, 200)->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    public function about()
    {
        return view('frontend.about');
    }

    public function warranty()
    {
        return view('frontend.warranty');
    }

    public function terms()
    {
        return view('frontend.terms');
    }

    public function privacy()
    {
        return view('frontend.privacy');
    }

    public function returns()
    {
        return view('frontend.returns');
    }
}