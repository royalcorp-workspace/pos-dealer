<?php

namespace App\Services;

class ProductService
{
    private array $brands = [
        "Lady Americana",
        "Elite Springbed",
        "Royal Foam",
        "Tote Bed",
        "Moro Baby",
        "Serenity"
    ];

    private array $categories = [
        "Kasur Spring",
        "Kasur Busa",
        "Bed Linen",
        "Accessories",
        "Sofabed",
        "Perlengkapan Bayi"
    ];

    private array $babySubcategories = [
        "Bolster",
        "Pillow",
        "Blanket",
        "Pyjamas"
    ];

    private array $mockReviews = [
        [
            'id' => 'r1',
            'user' => 'Budi S.',
            'rating' => 5,
            'text' => 'Kasurnya sangat nyaman, benar-benar mengurangi sakit punggung saya.',
            'date' => '12 Mei 2026'
        ],
        [
            'id' => 'r2',
            'user' => 'Siti R.',
            'rating' => 4,
            'text' => 'Pengiriman cepat, kualitas barang sesuai dengan harga. Cukup memuaskan.',
            'date' => '08 Mei 2026'
        ],
        [
            'id' => 'r3',
            'user' => 'Andi T.',
            'rating' => 5,
            'text' => 'Tidur jadi nyenyak banget, recommend buat yang cari springbed premium.',
            'date' => '01 Mei 2026'
        ]
    ];

    private array $products = [];

    public function __construct()
    {
        $this->products = [
            [
                'id' => 'p1',
                'name' => 'Elite Royal Sovereign Springbed',
                'brand' => 'Elite Springbed',
                'category' => 'Kasur Spring',
                'image' => 'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&q=80&w=400&h=300',
                'sell_price' => 3500000,
                'originalPrice' => 5000000,
                'discountBadge' => '-30% Hot',
                'isVariable' => false,
                'rating' => 4.8,
                'reviewsCount' => 124,
                'isSoldOut' => false,
                'reviews' => $this->mockReviews,
                'description' => 'Kasur Elite Royal Sovereign menawarkan kenyamanan luar biasa dengan teknologi spring terkini yang menopang tubuh dengan sempurna untuk tidur nyenyak.'
            ],
            [
                'id' => 'p2',
                'name' => 'Royal Foam Grand Exclusive (Set)',
                'brand' => 'Royal Foam',
                'category' => 'Kasur Busa',
                'image' => 'https://images.unsplash.com/photo-1584622650111-993a426fbf0a?auto=format&fit=crop&q=80&w=400&h=300',
                'sell_price' => 1223000,
                'minPrice' => 1223000,
                'maxPrice' => 2279000,
                'isVariable' => true,
                'rating' => 5.0,
                'reviewsCount' => 89,
                'isSoldOut' => false,
                'reviews' => $this->mockReviews,
                'description' => 'Royal Foam Grand Exclusive dirancang khusus dengan busa density tinggi untuk memberikan kenyamanan dan ketahanan maksimal sepanjang waktu.'
            ],
            [
                'id' => 'p3',
                'name' => 'Lady Americana Legacy Springbed',
                'brand' => 'Lady Americana',
                'category' => 'Kasur Spring',
                'image' => 'https://images.unsplash.com/photo-1522771731478-44fb1ab892ee?auto=format&fit=crop&q=80&w=400&h=300',
                'sell_price' => 5200000,
                'originalPrice' => 8666000,
                'discountBadge' => '-40%',
                'isVariable' => false,
                'rating' => 4.9,
                'reviewsCount' => 56,
                'isSoldOut' => true,
                'reviews' => $this->mockReviews,
                'description' => 'Lady Americana Legacy memberikan sentuhan kemewahan sejati dengan pegas independen dan lapisan lateks alami demi tidur yang berkualitas tinggi.'
            ],
            [
                'id' => 'p4',
                'name' => 'Tote Bed Scandinavian Style Sofabed',
                'brand' => 'Tote Bed',
                'category' => 'Sofabed',
                'image' => 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&q=80&w=400&h=300',
                'sell_price' => 2100000,
                'isVariable' => false,
                'rating' => 4.5,
                'reviewsCount' => 32,
                'isSoldOut' => false,
                'reviews' => $this->mockReviews,
                'description' => 'Tote Bed Scandinavian Sofabed adalah perpaduan sempurna antara fungsionalitas dan estetika minimalis modern untuk ruang tamu atau kamar tidur Anda.'
            ],
            [
                'id' => 'p5',
                'name' => 'Moro Baby Complete Sleep Set',
                'brand' => 'Moro Baby',
                'category' => 'Perlengkapan Bayi',
                'image' => 'https://images.unsplash.com/photo-1519689680058-324335c77eba?auto=format&fit=crop&q=80&w=400&h=300',
                'sell_price' => 450000,
                'minPrice' => 150000,
                'maxPrice' => 450000,
                'isVariable' => true,
                'discountBadge' => '-15%',
                'rating' => 4.7,
                'reviewsCount' => 210,
                'isSoldOut' => false,
                'reviews' => $this->mockReviews,
                'description' => 'Perlengkapan tidur bayi lengkap dari Moro Baby dibuat dengan bahan katun organik super lembut yang aman bagi kulit bayi yang sensitif.'
            ],
            [
                'id' => 'p6',
                'name' => 'Serenity Premium Mattress Protector',
                'brand' => 'Serenity',
                'category' => 'Accessories',
                'image' => 'https://images.unsplash.com/photo-1540518614846-7eded433c457?auto=format&fit=crop&q=80&w=400&h=300',
                'sell_price' => 300000,
                'originalPrice' => 350000,
                'isVariable' => false,
                'rating' => 4.6,
                'reviewsCount' => 45,
                'isSoldOut' => false,
                'reviews' => $this->mockReviews,
                'description' => 'Pelindung kasur premium Serenity melindungi kasur Anda dari cairan, tungau, dan bakteri demi menjaga kebersihan serta keawetan kasur kesayangan.'
            ]
        ];
    }

    public function all(): array
    {
        return $this->products;
    }

    public function getBrands(): array
    {
        return $this->brands;
    }

    public function getCategories(): array
    {
        return $this->categories;
    }

    public function getBabySubcategories(): array
    {
        return $this->babySubcategories;
    }

    public function getBestsellers(): array
    {
        return array_slice($this->products, 0, 4);
    }

    public function getFeatured(): array
    {
        return $this->products[1];
    }

    public function find(string $id): ?array
    {
        foreach ($this->products as $product) {
            if ($product['id'] === $id) {
                return $product;
            }
        }
        return null;
    }

    public function filter(?string $type, ?string $value): array
    {
        if (empty($type) || empty($value)) {
            return $this->products;
        }

        return array_filter($this->products, function ($product) use ($type, $value) {
            if ($type === 'brand') {
                return strtolower($product['brand']) === strtolower($value);
            }
            if ($type === 'category') {
                return strtolower($product['category']) === strtolower($value);
            }
            if ($type === 'search') {
                $query = strtolower($value);
                return str_contains(strtolower($product['name']), $query) ||
                       str_contains(strtolower($product['brand']), $query) ||
                       str_contains(strtolower($product['category']), $query) ||
                       str_contains(strtolower($product['description']), $query);
            }
            return true;
        });
    }
}
