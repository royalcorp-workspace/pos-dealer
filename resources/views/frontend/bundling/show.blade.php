@extends('frontend.layouts.app')

@section('title', $bundle->name . ' - Promo Bundling Spesial - IMG')
@section('meta_description', $bundle->description ?? 'Dapatkan penawaran promo bundling hemat eksklusif dari IMG. Beli produk utama dan dapatkan perlengkapan tidur berkualitas dengan harga diskon khusus.')

@section('content')
@php
    $bundleImage = $bundle->thumbnail_url ?: ($bundle->banner_image ? cms_asset($bundle->banner_image) : 'https://via.placeholder.com/600x450');
    $mainProduct = $bundle->main_product;
    $suggestItems = $bundle->suggest_items;
    $totalSavings = (float)($bundle->total_savings ?? 0);
@endphp

<div class="container mx-auto px-4 md:px-6 py-6 md:py-10 font-sans min-h-[70vh] pb-24 lg:pb-12">
    <!-- Breadcrumbs -->
    <nav class="mb-6 flex items-center gap-2 text-xs sm:text-sm text-gray-500 font-medium">
        <a href="{{ route('home') }}" class="hover:text-brand-gold transition-colors">Home</a>
        <span class="text-gray-300">/</span>
        <a href="{{ route('bundling.index') }}" class="hover:text-brand-gold transition-colors">Promo Bundling</a>
        <span class="text-gray-300">/</span>
        <span class="text-brand-dark font-semibold truncate">{{ $bundle->name }}</span>
    </nav>

    <!-- Top Announcement Header Banner - Clean, High-Contrast Luxury -->
    <div class="mb-8 p-5 sm:p-6 rounded-3xl border-2 shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-4" style="background-color: #fffdfa; border-color: #fcd34d;">
        <div class="flex items-center gap-3.5">
            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl text-white shadow-md text-xl" style="background-color: #d97706; color: #ffffff;">
                <i class="fa-solid fa-gift"></i>
            </span>
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-[10px] font-black uppercase tracking-wider px-2.5 py-0.5 rounded-full" style="background-color: #fef3c7; color: #78350f; border: 1px solid #fcd34d;">
                        Penawaran Spesial
                    </span>
                    <span class="text-xs font-bold" style="color: #92400e;">
                        • Persediaan Terbatas
                    </span>
                </div>
                <h2 class="text-base sm:text-lg font-extrabold mt-0.5 leading-tight tracking-tight" style="color: #2b1d12;">
                    Paket Kombo Bundling Hemat Pilihan
                </h2>
                <p class="text-xs font-medium mt-1" style="color: #4b5563;">
                    Dapatkan harga spesial untuk produk pelengkap saat membeli kasur utama di paket ini!
                </p>
            </div>
        </div>

        @if($totalSavings > 0)
            <div class="shrink-0 px-4 py-2.5 rounded-2xl flex items-center gap-3 shadow-2xs" style="background-color: #ffffff; border: 2px solid #86efac;">
                <div class="text-right">
                    <span class="text-[10px] uppercase font-bold block" style="color: #6b7280;">Total Penghematan</span>
                    <span class="text-base sm:text-lg font-black" style="color: #15803d;">
                        Hemat Rp {{ number_format($totalSavings, 0, ',', '.') }}
                    </span>
                </div>
                <span class="w-9 h-9 rounded-xl flex items-center justify-center text-base shadow-2xs" style="background-color: #dcfce7; color: #15803d; border: 1px solid #bbf7d0;">
                    <i class="fa-solid fa-piggy-bank"></i>
                </span>
            </div>
        @endif
    </div>

    <!-- Main Content Layout Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-10 mb-16 items-start">
        
        <!-- Left Column: Poster / Visual Showcase & Trust Badges -->
        <div class="lg:col-span-6 space-y-5">
            <!-- Poster Frame with Luxury Border & Shadows -->
            <div class="relative overflow-hidden rounded-3xl border border-amber-200/80 bg-white shadow-xl aspect-[4/3] flex items-center justify-center group">
                <img
                    src="{{ $bundleImage }}"
                    alt="{{ $bundle->name }}"
                    class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105"
                    id="bundle-main-image"
                />
                
                <!-- Floating Promo Badges -->
                <div class="absolute top-4 left-4 flex flex-col gap-2 z-10">
                    <span class="bg-gradient-to-r from-amber-600 to-amber-700 text-white font-black px-3.5 py-1.5 rounded-xl text-xs tracking-wider uppercase shadow-lg border border-amber-400/40 flex items-center gap-1.5">
                        <i class="fa-solid fa-tags text-[11px]"></i> PROMO BUNDLING
                    </span>
                    @if($totalSavings > 0)
                        <span class="bg-emerald-600 text-white font-black px-3.5 py-1.5 rounded-xl text-xs tracking-wider uppercase shadow-lg border border-emerald-400/40 flex items-center gap-1">
                            <i class="fa-solid fa-circle-check text-[11px]"></i> HEMAT Rp {{ number_format($totalSavings, 0, ',', '.') }}
                        </span>
                    @endif
                </div>

                <!-- Bottom Gradient Overlay with Label -->
                <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/70 via-black/30 to-transparent p-4 sm:p-5 pt-12 flex items-end justify-between">
                    <span class="text-white text-xs font-semibold drop-shadow flex items-center gap-1.5">
                        <i class="fa-solid fa-camera"></i> Poster Resmi Paket Promo
                    </span>
                    <span class="text-amber-200 text-xs font-bold drop-shadow flex items-center gap-1 bg-black/40 px-2.5 py-1 rounded-lg backdrop-blur-xs">
                        <i class="fa-solid fa-shield-halved text-amber-400"></i> Jaminan 100% Asli
                    </span>
                </div>
            </div>

            <!-- Mini Visual Combo Preview (Isi Paket Kombo) -->
            <div class="p-4 rounded-2xl bg-amber-50/60 border border-amber-200/70 shadow-2xs">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-extrabold uppercase tracking-wider text-amber-950 flex items-center gap-1.5">
                        <i class="fa-solid fa-layer-group text-amber-600"></i> Komposisi Paket Promo Ini:
                    </span>
                    <span class="text-[11px] text-amber-800 font-medium">Beli Kasur + Tebus Murah Pelengkap</span>
                </div>

                <div class="flex items-center justify-center gap-2 sm:gap-4">
                    <!-- Main Product Bubble -->
                    @if($mainProduct)
                        <div class="flex-1 bg-white p-2.5 rounded-xl border border-amber-200/80 flex items-center gap-2.5 shadow-2xs min-w-0">
                            <div class="w-12 h-12 rounded-lg bg-gray-100 overflow-hidden shrink-0 border border-gray-100">
                                <img src="{{ $mainProduct->thumbnail_url ?: 'https://via.placeholder.com/60x60' }}" alt="{{ $mainProduct->name }}" class="w-full h-full object-cover">
                            </div>
                            <div class="min-w-0">
                                <span class="text-[10px] font-black uppercase text-amber-700 block">Produk Utama</span>
                                <h5 class="text-xs font-bold text-brand-dark truncate">{{ $mainProduct->name }}</h5>
                            </div>
                        </div>
                    @endif

                    <!-- Plus Connector -->
                    <div class="w-8 h-8 rounded-full bg-amber-500 text-white flex items-center justify-center font-black text-sm shrink-0 shadow-md shadow-amber-500/30">
                        +
                    </div>

                    <!-- Suggest Item Bubble -->
                    @if($suggestItems && $suggestItems->isNotEmpty())
                        @php
                            $firstSuggest = $suggestItems->first();
                            $sThumb = $firstSuggest->product?->thumbnail_url ?: 'https://via.placeholder.com/60x60';
                        @endphp
                        <div class="flex-1 bg-white p-2.5 rounded-xl border border-amber-200/80 flex items-center gap-2.5 shadow-2xs min-w-0">
                            <div class="w-12 h-12 rounded-lg bg-amber-50 overflow-hidden shrink-0 border border-amber-100">
                                <img src="{{ $sThumb }}" alt="{{ $firstSuggest->product?->name }}" class="w-full h-full object-cover">
                            </div>
                            <div class="min-w-0">
                                <span class="text-[10px] font-black uppercase text-emerald-700 block">Pelengkap Diskon</span>
                                <h5 class="text-xs font-bold text-brand-dark truncate">{{ $firstSuggest->product?->name }}</h5>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Trust & Assurance Badges -->
            <div class="grid grid-cols-3 gap-2.5 sm:gap-3 pt-1">
                <div class="p-3 bg-white rounded-2xl border border-gray-200/80 text-center shadow-2xs">
                    <i class="fa-solid fa-truck-fast text-brand-gold-dark text-lg mb-1"></i>
                    <h6 class="text-[11px] font-bold text-brand-dark">Pengiriman Aman</h6>
                    <p class="text-[10px] text-gray-400">Armada Terpercaya</p>
                </div>
                <div class="p-3 bg-white rounded-2xl border border-gray-200/80 text-center shadow-2xs">
                    <i class="fa-solid fa-certificate text-brand-gold-dark text-lg mb-1"></i>
                    <h6 class="text-[11px] font-bold text-brand-dark">Garansi Resmi</h6>
                    <p class="text-[10px] text-gray-400">Pabrik Royal Foam</p>
                </div>
                <div class="p-3 bg-white rounded-2xl border border-gray-200/80 text-center shadow-2xs">
                    <i class="fa-solid fa-award text-brand-gold-dark text-lg mb-1"></i>
                    <h6 class="text-[11px] font-bold text-brand-dark">100% Original</h6>
                    <p class="text-[10px] text-gray-400">Kualitas Premium</p>
                </div>
            </div>
        </div>

        <!-- Right Column: Promo Details, Combo Offer Breakdown & High-Impact CTA -->
        <div class="lg:col-span-6 flex flex-col space-y-6">
            <div>
                <!-- Category & Exclusive Deal Tag -->
                <div class="flex flex-wrap items-center gap-2 mb-3">
                    <span class="inline-flex items-center gap-1.5 bg-amber-100 text-amber-900 border border-amber-300/80 text-[11px] font-black tracking-widest uppercase px-3 py-1 rounded-full shadow-2xs">
                        <i class="fa-solid fa-sparkles text-amber-600"></i> PAKET BUNDLING RESMI
                    </span>
                    @if($bundle->event)
                        <span class="bg-brand-light text-brand-gold-dark border border-brand-gold/30 text-[11px] font-bold px-2.5 py-1 rounded-full">
                            {{ $bundle->event->name }}
                        </span>
                    @endif
                </div>

                <h1 class="text-2xl sm:text-3xl md:text-4xl font-extrabold text-brand-dark tracking-tight font-serif mb-3 leading-tight">
                    {{ $bundle->name }}
                </h1>

                <!-- Description -->
                @if($bundle->description)
                    <p class="text-gray-600 text-sm leading-relaxed mb-6">{{ $bundle->description }}</p>
                @endif

                <!-- Pricing & Savings Highlight Card -->
                <div class="bg-gradient-to-br from-amber-500/10 via-amber-50 to-white border-2 border-amber-300 rounded-3xl p-5 sm:p-6 mb-6 shadow-sm relative overflow-hidden">
                    <div class="flex flex-wrap items-end justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-1.5 mb-1">
                                <span class="text-[11px] text-amber-900 uppercase font-black tracking-wider">Estimasi Mulai Dari</span>
                                <span class="text-[10px] text-gray-500">(Kasur + Pelengkap Diskon)</span>
                            </div>
                            <div class="font-extrabold text-3xl sm:text-4xl text-amber-950 tracking-tight">
                                Rp {{ number_format($bundle->start_price ?? 0, 0, ',', '.') }}
                            </div>
                        </div>

                        @if(($bundle->start_original_price ?? 0) > ($bundle->start_price ?? 0))
                            <div class="text-right">
                                <span class="text-[10px] text-gray-400 block uppercase font-bold tracking-wider mb-0.5">Harga Normal Paket</span>
                                <span class="text-sm sm:text-base text-gray-400 line-through font-semibold">
                                    Rp {{ number_format($bundle->start_original_price, 0, ',', '.') }}
                                </span>
                            </div>
                        @endif
                    </div>

                    @if($totalSavings > 0)
                        <div class="mt-4 pt-3.5 border-t border-amber-200/80 flex flex-wrap items-center justify-between gap-2">
                            <span class="inline-flex items-center gap-1.5 text-xs font-black text-emerald-800 bg-emerald-100 border border-emerald-300 rounded-xl px-3 py-1.5 shadow-2xs">
                                <i class="fa-solid fa-tag text-emerald-600"></i>
                                Anda Hemat: Rp {{ number_format($totalSavings, 0, ',', '.') }}
                            </span>
                            <span class="text-[11px] text-amber-800 font-medium">
                                *Harga akhir disesuaikan varian kasur yang Anda pilih
                            </span>
                        </div>
                    @endif
                </div>

                <!-- Rincian Produk dalam Paket (Step 1 & Step 2) -->
                <div class="space-y-4 mb-6">
                    <!-- Step 1: Produk Utama -->
                    @if($mainProduct)
                        <div class="p-4 bg-white border border-gray-200 rounded-2xl shadow-2xs hover:border-brand-gold transition-all duration-200">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-[10px] font-black uppercase tracking-wider text-white bg-brand-dark px-2.5 py-0.5 rounded-full">
                                    Langkah 1: Produk Utama
                                </span>
                                <span class="text-[11px] text-gray-400 font-medium">Bebas pilih ukuran di halaman produk</span>
                            </div>
                            <div class="flex items-center gap-3.5">
                                <div class="w-16 h-16 rounded-xl bg-gray-50 overflow-hidden shrink-0 border border-gray-100 flex items-center justify-center">
                                    <img src="{{ $mainProduct->thumbnail_url ?: 'https://via.placeholder.com/80x80' }}" alt="{{ $mainProduct->name }}" class="w-full h-full object-cover">
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="font-bold text-sm text-brand-dark truncate">{{ $mainProduct->name }}</h4>
                                    <div class="text-xs font-extrabold text-amber-900 mt-0.5">
                                        {{ $bundle->main_price_range_text ?? 'Harga sesuai ukuran varian' }}
                                    </div>
                                    <p class="text-[11px] text-gray-500 mt-0.5 truncate">Tersedia beragam ukuran (Single s/d King Size)</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Visual Connector -->
                    <div class="flex items-center justify-center -my-2 relative z-10">
                        <span class="bg-amber-100 border border-amber-300 text-amber-900 text-[10px] font-black uppercase px-3 py-0.5 rounded-full shadow-2xs flex items-center gap-1">
                            <i class="fa-solid fa-plus text-amber-600"></i> Tambahan Pelengkap dengan Diskon Bundling
                        </span>
                    </div>

                    <!-- Step 2: Produk Pelengkap (Harga Spesial Bundling) -->
                    @if($suggestItems && $suggestItems->isNotEmpty())
                        <div class="space-y-2">
                            @foreach($suggestItems as $sItem)
                                @php
                                    $sNorm = (float)($sItem->variant?->sell_price ?: ($sItem->product?->variants->where('deleted', false)->where('sell_price', '>', 0)->min('sell_price') ?: 0));
                                    $sPrice = (float)($sItem->bundle_price ?: $sNorm);
                                    if ($sItem->discount_percent && !$sItem->bundle_price && $sNorm > 0) {
                                        $sPrice = round($sNorm * (1 - ($sItem->discount_percent / 100)));
                                    }
                                    $sItemSavings = max(0, $sNorm - $sPrice);
                                    $sThumb = $sItem->product?->thumbnail_url ?: 'https://via.placeholder.com/60x60';
                                @endphp
                                <div class="p-4 bg-gradient-to-r from-amber-50/70 to-white border-2 border-amber-200/90 rounded-2xl flex items-center gap-3.5 shadow-2xs">
                                    <div class="w-16 h-16 rounded-xl bg-white overflow-hidden shrink-0 border border-amber-200 flex items-center justify-center">
                                        <img src="{{ $sThumb }}" alt="{{ $sItem->product?->name }}" class="w-full h-full object-cover">
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-[9px] font-black uppercase text-amber-800 bg-amber-200/80 px-2 py-0.2 rounded-sm">
                                                Langkah 2: Pelengkap Hemat
                                            </span>
                                        </div>
                                        <h4 class="font-bold text-sm text-brand-dark truncate mt-0.5">{{ $sItem->product?->name }}</h4>
                                        @if($sItem->variant && $sItem->variant->variant_name !== 'Default')
                                            <div class="text-[11px] text-gray-500 font-medium">Varian: {{ $sItem->variant->variant_name }}</div>
                                        @endif
                                        <div class="flex items-center gap-2 mt-1">
                                            <span class="text-sm font-black text-amber-900">Rp {{ number_format($sPrice, 0, ',', '.') }}</span>
                                            @if($sItemSavings > 0)
                                                <span class="text-xs text-gray-400 line-through">Rp {{ number_format($sNorm, 0, ',', '.') }}</span>
                                                <span class="text-[10px] font-black text-emerald-700 bg-emerald-100 border border-emerald-300 px-2 py-0.2 rounded-md">
                                                    Hemat Rp {{ number_format($sItemSavings, 0, ',', '.') }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Modern Step-by-Step Guide Pills (Cara Klaim) -->
                <div class="p-4 bg-gray-50/90 rounded-2xl border border-gray-200 mb-6">
                    <h4 class="font-extrabold text-xs uppercase tracking-wider text-gray-700 flex items-center gap-1.5 mb-3">
                        <i class="fa-solid fa-circle-question text-amber-600"></i> Cara Membeli Paket Bundling Ini:
                    </h4>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                        <div class="bg-white p-2.5 rounded-xl border border-gray-200/80 shadow-2xs">
                            <span class="w-5 h-5 rounded-full bg-amber-500 text-white font-black text-[10px] flex items-center justify-center mb-1.5">1</span>
                            <h6 class="text-xs font-bold text-brand-dark leading-snug">Pilih Varian Kasur</h6>
                            <p class="text-[11px] text-gray-500 mt-0.5">Tentukan ukuran & feel kasur favorit Anda.</p>
                        </div>
                        <div class="bg-white p-2.5 rounded-xl border border-gray-200/80 shadow-2xs">
                            <span class="w-5 h-5 rounded-full bg-amber-500 text-white font-black text-[10px] flex items-center justify-center mb-1.5">2</span>
                            <h6 class="text-xs font-bold text-brand-dark leading-snug">Diskon Otomatis Aktif</h6>
                            <p class="text-[11px] text-gray-500 mt-0.5">Produk pelengkap otomatis tercentang harga promo.</p>
                        </div>
                        <div class="bg-white p-2.5 rounded-xl border border-gray-200/80 shadow-2xs">
                            <span class="w-5 h-5 rounded-full bg-amber-500 text-white font-black text-[10px] flex items-center justify-center mb-1.5">3</span>
                            <h6 class="text-xs font-bold text-brand-dark leading-snug">Checkout Sekaligus</h6>
                            <p class="text-[11px] text-gray-500 mt-0.5">Klik Beli Sekarang untuk hemat maksimal.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- EXQUISITE HIGH-IMPACT CALL-TO-ACTION BUTTON SECTION -->
            <div class="pt-2 border-t border-gray-100 space-y-3">
                @if(!empty($bundle->main_product_slug))
                    <!-- Primary Glowing Action Button with Explicit Contrast -->
                    <a
                        href="{{ route('products.show', $bundle->main_product_slug) }}#bundling-addon"
                        class="group relative block w-full rounded-2xl p-0.5 shadow-xl shadow-amber-500/25 transition-all duration-300 hover:shadow-2xl hover:shadow-amber-500/40 hover:-translate-y-0.5 active:translate-y-0 cursor-pointer"
                        style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);"
                    >
                        <div class="flex flex-col sm:flex-row items-center justify-between gap-3 rounded-[14px] px-5 sm:px-6 py-4 transition-all duration-300" style="background-color: #2b1d12;">
                            <div class="flex items-center gap-3.5 text-left w-full sm:w-auto">
                                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl text-white text-lg shadow-md" style="background-color: #f59e0b; color: #ffffff;">
                                    <i class="fa-solid fa-cart-shopping"></i>
                                </span>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-extrabold text-base sm:text-lg tracking-tight" style="color: #ffffff !important;">
                                            Ambil Promo & Pilih Ukuran
                                        </span>
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-black uppercase" style="background-color: rgba(245, 158, 11, 0.25); color: #fef08a; border: 1px solid #f59e0b;">
                                            Diskon Aktif
                                        </span>
                                    </div>
                                    <p class="text-xs mt-0.5 font-normal" style="color: #fef3c7 !important;">
                                        Menuju halaman kasur • Diskon pelengkap otomatis terpasang
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 font-bold text-xs sm:text-sm shrink-0 self-end sm:self-center" style="color: #fef08a;">
                                <span>Klaim Penawaran</span>
                                <span class="flex h-8 w-8 items-center justify-center rounded-full text-white transition-colors" style="background-color: #f59e0b; color: #ffffff;">
                                    <i class="fa-solid fa-arrow-right text-xs"></i>
                                </span>
                            </div>
                        </div>
                    </a>

                    <!-- Secondary Supporting Actions (Specs, Share, WhatsApp) -->
                    <div class="flex flex-wrap items-center gap-2 pt-1">
                        <a
                            href="{{ route('products.show', $bundle->main_product_slug) }}"
                            class="flex-1 min-w-[140px] py-2.5 px-3.5 rounded-xl border border-gray-200 bg-white text-gray-700 hover:text-brand-dark hover:border-gray-300 font-bold text-xs flex items-center justify-center gap-1.5 transition shadow-2xs"
                        >
                            <i class="fa-solid fa-circle-info text-gray-400"></i>
                            <span>Lihat Spek Produk Utama</span>
                        </a>

                        <button
                            type="button"
                            onclick="copyPromoUrl()"
                            id="btn-copy-promo"
                            class="py-2.5 px-3.5 rounded-xl border border-gray-200 bg-white text-gray-700 hover:text-brand-dark hover:border-gray-300 font-bold text-xs flex items-center justify-center gap-1.5 transition shadow-2xs cursor-pointer"
                        >
                            <i class="fa-solid fa-share-nodes text-gray-400"></i>
                            <span id="copy-btn-text">Bagikan Promo</span>
                        </button>

                        <a
                            href="https://wa.me/?text={{ urlencode('Halo, saya tertarik dengan penawaran ' . $bundle->name . ' di IMG Store: ' . url()->current()) }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="py-2.5 px-3.5 rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-800 hover:bg-emerald-100 font-bold text-xs flex items-center justify-center gap-1.5 transition shadow-2xs"
                        >
                            <i class="fa-brands fa-whatsapp text-emerald-600 text-sm"></i>
                            <span class="hidden sm:inline">Tanya via WA</span>
                        </a>
                    </div>
                @else
                    <a
                        href="{{ route('products.index') }}"
                        class="w-full py-4 rounded-2xl font-bold text-base flex justify-center items-center gap-2 bg-brand-dark text-white hover:bg-brand-gold hover:text-brand-dark transition-all"
                    >
                        Lihat Katalog Produk
                    </a>
                @endif
            </div>
        </div>
    </div>

    <!-- Related Products -->
    @if(isset($relatedProducts) && $relatedProducts->isNotEmpty())
        <div class="mt-16 pt-12 border-t border-gray-100">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h2 class="text-xl sm:text-2xl font-serif font-extrabold text-brand-dark tracking-tight">Rekomendasi Produk Pilihan Lainnya</h2>
                    <p class="text-xs text-gray-500 mt-1">Temukan berbagai koleksi kasur dan perlengkapan tidur berkualitas tinggi</p>
                </div>
                <a href="{{ route('products.index') }}" class="text-xs sm:text-sm font-bold text-brand-gold-dark hover:text-brand-dark transition-colors flex items-center gap-1">
                    Lihat Semua
                    <i class="fa-solid fa-arrow-right text-xs"></i>
                </a>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">
                @foreach($relatedProducts as $product)
                    @include('frontend.components.product-card-dynamic', ['product' => $product])
                @endforeach
            </div>
        </div>
    @endif
