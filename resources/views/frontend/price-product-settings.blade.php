@extends('frontend.layouts.app', ['title' => 'Price Product Settings'])

@section('content')
    <div class="min-h-screen bg-gray-50 py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-2xl font-bold text-brand-dark mb-6">Promo Produk</h1>

            @if($featured->isNotEmpty())
                <div class="mb-8">
                    <h2 class="text-lg font-semibold text-brand-dark mb-4">Promo Unggulan</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($featured as $item)
                            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                                @if($item->image_url)
                                    <img src="{{ $item->image_url }}" alt="{{ $item->title }}" class="w-full h-40 object-cover">
                                @endif
                                <div class="p-4">
                                    <h3 class="font-bold text-brand-dark mb-2">{{ $item->title }}</h3>
                                    <p class="text-sm text-gray-600 mb-3">{{ Str::limit($item->description, 100) }}</p>
                                    <a href="{{ route('price-product-settings.show', $item->code) }}" 
                                    class="text-brand-gold hover:text-brand-gold-dark font-semibold text-sm">
                                        Lihat Produk
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div>
                <h2 class="text-lg font-semibold text-brand-dark mb-4">Semua Promo</h2>
                @if($active->isNotEmpty())
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($active as $item)
                            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                                @if($item->image_url)
                                    <img src="{{ $item->image_url }}" alt="{{ $item->title }}" class="w-full h-40 object-cover">
                                @endif
                                <div class="p-4">
                                    <h3 class="font-bold text-brand-dark mb-2">{{ $item->title }}</h3>
                                    <p class="text-sm text-gray-600 mb-3">{{ Str::limit($item->description, 100) }}</p>
                                    <a href="{{ route('price-product-settings.show', $item->code) }}" 
                                    class="text-brand-gold hover:text-brand-gold-dark font-semibold text-sm">
                                        Lihat Produk
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-6">
                        {{ $active->links() }}
                    </div>
                @else
                    <p class="text-gray-500">Belum ada promo tersedia.</p>
                @endif
            </div>
        </div>
    </div>
@endsection