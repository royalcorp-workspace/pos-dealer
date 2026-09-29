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

                            <h3 class="text-xl font-bold text-brand-dark mb-1">{{ $promo->title }}</h3>
                            <div class="flex items-center justify-center gap-1.5 mb-3 flex-wrap">
                                <span class="inline-flex items-center rounded-md bg-brand-light px-2 py-0.5 text-[10px] font-bold text-brand-gold-dark border border-brand-muted">
                                    {{ $promo->scopeLabel() }}
                                </span>
                                @if($promo->require_follow)
                                    <span class="inline-flex items-center rounded-md bg-purple-50 text-purple-700 border border-purple-200 px-2 py-0.5 text-[10px] font-bold">
                                        Wajib Ikuti Toko
                                    </span>
                                @endif
                                @if((float)($promo->min_purchase ?? 0) > 0)
                                    <span class="inline-flex items-center rounded-md bg-amber-50 text-amber-800 border border-amber-200 px-2 py-0.5 text-[10px] font-bold">
                                        Min. Rp {{ number_format($promo->min_purchase, 0, ',', '.') }}
                                    </span>
                                @endif
                            </div>
                            <p class="text-gray-500 text-sm mb-4 pb-4 border-b border-gray-100">{{ $promo->description }}</p>

                            <div class="flex flex-col gap-3">
                                <div class="bg-brand-light border border-dashed border-brand-gold/50 rounded-xl py-3 px-4 flex justify-between items-center">
                                    <span class="font-mono font-bold tracking-widest text-brand-dark">{{ $promo->code }}</span>
                                    <button type="button" @click="navigator.clipboard.writeText('{{ $promo->code }}'); copiedCode = '{{ $promo->code }}'; setTimeout(() => copiedCode = null, 2000)" class="text-xs font-semibold text-brand-gold-dark hover:underline">
                                        <span x-text="copiedCode === '{{ $promo->code }}' ? 'Tersalin!' : 'Salin'"></span>
                                    </button>
                                </div>

                                @if($promo->visibility === 'claimable')
                                    @if($promo->is_claimed)
                                        <button type="button" disabled class="w-full py-2.5 bg-emerald-50 text-emerald-700 border border-emerald-200 font-bold rounded-xl text-xs flex items-center justify-center gap-1 cursor-default">
                                            <i class="fa-solid fa-check"></i> Sudah Diklaim
                                        </button>
                                    @else
                                        <button type="button" onclick="claimVoucher('{{ $promo->id }}', this)" class="w-full py-2.5 bg-brand-gold text-brand-dark hover:bg-brand-light font-bold rounded-xl transition-all text-xs flex items-center justify-center gap-1.5 shadow-sm">
                                            <i class="fa-solid fa-gift"></i> Klaim Voucher
                                        </button>
                                    @endif
                                @endif

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

@push('scripts')
<script>
function claimVoucher(voucherId, btn) {
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Mengklaim...';

    fetch('{{ route("voucher.claim") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
        },
        body: JSON.stringify({ voucher_id: voucherId })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            btn.className = 'w-full py-2.5 bg-emerald-50 text-emerald-700 border border-emerald-200 font-bold rounded-xl text-xs flex items-center justify-center gap-1 cursor-default';
            btn.innerHTML = '<i class="fa-solid fa-check"></i> Berhasil Diklaim!';
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: data.message,
                    timer: 2000,
                    showConfirmButton: false
                });
            }
        } else {
            btn.disabled = false;
            btn.innerHTML = originalText;
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Pemberitahuan',
                    text: data.message,
                    confirmButtonColor: '#1e3a8a',
                    confirmButtonText: 'Mengerti'
                });
            } else {
                window.dispatchEvent(new CustomEvent('show-toast', { detail: { type: 'warning', message: data.message } }));
            }
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: 'Terjadi kesalahan saat mengklaim voucher.',
                confirmButtonColor: '#1e3a8a',
                confirmButtonText: 'Tutup'
            });
        } else {
            window.dispatchEvent(new CustomEvent('show-toast', { detail: { type: 'error', message: 'Terjadi kesalahan saat mengklaim voucher.' } }));
        }
    });
}
</script>
@endpush

