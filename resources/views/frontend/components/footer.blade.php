<footer class="bg-brand-dark text-white border-t border-brand-darker font-sans mt-20">
    <div class="container mx-auto px-6 py-16 lg:py-20">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 lg:gap-8">
            <!-- Brand Column -->
            <div class="space-y-6">
                <a href="{{ route('home') }}" class="text-3xl font-extrabold tracking-tight text-white flex items-center gap-2 font-serif">
                    IMG
                    <span class="text-xs font-sans tracking-widest text-brand-gold uppercase leading-tight ml-2 border-l-2 border-brand-gold pl-2">
                        International<br/>Mattress Gallery
                    </span>
                </a>
                <p class="text-brand-light/70 max-w-sm leading-relaxed">
                    Toko kasur dan perlengkapan tidur terpercaya. Memberikan kualitas istirahat terbaik untuk Anda dan keluarga.
                </p>
                <div class="flex gap-4">
                    <a href="#" class="footer-social w-10 h-10 rounded-full bg-brand-darker border border-brand-gold/30 flex items-center justify-center text-brand-gold hover:text-white hover:bg-[#c09d6b] hover:border-[#c09d6b] hover:shadow-md transition-all">
                        <i class="fa-brands fa-facebook-f w-5 h-5 footer-social-icon"></i>
                    </a>
                    <a href="#" class="footer-social w-10 h-10 rounded-full bg-brand-darker border border-brand-gold/30 flex items-center justify-center text-brand-gold hover:text-white hover:bg-[#c09d6b] hover:border-[#c09d6b] hover:shadow-md transition-all">
                        <i class="fa-brands fa-instagram w-5 h-5 footer-social-icon"></i>
                    </a>
                    <a href="#" class="footer-social w-10 h-10 rounded-full bg-brand-darker border border-brand-gold/30 flex items-center justify-center text-brand-gold hover:text-white hover:bg-[#c09d6b] hover:border-[#c09d6b] hover:shadow-md transition-all">
                        <i class="fa-brands fa-x-twitter w-5 h-5 footer-social-icon"></i>
                    </a>
                </div>
            </div>

            <!-- Customer Service -->
            <div>
                <h4 class="font-bold text-brand-gold mb-6 uppercase tracking-wider text-sm">Layanan Konsumen</h4>
                <ul class="space-y-4">
                    <li><a href="{{ route('help') }}" class="text-brand-light/70 hover:text-brand-gold transition-colors font-medium">Pusat Bantuan</a></li>
                    <li><a href="{{ route('help') }}" class="text-brand-light/70 hover:text-brand-gold transition-colors font-medium">Hubungi Kami</a></li>
                    <li><a href="#" class="text-brand-light/70 hover:text-brand-gold transition-colors font-medium">Informasi Pengiriman</a></li>
                    <li><a href="#" class="text-brand-light/70 hover:text-brand-gold transition-colors font-medium">Klaim Garansi</a></li>
                </ul>
            </div>

            <!-- Company Info -->
            <div>
                <h4 class="font-bold text-brand-gold mb-6 uppercase tracking-wider text-sm">Perusahaan</h4>
                <ul class="space-y-4">
                    <li><a href="#" class="text-brand-light/70 hover:text-brand-gold transition-colors font-medium">Tentang Kami</a></li>
                    <li><a href="#" class="text-brand-light/70 hover:text-brand-gold transition-colors font-medium">Syarat &amp; Ketentuan</a></li>
                    <li><a href="#" class="text-brand-light/70 hover:text-brand-gold transition-colors font-medium">Kebijakan Privasi</a></li>
                    <li><a href="{{ route('blog') }}" class="text-brand-light/70 hover:text-brand-gold transition-colors font-medium">Blog &amp; Tips Tidur</a></li>
                </ul>
            </div>

            <!-- Contact & Trust -->
            <div class="space-y-6">
                <h4 class="font-bold text-brand-gold mb-6 uppercase tracking-wider text-sm">Hubungi Kami</h4>
                <div class="space-y-4 text-brand-light/70">
                    <div class="flex items-start gap-3">
                        <i class="fa-solid fa-location-dot w-5 h-5 text-brand-gold mt-1 flex-shrink-0"></i>
                        <span>Jl. Tidur Nyenyak No. 99, Jakarta Selatan, 12345</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <i class="fa-solid fa-phone w-5 h-5 text-brand-gold flex-shrink-0"></i>
                        <span class="font-medium">0812-3456-7890</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <i class="fa-solid fa-envelope w-5 h-5 text-brand-gold flex-shrink-0"></i>
                        <span class="font-medium">halo@img.id</span>
                    </div>
                </div>
                
                <div class="pt-4">
                    <h4 class="font-bold text-brand-gold mb-4 uppercase tracking-wider text-sm">Metode Pembayaran</h4>
                    <div class="flex flex-wrap gap-2">
                        <div class="w-12 h-8 bg-white border border-transparent rounded flex items-center justify-center text-[10px] font-bold text-brand-dark">BCA</div>
                        <div class="w-12 h-8 bg-white border border-transparent rounded flex items-center justify-center text-[10px] font-bold text-brand-dark">Mandiri</div>
                        <div class="w-12 h-8 bg-white border border-transparent rounded flex items-center justify-center text-[10px] font-bold text-brand-dark">Visa</div>
                        <div class="w-12 h-8 bg-white border border-transparent rounded flex items-center justify-center text-[10px] font-bold text-brand-dark">Master</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="border-t border-brand-gold/20 mt-16 pt-8 flex flex-col md:flex-row items-center justify-between gap-4">
            <p class="text-brand-light/50 text-sm font-medium">&copy; {{ date('Y') }} IMG (International Mattress Gallery). Hak Cipta Dilindungi.</p>
            <div class="flex gap-2 text-brand-light/50 text-sm">
                <span>Didesain dengan ❤️ untuk tidur yang lebih baik.</span>
            </div>
        </div>
    </div>
</footer>
