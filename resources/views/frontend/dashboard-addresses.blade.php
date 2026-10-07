<!-- Address Modal -->
<div id="address-modal" class="fixed inset-0 bg-black/60 backdrop-blur-xs hidden z-50 overflow-y-auto p-3 sm:p-4 md:p-6 transition-all duration-200">
    <div class="min-h-full flex items-center justify-center py-2 sm:py-6" onclick="if(event.target === this) closeAddressModal()">
        <div class="bg-white rounded-2xl sm:rounded-3xl w-full max-w-lg sm:max-w-xl mx-auto shadow-2xl border border-gray-100 overflow-hidden flex flex-col max-h-[calc(100vh-2rem)] sm:max-h-[calc(100vh-3.5rem)] my-auto transition-all animate-in fade-in zoom-in-95 duration-200">
            <!-- Modal Header (Fixed / Sticky at Top) -->
            <div class="flex items-center justify-between p-4 sm:p-6 border-b border-gray-100 bg-white shrink-0">
                <div class="flex items-center gap-2.5 min-w-0">
                    <div class="w-8 h-8 rounded-lg bg-brand-gold/15 text-brand-gold-dark flex items-center justify-center text-sm font-bold shrink-0">
                        <i class="fa-solid fa-location-dot"></i>
                    </div>
                    <h3 class="font-extrabold text-base sm:text-lg text-brand-dark truncate" id="modal-title">Tambah Alamat Baru</h3>
                </div>
                <button type="button" onclick="closeAddressModal()" class="w-8 h-8 rounded-full text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex items-center justify-center transition-colors cursor-pointer shrink-0 ml-2" aria-label="Tutup modal">
                    <i class="fa-solid fa-xmark text-base"></i>
                </button>
            </div>

            <!-- Form with scrollable body and fixed footer -->
            <form method="POST" id="address-form" action="{{ route('dashboard.addresses.store') }}" data-route-store="{{ route('dashboard.addresses.store') }}" data-route-update-base="{{ url('/dashboard/addresses') }}" class="flex flex-col flex-1 min-h-0">
                @csrf
                <div id="method-container"></div>
                <input type="hidden" name="id" id="address-id">

                <!-- Scrollable Form Body -->
                <div class="p-4 sm:p-6 overflow-y-auto flex-1 space-y-4 scrollbar-thin">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">
                            Label Alamat <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="label" id="address-label" required placeholder="Contoh: Rumah, Kantor, Apartemen" class="w-full px-4 py-2.5 bg-gray-50/50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-brand-gold focus:ring-2 focus:ring-brand-gold/20 focus:bg-white transition-all">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">
                                Nama Penerima <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="recipient_name" id="address-recipient" required placeholder="Nama lengkap penerima" class="w-full px-4 py-2.5 bg-gray-50/50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-brand-gold focus:ring-2 focus:ring-brand-gold/20 focus:bg-white transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">
                                Nomor Telepon / WA <span class="text-red-500">*</span>
                            </label>
                            <input type="tel" name="phone" id="address-phone" required maxlength="16" pattern="^(62|0)[0-9]{8,14}$" placeholder="08xx xxxx xxxx / 628xx" class="w-full px-4 py-2.5 bg-gray-50/50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-brand-gold focus:ring-2 focus:ring-brand-gold/20 focus:bg-white transition-all">
                        </div>
                    </div>

                    <!-- Cascading Location Selector: Provinsi -> Kota -> Kecamatan -->
                    <div class="space-y-3 p-3.5 sm:p-4 bg-gray-50/80 rounded-2xl border border-gray-200/80">
                        <span class="text-xs font-bold text-gray-700 uppercase tracking-wider block">Wilayah Pengiriman</span>

                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Provinsi <span class="text-red-500">*</span></label>
                            <select id="address-province-select" class="w-full px-3.5 py-2.5 bg-white border border-gray-200 rounded-xl text-xs sm:text-sm focus:outline-none focus:border-brand-gold focus:ring-2 focus:ring-brand-gold/20 transition-all">
                                <option value="">-- Pilih Provinsi --</option>
                                @foreach($provinces ?? \App\Models\Frontend\Location\Province::orderBy('name')->get() as $prov)
                                    <option value="{{ $prov->id }}">{{ $prov->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Kota / Kabupaten <span class="text-red-500">*</span></label>
                            <select id="address-city-select" disabled class="w-full px-3.5 py-2.5 bg-white border border-gray-200 rounded-xl text-xs sm:text-sm focus:outline-none focus:border-brand-gold focus:ring-2 focus:ring-brand-gold/20 transition-all disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed">
                                <option value="">Pilih Provinsi Terlebih Dahulu</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Kecamatan / Kelurahan <span class="text-red-500">*</span></label>
                            <select name="sub_district_id" id="address-sub-district-select" required disabled class="w-full px-3.5 py-2.5 bg-white border border-gray-200 rounded-xl text-xs sm:text-sm focus:outline-none focus:border-brand-gold focus:ring-2 focus:ring-brand-gold/20 transition-all disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed">
                                <option value="">Pilih Kota Terlebih Dahulu</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">
                            Alamat Lengkap <span class="text-red-500">*</span>
                        </label>
                        <textarea name="address" id="address-detail" required minlength="5" maxlength="500" rows="3" placeholder="Nama jalan, nomor rumah, RT/RW, patokan lokasi" class="w-full px-4 py-2.5 bg-gray-50/50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-brand-gold focus:ring-2 focus:ring-brand-gold/20 focus:bg-white transition-all"></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">
                            Kode Pos
                        </label>
                        <input type="text" name="postal_code" id="address-postal" placeholder="12345" class="w-full px-4 py-2.5 bg-gray-50/50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-brand-gold focus:ring-2 focus:ring-brand-gold/20 focus:bg-white transition-all">
                    </div>

                    <div class="pt-1">
                        <label class="flex items-center gap-2.5 cursor-pointer">
                            <input type="checkbox" name="is_primary" value="1" id="address-is-primary" class="w-4 h-4 rounded text-brand-gold accent-brand-gold border-gray-300">
                            <span class="text-xs font-semibold text-gray-700">Jadikan sebagai Alamat Utama</span>
                        </label>
                    </div>
                </div>

                <!-- Modal Footer (Fixed / Sticky at Bottom) -->
                <div class="flex items-center gap-3 p-4 sm:p-5 border-t border-gray-100 bg-gray-50/80 shrink-0">
                    <button type="button" onclick="closeAddressModal()" class="flex-1 py-3 border border-gray-200 text-gray-600 hover:bg-gray-100 rounded-xl text-xs sm:text-sm font-bold transition-colors cursor-pointer text-center">
                        Batal
                    </button>
                    <button type="submit" id="address-submit-btn" class="flex-1 py-3 bg-brand-dark hover:bg-brand-darker text-brand-gold hover:text-white rounded-xl text-xs sm:text-sm font-extrabold transition-all shadow-md cursor-pointer flex items-center justify-center gap-2">
                        <i class="fa-solid fa-check text-xs"></i>
                        <span>Simpan Alamat</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="{{ asset('js/frontend/dashboard-addresses.js') }}?v={{ filemtime(public_path('js/frontend/dashboard-addresses.js')) }}"></script>
