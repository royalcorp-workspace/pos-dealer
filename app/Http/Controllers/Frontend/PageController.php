<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Frontend\AboutUs;
use App\Models\Frontend\BlogPost;
use App\Models\Frontend\Faq;
use App\Models\Frontend\HowToReturn;
use App\Models\Frontend\PrivacyPolicy;
use App\Models\Frontend\ProductsCatalog\Brand;
use App\Models\Frontend\ProductsCatalog\Product;
use App\Models\Frontend\ProductsCatalog\ProductCategory;
use App\Models\Frontend\Promo\Voucher;
use App\Models\Frontend\TermsAndCondition;
use App\Models\Frontend\WarrantyClaim;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function brands()
    {
        $brandsWithProducts = Brand::where('deleted', false)
            ->withCount('products')
            ->orderBy('sort_order')
            ->get();
        return view('frontend.brands', compact('brandsWithProducts'));
    }

    public function categories()
    {
        $categories = ProductCategory::where('deleted', false)
            ->whereNull('parent_id')
            ->with(['children.children', 'products' => fn($q) => $q->where('deleted', false)])
            ->withCount('products')
            ->orderBy('sort_order')
            ->get();
        return view('frontend.categories', compact('categories'));
    }

    public function promos()
    {
        $promos = Voucher::active()
            ->orderByDesc('start_date')
            ->orderByDesc('end_date')
            ->get();

        return view('frontend.promos', compact('promos'));
    }

    public function blog()
    {
        $blogs = BlogPost::where('is_published', true)
            ->orderBy('published_at', 'desc')
            ->orderBy('sort_order')
            ->get();

        return view('frontend.blog', compact('blogs'));
    }

    public function blogShow(BlogPost $blogPost)
    {
        // blog_posts table does not have view_count column
        // $blogPost->increment('view_count');

        $structuredData = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $blogPost->title,
            'description' => $blogPost->excerpt,
            'datePublished' => $blogPost->published_at?->format('Y-m-d') ?? $blogPost->created_at->format('Y-m-d'),
            'dateModified' => $blogPost->updated_at->format('Y-m-d'),
            'author' => [
                '@type' => 'Organization',
                'name' => $blogPost->author_name ?? 'IMG International Mattress Gallery',
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => 'IMG International Mattress Gallery',
                'url' => route('home'),
            ],
        ];

        return view('frontend.blog-detail', compact('blogPost', 'structuredData'));
    }

public function help()
    {
        $about = AboutUs::first();

        $contacts = [
            ['label' => 'Telepon', 'value' => $about->phone ?? '', 'icon' => 'phone'],
            ['label' => 'Email', 'value' => $about->email ?? '', 'icon' => 'mail'],
            ['label' => 'WhatsApp', 'value' => $about->social_media['whatsapp'] ?? '', 'icon' => 'message-square'],
        ];

        $faqs = Faq::where('is_published', true)
            ->orderBy('sort_order')
            ->get();

        return view('frontend.help', compact('about', 'contacts', 'faqs'));
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
        $about = AboutUs::first();

        return view('frontend.about', compact('about'));
    }

    public function warranty()
    {
        $warranty = WarrantyClaim::first();

        return view('frontend.warranty', compact('warranty'));
    }

    public function terms()
    {
        $terms = TermsAndCondition::first();

        return view('frontend.terms', compact('terms'));
    }

    public function privacy()
    {
        $privacy = PrivacyPolicy::first();

        return view('frontend.privacy', compact('privacy'));
    }

    public function returns()
    {
        $returns = HowToReturn::first();

        return view('frontend.returns', compact('returns'));
    }
}
