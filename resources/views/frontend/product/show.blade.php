@extends('frontend.layouts.app')

@section('title', $product->name . ' - IMG')

@section('meta_description', \Illuminate\Support\Str::limit($product->short_description ?: $product->description ?: $product->name, 160))
@section('canonical', route('products.show', $product->slug))
@section('og_image', $product->thumbnail_url ?: 'https://images.unsplash.com/photo-1540518614846-7eded433c457?auto=format&fit=crop&q=80&w=1200&h=800')
@section('og_type', 'product')

@section('content')
    @php
        $variantsData = $product->variants;
        $hasVariants = $variantsData->isNotEmpty();
        $basePrice = (float)($product->base_price ?? 0);
        $minPrice = $variantsData->min('price');
        $maxPrice = $variantsData->max('price');
        $hasMultiplePrices = $hasVariants && $minPrice != $maxPrice;
        $firstVariantName = $variantsData->first()->variant_name ?? '';
        $totalStock = $variantsData->sum('stock_qty');
        $price = $hasVariants ? $variantsData->first()->price : $basePrice;
        $availability = $totalStock > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock';
        $images = collect([$product->thumbnail_url])
            ->merge($product->images->map(fn($image) => $image->image_url ?? ($image->image ? asset('storage/' . $image->image) : null)))
            ->filter()
            ->values()
            ->take(8)
            ->toArray();
        $brandName = $product->brand->name ?? 'IMG';
        $categoryName = $product->category->name ?? 'Kategori';
        $categoryUrl = $product->category?->slug ? route('category.show', $product->category->slug) : route('categories');
        $brandUrl = $product->brand?->slug ? route('products.index', ['type' => 'brand', 'value' => $product->brand->slug]) : route('brands');
        $productUrl = route('products.show', $product->slug);
        $offer = [
            '@type' => 'Offer',
            '@id' => $productUrl . '#offers',
            'url' => $productUrl,
            'priceCurrency' => 'IDR',
            'price' => $price,
            'availability' => $availability,
            'itemCondition' => 'https://schema.org/NewCondition',
            'seller' => [
                '@type' => 'Organization',
                'name' => 'IMG International Mattress Gallery',
                'url' => route('home'),
            ],
        ];
        $productSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            '@id' => $productUrl,
            'name' => $product->name,
            'image' => $images,
            'description' => $product->short_description ?: $product->description ?: $product->name,
            'brand' => [
                '@type' => 'Brand',
                'name' => $brandName,
            ],
            'category' => $categoryName,
            'sku' => $product->id,
            'offers' => $offer,
        ];
        if ($hasVariants) {
            $productSchema['hasVariant'] = $variantsData->map(fn($variant) => [
                '@type' => 'Product',
                'name' => $product->name . ' - ' . $variant->variant_name,
                'sku' => (string) $variant->id,
                'offers' => [
                    '@type' => 'Offer',
                    'priceCurrency' => 'IDR',
                    'price' => $variant->price,
                    'availability' => ($variant->stock_qty ?? 0) > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                    'itemCondition' => 'https://schema.org/NewCondition',
                ],
            ])->values()->toArray();
        }
        $breadcrumbSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => 'Home',
                    'item' => route('home'),
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => $categoryName,
                    'item' => $categoryUrl,
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 3,
                    'name' => $brandName,
                    'item' => $brandUrl,
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 4,
                    'name' => $product->name,
                ],
            ],
        ];

        $productFaqSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => [
                [
                    '@type' => 'Question',
                    'name' => "Apakah kasur " . $product->name . " cocok untuk penderita sakit punggung?",
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => "Ya. Dengan dukungan sistem pegas berkualitas tinggi dan lapisan penopang premium, " . $product->name . " dirancang untuk menjaga kesejajaran tulang belakang secara alami, sehingga sangat membantu mengurangi dan mencegah sakit punggung."
                    ]
                ],
                [
                    '@type' => 'Question',
                    'name' => "Berapa tahun garansi yang diberikan untuk " . $product->name . "?",
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => $product->name . " dilengkapi dengan garansi resmi pegas hingga 15 tahun dari produsen untuk menjamin daya tahan dan kenyamanan jangka panjang Anda."
                    ]
                ],
                [
                    '@type' => 'Question',
                    'name' => "Apakah ada layanan pengiriman gratis untuk pembelian kasur ini?",
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => "Kami menyediakan layanan gratis ongkos kirim (Free Ongkir) untuk area Jabodetabek serta pemasangan langsung di kamar tidur Anda oleh tim profesional kami."
                    ]
                ]
            ]
        ];
    @endphp
    <div class="container mx-auto px-4 md:px-6 py-8">
        <!-- Breadcrumbs -->
        <nav class="flex items-center gap-2 text-sm text-gray-500 mb-8 font-sans">
            <a href="{{ route('home') }}" class="hover:text-brand-dark transition-colors">Home</a>
            <i class="fa-solid fa-chevron-right w-4 h-4 text-gray-400"></i>
            <a href="{{ $product->category?->slug ? route('category.show', $product->category->slug) : route('categories') }}" class="hover:text-brand-dark transition-colors">
                {{ $product->category->name ?? 'Uncategorized' }}
            </a>
            <i class="fa-solid fa-chevron-right w-4 h-4 text-gray-400"></i>
            <a href="{{ $product->brand?->slug ? route('products.index', ['type' => 'brand', 'value' => $product->brand->slug]) : route('brands') }}" class="hover:text-brand-dark transition-colors">
                {{ $product->brand->name ?? 'Unknown Brand' }}
            </a>
            <i class="fa-solid fa-chevron-right w-4 h-4 text-gray-400"></i>
            <span class="text-brand-dark font-medium truncate">{{ $product->name }}</span>
        </nav>

        <div class="flex flex-col lg:flex-row gap-12">
            <!-- Left: Product Images -->
            <div class="w-full lg:w-1/2 space-y-4">
                <div class="aspect-[4/3] bg-brand-light rounded-3xl overflow-hidden border border-brand-muted relative">
                    <img 
                        src="{{ $product->thumbnail_url ?? 'https://via.placeholder.com/600x400' }}" 
                        alt="{{ $product->alt_text ?? $product->name }}" 
                        class="w-full h-full object-cover"
                    />
                    @if($product->best_seller)
                        <span class="absolute top-4 left-4 bg-brand-dark text-brand-gold text-xs font-bold px-3 py-1.5 rounded-sm uppercase tracking-wider">
                            Best Seller
                        </span>
                    @endif
                </div>
                
                <!-- Image Gallery -->
                @if($product->images->isNotEmpty())
                    <div class="grid grid-cols-4 gap-4">
                        @foreach($product->images as $image)
                            <div class="aspect-square bg-brand-light rounded-xl overflow-hidden border-2 cursor-pointer border-brand-gold">
                                <img src="{{ $image->image_url ?? ($image->image ? asset('storage/' . $image->image) : 'https://via.placeholder.com/150') }}" alt="{{ $image->alt_text }}" class="w-full h-full object-cover opacity-80 hover:opacity-100 transition-opacity" />
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Right: Product Info -->
            <div class="w-full lg:w-1/2 flex flex-col font-sans">
                <div class="mb-2 text-sm font-bold text-brand-gold-dark uppercase tracking-widest">
                    {{ $product->brand->name ?? 'Unknown Brand' }}
                </div>
                
                <h1 class="text-3xl md:text-4xl font-extrabold text-brand-dark mb-4 leading-tight font-serif">
                    {{ $product->name }}
                </h1>

                @if($product->tags->isNotEmpty())
                    <div class="flex flex-wrap gap-2 mb-4">
                        @foreach($product->tags as $tag)
                            <a href="{{ url('products/' . $tag->slug) }}"
                                class="text-xs px-3 py-1 bg-brand-light text-brand-gold-dark rounded-full hover:bg-brand-gold hover:text-white transition-colors border-2 border-brand-gold/20 hover:border-brand-gold"
                            >
                                {{ $tag->name }}
                            </a>
                        @endforeach
                    </div>
                @endif

                <!-- Rating -->
                <div class="flex items-center gap-1.5 mb-6">
                    <div class="flex items-center text-brand-gold">
                        <i class="fa-solid fa-star w-5 h-5 fill-current"></i>
                    </div>
                    <span class="font-bold text-brand-dark">0.0</span>
                    <span class="text-sm text-gray-400 hover:text-brand-gold-dark underline-offset-2 hover:underline">(0 Ulasan)</span>
                </div>

                <!-- Price Card -->
                <div class="mb-8 p-6 bg-brand-muted/30 rounded-2xl border border-brand-light">
                    @php
                        $minPrice = $variantsData->min('price');
                        $maxPrice = $variantsData->max('price');
                        $hasMultiplePrices = $hasVariants && $minPrice != $maxPrice;
                        $firstVariantName = $variantsData->first()->variant_name ?? '';
                    @endphp
                    <span class="text-3xl font-extrabold text-brand-dark tracking-tight" id="product-price">Rp {{ $hasVariants ? number_format($variantsData->first()->price, 0, ',', '.') : number_format($basePrice, 0, ',', '.') }}</span>
                    <span class="block text-sm text-brand-gold-dark mt-2" id="price-label">Harga untuk ukuran: {{ $firstVariantName }}</span>
                </div>

                <!-- AI Quick Summary (Generative Engine Optimization) -->
                <div class="mb-8 p-5 bg-brand-light/50 border border-brand-gold/30 rounded-2xl">
                    <div class="flex items-center gap-2 text-brand-gold-dark mb-3">
                        <i class="fa-solid fa-wand-magic-sparkles"></i>
                        <h4 class="font-bold text-xs uppercase tracking-wider">{{ config('seo.geo_optimize.ai_summary_title') }}</h4>
                    </div>
                    <ul class="space-y-2 text-sm text-gray-700">
                        <li class="flex justify-between border-b border-dashed border-gray-200 pb-1.5">
                            <span class="text-gray-500 font-medium">Seri Produk</span>
                            <strong class="text-brand-dark">{{ $product->name }}</strong>
                        </li>
                        <li class="flex justify-between border-b border-dashed border-gray-200 pb-1.5">
                            <span class="text-gray-500 font-medium">Merek Resmi</span>
                            <strong class="text-brand-dark">{{ $brandName }}</strong>
                        </li>
                        <li class="flex justify-between border-b border-dashed border-gray-200 pb-1.5">
                            <span class="text-gray-500 font-medium">Garansi Pegas</span>
                            <strong class="text-brand-dark">Resmi 15 Tahun</strong>
                        </li>
                        <li class="flex justify-between border-b border-dashed border-gray-200 pb-1.5">
                            <span class="text-gray-500 font-medium">Sistem Pegas</span>
                            <strong class="text-brand-dark">Pocket Spring Dan Orthopedic Support</strong>
                        </li>
                        <li class="flex justify-between">
                            <span class="text-gray-500 font-medium">Sumber Data</span>
                            <span class="text-xs text-gray-400 italic">{{ config('seo.geo_optimize.citation_source') }}</span>
                        </li>
                    </ul>
                </div>

                <!-- Product Purchase Form -->
                <form action="{{ route('cart.add') }}" method="POST">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">

                    <!-- Options (Variants) -->
                    @if($hasVariants)
                        <div class="mb-8">
                            <h3 class="font-bold text-brand-dark mb-4">Pilih Ukuran</h3>
                            <div class="grid grid-cols-2 lg:grid-cols-3 gap-3">
                                @foreach($variantsData as $i => $variant)
                                    <button 
                                        type="button"
                                        data-variant-id="{{ $variant->id }}"
                                        data-variant-price="{{ $variant->price }}"
                                        onclick="selectVariant(this)"
                                        class="py-3 px-4 rounded-xl font-semibold text-sm transition-all text-center focus:outline-none border-2 {{ $i === 0 ? 'border-brand-gold bg-brand-light text-brand-dark' : 'border-brand-muted bg-white text-gray-600' }}"
                                    >
                                        {{ $variant->variant_name }}
                                        @if($variant->stock_qty <= 0)
                                            <div class="text-xs text-gray-400 mt-1">Habis</div>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        </div>
                        <input type="hidden" name="variant_id" id="variant-id-input" value="{{ $variantsData->first()->id }}">
                    @endif

                    @php
                        $totalStock = $product->variants->sum('stock_qty');
                    @endphp

                    <!-- Action Buttons -->
                    <div class="flex flex-col sm:flex-row gap-4 mb-8 pt-6 border-t border-brand-muted">
                        @if($totalStock > 0)
                            <div class="flex gap-2 w-full">
                                <div class="w-1/5">
                                    <div class="flex items-center gap-1">
                                        <button type="button" onclick="updateQty(-1)" class="w-full h-12 rounded-lg border border-brand-muted bg-white hover:bg-brand-light flex items-center justify-center text-lg font-bold text-brand-dark">-</button>
                                        <input type="number" name="quantity" id="quantity-input" value="1" min="1" max="{{ $totalStock }}" class="w-full h-12 border border-brand-muted rounded-lg text-center font-bold text-brand-dark">
                                        <button type="button" onclick="updateQty(1)" class="w-full h-12 rounded-lg border border-brand-muted bg-white hover:bg-brand-light flex items-center justify-center text-lg font-bold text-brand-dark">+</button>
                                    </div>
                                </div>
                                <div class="w-4/5 flex gap-2">
                                    <button 
                                        type="submit"
                                        class="flex-1 h-12 rounded-xl font-bold flex items-center justify-center gap-2 bg-brand-dark text-brand-gold hover:bg-brand-darker shadow-lg shadow-brand-dark/20 transition-transform active:scale-[0.98] focus:outline-none"
                                    >
                                        <i class="fa-solid fa-cart-shopping w-4 h-4"></i>
                                        Tambah ke Keranjang
                                    </button>
                                    <button 
                                        type="button"
                                        class="h-12 w-12 flex items-center justify-center border-2 border-brand-muted rounded-xl text-gray-400 hover:border-brand-gold hover:text-brand-gold transition-colors focus:outline-none"
                                        data-product-id="{{ $product->id }}"
                                        onclick="toggleWishlist(this)"
                                        title="Favorit"
                                    >
                                        <i class="fa-regular fa-heart w-5 h-5"></i>
                                    </button>
                                </div>
                            </div>
                        @else
                            <button 
                                disabled
                                class="flex-1 h-14 rounded-xl font-bold flex items-center justify-center gap-2 bg-gray-100 text-gray-400 cursor-not-allowed"
                            >
                                Sold Out
                            </button>
                        @endif
                    </div>
                </form>

                <!-- Features list -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8">
                    <div class="flex items-center gap-3 p-4 bg-brand-light rounded-xl border border-brand-muted/50">
                        <i class="fa-solid fa-shield-halved w-6 h-6 text-brand-gold flex-shrink-0"></i>
                        <span class="text-sm font-semibold text-brand-dark">Garansi Resmi 15 Tahun</span>
                    </div>
                    <div class="flex items-center gap-3 p-4 bg-brand-light rounded-xl border border-brand-muted/50">
                        <i class="fa-solid fa-truck w-6 h-6 text-brand-gold flex-shrink-0"></i>
                        <span class="text-sm font-semibold text-brand-dark">Gratis Ongkir Jabodetabek</span>
                    </div>
                    <div class="flex items-center gap-3 p-4 bg-brand-light rounded-xl border border-brand-muted/50">
                        <i class="fa-solid fa-rotate-left w-6 h-6 text-brand-gold flex-shrink-0"></i>
                        <span class="text-sm font-semibold text-brand-dark">100 Malam Uji Coba Tidur</span>
                    </div>
                    <div class="flex items-center gap-3 p-4 bg-brand-light rounded-xl border border-brand-muted/50">
                        <i class="fa-solid fa-comment-dots w-6 h-6 text-brand-gold flex-shrink-0"></i>
                        <span class="text-sm font-semibold text-brand-dark">Konsultasi Gratis</span>
                    </div>
                </div>

                <!-- Description -->
                <div class="border-t border-brand-muted pt-8">
                    <h3 class="text-xl font-bold text-brand-dark mb-4">Deskripsi Produk</h3>
                    <div class="prose prose-sm text-gray-600 max-w-none leading-relaxed">
                        <p class="mb-4">
                            {{ $product->description }}
                        </p>
                    </div>
                </div>

                <!-- Tanya Jawab Seputar {{ $product->name }} (GEO) -->
                <div class="border-t border-brand-muted pt-8 mt-8">
                    <h3 class="text-xl font-bold text-brand-dark mb-6 font-serif">Tanya Jawab Seputar {{ $product->name }}</h3>
                    <div class="space-y-4">
                        <div class="bg-white border border-gray-100 p-5 rounded-2xl shadow-sm hover:shadow-md transition-shadow">
                            <h4 class="font-bold text-brand-dark mb-2 text-sm">Apakah kasur {{ $product->name }} cocok untuk penderita sakit punggung?</h4>
                            <p class="text-xs text-gray-600 leading-relaxed">Ya. Dengan dukungan sistem pegas berkualitas tinggi dan lapisan penopang premium, {{ $product->name }} dirancang untuk menjaga kesejajaran tulang belakang secara alami, sehingga sangat membantu mengurangi dan mencegah sakit punggung.</p>
                        </div>
                        <div class="bg-white border border-gray-100 p-5 rounded-2xl shadow-sm hover:shadow-md transition-shadow">
                            <h4 class="font-bold text-brand-dark mb-2 text-sm">Berapa tahun garansi yang diberikan untuk {{ $product->name }}?</h4>
                            <p class="text-xs text-gray-600 leading-relaxed">{{ $product->name }} dilengkapi dengan garansi resmi pegas hingga 15 tahun dari produsen untuk menjamin daya tahan dan kenyamanan jangka panjang Anda.</p>
                        </div>
                        <div class="bg-white border border-gray-100 p-5 rounded-2xl shadow-sm hover:shadow-md transition-shadow">
                            <h4 class="font-bold text-brand-dark mb-2 text-sm">Apakah ada layanan pengiriman gratis untuk pembelian kasur ini?</h4>
                            <p class="text-xs text-gray-600 leading-relaxed">Kami menyediakan layanan gratis ongkos kirim (Free Ongkir) untuk area Jabodetabek serta pemasangan langsung di kamar tidur Anda oleh tim profesional kami.</p>
                        </div>
                    </div>
                </div>

                <div class="border-t border-brand-muted pt-8 mt-8">
                    <h3 class="text-xl font-bold text-brand-dark mb-4">Informasi Penting</h3>
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div class="bg-brand-light rounded-xl p-4">
                            <dt class="text-xs font-bold uppercase tracking-wider text-gray-500">Merek</dt>
                            <dd class="mt-1 font-semibold text-brand-dark">{{ $brandName }}</dd>
                        </div>
                        <div class="bg-brand-light rounded-xl p-4">
                            <dt class="text-xs font-bold uppercase tracking-wider text-gray-500">Kategori</dt>
                            <dd class="mt-1 font-semibold text-brand-dark">{{ $categoryName }}</dd>
                        </div>
                        <div class="bg-brand-light rounded-xl p-4">
                            <dt class="text-xs font-bold uppercase tracking-wider text-gray-500">Ketersediaan</dt>
                            <dd class="mt-1 font-semibold text-brand-dark">{{ $totalStock > 0 ? 'Tersedia untuk dipesan' : 'Stok sedang habis' }}</dd>
                        </div>
                        <div class="bg-brand-light rounded-xl p-4">
                            <dt class="text-xs font-bold uppercase tracking-wider text-gray-500">Layanan</dt>
                            <dd class="mt-1 font-semibold text-brand-dark">Garansi resmi, konsultasi gratis, dan pengiriman Jabodetabek.</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    @push('jsonld')
        <script type="application/ld+json">
        @json($productSchema)
        </script>
        <script type="application/ld+json">
        @json($breadcrumbSchema)
        </script>
        <script type="application/ld+json">
        @json($productFaqSchema)
        </script>
    @endpush
