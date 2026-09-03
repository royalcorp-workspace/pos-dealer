@extends('frontend.layouts.app')
@section('title', __('Home (Modern & Friendly)') . ' - IMG')

@section('content')
<div class="bg-[#FCF9F3] font-sans text-brand-dark antialiased">
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

    <!-- HERO SECTION (Tied to 'hero' or always at top if no section_key matches) -->
    @php ob_start(); @endphp
    <section class="relative bg-brand-muted/40 pt-20 pb-32 px-6 md:px-12 flex flex-col md:flex-row items-center justify-between rounded-b-[3rem] md:rounded-b-[6rem]">
        <div class="md:w-1/2 z-10 mb-12 md:mb-0 text-center md:text-left max-w-xl mx-auto md:mx-0">
            <div class="inline-flex items-center gap-2 bg-white px-4 py-2 rounded-full text-sm font-bold mb-6 text-brand-dark shadow-sm border border-brand-gold/20">
                <span class="w-2 h-2 rounded-full bg-brand-gold animate-pulse"></span> International Mattress Gallery
            </div>
            <h1 class="text-5xl md:text-[5.5rem] font-extrabold mb-6 leading-[1.05] tracking-tight">
                Tidur Nyenyak.<br>Hidup Lebih<br><span class="text-brand-gold">Bermakna.</span>
            </h1>
            <p class="text-lg text-gray-600 mb-8 font-medium">Busa memori dan pegas premium yang memeluk tubuhmu. Dapatkan pengalaman tidur layaknya di hotel bintang 5.</p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center md:justify-start">
                <a href="/products" class="bg-brand-dark text-white px-8 py-4 rounded-full text-lg font-bold hover:bg-black shadow-[0_10px_20px_rgba(43,29,18,0.2)] hover:shadow-none transition-all text-center">Pilih Kasurmu</a>
            </div>
        </div>
        
        <div class="md:w-1/2 flex justify-center relative">
            <div class="absolute inset-0 bg-brand-gold/20 rounded-[40%_60%_70%_30%/40%_50%_60%_50%] blur-2xl opacity-70 w-[120%] h-[120%] -left-[10%] -top-[10%] mix-blend-multiply"></div>
            @if($sliderImages->isNotEmpty())
                <div class="relative z-10 w-full max-w-lg aspect-video rounded-[2.5rem] rotate-3 hover:rotate-0 transition-transform duration-500 shadow-2xl bg-white p-2 border-4 border-white overflow-hidden">
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
                </div>
            @else
                <div class="relative z-10 w-full max-w-md h-80 bg-white rounded-[3rem] shadow-xl rotate-3 flex items-center justify-center p-4 text-gray-400 font-bold border-4 border-dashed border-gray-200">Main Banner (Tidak Aktif)</div>
            @endif
        </div>
    </section>
    @php $htmlBlocks['hero'] = ob_get_clean(); @endphp

    <!-- KATEGORI -->
    @php ob_start(); @endphp
    @if(isset($categories) && $categories->isNotEmpty())
    <section class="py-12 px-6">
        <div class="max-w-6xl mx-auto grid grid-cols-2 md:grid-cols-4 gap-6 relative z-20">
            @foreach($categories->take(4) as $cat)
            <a href="{{ route('products.by-tag', $cat->slug) }}" class="bg-white p-6 rounded-3xl shadow-xl text-center border-b-4 border-brand-muted hover:border-brand-gold transition duration-300 group block">
                <div class="w-16 h-16 mx-auto bg-brand-light rounded-full flex items-center justify-center mb-4 group-hover:scale-110 transition">
                    <img src="{{ cms_asset($cat->image_url ?? '') }}" class="w-10 h-10 object-contain mix-blend-multiply" onerror="this.outerHTML='🛏️'">
                </div>
                <h3 class="font-bold text-lg mb-1 text-brand-dark">{{ $cat->name }}</h3>
            </a>
            @endforeach
        </div>
    </section>
    @endif
    @php $htmlBlocks['kategori'] = ob_get_clean(); @endphp

    <!-- BEST SELLER -->
    @php ob_start(); @endphp
    @if(isset($bestsellers) && $bestsellers->isNotEmpty())
    <section class="py-16 px-6 md:px-12 max-w-7xl mx-auto">
        <div class="text-center mb-12">
            <h2 class="text-4xl md:text-5xl font-extrabold mb-4 text-brand-dark">Paling Laku & Populer</h2>
            <p class="text-gray-600 text-lg">Hati-hati, efek sampingnya telat bangun karena terlalu nyaman.</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach($bestsellers->take(3) as $product)
            <a href="{{ route('products.by-tag', $product->slug) }}" class="bg-white rounded-[2.5rem] p-5 shadow-sm hover:shadow-2xl hover:-translate-y-2 transition-all duration-300 border-2 border-transparent hover:border-brand-gold group flex flex-col">
                <div class="bg-gray-100 rounded-[2rem] aspect-square flex items-center justify-center p-6 relative mb-6">
                    @if($product->best_seller)
                        <span class="absolute top-4 right-4 bg-brand-dark text-white text-xs font-bold px-4 py-2 rounded-full z-10">HOT 🔥</span>
                    @endif
                    <img src="{{ cms_asset($product->thumbnail_url ?? '') }}" class="object-contain h-full group-hover:scale-110 transition duration-500 drop-shadow-md">
                </div>
                <div class="px-2 flex-grow">
                    <h3 class="font-bold text-2xl mb-2 line-clamp-1">{{ $product->name }}</h3>
                    <p class="text-gray-500 mb-6 line-clamp-2 text-sm">{{ $product->short_description }}</p>
                </div>
                @php $minPrice = $product->variants->where('status', true)->where('sell_price', '>', 0)->min('sell_price') ?? 0; @endphp
                <div class="bg-brand-muted/30 rounded-2xl p-4 flex justify-between items-center mt-auto">
                    <div>
                        <div class="text-xs text-gray-500 font-bold uppercase mb-1">Mulai</div>
                        <div class="font-black text-xl text-brand-dark">Rp {{ number_format($minPrice, 0, ',', '.') }}</div>
                    </div>
                    <div class="bg-brand-gold text-brand-dark w-12 h-12 rounded-full flex items-center justify-center font-bold text-xl group-hover:bg-brand-dark group-hover:text-white transition-colors">
                        +
                    </div>
                </div>
            </a>
            @endforeach
        </div>
    </section>
    @endif
    @php $htmlBlocks['best_seller'] = ob_get_clean(); @endphp

    <!-- REKOMENDASI -->
    @php ob_start(); @endphp
    @if(isset($recommended) && $recommended->isNotEmpty())
    <section class="py-16 px-6 md:px-12 max-w-7xl mx-auto bg-white rounded-[3rem] shadow-sm mb-16">
        <h2 class="text-3xl font-extrabold mb-8 text-brand-dark text-center">Rekomendasi Spesial Untuk Anda</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach($recommended->take(8) as $product)
            <a href="{{ route('products.by-tag', $product->slug) }}" class="block p-4 border border-brand-muted rounded-2xl hover:border-brand-gold hover:shadow-lg transition-all group">
                <div class="bg-brand-light rounded-xl aspect-square mb-4 p-4 flex items-center justify-center">
                    <img src="{{ cms_asset($product->thumbnail_url ?? '') }}" class="object-contain h-full group-hover:scale-110 transition duration-300">
                </div>
                <h4 class="font-bold text-sm line-clamp-2 mb-2">{{ $product->name }}</h4>
                @php $minPrice = $product->variants->where('status', true)->where('sell_price', '>', 0)->min('sell_price') ?? 0; @endphp
                <div class="text-brand-gold font-black">Rp {{ number_format($minPrice, 0, ',', '.') }}</div>
            </a>
            @endforeach
        </div>
    </section>
    @endif
    @php $htmlBlocks['rekomendasi'] = ob_get_clean(); @endphp

    <!-- BUNDLING -->
    @php ob_start(); @endphp
    @if(isset($bundles) && $bundles->isNotEmpty())
    <section class="py-16 px-6 bg-brand-dark text-white rounded-[3rem] max-w-7xl mx-auto mb-16">
        <div class="text-center mb-10">
            <h2 class="text-4xl font-extrabold text-brand-gold mb-4">Paket Bundling Hemat</h2>
            <p class="text-gray-300">Beli sepaket lebih murah dan praktis.</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 px-6">
            @foreach($bundles->take(4) as $bundle)
            <div class="bg-white/10 rounded-[2rem] p-6 border border-white/20 backdrop-blur-sm flex flex-col md:flex-row gap-6 items-center">
                <div class="w-full md:w-1/3 aspect-square bg-white rounded-xl flex justify-center items-center p-4">
                    <img src="{{ cms_asset($bundle->thumbnail_url ?? '') }}" class="max-h-full object-contain">
                </div>
                <div class="w-full md:w-2/3">
                    <h3 class="text-2xl font-bold text-brand-gold mb-2">{{ $bundle->name }}</h3>
                    <div class="flex gap-4 items-center mb-4">
                        <span class="text-gray-400 line-through">Rp {{ number_format($bundle->total_original ?? 0, 0, ',', '.') }}</span>
                        <span class="text-3xl font-black text-white">Rp {{ number_format($bundle->total_price ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <button class="bg-brand-gold text-brand-dark px-6 py-2 rounded-full font-bold w-full md:w-auto">Lihat Paket</button>
                </div>
            </div>
            @endforeach
        </div>
    </section>
    @endif
    @php $htmlBlocks['bundling'] = ob_get_clean(); @endphp

    <!-- DYNAMIC RENDERER: Only what is in homepageSections -->
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
            // Fallback jika kosong dari DB
            foreach($htmlBlocks as $remainingHtml) {
                echo $remainingHtml;
            }
        }
    @endphp
</div>
@endsection