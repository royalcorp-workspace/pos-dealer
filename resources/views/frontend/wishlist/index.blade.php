@extends('frontend.layouts.app')

@section('title', 'Wishlist')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold text-brand-dark mb-6">Wishlist Saya</h1>

    @if($wishlists->isEmpty())
        <div class="text-center py-16">
            <i class="fa-solid fa-heart w-12 h-12 text-gray-300 mb-4"></i>
            <h3 class="font-bold text-brand-dark text-lg mb-2">Wishlist Kosong</h3>
            <p class="text-gray-500 mb-4">Simpan produk favorit Anda ke wishlist untuk dibeli nanti.</p>
            <a href="{{ route('products.index') }}" class="px-6 py-2.5 bg-brand-dark text-brand-gold rounded-xl font-bold hover:bg-brand-darker transition-colors">
                Jelajahi Produk
            </a>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            @foreach($wishlists as $wishlist)
                @php($product = $wishlist->product)
                @if($product)
                    @include('frontend.components.product-card-dynamic', ['product' => $product])
                @endif
            @endforeach
        </div>
    @endif
</div>
@endsection
