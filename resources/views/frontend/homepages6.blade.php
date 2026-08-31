@extends('frontend.layouts.app')
@section('title', __('Home (Destry Style)') . ' - IMG')

@section('content')
<div class="font-sans bg-[#faf9f8] text-gray-800 pb-20 md:pb-0">
    <!-- ELEGANT HERO -->
    @if(isset($banners) && isset($banners[1]) && count($banners[1]) > 0)
    <section class="relative w-full h-[70vh] md:h-[90vh] overflow-hidden" x-data="{ activeSlide: 0, slides: {{ $banners[1]->count() }} }">
        @foreach($banners[1] as $index => $banner)
            @if($banner->images->isNotEmpty())
                @php $img = $banner->images->first(); @endphp
                <div class="absolute inset-0 transition-opacity duration-700 ease-in-out" x-show="activeSlide === {{ $index }}" x-transition:enter="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="opacity-100" x-transition:leave-end="opacity-0">
                    <img src="{{ $img->image_web_url }}" alt="{{ $banner->title }}" class="w-full h-full object-cover">
                    <!-- Elegant fade overlay -->
                    <div class="absolute inset-0 bg-gradient-to-r from-black/50 to-transparent flex items-center">
                        <div class="container mx-auto px-8 md:px-16">
                            <div class="max-w-xl text-white">
                                <span class="uppercase tracking-[0.3em] text-sm md:text-base font-semibold mb-4 block text-brand-gold">{{ __('New Collection') }}</span>
                                <h2 class="text-5xl md:text-7xl font-serif font-light mb-6 leading-tight">{{ $banner->title }}</h2>
                                @if($banner->description)
                                <p class="text-lg md:text-xl font-light mb-8 opacity-90">{{ $banner->description }}</p>
                                @endif
                                @if($banner->link_url)
                                <a href="{{ $banner->link_url }}" class="inline-flex items-center gap-3 uppercase tracking-widest text-xs font-bold border-b-2 border-brand-gold pb-1 hover:text-brand-gold transition">
                                    {{ __('Discover Now') }} <i class="fa-solid fa-arrow-right"></i>
                                </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @endforeach
        <!-- Minimal Controls -->
        <div class="absolute bottom-8 left-1/2 -translate-x-1/2 flex gap-4">
            <template x-for="i in slides">
                <button @click="activeSlide = i - 1" :class="activeSlide === i - 1 ? 'w-8 bg-brand-gold' : 'w-2 bg-white/50 hover:bg-white'" class="h-2 rounded-full transition-all duration-300"></button>
            </template>
        </div>
    </section>
    @endif

    <!-- FEATURED CATEGORIES (MINIMAL) -->
    <section class="container mx-auto px-4 py-16 md:py-24">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            @foreach($categories->take(4) as $cat)
            <div class="group cursor-pointer">
                <div class="overflow-hidden rounded-sm mb-6 bg-white aspect-[3/4]">
                    <img src="{{ $cat->thumbnail_url ?? asset('images/dummy/cat.jpg') }}" alt="{{ $cat->name }}" class="w-full h-full object-cover mix-blend-multiply group-hover:scale-105 transition duration-700">
                </div>
                <div class="text-center">
                    <h3 class="text-lg font-serif tracking-wide text-brand-dark">{{ html_entity_decode($cat->name) }}</h3>
                    <p class="text-gray-400 text-sm mt-1">{{ $cat->products_count }} {{ __('Products') }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </section>

    <!-- TRENDING ARRIVALS -->
    <section class="bg-white py-16 md:py-24">
        <div class="container mx-auto px-4">
            <div class="flex flex-col md:flex-row justify-between items-end mb-12">
                <div>
                    <h2 class="text-4xl font-serif font-light text-brand-dark">{{ __('Trending Now') }}</h2>
                    <p class="text-gray-500 mt-2">{{ __('Top choices to elevate your comfort.') }}</p>
                </div>
                <a href="{{ route('products.index') }}" class="hidden md:inline-flex items-center gap-2 uppercase tracking-widest text-xs font-bold text-brand-dark hover:text-brand-gold transition border-b border-black hover:border-brand-gold pb-1 mt-6 md:mt-0">
                    {{ __('View All') }} <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-x-6 gap-y-12">
                @foreach($recommended->take(8) as $product)
                    @include('frontend.components.product-card', ['product' => $product])
                @endforeach
            </div>
            
            <div class="mt-12 text-center md:hidden">
                <a href="{{ route('products.index') }}" class="inline-block border border-brand-dark px-8 py-3 text-xs uppercase tracking-widest font-bold hover:bg-brand-dark hover:text-white transition">
                    {{ __('View All') }}
                </a>
            </div>
        </div>
    </section>

    <!-- FEATURED BANNER SECTION -->
    @if(isset($homepageSections) && $homepageSections->count() > 0)
    <section class="py-16 md:py-0 md:mb-24">
        <div class="container mx-auto px-4">
            <div class="bg-brand-dark text-white rounded-3xl overflow-hidden flex flex-col md:flex-row items-center">
                <div class="w-full md:w-1/2 p-12 md:p-20">
                    <span class="text-brand-gold uppercase tracking-widest text-xs font-bold mb-4 block">{{ __('Exclusive Edition') }}</span>
                    <h2 class="text-4xl md:text-5xl font-serif font-light mb-6">Experience Ultimate Luxury</h2>
                    <p class="text-gray-400 mb-8 font-light leading-relaxed">Discover our premium range of mattresses crafted for the perfect night's sleep. Limited time offers available on selected items.</p>
                    <a href="{{ route('promos') }}" class="inline-block bg-white text-brand-dark px-8 py-4 text-xs uppercase tracking-widest font-bold hover:bg-brand-gold hover:text-white transition">
                        {{ __('Explore Offers') }}
                    </a>
                </div>
                <div class="w-full md:w-1/2 h-64 md:h-full min-h-[400px] bg-cover bg-center" style="background-image: url('{{ asset('images/dummy/header.jpg') }}');">
                </div>
            </div>
        </div>
    </section>
    @endif

</div>
@endsection
