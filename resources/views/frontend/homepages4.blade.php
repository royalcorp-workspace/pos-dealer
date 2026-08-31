@extends('frontend.layouts.app')
@section('title', __('Home (Marketplace Style)') . ' - IMG')

@section('content')
<div class="bg-brand-light font-sans text-sm pb-20 md:pb-0">
    @php
        $sliderImages = collect();
        if(isset($banners) && isset($banners[1]) && count($banners[1]) > 0) {
            $sliderImages = $banners[1]->flatMap(function($b) {
                if ($b->content_type == 2) {
                    return [[ 'is_embed' => true, 'web' => $b->embed_web_content, 'mobile' => $b->embed_mobile_content ?: $b->embed_web_content, 'link' => $b->link_url, 'title' => $b->title ]];
                } else {
                    if ($b->images->isNotEmpty()) {
                        return $b->images->map(fn($img) => [
                            'is_embed' => false,
                            'web' => $img->image_web_url,
                            'mobile' => $img->image_mobile_url ?: $img->image_web_url,
                            'link' => $img->link_url ?: $b->link_url,
                            'title' => $b->title
                        ]);
                    } else {
                        return [[
                            'is_embed' => false,
                            'web' => $b->image_web_url,
                            'mobile' => $b->image_mobile_url ?: $b->image_web_url,
                            'link' => $b->link_url,
                            'title' => $b->title
                        ]];
                    }
                }
            })->filter(fn($img) => !empty($img['web']) || !empty($img['is_embed']))->values();
        }
        
        $sideBanners = collect();
        if(isset($banners) && isset($banners[2]) && count($banners[2]) > 0) {
            $sideBanners = $banners[2]->flatMap(function($b) {
                if ($b->images->isNotEmpty()) {
                    return $b->images;
                } else {
                    return [$b];
                }
            })->filter(fn($img) => !empty($img->image_web_url))->values();
        }
    @endphp

    @php $htmlBlocks = []; @endphp

    <!-- HERO & BANNERS -->
    @php ob_start(); @endphp
    <div class="bg-gradient-to-r from-brand-dark to-brand-darker py-6 md:py-10 px-4 shadow-sm">
        <div class="max-w-7xl mx-auto flex flex-col md:flex-row gap-4">
            <!-- Main Slider -->
            <div class="md:w-2/3 bg-white rounded-xl overflow-hidden shadow-lg h-[200px] md:h-[350px]">
                @if($sliderImages->isNotEmpty())
                    <div class="relative w-full h-full group" x-data="{ activeSlide: 0, slidesCount: {{ count($sliderImages) }} }" x-init="setInterval(() => activeSlide = (activeSlide + 1) % slidesCount, 5000)">
                        @foreach($sliderImages as $index => $img)
                            <div x-show="activeSlide === {{ $index }}" 
                                 x-transition:enter="transition ease-out duration-700" 
                                 x-transition:enter-start="opacity-0" 
                                 x-transition:enter-end="opacity-100" 
                                 x-transition:leave="transition ease-in duration-500" 
                                 x-transition:leave-start="opacity-100" 
                                 x-transition:leave-end="opacity-0"
                                 class="absolute inset-0 w-full h-full">
                                @if($img['link'])
                                    <a href="{{ $img['link'] }}" class="block w-full h-full">
                                @endif
                                
                                @if($img['is_embed'])
                                    <div class="w-full h-full hidden md:block">{!! $img['web'] !!}</div>
                                    <div class="w-full h-full block md:hidden">{!! $img['mobile'] !!}</div>
                                @else
                                    <div class="w-full h-full hidden md:block">
                                        <img src="{{ cms_asset($img['web']) }}" alt="{{ $img['title'] }}" class="w-full h-full object-cover object-center">
                                    </div>
                                    <div class="w-full h-full block md:hidden">
                                        <img src="{{ cms_asset($img['mobile']) }}" alt="{{ $img['title'] }}" class="w-full h-full object-cover object-center">
                                    </div>
                                @endif

                                @if($img['link'])
                                    </a>
                                @endif
                            </div>
                        @endforeach
                        
                        <!-- Prev / Next Navigation Arrows -->
                        @if(count($sliderImages) > 1)
                            <button @click="activeSlide = (activeSlide - 1 + slidesCount) % slidesCount" class="absolute left-2 top-1/2 -translate-y-1/2 w-8 h-8 rounded-full bg-brand-dark/40 hover:bg-brand-dark text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all z-20">
                                &larr;
                            </button>
                            <button @click="activeSlide = (activeSlide + 1) % slidesCount" class="absolute right-2 top-1/2 -translate-y-1/2 w-8 h-8 rounded-full bg-brand-dark/40 hover:bg-brand-dark text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all z-20">
                                &rarr;
                            </button>
                            <div class="absolute bottom-2 left-1/2 -translate-x-1/2 flex gap-1 z-20">
                                @foreach($sliderImages as $index => $img)
                                    <button @click="activeSlide = {{ $index }}" class="h-1.5 rounded-full transition-all" :class="activeSlide === {{ $index }} ? 'bg-brand-gold w-4' : 'bg-white/60 w-1.5 hover:bg-white'"></button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @else
                    <div class="w-full h-full bg-brand-muted flex items-center justify-center text-brand-dark font-semibold text-lg">Main Promo Banner (CMS)</div>
                @endif
            </div>
            <!-- Side Banners -->
            <div class="md:w-1/3 flex flex-col gap-4">
                @if($sideBanners->isNotEmpty())
                    @foreach($sideBanners->take(2) as $sideBanner)
                    <div class="bg-white rounded-xl overflow-hidden shadow-lg h-1/2 relative group">
                        <img src="{{ cms_asset($sideBanner->image_web_url ?? '') }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                    </div>
                    @endforeach
                @else
                    <div class="bg-white rounded-xl overflow-hidden shadow-lg h-[95px] md:h-1/2 flex items-center justify-center bg-brand-gold text-brand-dark font-semibold">Side Promo 1</div>
                    <div class="bg-white rounded-xl overflow-hidden shadow-lg h-[95px] md:h-1/2 flex items-center justify-center bg-brand-muted text-brand-dark font-semibold">Side Promo 2</div>
                @endif
            </div>
        </div>
    </div>
    @php $htmlBlocks['hero'] = ob_get_clean(); @endphp

    <!-- KATEGORI -->
    @php ob_start(); @endphp
    @if(isset($categories) && $categories->isNotEmpty())
    <div class="max-w-7xl mx-auto bg-white rounded-xl shadow-sm mt-4 p-4 border border-brand-muted/50">
        <div class="grid grid-cols-4 md:grid-cols-8 gap-4 text-center">
            @foreach($categories->take(8) as $cat)
            <a href="{{ route('products.by-tag', $cat->slug) }}" class="flex flex-col items-center gap-2 hover:text-brand-gold transition-colors group">
                <div class="w-14 h-14 bg-brand-muted rounded-full flex items-center justify-center text-brand-dark text-2xl group-hover:bg-brand-gold group-hover:text-brand-dark transition-all duration-300">
                    <img src="{{ cms_asset($cat->image_url ?? '') }}" class="w-8 h-8 object-contain mix-blend-multiply" onerror="this.outerHTML='🛏️'">
                </div>
                <span class="text-xs font-medium leading-tight line-clamp-2">{{ $cat->name }}</span>
            </a>
            @endforeach
        </div>
    </div>
    @endif
    @php $htmlBlocks['kategori'] = ob_get_clean(); @endphp

    <!-- BEST SELLER (Flash Sale Vibe) -->
    @php ob_start(); @endphp
    @if(isset($bestsellers) && $bestsellers->isNotEmpty())
    <div class="max-w-7xl mx-auto bg-white rounded-xl shadow-sm mt-4 overflow-hidden border border-brand-gold/30">
        <div class="bg-brand-dark px-4 py-3 flex justify-between items-center">
            <div class="flex items-center gap-3">
                <h2 class="text-brand-gold font-bold text-xl italic uppercase"><i class="fa-solid fa-bolt mr-2"></i> Super Deal / Terlaris</h2>
            </div>
            <a href="/products" class="text-brand-gold font-medium text-sm hover:underline">Lihat Semua ></a>
        </div>
        <div class="p-4 overflow-x-auto flex gap-4 no-scrollbar">
            @foreach($bestsellers->take(6) as $product)
            <a href="{{ route('products.by-tag', $product->slug) }}" class="w-[160px] flex-shrink-0 border border-brand-muted rounded-lg hover:shadow-lg hover:border-brand-gold cursor-pointer transition-all duration-300 bg-white block">
                <div class="aspect-square relative overflow-hidden rounded-t-lg bg-gray-50 p-2 flex justify-center items-center">
                    <img src="{{ cms_asset($product->thumbnail_url ?? '') }}" class="w-full h-full object-contain mix-blend-multiply">
                    @if($product->calculated_discount > 0)
                        <span class="absolute top-0 right-0 bg-brand-gold text-brand-dark text-[10px] font-bold px-2 py-1 rounded-bl-lg">-{{ round($product->calculated_discount) }}%</span>
                    @endif
                </div>
                <div class="p-3">
                    <div class="text-xs text-gray-700 mb-1 line-clamp-2 h-8 font-medium">{{ $product->name }}</div>
                    @php $minPrice = $product->variants->where('status', true)->where('sell_price', '>', 0)->min('sell_price') ?? 0; @endphp
                    <div class="text-brand-dark font-bold text-base">Rp {{ number_format($minPrice, 0, ',', '.') }}</div>
                    <div class="w-full bg-brand-muted h-2 rounded-full mt-3 relative overflow-hidden">
                        <div class="absolute top-0 left-0 h-full bg-brand-gold w-3/4"></div>
                        <span class="absolute inset-0 text-[9px] text-brand-dark text-center font-bold leading-3">TERJUAL 75%</span>
                    </div>
                </div>
            </a>
            @endforeach
        </div>
    </div>
    @endif
    @php $htmlBlocks['best_seller'] = ob_get_clean(); @endphp

    <!-- BUNDLING SECTION -->
    @php ob_start(); @endphp
    @if(isset($bundles) && $bundles->isNotEmpty())
    <div class="max-w-7xl mx-auto mt-6">
        <div class="bg-white rounded-xl shadow-sm p-4 border border-brand-gold/30">
            <h2 class="text-lg font-bold text-brand-dark mb-4 uppercase">Paket Bundling Spesial</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($bundles->take(4) as $bundle)
                <div class="border border-brand-muted rounded-lg p-3 flex gap-4 items-center hover:bg-gray-50 transition">
                    <div class="w-24 h-24 bg-gray-100 rounded-md p-2 flex-shrink-0">
                        <img src="{{ cms_asset($bundle->thumbnail_url ?? '') }}" class="w-full h-full object-contain mix-blend-multiply">
                    </div>
                    <div class="flex-grow">
                        <h4 class="font-bold text-sm text-brand-dark line-clamp-1">{{ $bundle->name }}</h4>
                        <div class="text-gray-400 text-xs line-through mt-1">Rp {{ number_format($bundle->total_original ?? 0, 0, ',', '.') }}</div>
                        <div class="text-brand-dark font-black text-lg">Rp {{ number_format($bundle->total_price ?? 0, 0, ',', '.') }}</div>
                    </div>
                    <a href="/products" class="bg-brand-gold text-brand-dark px-3 py-1 text-xs font-bold rounded hover:bg-brand-dark hover:text-brand-gold transition flex-shrink-0">Beli</a>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif
    @php $htmlBlocks['bundling'] = ob_get_clean(); @endphp

    <!-- REKOMENDASI (Endless Grid) -->
    @php ob_start(); @endphp
    @if(isset($recommended) && $recommended->isNotEmpty())
    <div class="max-w-7xl mx-auto mt-6 mb-10">
        <div class="flex items-center mb-4">
            <div class="bg-white border-b-4 border-brand-gold inline-block px-6 py-3 font-bold text-brand-dark uppercase text-lg shadow-sm rounded-t-lg">
                Rekomendasi Untukmu
            </div>
            <div class="flex-grow border-b-2 border-brand-muted"></div>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">
            @foreach($recommended->take(12) as $product)
            <a href="{{ route('products.by-tag', $product->slug) }}" class="bg-white rounded-lg hover:border-brand-gold border border-brand-muted transition-all duration-300 p-2 cursor-pointer flex flex-col h-full shadow-sm hover:shadow-md">
                <div class="relative w-full aspect-square bg-gray-50 rounded mb-2 overflow-hidden flex items-center justify-center p-2">
                    <img src="{{ cms_asset($product->thumbnail_url ?? '') }}" class="max-h-full object-contain mix-blend-multiply hover:scale-105 transition-transform duration-500">
                </div>
                <div class="text-xs font-medium text-gray-700 line-clamp-2 mb-2 h-8">{{ $product->name }}</div>
                @php $recPrice = $product->variants->where('status', true)->where('sell_price', '>', 0)->min('sell_price') ?? 0; @endphp
                <div class="text-brand-dark font-bold text-sm mt-auto">Rp {{ number_format($recPrice, 0, ',', '.') }}</div>
                <div class="flex items-center justify-between text-[10px] text-gray-500 mt-2">
                    <span class="flex items-center"><i class="fa-solid fa-star text-brand-gold mr-1"></i> 4.9</span>
                    <span>Terjual 1rb+</span>
                </div>
            </a>
            @endforeach
        </div>
        <div class="text-center mt-8">
            <button class="bg-white border border-brand-dark text-brand-dark hover:bg-brand-dark hover:text-white transition-colors px-12 py-3 rounded-full font-semibold shadow-sm w-full md:w-auto text-sm">
                Muat Lebih Banyak
            </button>
        </div>
    </div>
    @endif
    @php $htmlBlocks['rekomendasi'] = ob_get_clean(); @endphp

    <!-- Dynamic Rendering based on $homepageSections order -->
    @php
        // Selalu tampilkan hero di paling atas
        if(isset($htmlBlocks['hero'])) {
            echo $htmlBlocks['hero'];
            unset($htmlBlocks['hero']);
        }

        // Looping berdasarkan database CMS
        if (isset($homepageSections) && $homepageSections->isNotEmpty()) {
            foreach($homepageSections as $section) {
                $lowerKey = strtolower($section->section_key);
                if((str_contains($lowerKey, 'kategori') || str_contains($lowerKey, 'category')) && isset($htmlBlocks['kategori'])) {
                    echo $htmlBlocks['kategori'];
                    unset($htmlBlocks['kategori']);
                }
                elseif(str_contains($lowerKey, 'best') && isset($htmlBlocks['best_seller'])) {
                    echo $htmlBlocks['best_seller'];
                    unset($htmlBlocks['best_seller']);
                }
                elseif((str_contains($lowerKey, 'bundl') || str_contains($lowerKey, 'paket')) && isset($htmlBlocks['bundling'])) {
                    echo $htmlBlocks['bundling'];
                    unset($htmlBlocks['bundling']);
                }
                elseif((str_contains($lowerKey, 'rekomendasi') || str_contains($lowerKey, 'recommend')) && isset($htmlBlocks['rekomendasi'])) {
                    echo $htmlBlocks['rekomendasi'];
                    unset($htmlBlocks['rekomendasi']);
                }
            }
        } else {
            // Fallback
            foreach($htmlBlocks as $remainingHtml) {
                echo $remainingHtml;
            }
        }
    @endphp

</div>
@endsection