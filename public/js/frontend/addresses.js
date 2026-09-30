(function () {
    // DOM Elements
    const modal = document.getElementById('address-modal');
    const form = document.getElementById('address-form');
    const modalTitle = document.getElementById('modal-title');
    const methodContainer = document.getElementById('method-container');
    const idInput = document.getElementById('address-id');
    const labelInput = document.getElementById('address-label');
    const recipientInput = document.getElementById('address-recipient');
    const phoneInput = document.getElementById('address-phone');
    const addressInput = document.getElementById('address-detail');
    const postalInput = document.getElementById('address-postal');
    const isPrimaryInput = document.getElementById('address-is-primary');

    const provinceSelect = document.getElementById('address-province-select');
    const citySelect = document.getElementById('address-city-select');
    const subDistrictSelect = document.getElementById('address-sub-district-select');

    function resetCascadingSelects() {
        if (citySelect) {
            citySelect.innerHTML = '<option value="">Pilih Provinsi Terlebih Dahulu</option>';
            citySelect.disabled = true;
        }
        if (subDistrictSelect) {
            subDistrictSelect.innerHTML = '<option value="">Pilih Kota Terlebih Dahulu</option>';
            subDistrictSelect.disabled = true;
        }
    }

    function loadCities(provinceId, selectedCityId, callback) {
        if (!citySelect) return;
        if (!provinceId) {
            resetCascadingSelects();
            return;
        }

        citySelect.disabled = true;
        citySelect.innerHTML = '<option value="">Memuat kota...</option>';
        if (subDistrictSelect) {
            subDistrictSelect.innerHTML = '<option value="">Pilih Kota Terlebih Dahulu</option>';
            subDistrictSelect.disabled = true;
        }

        fetch(`/checkout/cities?province_id=${encodeURIComponent(provinceId)}`)
            .then(res => res.json())
            .then(cities => {
                citySelect.innerHTML = '<option value="">-- Pilih Kota / Kabupaten --</option>';
                if (Array.isArray(cities) && cities.length > 0) {
                    cities.forEach(c => {
                        const opt = document.createElement('option');
                        opt.value = c.id;
                        opt.textContent = c.name;
                        if (selectedCityId && String(c.id) === String(selectedCityId)) {
                            opt.selected = true;
                        }
                        citySelect.appendChild(opt);
                    });
                    citySelect.disabled = false;
                } else {
                    citySelect.innerHTML = '<option value="">Tidak ada kota ditemukan</option>';
                }
                if (typeof callback === 'function') callback();
            })
            .catch(err => {
                console.error('Error loading cities:', err);
                citySelect.innerHTML = '<option value="">Gagal memuat data kota</option>';
            });
    }

    function loadSubDistricts(cityId, selectedSubDistrictId, callback) {
        if (!subDistrictSelect) return;
        if (!cityId) {
            subDistrictSelect.innerHTML = '<option value="">Pilih Kota Terlebih Dahulu</option>';
            subDistrictSelect.disabled = true;
            return;
        }

        subDistrictSelect.disabled = true;
        subDistrictSelect.innerHTML = '<option value="">Memuat kecamatan...</option>';

        fetch(`/checkout/sub-districts?city_id=${encodeURIComponent(cityId)}`)
            .then(res => res.json())
            .then(subs => {
                subDistrictSelect.innerHTML = '<option value="">-- Pilih Kecamatan / Kelurahan --</option>';
                if (Array.isArray(subs) && subs.length > 0) {
                    subs.forEach(s => {
                        const opt = document.createElement('option');
                        opt.value = s.id;
                        opt.textContent = s.label || s.sub_district;
                        if (s.postal_code) {
                            opt.setAttribute('data-postal', s.postal_code);
                        }
                        if (selectedSubDistrictId && String(s.id) === String(selectedSubDistrictId)) {
                            opt.selected = true;
                        }
                        subDistrictSelect.appendChild(opt);
                    });
                    subDistrictSelect.disabled = false;
                } else {
                    subDistrictSelect.innerHTML = '<option value="">Tidak ada kecamatan ditemukan</option>';
                }
                if (typeof callback === 'function') callback();
            })
            .catch(err => {
                console.error('Error loading sub-districts:', err);
                subDistrictSelect.innerHTML = '<option value="">Gagal memuat kecamatan</option>';
            });
    }

    if (provinceSelect) {
        provinceSelect.addEventListener('change', function () {
            loadCities(this.value, null, null);
        });
    }

    if (citySelect) {
        citySelect.addEventListener('change', function () {
            loadSubDistricts(this.value, null, null);
        });
    }

    if (subDistrictSelect) {
        subDistrictSelect.addEventListener('change', function () {
            const selectedOpt = this.options[this.selectedIndex];
            if (selectedOpt && postalInput) {
                const postal = selectedOpt.getAttribute('data-postal');
                if (postal && (!postalInput.value || postalInput.value.length < 5)) {
                    postalInput.value = postal;
                }
            }
        });
    }

    window.openAddressModal = function () {
        if (!modal) return;
        modal.classList.remove('hidden');
        modal.classList.add('flex');

        if (modalTitle) modalTitle.textContent = 'Tambah Alamat Baru';
        if (form) {
            form.action = form.dataset.routeStore || '/dashboard/addresses';
            form.reset();
        }
        if (methodContainer) methodContainer.innerHTML = '';
        if (idInput) idInput.value = '';
        if (provinceSelect) provinceSelect.value = '';
        resetCascadingSelects();
    };

    window.editAddress = function (data) {
        if (!modal) return;
        modal.classList.remove('hidden');
        modal.classList.add('flex');

        if (modalTitle) modalTitle.textContent = 'Ubah Alamat';

        // Parse data if passed as string or object
        let address = data;
        if (typeof data === 'string') {
            try {
                address = JSON.parse(data);
            } catch (e) {
                address = {};
            }
        }

        const updateBase = form ? (form.dataset.routeUpdateBase || '/dashboard/addresses') : '/dashboard/addresses';
        if (form) {
            form.action = `${updateBase}/${address.id}`;
        }

        if (methodContainer) {
            methodContainer.innerHTML = '<input type="hidden" name="_method" value="PUT" id="address-method-input">';
        }

        if (idInput) idInput.value = address.id || '';
        if (labelInput) labelInput.value = address.label || '';
        if (recipientInput) recipientInput.value = address.recipient_name || '';
        if (phoneInput) phoneInput.value = address.phone || '';
        if (addressInput) addressInput.value = address.address || '';
        if (postalInput) postalInput.value = address.postal_code || '';
        if (isPrimaryInput) isPrimaryInput.checked = !!address.is_primary;

        // Cascade load: Province -> City -> SubDistrict
        if (provinceSelect && address.province_id) {
            provinceSelect.value = address.province_id;
            loadCities(address.province_id, address.city_id, function () {
                if (address.city_id) {
                    loadSubDistricts(address.city_id, address.sub_district_id, function () {
                        if (subDistrictSelect && address.sub_district_id) {
                            subDistrictSelect.value = address.sub_district_id;
                        }
                    });
                }
            });
        } else if (provinceSelect) {
            provinceSelect.value = '';
            resetCascadingSelects();
        }
    };

    window.closeAddressModal = function () {
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    };

    // Close on backdrop click
    if (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) {
                window.closeAddressModal();
            }
        });
    }
})();
