@extends('frontend.layouts.app')

@section('title', $product['name'] . ' - IMG')

@section('content')
    <div 
        class="container mx-auto px-4 md:px-6 py-8"
        x-data="{ 
            quantity: 1, 
            selectedSize: '{{ $product['isVariable'] ? '200 x 160 cm' : '' }}',
            basePrice: {{ $product['minPrice'] ?? $product['price'] }},
            sizes: [
                '200 x 090 cm', 
                '200 x 100 cm', 
                '200 x 120 cm', 
                '200 x 160 cm', 
                '200 x 180 cm', 
                '200 x 200 cm'
            ],
            getPrice() {
                if (!'{{ $product['isVariable'] }}') return {{ $product['price'] }};
                let idx = this.sizes.indexOf(this.selectedSize);
                if (idx > -1) {
                    return this.basePrice + (idx * 500000);
                }
                return this.basePrice;
            }
        }"
    >
        <!-- Breadcrumbs -->
        <nav class="flex items-center gap-2 text-sm text-gray-500 mb-8 font-sans">
            <a href="{{ route('home') }}" class="hover:text-brand-dark transition-colors">Home</a>
            <i class="fa-solid fa-chevron-right w-4 h-4 text-gray-400"></i>
            <span class="hover:text-brand-dark transition-colors cursor-pointer">{{ $product['category'] }}</span>
            <i class="fa-solid fa-chevron-right w-4 h-4 text-gray-400"></i>
            <span class="hover:text-brand-dark transition-colors cursor-pointer">{{ $product['brand'] }}</span>
            <i class="fa-solid fa-chevron-right w-4 h-4 text-gray-400"></i>
            <span class="text-brand-dark font-medium truncate">{{ $product['name'] }}</span>
        </nav>

        <div class="flex flex-col lg:flex-row gap-12">
            <!-- Left: Product Images -->
            <div class="w-full lg:w-1/2 space-y-4">
                <div class="aspect-[4/3] bg-brand-light rounded-3xl overflow-hidden border border-brand-muted relative">
                    <img 
                        src="{{ $product['image'] }}" 
                        alt="{{ $product['name'] }}" 
                        class="w-full h-full object-cover"
                    />
                    @if(isset($product['discountBadge']))
                        <span class="absolute top-4 left-4 bg-brand-dark text-white text-xs font-bold px-3 py-1.5 rounded-sm uppercase tracking-wider">
                            {{ $product['discountBadge'] }}
                        </span>
                    @endif
                </div>
                
                <!-- Mock thumbnail gallery -->
                <div class="grid grid-cols-4 gap-4">
                    @foreach([1, 2, 3, 4] as $i)
                        <div class="aspect-square bg-brand-light rounded-xl overflow-hidden border-2 cursor-pointer {{ $i === 1 ? 'border-brand-gold' : 'border-transparent' }}">
                            <img src="{{ $product['image'] }}" alt="" class="w-full h-full object-cover opacity-80 hover:opacity-100 transition-opacity" />
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Right: Product Info -->
            <div class="w-full lg:w-1/2 flex flex-col font-sans">
                <div class="mb-2 text-sm font-bold text-brand-gold-dark uppercase tracking-widest">
                    {{ $product['brand'] }}
                </div>
                
                <h1 class="text-3xl md:text-4xl font-extrabold text-brand-dark mb-4 leading-tight font-serif">
                    {{ $product['name'] }}
                </h1>

                <!-- Rating -->
                <div class="flex items-center gap-1.5 mb-6">
                    <div 
                        class="flex items-center gap-1.5 cursor-pointer hover:bg-brand-light p-1 -ml-1 rounded transition-colors"
                        data-product-review="{{ json_encode($product, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG) }}"
                        data-product-id="{{ $product['id'] }}"
                        @click="window.openProductReview($event, '{{ $product['id'] }}')"
                    >
                        <div class="flex items-center text-brand-gold">
                            <i class="fa-solid fa-star w-5 h-5 fill-current"></i>
                        </div>
                        <span class="font-bold text-brand-dark">{{ $product['rating'] }}</span>
                        <span class="text-sm text-gray-500 hover:text-brand-gold-dark underline-offset-2 hover:underline">
                            ({{ $product['reviewsCount'] }} Ulasan)
                        </span>
                    </div>
                </div>

                <!-- Price Card -->
                <div class="mb-8 p-6 bg-brand-muted/30 rounded-2xl border border-brand-light">
                    @if(isset($product['originalPrice']))
                        <div class="text-gray-500 line-through mb-1">
                            Rp {{ number_format($product['originalPrice'], 0, ',', '.') }}
                        </div>
                    @endif
                    <div class="text-4xl font-extrabold text-brand-dark tracking-tight">
                        Rp <span x-text="Number(getPrice()).toLocaleString('id-ID')"></span>
                    </div>
                    
                    @if($product['isVariable'])
                        <div class="text-sm text-brand-gold mt-2 font-medium">
                            Harga untuk ukuran: <span x-text="selectedSize"></span>
                        </div>
                    @endif
                </div>

                <!-- Product Purchase Form -->
                <form action="{{ route('cart.add') }}" method="POST">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product['id'] }}">
                    <input type="hidden" name="size" :value="selectedSize">
                    <input type="hidden" name="quantity" :value="quantity">

                    <!-- Options -->
                    @if($product['isVariable'])
                        <div class="mb-8">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="font-bold text-brand-dark">Pilih Ukuran</h3>
                                <a href="#" class="font-medium text-sm text-brand-gold hover:text-brand-gold-dark transition-colors">Panduan Ukuran</a>
                            </div>
                            <div class="grid grid-cols-2 lg:grid-cols-3 gap-3">
                                <template x-for="size in sizes" :key="size">
                                    <button 
                                        type="button"
                                        @click="selectedSize = size"
                                        :class="selectedSize === size ? 'border-2 border-brand-gold bg-brand-light text-brand-dark text-brand-gold shadow-sm' : 'border-2 border-brand-muted bg-white text-gray-600 hover:border-brand-gold/50'"
                                        class="py-3 px-4 rounded-xl font-semibold text-sm transition-all text-center focus:outline-none"
                                    >
                                        <span x-text="size"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    @endif

                    <!-- Action Buttons -->
                    <div class="flex flex-col sm:flex-row gap-4 mb-8 pt-6 border-t border-brand-muted">
                        <!-- Quantity selector -->
                        <div class="flex items-center border-2 border-brand-muted rounded-xl bg-white h-14 w-32 justify-between px-2">
                            <button 
                                type="button"
                                @click="quantity = Math.max(1, quantity - 1)"
                                class="w-10 h-10 flex items-center justify-center text-gray-500 hover:text-brand-dark transition-colors font-medium text-xl focus:outline-none"
                            >
                                -
                            </button>
                            <span class="font-bold text-brand-dark" x-text="quantity"></span>
                            <button 
                                type="button"
                                @click="quantity = quantity + 1"
                                class="w-10 h-10 flex items-center justify-center text-gray-500 hover:text-brand-dark transition-colors font-medium text-xl focus:outline-none"
                            >
                                +
                            </button>
                        </div>

                        <!-- Add to Cart -->
                        @if($product['isSoldOut'])
                            <button 
                                disabled
                                class="flex-1 h-14 rounded-xl font-bold flex items-center justify-center gap-2 bg-gray-100 text-gray-400 cursor-not-allowed"
                            >
                                Sold Out
                            </button>
                        @else
                            <button 
                                type="submit"
                                class="flex-1 h-14 rounded-xl font-bold flex items-center justify-center gap-2 bg-brand-dark text-brand-gold hover:bg-brand-darker shadow-lg shadow-brand-dark/20 transition-transform active:scale-[0.98] focus:outline-none"
                            >
                                <i class="fa-solid fa-cart-shopping w-5 h-5"></i>
                                Tambah ke Keranjang
                            </button>
                        @endif

                        <button type="button" class="h-14 w-14 flex items-center justify-center border-2 border-brand-muted rounded-xl text-gray-400 hover:border-brand-gold hover:text-brand-gold transition-colors focus:outline-none">
                            <i class="fa-regular fa-heart w-6 h-6"></i>
                        </button>
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
                            {{ $product['description'] }}
                        </p>
                        <p class="mb-4">
                            Rasakan pengalaman tidur layaknya di hotel bintang lima dengan kasur premium ini. Dirancang menggunakan teknologi pegas (spring) inovatif dan lapisan busa kepadatan tinggi yang memberikan topangan optimal untuk tulang belakang Anda sepanjang malam.
                        </p>
                        <p class="mb-2 font-bold text-brand-dark">
                            Fitur Unggulan:
                        </p>
                        <ul class="list-disc pl-5 mb-4 space-y-1">
                            <li>Material kain anti bakteri dan hypoallergenic</li>
                            <li>Lapisan Plush Top yang super lembut</li>
                            <li>Sistem pegas independen (Pocket Spring) penahan guncangan</li>
                            <li>Sirkulasi udara maksimal untuk tidur yang sejuk</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