</div>

<!-- Sticky Bottom Action Bar for Mobile Devices -->
@if(!empty($bundle->main_product_slug))
    <div class="lg:hidden fixed bottom-0 left-0 right-0 z-40 bg-white/95 backdrop-blur-md border-t border-amber-200 shadow-2xl p-3 px-4 flex items-center justify-between gap-3">
        <div class="min-w-0 flex-1">
            <div class="flex items-center gap-1.5">
                <span class="text-[9px] uppercase font-black text-amber-800 bg-amber-100 px-1.5 py-0.2 rounded">Paket Hemat</span>
                @if($totalSavings > 0)
                    <span class="text-[10px] font-black text-emerald-700">Hemat Rp {{ number_format($totalSavings, 0, ',', '.') }}</span>
                @endif
            </div>
            <div class="text-sm font-extrabold text-brand-dark truncate mt-0.5">
                Mulai Rp {{ number_format($bundle->start_price ?? 0, 0, ',', '.') }}
            </div>
        </div>
        <a
            href="{{ route('products.show', $bundle->main_product_slug) }}#bundling-addon"
            class="shrink-0 px-5 py-2.5 rounded-xl bg-gradient-to-r from-amber-600 via-amber-500 to-amber-600 text-white font-extrabold text-xs flex items-center gap-1.5 shadow-md shadow-amber-500/30 active:scale-95 transition cursor-pointer"
        >
            <i class="fa-solid fa-cart-shopping text-xs"></i>
            <span>Ambil Promo</span>
            <i class="fa-solid fa-arrow-right text-[10px]"></i>
        </a>
    </div>
@endif

<script>
    function copyPromoUrl() {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(window.location.href).then(() => {
                showCopyToast();
            }).catch(() => {
                fallbackCopyText();
            });
        } else {
            fallbackCopyText();
        }
    }

    function fallbackCopyText() {
        const dummy = document.createElement('input');
        document.body.appendChild(dummy);
        dummy.value = window.location.href;
        dummy.select();
        document.execCommand('copy');
        document.body.removeChild(dummy);
        showCopyToast();
    }

    function showCopyToast() {
        const btnText = document.getElementById('copy-btn-text');
        if (!btnText) return;
        const orig = btnText.textContent;
        btnText.textContent = 'Link Disalin!';
        btnText.parentElement.classList.add('text-emerald-700', 'border-emerald-300', 'bg-emerald-50');
        setTimeout(() => {
            btnText.textContent = orig;
            btnText.parentElement.classList.remove('text-emerald-700', 'border-emerald-300', 'bg-emerald-50');
        }, 2000);
    }
</script>
@endsection
