(function () {
    const locationOptionsEl = document.getElementById('address-location-options');
    const locationOptions = locationOptionsEl ? JSON.parse(locationOptionsEl.textContent || '[]') : [];
    const savedAddressesEl = document.getElementById('address-saved-addresses');
    const savedAddresses = savedAddressesEl ? JSON.parse(savedAddressesEl.textContent || '[]') : [];

    function locationSelect() {
        return {
            open: false,
            search: '',
            options: locationOptions.reduce((groups, item) => {
                if (!groups[item.province]) groups[item.province] = { province: item.province, cities: {} };
                if (!groups[item.province].cities[item.city]) {
                    groups[item.province].cities[item.city] = { city: item.city, options: [] };
                }
                groups[item.province].cities[item.city].options.push({ id: item.id, label: item.label });
                return Object.values(groups).map(p => ({
                    province: p.province,
                    cities: Object.values(p.cities)
                }));
            }, {}),
            filteredOptions: [],
            init() {
                this.filteredOptions = this.options;
            },
            filterOptions() {
                this.filteredOptions = this.options.map(p => ({
                    province: p.province,
                    cities: p.cities.map(c => ({
                        city: c.city,
                        options: c.options.filter(o => o.label.toLowerCase().includes(this.search.toLowerCase()) || c.city.toLowerCase().includes(this.search.toLowerCase()))
                    })).filter(c => c.options.length)
                })).filter(p => p.cities.length);
            },
            select(option) {
                this.search = option.label;
                const input = document.getElementById('address-sub-district-input');
                if (input) input.value = option.id;
                this.open = false;
            }
        }
    }

    window.openAddressModal = function () {
        const modal = document.getElementById('address-modal');
        if (!modal) return;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        const title = document.getElementById('modal-title');
        if (title) title.textContent = 'Tambah Alamat';
        const form = document.getElementById('address-form');
        if (form) {
            form.action = form.dataset.routeStore || '';
            form.reset();
        }
        const primaryCheckbox = document.getElementById('address-is-primary');
        if (primaryCheckbox) primaryCheckbox.checked = false;
        let methodInput = document.getElementById('method-input');
        if (methodInput) methodInput.remove();
    };

    window.editAddress = function (id, label, recipient, phone, address) {
        const modal = document.getElementById('address-modal');
        if (!modal) return;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        const title = document.getElementById('modal-title');
        if (title) title.textContent = 'Edit Alamat';
        const form = document.getElementById('address-form');
        if (form) {
            form.action = form.dataset.routeStore.replace('__ID__', id);
        }

        const idInput = document.getElementById('address-id');
        if (idInput) idInput.value = id;
        const labelInput = document.getElementById('address-label');
        if (labelInput) labelInput.value = label;
        const recipientInput = document.getElementById('address-recipient');
        if (recipientInput) recipientInput.value = recipient;
        const phoneInput = document.getElementById('address-phone');
        if (phoneInput) phoneInput.value = phone;
        const addressInput = document.getElementById('address-detail');
        if (addressInput) addressInput.value = address;
    };

    window.closeAddressModal = function () {
        const modal = document.getElementById('address-modal');
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    };
})();
