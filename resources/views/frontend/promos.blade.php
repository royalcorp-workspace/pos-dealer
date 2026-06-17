@extends('frontend.layouts.app')

@section('title', 'Promo Spesial - IMG')
@section('meta_description', 'Dapatkan promo kasur, springbed, dan perlengkapan tidur premium di IMG. Nikmati diskon, cashback, dan gratis ongkir untuk kenyamanan tidur Anda.')
@section('canonical', route('promos'))

@section('content')
    @php
        $offerItems = collect($promos)->map(function ($promo, $index) {
            return [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'item' => [
                    '@type' => 'Offer',
                    'name' => $promo['title'],
                    'description' => $promo['desc'],
                    'url' => route('promos'),
                    'availability' => 'https://schema.org/InStock',
                    'areaServed' => [
                        '@type' => 'Place',
                        'name' => 'Indonesia',
                    ],
                ],
            ];
        })->values()->toArray();

        $offerCatalogSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'OfferCatalog',
            '@id' => route('promos') . '#offers',
            'name' => 'Promo Spesial IMG',
            'description' => 'Kumpulan promo kasur, springbed, dan perlengkapan tidur premium di International Mattress Gallery.',
            'url' => route('promos'),
            'itemListElement' => $offerItems,
        ];

        $promoBreadcrumbSchema = [
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
                    'name' => 'Promo Spesial',
                ],
            ],
        ];
    @endphp

    @push('jsonld')
        <script type="application/ld+json">
        @json($offerCatalogSchema)
        </script>
        <script type="application/ld+json">
        @json($promoBreadcrumbSchema)
        </script>
    @endpush

        <div class="container mx-auto px-4 md:px-6 py-12 min-h-[60vh]" x-data="{ copiedCode: null }">
            <div class="text-center mb-12">
                <h1 class="text-3xl md:text-4xl font-extrabold text-brand-dark mb-4 font-serif">Promo Spesial</h1>
                <p class="text-gray-500 max-w-2xl mx-auto">Nikmati berbagai penawaran eksklusif dan voucher diskon yang bisa Anda gunakan hari ini.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 max-w-5xl mx-auto">
                @foreach($promos as $promo)
                    <div class="bg-white border text-center border-brand-muted hover:border-brand-gold hover:shadow-lg transition-all rounded-3xl p-8 relative overflow-hidden group">
                        <div class="absolute top-0 left-0 w-full h-1 bg-brand-gold"></div>

                        <div class="w-16 h-16 bg-brand-light rounded-full flex items-center justify-center text-brand-gold-dark mx-auto mb-6 group-hover:scale-110 transition-transform">
                            <i class="fa-solid fa-ticket w-8 h-8"></i>
                        </div>

                        <h3 class="text-xl font-bold text-brand-dark mb-3">{{ $promo['title'] }}</h3>
                        <p class="text-gray-500 text-sm mb-6 pb-6 border-b border-gray-100">{{ $promo['desc'] }}</p>

                        <div class="flex flex-col gap-3">
                            <div class="bg-brand-light border border-dashed border-brand-gold/50 rounded-xl py-3 px-4 flex justify-center items-center">
                                <span class="font-mono font-bold tracking-widest text-brand-dark">{{ $promo['code'] }}</span>
                            </div>

                            <div class="flex items-center justify-center gap-1.5 text-xs font-semibold text-red-500">
                                <i class="fa-regular fa-clock w-3.5 h-3.5"></i>
                                Sisa: {{ $promo['expiry'] }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
@endsection