@endsection

@push('scripts')
<script>
function selectVariant(el) {
    document.querySelectorAll('[data-variant-id]').forEach(btn => {
        btn.classList.remove('border-brand-gold', 'bg-brand-light', 'text-brand-dark');
        btn.classList.add('border-brand-muted', 'bg-white', 'text-gray-600');
    });
    el.classList.remove('border-brand-muted', 'bg-white', 'text-gray-600');
    el.classList.add('border-brand-gold', 'bg-brand-light', 'text-brand-dark');
    
    const priceEl = document.getElementById('product-price');
    const priceLabel = document.getElementById('price-label');
    
    if (priceEl && el.dataset.variantPrice) {
        priceEl.textContent = 'Rp ' + Number(el.dataset.variantPrice).toLocaleString('id-ID');
    }
    
    if (priceLabel) {
        const variantName = el.textContent.trim().split('\n')[0];
        priceLabel.textContent = 'Harga untuk ukuran: ' + variantName;
    }
    
    const variantInput = document.getElementById('variant-id-input');
    if (variantInput) {
        variantInput.value = el.dataset.variantId;
    }
}

function updateQty(change) {
    const input = document.getElementById('quantity-input');
    if (!input) return;
    let val = parseInt(input.value) || 1;
    val = Math.max(1, val + change);
    input.value = val;
}
</script>
@endpush