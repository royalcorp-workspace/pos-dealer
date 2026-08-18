@extends('frontend.layouts.app')

@section('title', __('Pusat Bantuan') . ' - IMG')
@section('meta_description', __('Pusat bantuan IMG untuk pertanyaan seputar garansi, pengiriman, pembayaran, tukar tambah kasur, dan kontak layanan pelanggan.'))
@section('canonical', route('help'))

@section('content')
    @php
        $phoneContact = collect($contacts)->firstWhere('icon', 'phone');
        $emailContact = collect($contacts)->firstWhere('icon', 'mail');
        $faqSchema = collect($faqs)->map(function ($faq) {
            return [
                '@type' => 'Question',
                'name' => $faq['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $faq['answer'],
                ],
            ];
        })->values()->toArray();

        $faqPageSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            '@id' => route('help') . '#faq',
            'name' => 'Pusat Bantuan IMG',
            'mainEntity' => $faqSchema,
        ];

        $helpContactSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'ContactPoint',
            'contactType' => 'customer service',
            'telephone' => $phoneContact['value'] ?? '+62-811-1234-5678',
            'email' => $emailContact['value'] ?? 'support@img.co.id',
            'areaServed' => 'ID',
            'availableLanguage' => ['Indonesian'],
        ];

        $helpBreadcrumbSchema = [
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
                    'name' => 'Pusat Bantuan',
                ],
            ],
        ];
    @endphp

    @push('jsonld')
        <script type="application/ld+json">
        @json($faqPageSchema)
        </script>
        <script type="application/ld+json">
        @json($helpContactSchema)
        </script>
        <script type="application/ld+json">
        @json($helpBreadcrumbSchema)
        </script>
    @endpush

<div class="container mx-auto px-4 md:px-6 py-12 min-h-[60vh]">
    <div class="text-center mb-12">
        <h1 class="text-3xl md:text-4xl font-extrabold text-brand-dark mb-4 font-serif">{{ __('Pusat Bantuan') }}</h1>
        <p class="text-gray-500 max-w-2xl mx-auto">{{ __('Kami siap membantu Anda. Temukan jawaban dari pertanyaan yang sering diajukan, atau hubungi tim customer service kami.') }}</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 max-w-6xl mx-auto">
        <!-- Contact Info -->
        <div>
            <h2 class="text-2xl font-bold text-brand-dark mb-6 flex items-center gap-2">
                <svg class="w-6 h-6 text-brand-gold" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.6"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><circle cx="12" cy="17" r="0.5" fill="currentColor"/></svg>
                {{ __('Hubungi Kami') }}
            </h2>
            <div class="space-y-4">
                @foreach($contacts as $contact)
                    @php
                        $contactUrl = $contact['icon'] === 'phone' ? 'tel:' . preg_replace('/[^0-9+]/', '', $contact['value']) : ($contact['icon'] === 'mail' ? 'mailto:' . $contact['value'] : 'https://wa.me/' . preg_replace('/[^0-9]/', '', $contact['value']));
                    @endphp
                    <a href="{{ $contactUrl }}" class="help-contact-card flex items-center p-6 bg-white border border-brand-muted rounded-2xl hover:border-brand-gold transition-colors group shadow-sm">
                        <div class="help-contact-icon w-12 h-12 bg-brand-light rounded-full flex items-center justify-center text-brand-gold group-hover:bg-brand-gold group-hover:text-white transition-colors mr-4 shrink-0">
                            @if($contact['icon'] === 'phone')
                                <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            @elseif($contact['icon'] === 'mail')
                                <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="2" y="4" width="20" height="16" rx="2" stroke="currentColor" stroke-width="1.6"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                            @else
                                <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            @endif
                        </div>
                        <div>
                            <h4 class="text-sm text-gray-500 mb-1">{{ $contact['label'] }}</h4>
                            <p class="text-lg font-bold text-brand-dark group-hover:text-brand-gold transition-colors">{{ $contact['value'] }}</p>
                        </div>
                    </a>
                @endforeach
            </div>

            <!-- Jam Operasional -->
            <div class="mt-8 p-6 bg-brand-light/30 border border-brand-muted rounded-2xl">
                <h3 class="text-lg font-bold text-brand-dark mb-4 flex items-center gap-2">
                    <i class="fa-regular fa-clock text-brand-gold"></i>
                    {{ __('Jam Operasional') }}
                </h3>
                <ul class="space-y-3 text-gray-600">
                    <li class="flex justify-between items-center border-b border-gray-100 pb-2">
                        <span>{{ __('Senin - Jumat') }}</span> 
                        <span class="font-bold text-brand-dark">08:00 - 17:00 WIB</span>
                    </li>
                    <li class="flex justify-between items-center border-b border-gray-100 pb-2">
                        <span>{{ __('Sabtu') }}</span> 
                        <span class="font-bold text-brand-dark">08:00 - 14:00 WIB</span>
                    </li>
                    <li class="flex justify-between items-center">
                        <span>{{ __('Minggu & Hari Libur') }}</span> 
                        <span class="font-bold text-red-500">{{ __('Tutup') }}</span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- FAQ Preview -->
        <div>
            <h2 class="text-2xl font-bold text-brand-dark mb-6 flex items-center gap-2">
                <svg class="w-6 h-6 text-brand-gold" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="M14 2v6h6M9 15h6M9 11h6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                {{ __('Pertanyaan Populer') }}
            </h2>
            <div class="space-y-3">
                @foreach($faqs as $faq)
                    <div class="help-faq-item bg-white border border-gray-100 p-5 rounded-2xl shadow-sm hover:shadow-md transition-shadow group cursor-pointer">
                        <div class="flex justify-between items-start gap-4">
                            <div class="flex-1">
                                <span class="font-semibold text-gray-700 group-hover:text-brand-dark block">{{ $faq['question'] }}</span>
                                <div class="help-faq-answer hidden mt-3 pt-3 border-t border-gray-100">
                                    <p class="text-sm text-gray-500 leading-relaxed">{{ $faq['answer'] }}</p>
                                </div>
                            </div>
                            <span class="help-faq-toggle w-8 h-8 rounded-full bg-brand-light text-brand-gold-dark flex items-center justify-center group-hover:bg-brand-gold group-hover:text-white transition-colors shrink-0 ml-4 font-bold text-lg">+</span>
                        </div>
                    </div>
                @endforeach

                <div class="mt-6 pt-4 text-center">
                    <button type="button" class="text-brand-gold-dark font-bold hover:text-brand-dark transition-colors">{!! __('Lihat Semua Pertanyaan &rarr;') !!}</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const faqItems = document.querySelectorAll('.help-faq-item');
        faqItems.forEach(item => {
            item.addEventListener('click', function () {
                const answer = this.querySelector('.help-faq-answer');
                const toggle = this.querySelector('.help-faq-toggle');
                
                const isExpanded = !answer.classList.contains('hidden');
                
                // Close all items
                faqItems.forEach(otherItem => {
                    otherItem.querySelector('.help-faq-answer').classList.add('hidden');
                    otherItem.querySelector('.help-faq-toggle').textContent = '+';
                });
                
                // If the clicked item was not expanded, expand it
                if (!isExpanded) {
                    answer.classList.remove('hidden');
                    toggle.textContent = '-';
                }
            });
        });
    });
</script>
@endpush

