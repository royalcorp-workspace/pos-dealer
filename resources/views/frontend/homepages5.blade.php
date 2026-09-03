@extends('frontend.layouts.app')
@section('title', __('Home (Eco-Shop Style)') . ' - IMG')

@section('content')
<div class="font-sans text-gray-800 pb-20 md:pb-0 bg-white">
    <!-- HERO SLIDER -->
    @if(isset($banners) && isset($banners[1]) && count($banners[1]) > 0)
    <section class="relative w-full h-[60vh] md:h-[80vh] overflow-hidden" x-data="{ activeSlide: 0, slides: {{ $banners[1]->count() }} }">
        @foreach($banners[1] as $index => $banner)
            @if($banner->images->isNotEmpty())
                @php $img = $banner->images->first(); @endphp
                <div class="absolute inset-0 transition-opacity duration-1000" x-show="activeSlide === {{ $index }}" x-transition:enter="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="opacity-100" x-transition:leave-end="opacity-0">
                    <img src="{{ $img->image_web_url }}" alt="{{ $banner->title }}" class="w-full h-full object-cover">
                    <div class="absolute inset-0 bg-black/30 flex items-center justify-center">
                        <div class="text-center text-white p-6 max-w-2xl">
                            <h2 class="text-4xl md:text-6xl font-serif font-bold mb-4 uppercase tracking-widest">{{ $banner->title }}</h2>
                            @if($banner->description)
                            <p class="text-lg mb-6">{{ $banner->description }}</p>
                            @endif
                            @if($banner->link_url)
                            <a href="{{ $banner->link_url }}" class="inline-block bg-brand-gold hover:bg-brand-gold-dark text-white font-bold py-3 px-8 rounded-full transition">Shop Now</a>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        @endforeach
        <!-- Controls -->
        <button @click="activeSlide = activeSlide === 0 ? slides - 1 : activeSlide - 1" class="absolute left-4 top-1/2 -translate-y-1/2 w-12 h-12 bg-white/20 hover:bg-white text-white hover:text-black rounded-full flex items-center justify-center transition"><i class="fa-solid fa-chevron-left"></i></button>
        <button @click="activeSlide = activeSlide === slides - 1 ? 0 : activeSlide + 1" class="absolute right-4 top-1/2 -translate-y-1/2 w-12 h-12 bg-white/20 hover:bg-white text-white hover:text-black rounded-full flex items-center justify-center transition"><i class="fa-solid fa-chevron-right"></i></button>
    </section>
    @endif

    <!-- CATEGORIES / SERVICES BLOCKS -->
    <section class="container mx-auto px-4 py-12">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach($categories->take(3) as $cat)
            <a href="{{ route('category.show', $cat->slug) }}" class="group relative h-64 overflow-hidden rounded-lg shadow-sm">
                <img src="{{ $cat->thumbnail_url ?? asset('images/dummy/cat.jpg') }}" alt="{{ $cat->name }}" class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
                <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent flex flex-col justify-end p-6">
                    <h3 class="text-white text-2xl font-bold uppercase">{{ html_entity_decode($cat->name) }}</h3>
                    <p class="text-brand-gold mt-2 font-semibold">View Collection &rarr;</p>
                </div>
            </a>
            @endforeach
        </div>
    </section>

    <!-- PRODUCTS TABS (Bestsellers vs Recommended) -->
    <section class="container mx-auto px-4 py-12" x-data="{ tab: 'bestsellers' }">
        <div class="text-center mb-10">
            <h2 class="text-3xl font-serif font-bold mb-4 uppercase tracking-wider text-brand-dark">Our Products</h2>
            <div class="flex justify-center gap-6 border-b border-gray-200 pb-2 max-w-md mx-auto">
                <button @click="tab = 'bestsellers'" :class="tab === 'bestsellers' ? 'text-brand-gold border-b-2 border-brand-gold pb-2 -mb-[9px] font-bold' : 'text-gray-500 hover:text-brand-dark pb-2'">Best Sellers</button>
                <button @click="tab = 'recommended'" :class="tab === 'recommended' ? 'text-brand-gold border-b-2 border-brand-gold pb-2 -mb-[9px] font-bold' : 'text-gray-500 hover:text-brand-dark pb-2'">Recommended</button>
            </div>
        </div>

        <div x-show="tab === 'bestsellers'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                @foreach($bestsellers->take(8) as $product)
                    @include('frontend.components.product-card', ['product' => $product])
                @endforeach
            </div>
        </div>
        <div x-show="tab === 'recommended'" x-cloak x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                @foreach($recommended->take(8) as $product)
                    @include('frontend.components.product-card', ['product' => $product])
                @endforeach
            </div>
        </div>
    </section>

    <!-- PARALLAX PROMO / BUNDLES -->
    @if(isset($bundles) && count($bundles) > 0)
    <section class="w-full bg-brand-dark py-16 relative overflow-hidden">
        <div class="absolute inset-0 opacity-20 bg-cover bg-center" style="background-image: url('{{ asset('images/dummy/header.jpg') }}'); background-attachment: fixed;"></div>
        <div class="container mx-auto px-4 relative z-10">
            <div class="text-center mb-10 text-white">
                <h2 class="text-3xl font-serif font-bold mb-2 uppercase tracking-wider text-brand-gold">Special Bundles</h2>
                <p class="text-gray-300">Save more with our exclusive package deals</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($bundles->take(4) as $bundle)
                <div class="bg-white rounded-lg overflow-hidden shadow-xl p-4 text-center group">
                    <img src="{{ $bundle->thumbnail_url ?? asset('images/dummy/bundle.jpg') }}" class="w-full h-48 object-cover rounded mb-4 group-hover:scale-105 transition">
                    <h3 class="font-bold text-brand-dark text-lg mb-2 truncate">{{ $bundle->name }}</h3>
                    <div class="text-brand-gold-dark font-extrabold text-xl">Rp {{ number_format($bundle->total_price, 0, ',', '.') }}</div>
                    @if($bundle->discount_percent > 0)
                    <div class="text-xs text-gray-500 line-through mt-1">Rp {{ number_format($bundle->total_original, 0, ',', '.') }}</div>
                    @endif
                    <a href="{{ route('bundling.show', $bundle->slug) }}" class="mt-4 block w-full py-2 border border-brand-dark text-brand-dark hover:bg-brand-dark hover:text-white transition rounded uppercase text-xs font-bold">View Bundle</a>
                </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    <!-- BRANDS LOGOS -->
    <section class="container mx-auto px-4 py-12 border-t border-gray-100">
        <h3 class="text-center text-xl font-bold mb-8 uppercase text-gray-400 tracking-widest">Our Trusted Brands</h3>
        <div class="flex flex-wrap justify-center items-center gap-8 md:gap-16 opacity-60 hover:opacity-100 transition-opacity">
            @foreach($brands->take(6) as $brand)
            <a href="{{ route('brands.show', $brand->slug) }}" class="w-24 md:w-32 hover:scale-110 transition">
                @if($brand->logo_url)
                    <img src="{{ $brand->logo_url }}" alt="{{ $brand->name }}" class="w-full h-auto grayscale hover:grayscale-0 transition">
                @else
                    <span class="font-black text-2xl text-gray-300">{{ $brand->name }}</span>
                @endif
            </a>
            @endforeach
        </div>
    </section>
</div>
@endsection
