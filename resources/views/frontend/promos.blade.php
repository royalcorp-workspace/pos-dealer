@extends('frontend.layouts.app')

@section('title', __('Promo Spesial') . ' - IMG')
@section('meta_description', __('Dapatkan promo kasur, springbed, dan perlengkapan tidur premium di IMG. Nikmati diskon dan gratis ongkir untuk kenyamanan tidur Anda.'))
@section('canonical', route('promos'))

@section('content')
    @php
        $offerItems = collect($promos)->map(function ($promo, $index) {
            return [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'item' => [
                    '@type' => 'Offer',
                    'name' => $promo->title,
                    'description' => $promo->description,
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
                <h1 class="text-3xl md:text-4xl font-extrabold text-brand-dark mb-4 font-serif">{{ __('Promo Spesial') }}</h1>
                <p class="text-gray-500 max-w-2xl mx-auto">{{ __('Nikmati berbagai penawaran eksklusif dan voucher diskon yang bisa Anda gunakan hari ini.') }}</p>
            </div>

            @if($promos->isEmpty())
                <div class="text-center text-gray-500 py-12">
                    <p>{{ __('Belum ada promo aktif saat ini. Silakan cek kembali nanti.') }}</p>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 max-w-5xl mx-auto">
                    @foreach($promos as $promo)
                        @php
                            $now = now();
                            $endDate = $promo->end_date;
                            $isExpired = $endDate && $endDate->isPast();
                            $daysLeft = $endDate ? $now->diffInDays($endDate, false) : null;
                            $daysLeftAbs = $daysLeft !== null ? abs((int) $daysLeft) : null;

                            if ($endDate === null) {
                                $expiryText = __('Berlaku Selamanya');
                            } elseif ($isExpired) {
                                $expiryText = __('Berakhir');
                            } elseif ($daysLeftAbs === 0) {
                                $expiryText = __('Hari Ini');
                            } else {
                                $expiryText = $daysLeftAbs . ' ' . __('Hari Lagi');
                            }

                            $discountLabel = match ((int) $promo->type) {
                                1 => __('Persentase'),
                                2 => __('Nominal'),
                                3 => __('Gratis Ongkir'),
                                4 => __('Bonus Produk'),
                                default => __('Voucher'),
                            };

                            $discountSuffix = match ((int) $promo->type) {
                                1 => '%',
                                2 => '',
                                3 => '',
                                4 => ' pcs',
                                default => '',
                            };

                            if ($promo->value > 0) {
                                $promoDisplay = $discountSuffix === '%'
                                    ? number_format((float) $promo->value, 0) . '%'
                                    : ($discountSuffix === ''
                                        ? 'Rp ' . number_format((float) $promo->value, 0, ',', '.')
                                        : (string) (int) $promo->value . ' ' . $discountSuffix);
                            } else {
                                $promoDisplay = __('Voucher');
                            }
                        @endphp

                        <div class="bg-white border text-center {{ $isExpired ? 'opacity-60 border-gray-200' : 'border-brand-muted hover:border-brand-gold hover:shadow-lg' }} transition-all rounded-3xl p-8 relative overflow-hidden group">
                            @if(!$isExpired)
                                <div class="absolute top-0 left-0 w-full h-1 bg-brand-gold"></div>
                            @endif

                            <div class="w-16 h-16 bg-brand-light rounded-full flex items-center justify-center text-brand-gold-dark mx-auto mb-6 group-hover:scale-110 transition-transform">
                                <i class="fa-solid fa-ticket w-8 h-8"></i>
                            </div>

                            <h3 class="text-xl font-bold text-brand-dark mb-3">{{ $promo->title }}</h3>
                            <p class="text-gray-500 text-sm mb-6 pb-6 border-b border-gray-100">{{ $promo->description }}</p>

                            <div class="flex flex-col gap-3">
                                <div class="bg-brand-light border border-dashed border-brand-gold/50 rounded-xl py-3 px-4 flex justify-center items-center">
                                    <span class="font-mono font-bold tracking-widest text-brand-dark">{{ $promo->code }}</span>
                                </div>

                                <div class="flex items-center justify-center gap-1.5 text-xs font-semibold {{ $isExpired ? 'text-gray-400' : 'text-red-500' }}">
                                    <i class="fa-regular fa-clock w-3.5 h-3.5"></i>
                                    {{ __('Sisa:') }} {{ $expiryText }}
                                </div>

                                @if($promo->value > 0)
                                    <div class="text-xs text-gray-500">
                                        {{ $discountLabel }} {{ $promoDisplay }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
@endsection
