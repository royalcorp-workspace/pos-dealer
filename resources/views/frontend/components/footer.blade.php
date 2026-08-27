<footer class="bg-brand-dark text-white border-t border-brand-darker font-sans mt-20">
    @php
        if (!isset($about)) {
            $about = \App\Models\Frontend\AboutUs::first();
        }
    @endphp
    <div class="container mx-auto px-6 py-16 lg:py-20">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-12 lg:gap-8">
            <!-- Brand Column -->
            <div class="space-y-6">
                <a href="{{ route('home') }}" class="text-3xl font-extrabold tracking-tight text-white flex items-center gap-2 font-serif">
                    IMG
                    <span class="text-xs font-sans tracking-widest text-brand-gold uppercase leading-tight ml-2 border-l-2 border-brand-gold pl-2">
                        International<br/>Mattress Gallery
                    </span>
                </a>
                <p class="text-brand-light/70 max-w-sm leading-relaxed">
                    {{ __('Destinasi perlengkapan tidur eksklusif. Memberikan kualitas istirahat terbaik untuk Anda dan keluarga dengan koleksi kasur premium pilihan.') }}
                </p>
            </div>

            <!-- Customer Service -->
            <div>
                <h4 class="font-bold text-brand-gold mb-6 uppercase tracking-wider text-sm">{{ __('Layanan Konsumen') }}</h4>
                <ul class="space-y-4">
                    <li><a href="{{ route('help') }}" class="text-brand-light/70 hover:text-brand-gold transition-colors font-medium">{{ __('Pusat Bantuan') }}</a></li>
                    <li><a href="{{ route('warranty') }}" class="text-brand-light/70 hover:text-brand-gold transition-colors font-medium">{{ __('Klaim Garansi') }}</a></li>
                    <li><a href="{{ route('returns') }}" class="text-brand-light/70 hover:text-brand-gold transition-colors font-medium">{{ __('Cara Pengembalian') }}</a></li>
                    <li><a href="{{ route('track-order') }}" class="text-brand-light/70 hover:text-brand-gold transition-colors font-medium">{{ __('Lacak Pemesanan') }}</a></li>
                </ul>
            </div>

            <!-- Company Info -->
            <div>
                <h4 class="font-bold text-brand-gold mb-6 uppercase tracking-wider text-sm">{{ __('Perusahaan') }}</h4>
                <ul class="space-y-4">
                    <li><a href="{{ route('about') }}" class="text-brand-light/70 hover:text-brand-gold transition-colors font-medium">{{ __('Tentang Kami') }}</a></li>
                    <li><a href="{{ route('terms') }}" class="text-brand-light/70 hover:text-brand-gold transition-colors font-medium">{{ __('Syarat Dan Ketentuan') }}</a></li>
                    <li><a href="{{ route('privacy') }}" class="text-brand-light/70 hover:text-brand-gold transition-colors font-medium">{{ __('Kebijakan Privasi') }}</a></li>
                    <li><a href="{{ route('blog') }}" class="text-brand-light/70 hover:text-brand-gold transition-colors font-medium">{{ __('Blog Dan Tips Tidur') }}</a></li>
                </ul>
            </div>

            <!-- Contact & Trust -->
            <div class="space-y-6">
                <h4 class="font-bold text-brand-gold mb-6 uppercase tracking-wider text-sm">{{ __('Hubungi Kami') }}</h4>
                <div class="space-y-4 text-brand-light/70">
                    @if($about && $about->address)
                    <div class="flex items-start gap-3">
                        <i class="fa-solid fa-location-dot w-5 h-5 text-brand-gold mt-1 flex-shrink-0"></i>
                        <span>{{ $about->address }}</span>
                    </div>
                    @endif
                    @if($about && $about->phone)
                    <div class="flex items-center gap-3">
                        <i class="fa-solid fa-phone w-5 h-5 text-brand-gold flex-shrink-0"></i>
                        <span class="font-medium">{{ $about->phone }}</span>
                    </div>
                    @endif
                    @if($about && $about->email)
                    <div class="flex items-center gap-3">
                        <i class="fa-solid fa-envelope w-5 h-5 text-brand-gold flex-shrink-0"></i>
                        <a href="mailto:{{ $about->email }}" class="hover:text-brand-gold transition-colors font-medium">{{ $about->email }}</a>
                    </div>
                    @endif
                    <div class="pt-4 border-t border-brand-gold/20 mt-4">
                        <h5 class="text-white text-sm font-bold mb-2">{{ __('Jam Operasional') }}</h5>
                        <ul class="space-y-1 text-sm">
                            <li class="flex justify-between"><span>{{ __('Senin - Jumat:') }}</span> <span>08:00 - 17:00</span></li>
                            <li class="flex justify-between"><span>{{ __('Sabtu:') }}</span> <span>08:00 - 14:00</span></li>
                            <li class="flex justify-between"><span>{{ __('Minggu / Libur:') }}</span> <span class="text-red-400">{{ __('Tutup') }}</span></li>
                        </ul>
                    </div>
                </div>
                
                <div class="pt-4">
                    <h4 class="font-bold text-brand-gold mb-4 uppercase tracking-wider text-sm">{{ __('Metode Pembayaran') }}</h4>
                    <div class="flex flex-wrap gap-2">
                        @php
                            $paymentMethods = \App\Models\PaymentMethod::active()->orderBy('sort_order')->get();
                        @endphp
                        @forelse($paymentMethods as $method)
                            @if($method->image)
                                <div class="w-12 h-8 bg-white border border-transparent rounded flex items-center justify-center p-1" title="{{ $method->name }}">
                                    <img src="{{ cms_asset($method->image) }}" alt="{{ $method->name }}" class="max-h-full max-w-full object-contain">
                                </div>
                            @else
                                <div class="w-12 h-8 bg-white border border-transparent rounded flex items-center justify-center text-[9px] font-bold text-brand-dark px-1 text-center" title="{{ $method->name }}">
                                    {{ $method->code ?? $method->name }}
                                </div>
                            @endif
                        @empty
                            <div class="w-12 h-8 bg-white border border-transparent rounded flex items-center justify-center text-[10px] font-bold text-brand-dark">BCA</div>
                            <div class="w-12 h-8 bg-white border border-transparent rounded flex items-center justify-center text-[10px] font-bold text-brand-dark">Mandiri</div>
                            <div class="w-12 h-8 bg-white border border-transparent rounded flex items-center justify-center text-[10px] font-bold text-brand-dark">Visa</div>
                            <div class="w-12 h-8 bg-white border border-transparent rounded flex items-center justify-center text-[10px] font-bold text-brand-dark">Master</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Ikuti Kami (Social Media per Brand) -->
            <div class="space-y-6">
                <h4 class="font-bold text-brand-gold mb-6 uppercase tracking-wider text-sm">{{ __('Ikuti Kami') }}</h4>
                <div class="space-y-4">
                    @php
                        $brandsForFooter = \App\Models\Frontend\ProductsCatalog\Brand::where('status', true)->where('deleted', false)->orderBy('sort_order', 'asc')->get();
                        $socialIcons = [
                            'facebook' => ['icon' => 'fa-brands fa-facebook-f', 'aria' => 'Facebook'],
                            'instagram' => ['icon' => 'fa-brands fa-instagram', 'aria' => 'Instagram'],
                            'twitter' => ['icon' => 'fa-brands fa-x-twitter', 'aria' => 'Twitter'],
                            'x' => ['icon' => 'fa-brands fa-x-twitter', 'aria' => 'X'],
                            'linkedin' => ['icon' => 'fa-brands fa-linkedin-in', 'aria' => 'LinkedIn'],
                            'youtube' => ['icon' => 'fa-brands fa-youtube', 'aria' => 'YouTube'],
                            'tiktok' => ['icon' => 'fa-brands fa-tiktok', 'aria' => 'TikTok'],
                            'whatsapp' => ['icon' => 'fa-brands fa-whatsapp', 'aria' => 'WhatsApp'],
                        ];
                    @endphp

                    <!-- Corporate IMG Socials -->
                    <div class="mb-4">
                        <span class="text-brand-light/90 font-bold block mb-2 text-sm">IMG</span>
                        <div class="flex gap-2">
                            @if($about && $about->social_media)
                                @foreach($about->social_media as $platform => $url)
                                    @if(!empty($url) && isset($socialIcons[$platform]))
                                        <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="w-8 h-8 rounded-full bg-brand-darker border border-brand-gold/30 flex items-center justify-center text-brand-gold hover:text-white hover:bg-[#c09d6b] hover:border-[#c09d6b] transition-all">
                                            <i class="{{ $socialIcons[$platform]['icon'] }} text-sm"></i>
                                        </a>
                                    @endif
                                @endforeach
                            @endif
                        </div>
                    </div>

                    <!-- Brands Socials -->
                    @foreach($brandsForFooter as $brand)
                        @php
                            $safeName = \Illuminate\Support\Str::slug($brand->name);
                            $fb = $about->social_media[$safeName . '_facebook'] ?? null;
                            $ig = $about->social_media[$safeName . '_instagram'] ?? null;
                            $tk = $about->social_media[$safeName . '_tiktok'] ?? null;
                            
                            if (empty($fb) && empty($ig) && empty($tk)) continue;
                        @endphp
                        <div class="mb-4">
                            <span class="text-brand-light/90 font-bold block mb-2 text-sm">{{ $brand->name }}</span>
                            <div class="flex gap-2">
                                @if(!empty($fb))
                                    <a href="{{ $fb }}" target="_blank" rel="noopener noreferrer" class="w-8 h-8 rounded-full bg-brand-darker border border-brand-gold/30 flex items-center justify-center text-brand-gold hover:text-white hover:bg-[#c09d6b] transition-all">
                                        <i class="fa-brands fa-facebook-f text-sm"></i>
                                    </a>
                                @endif
                                @if(!empty($ig))
                                    <a href="{{ $ig }}" target="_blank" rel="noopener noreferrer" class="w-8 h-8 rounded-full bg-brand-darker border border-brand-gold/30 flex items-center justify-center text-brand-gold hover:text-white hover:bg-[#c09d6b] transition-all">
                                        <i class="fa-brands fa-instagram text-sm"></i>
                                    </a>
                                @endif
                                @if(!empty($tk))
                                    <a href="{{ $tk }}" target="_blank" rel="noopener noreferrer" class="w-8 h-8 rounded-full bg-brand-darker border border-brand-gold/30 flex items-center justify-center text-brand-gold hover:text-white hover:bg-[#c09d6b] transition-all">
                                        <i class="fa-brands fa-tiktok text-sm"></i>
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                <!-- Download App -->
                    <div class="mt-8 pt-6 border-t border-brand-gold/20">
                        <h4 class="font-bold text-brand-gold mb-4 uppercase tracking-wider text-sm">{{ __('Download Aplikasi') }}</h4>
                        <a href="#" class="inline-block transition-transform duration-300 hover:scale-105">
                            <img src="https://play.google.com/intl/en_us/badges/static/images/badges/id_badge_web_generic.png" alt="Get it on Google Play" class="h-12 w-auto">
                        </a>
                    </div>
</div>
            </div>
        </div>
        
        <div class="border-t border-brand-gold/20 mt-16 pt-8 flex flex-col md:flex-row items-center justify-between gap-4">
            <p class="text-brand-light/50 text-sm font-medium">&copy; {{ date('Y') }} IMG (International Mattress Gallery). {{ __('Hak Cipta Dilindungi.') }}</p>
            <div class="flex gap-2 text-brand-light/50 text-sm">
                <span>{{ __('Didesain dengan ❤️ untuk tidur yang lebih baik.') }}</span>
            </div>
        </div>
    </div>
</footer>
