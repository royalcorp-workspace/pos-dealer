@php
$locationData = \App\Models\Frontend\Location\SubDistrict::with(['city.province'])->get()->map(function($sd) {
    return [
        'id' => $sd->id,
        'label' => $sd->sub_district,
        'city' => $sd->city->name ?? '',
        'province' => $sd->city->province->name ?? ''
    ];
})->toArray();
@endphp

<script type="application/json" id="address-location-options">
@json($locationData)
</script>

<!-- Modal -->
<div id="address-modal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-xl p-6 w-full max-w-md mx-4">
        <h3 class="font-bold text-brand-dark mb-4" id="modal-title">Tambah Alamat</h3>
        <form method="POST" id="address-form" data-route-store="{{ route('dashboard.addresses.store') }}">
            @csrf
            <input type="hidden" name="id" id="address-id">
            <div class="space-y-3">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Label</label>
                    <input type="text" name="label" id="address-label" required class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Nama Penerima</label>
                    <input type="text" name="recipient_name" id="address-recipient" required class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">No. Telepon</label>
                    <input type="tel" name="phone" id="address-phone" required class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div x-data="locationSelect()" x-init="init()" class="relative" @click.outside="open = false">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Kota/Kelurahan</label>
                    <input type="hidden" name="sub_district_id" id="address-sub-district-input" required>
                    <input type="text" x-model="search" @input="filterOptions()" @focus="open = true" placeholder="Cari kelurahan..." required class="w-full px-3 py-2 border rounded-lg">
                    <div x-show="open" class="absolute z-10 mt-1 w-full bg-white border rounded-lg max-h-60 overflow-y-auto" x-cloak>
                        <template x-for="province in filteredOptions" :key="province.province">
                            <div>
                                <div class="px-3 py-2 font-bold text-brand-dark bg-gray-100" x-text="province.province"></div>
                                <template x-for="city in province.cities" :key="city.city">
                                    <div>
                                        <div class="px-3 py-2 font-semibold text-gray-600 bg-gray-50 pl-4" x-text="city.city"></div>
                                        <template x-for="option in city.options" :key="option.id">
                                            <div @click="select(option)" class="px-3 py-2 pl-8 cursor-pointer hover:bg-brand-light" x-text="option.label"></div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Alamat Lengkap</label>
                    <textarea name="address" id="address-detail" required rows="3" class="w-full px-3 py-2 border rounded-lg"></textarea>
                </div>
                <div>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="is_primary" value="1" id="address-is-primary">
                        <span class="text-sm">Jadikan Alamat Utama</span>
                    </label>
                </div>
            </div>
            <div class="flex gap-3 mt-6">
                <button type="button" onclick="closeAddressModal()" class="flex-1 px-4 py-2 border rounded-lg">Batal</button>
                <button type="submit" class="flex-1 px-4 py-2 bg-brand-gold text-brand-dark font-semibold rounded-lg">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script src="{{ asset('js/frontend/dashboard-addresses.js') }}?v={{ filemtime(public_path('js/frontend/dashboard-addresses.js')) }}"></script>
