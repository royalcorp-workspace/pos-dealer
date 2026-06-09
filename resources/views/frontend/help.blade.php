@extends('frontend.layouts.app')

@section('title', 'Pusat Bantuan - IMG')

@section('content')
<div class="container mx-auto px-4 md:px-6 py-12 min-h-[60vh]">
    <div class="text-center mb-12">
        <h1 class="text-3xl md:text-4xl font-extrabold text-brand-dark mb-4 font-serif">Pusat Bantuan</h1>
        <p class="text-gray-500 max-w-2xl mx-auto">Kami siap membantu Anda. Temukan jawaban dari pertanyaan yang sering diajukan, atau hubungi tim customer service kami.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 max-w-6xl mx-auto">
        <!-- Contact Info -->
        <div>
            <h2 class="text-2xl font-bold text-brand-dark mb-6 flex items-center gap-2">
                <svg class="w-6 h-6 text-brand-gold" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.6"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><circle cx="12" cy="17" r="0.5" fill="currentColor"/></svg>
                Hubungi Kami
            </h2>
            <div class="space-y-4">
                @foreach($contacts as $contact)
                    <div class="help-contact-card flex items-center p-6 bg-white border border-brand-muted rounded-2xl hover:border-brand-gold transition-colors group cursor-pointer shadow-sm">
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
                            <p class="font-bold text-brand-dark text-lg">{{ $contact['value'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- FAQ Preview -->
        <div>
            <h2 class="text-2xl font-bold text-brand-dark mb-6 flex items-center gap-2">
                <svg class="w-6 h-6 text-brand-gold" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="M14 2v6h6M9 15h6M9 11h6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                Pertanyaan Populer
            </h2>
            <div class="space-y-3">
                @foreach($faqs as $faq)
                    <div class="help-faq-item bg-white border border-gray-100 p-5 rounded-2xl shadow-sm hover:shadow-md transition-shadow cursor-pointer flex justify-between items-center group">
                        <span class="font-semibold text-gray-700 group-hover:text-brand-dark">{{ $faq }}</span>
                        <span class="help-faq-toggle w-8 h-8 rounded-full bg-brand-light text-brand-gold-dark flex items-center justify-center group-hover:bg-brand-gold group-hover:text-white transition-colors shrink-0 ml-4">+</span>
                    </div>
                @endforeach

                <div class="mt-6 pt-4 text-center">
                    <button type="button" class="text-brand-gold-dark font-bold hover:text-brand-dark transition-colors">Lihat Semua Pertanyaan &rarr;</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

