document.addEventListener('DOMContentLoaded', function () {
    // Clear stale localStorage voucher data if checkout is fresh/new
    var checkoutFormData = document.getElementById('checkout-form-data');
    if (!checkoutFormData || !checkoutFormData.dataset.hasExistingData) {
        localStorage.removeItem('selectedCartCoupon');
        localStorage.removeItem('selectedCartCoupons');
    }

    var courierSelect = document.querySelector('select[name="courier"]');
    var shippingCost = document.getElementById('shipping-cost');
    var voucherDiscount = document.getElementById('voucher-discount');
    var voucherDiscountValue = document.getElementById('voucher-discount-value');
    var totalCost = document.getElementById('total-cost');
    var subtotal = Number(document.getElementById('checkout-subtotal')?.dataset.value || 0);
    var promoDiscount = Number(document.getElementById('checkout-promo-discount')?.dataset.value || 0);
    var currentShippingCost = Number(document.getElementById('checkout-shipping-cost')?.dataset.value || 0);
    var productDiscount = Number(document.getElementById('checkout-product-discount')?.dataset.value || 0);
    var selectedCouponDiscount = Number(document.getElementById('checkout-voucher-discount')?.dataset.value || 0);
    var selectedCoupons = document.getElementById('checkout-selected-voucher-codes')?.dataset.value ? document.getElementById('checkout-selected-voucher-codes').dataset.value.split(',').filter(function(v) { return v.trim(); }) : [];
    var manualCouponsData = {};

    function formatRupiah(value) {
        return 'Rp ' + Number(value).toLocaleString('id-ID');
    }

    function updateTotal() {
        var total = Math.max(0, subtotal - promoDiscount - productDiscount + currentShippingCost - selectedCouponDiscount);
        if (totalCost) totalCost.textContent = formatRupiah(total);
        if (voucherDiscount) voucherDiscount.textContent = '- ' + formatRupiah(selectedCouponDiscount);

        var productDiscountRow = document.getElementById('product-discount-row');
        if (productDiscountRow) {
            if (productDiscount > 0) {
                productDiscountRow.classList.remove('hidden');
                var productDiscountSpan = document.getElementById('product-discount');
                if (productDiscountSpan) {
                    productDiscountSpan.textContent = '- ' + formatRupiah(productDiscount);
                }
            } else {
                productDiscountRow.classList.add('hidden');
            }
        }
    }

    function restoreCartCoupon() {
        var saved = localStorage.getItem('selectedCartCoupon');
        if (!saved) return;
        try {
            var coupons = JSON.parse(saved);
            if (!Array.isArray(coupons)) {
                if (coupons && coupons.code) {
                    coupons = [coupons];
                } else {
                    return;
                }
            }
            coupons.forEach(function (coupon) {
                var button = document.querySelector('.coupon-card[data-code="' + coupon.code + '"]');
                if (!button) {
                    if (coupon.discountValue !== undefined) {
                        manualCouponsData[coupon.code] = {
                            discountType: coupon.discountType == '1' || coupon.discountType === 'percentage' ? 1 : (coupon.discountType == '2' || coupon.discountType === 'fixed' ? 2 : (coupon.discountType == '3' || coupon.discountType === 'shipping' ? 3 : 4)),
                            discountValue: parseFloat(coupon.discountValue) || 0,
                            maxDiscount: coupon.maxDiscount && Number(coupon.maxDiscount) > 0 ? Number(coupon.maxDiscount) : Infinity
                        };
                    }
                    if (!selectedCoupons.includes(coupon.code)) selectedCoupons.push(coupon.code);
                    return;
                }
                if (!selectedCoupons.includes(coupon.code)) selectedCoupons.push(coupon.code);
                button.classList.add('border-brand-gold', 'bg-brand-light');
                var label = button.querySelector('.select-coupon-label');
                if (label) label.textContent = 'Dipilih';
            });
            updateSelectedCouponDisplay();
            updateTotal();
        } catch (e) {}

        selectedCoupons.forEach(function (code) {
            var button = document.querySelector('.coupon-card[data-code="' + code + '"]');
            if (!button) return;
            button.classList.add('border-brand-gold', 'bg-brand-light');
            var label = button.querySelector('.select-coupon-label');
            if (label) label.textContent = 'Dipilih';
        });
        updateSelectedCouponDisplay();
        updateTotal();
    }

    var courierShippingPricesEl = document.getElementById('checkout-courier-shipping-prices');
    var courierShippingPrices = courierShippingPricesEl ? JSON.parse(courierShippingPricesEl.textContent || '{}') : {};

    if (courierSelect) {
        courierSelect.addEventListener('change', function () {
            currentShippingCost = courierShippingPrices[this.value] || 0;
            if (shippingCost) {
                shippingCost.innerHTML = '<span class="text-brand-dark">' + formatRupiah(currentShippingCost) + '</span>';
            }
            var shippingLabel = document.getElementById('checkout-shipping-label');
            if (shippingLabel) {
                shippingLabel.textContent = 'Shipping (' + this.value.toUpperCase() + ')';
            }
            updateSelectedCouponDisplay();
            updateTotal();
        });
    }

    window.selectCoupon = function (button) {
        var code = button.dataset.code;
        var allowStacking = button.dataset.allowStacking === '1';

        if (selectedCoupons.includes(code)) {
            selectedCoupons = selectedCoupons.filter(function (selectedCode) { return selectedCode !== code; });
        } else {
            var hasNonStackable = selectedCoupons.some(function (selectedCode) {
                var item = document.querySelector('.coupon-card[data-code="' + selectedCode + '"]');
                return item && item.dataset.allowStacking !== '1';
            });

            if (!allowStacking || hasNonStackable) {
                selectedCoupons = [];
            }
            selectedCoupons.push(code);
        }

        document.querySelectorAll('.coupon-card').forEach(function (item) {
            var isSelected = selectedCoupons.includes(item.dataset.code);
            item.classList.toggle('border-brand-gold', isSelected);
            item.classList.toggle('bg-brand-light', isSelected);
            var label = item.querySelector('.select-coupon-label');
            if (label) label.textContent = isSelected ? 'Dipilih' : 'Pilih';
        });

        updateSelectedCouponDisplay();
        updateTotal();
    };

    function updateSelectedCouponDisplay() {
        var voucherCodeEl = document.getElementById('voucher-code');
        var voucherCodesEl = document.getElementById('voucher-codes');
        var voucherRow = document.getElementById('checkout-voucher-row');
        var voucherLabel = document.getElementById('checkout-voucher-label');

        if (voucherCodeEl) voucherCodeEl.value = selectedCoupons.join(',');
        if (voucherCodesEl) voucherCodesEl.value = selectedCoupons.join(',');
        if (selectedCoupons.length === 0) {
            localStorage.removeItem('selectedCartCoupon');
            localStorage.removeItem('selectedCartCoupons');
            selectedCouponDiscount = 0;
            var selectedText = document.getElementById('selected-coupon-text');
            if (selectedText) selectedText.innerHTML = 'Belum ada kupon dipilih.';
            if (voucherRow) voucherRow.style.display = 'none';
        } else {
            var mainCode = selectedCoupons[0];
            var btn = document.querySelector('.coupon-card[data-code="' + mainCode + '"]');
            var couponData = { code: mainCode };
            if (btn) {
                couponData.title = btn.dataset.title || '';
                couponData.description = btn.dataset.description || '';
                couponData.discountType = btn.dataset.discountType || '';
                couponData.discountValue = btn.dataset.discountValue || '';
                couponData.maxDiscount = btn.dataset.maxDiscount || '';
            } else if (manualCouponsData[mainCode]) {
                var mc = manualCouponsData[mainCode];
                couponData.discountType = mc.discountType === 1 ? 'percentage' : (mc.discountType === 2 ? 'fixed' : 'shipping');
                couponData.discountValue = mc.discountValue;
                couponData.maxDiscount = mc.maxDiscount;
            }
            localStorage.setItem('selectedCartCoupon', JSON.stringify(couponData));
            localStorage.setItem('selectedCartCoupons', JSON.stringify(selectedCoupons.map(function (code) { return { code: code }; })));
            var totalDiscount = 0;
            var bonusHtml = '';
            selectedCoupons.forEach(function (code) {
                var button = document.querySelector('.coupon-card[data-code="' + code + '"]');
                var couponData = null;
                if (button) {
                    couponData = {
                        discountType: button.dataset.discountType == '1' || button.dataset.discountType === 'percentage' ? 1 : (button.dataset.discountType == '2' || button.dataset.discountType === 'fixed' ? 2 : (button.dataset.discountType == '3' || button.dataset.discountType === 'shipping' ? 3 : 4)),
                        discountValue: parseFloat(button.dataset.discountValue) || 0,
                        maxDiscount: button.dataset.maxDiscount && Number(button.dataset.maxDiscount) > 0 ? Number(button.dataset.maxDiscount) : Infinity,
                        products: button.dataset.products ? JSON.parse(button.dataset.products) : []
                    };
                } else if (manualCouponsData[code]) {
                    couponData = manualCouponsData[code];
                }

                if (!couponData) return;

                var discountType = couponData.discountType;
                var discountValue = couponData.discountValue;
                var maxDiscount = couponData.maxDiscount;
                var discount = 0;
                var discountableBase = Math.max(0, subtotal - promoDiscount - productDiscount);
                if (discountType === 1) { discount = Math.min((discountableBase * discountValue) / 100, maxDiscount); }
                else if (discountType === 2) { discount = Math.min(discountValue, discountableBase); }
                else if (discountType === 3) { discount = Math.min(discountValue, currentShippingCost); }
                else if (discountType === 4) { discount = 0; } // Bonus produk tidak mengurangi total
                totalDiscount += Math.max(0, Math.min(discount, discountableBase + currentShippingCost));

                // If Bonus Product, build HTML alert
                if (discountType === 4 && couponData.products && couponData.products.length > 0) {
                    var listItems = couponData.products.map(function(p) {
                        return '<li>' + parseInt(discountValue) + 'x ' + p + '</li>';
                    }).join('');
                    bonusHtml += '<div class="mt-3 flex items-start gap-2.5 bg-green-50 text-green-800 p-3 rounded-xl border border-green-200 text-xs font-semibold">' +
                        '<i class="fa-solid fa-circle-check text-sm text-green-600 mt-0.5"></i>' +
                        '<div>' +
                            '<p class="font-extrabold">Selamat! Anda mendapatkan Bonus Produk:</p>' +
                            '<ul class="list-disc pl-4 mt-1 space-y-0.5">' + listItems + '</ul>' +
                        '</div>' +
                    '</div>';
                }
            });
            selectedCouponDiscount = Math.max(0, Math.min(totalDiscount, subtotal + currentShippingCost));
            var selectedText = document.getElementById('selected-coupon-text');
            if (selectedText) selectedText.innerHTML = 'Kupon dipilih: <strong class="text-brand-dark">' + selectedCoupons.join(', ') + '</strong>';
            
            var bonusDisplay = document.getElementById('bonus-products-display');
            if (bonusDisplay) bonusDisplay.innerHTML = bonusHtml;

            if (voucherRow) voucherRow.style.display = 'flex';
            if (voucherLabel) voucherLabel.textContent = 'Voucher (' + selectedCoupons.join(', ') + ')';
        }
        if (voucherDiscountValue) voucherDiscountValue.value = selectedCouponDiscount.toFixed(2);
    }

    window.toggleAddressSelector = function () {
        var el = document.getElementById('address-selector');
        if (el) el.classList.toggle('hidden');
    };

    window.fillAddress = function (el) {
        var addresses = savedAddresses;
        var selected = addresses.find(function (a) { return a.id == el.value; });
        if (selected) {
            var nameInput = document.querySelector('input[name="name"]');
            var phoneInput = document.querySelector('input[name="phone"]');
            var cityInput = document.querySelector('input[name="city"]');
            var addressInput = document.querySelector('textarea[name="address"]');
            var postalInput = document.querySelector('input[name="postal_code"]');
            if (nameInput) nameInput.value = selected.recipient_name;
            if (phoneInput) phoneInput.value = selected.phone;
            if (cityInput) cityInput.value = selected.city;
            if (addressInput) addressInput.value = selected.address;
            if (postalInput) postalInput.value = selected.postal_code;
            var addressSelector = document.getElementById('address-selector');
            if (addressSelector) addressSelector.classList.add('hidden');
        }
    };

    window.validateAndApplyVoucher = function() {
        var input = document.getElementById('manual-voucher-input');
        var code = input ? input.value.trim().toUpperCase() : '';
        var feedback = document.getElementById('manual-voucher-feedback');

        if (!code) {
            if (feedback) feedback.innerHTML = '<span class="text-red-500">Masukkan kode voucher terlebih dahulu.</span>';
            return;
        }

        var currCodes = selectedCoupons.map(function(c){return c.trim().toUpperCase();});
        if (currCodes.includes(code)) {
            if (feedback) feedback.innerHTML = '<span class="text-red-500">Kupon sudah dipilih.</span>';
            return;
        }

        var productIds = [];
        var categoryIds = [];
        var productIdsEl = document.getElementById('checkout-product-ids');
        var categoryIdsEl = document.getElementById('checkout-category-ids');
        if (productIdsEl) {
            try {
                productIds = JSON.parse(productIdsEl.dataset.value || '[]');
            } catch (e) {}
        }
        if (categoryIdsEl) {
            try {
                categoryIds = JSON.parse(categoryIdsEl.dataset.value || '[]');
            } catch (e) {}
        }

        if (feedback) feedback.innerHTML = '<span class="text-gray-500">Memvalidasi...</span>';

        fetch('/voucher/validate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                code: code,
                cart_total: Math.max(0, subtotal - promoDiscount - productDiscount),
                product_ids: productIds,
                category_ids: categoryIds
            })
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.valid) {
                var discountVal = parseFloat(data.voucher.value) || 0;
                var discountType = data.voucher.type == '1' || data.voucher.type === 'percentage' ? 1 : (data.voucher.type == '2' || data.voucher.type === 'fixed' ? 2 : (data.voucher.type == '3' || data.voucher.type === 'shipping' ? 3 : 4));
                var maxDiscount = data.voucher.max_discount && Number(data.voucher.max_discount) > 0 ? Number(data.voucher.max_discount) : Infinity;

                manualCouponsData[code] = {
                    discountType: discountType,
                    discountValue: discountVal,
                    maxDiscount: maxDiscount,
                    products: data.voucher.products || []
                };

                var allowStacking = data.voucher.allow_stacking ? 1 : 0;
                var hasNonStackable = selectedCoupons.some(function (selectedCode) {
                    var item = document.querySelector('.coupon-card[data-code="' + selectedCode + '"]');
                    if (item && item.dataset.allowStacking !== '1') return true;
                    return true;
                });

                if (!allowStacking || hasNonStackable) {
                    selectedCoupons = [];
                    document.querySelectorAll('.coupon-card').forEach(function (item) {
                        item.classList.remove('border-brand-gold', 'bg-brand-light');
                        var label = item.querySelector('.select-coupon-label');
                        if (label) label.textContent = 'Pilih';
                    });
                }

                if (!selectedCoupons.includes(code)) {
                    selectedCoupons.push(code);
                }

                updateSelectedCouponDisplay();
                updateTotal();

                if (feedback) feedback.innerHTML = '<span class="text-green-600">Voucher berhasil diterapkan: ' + code + '</span>';
                if (input) input.value = '';
            } else {
                if (feedback) feedback.innerHTML = '<span class="text-red-500">' + data.message + '</span>';
            }
        })
        .catch(function() {
            if (feedback) feedback.innerHTML = '<span class="text-red-500">Gagal memvalidasi voucher. Coba lagi.</span>';
        });
    };

    var formEl = document.getElementById('checkout-form');
    var isLoggedIn = formEl ? formEl.dataset.isLoggedIn === '1' : false;

    restoreCartCoupon();
    updateTotal();
});

document.addEventListener('DOMContentLoaded', function() {
    var provinceSelect = document.getElementById('checkout-province') || document.querySelector('select[name="province_id"]');
    var citySelect = document.getElementById('checkout-city') || document.querySelector('select[name="city_id"]');
    var subDistrictSelect = document.getElementById('checkout-sub-district') || document.querySelector('select[name="sub_district_id"]');
    var cityInput = document.getElementById('city-display') || document.querySelector('input[name="city"]');
    var postalInput = document.querySelector('input[name="postal_code"]');
    
    var subDistrictMapEl = document.getElementById('checkout-subdistrict-map');
    var subDistrictMap = subDistrictMapEl ? JSON.parse(subDistrictMapEl.textContent || '{}') : {};

    function loadCities(provinceId, selectedCityId, callback) {
        if (!citySelect) return;
        citySelect.innerHTML = '<option value="">Memuat kota/kabupaten...</option>';
        citySelect.disabled = true;
        if (subDistrictSelect) {
            subDistrictSelect.innerHTML = '<option value="">Pilih Kota Terlebih Dahulu</option>';
            subDistrictSelect.disabled = true;
        }

        if (!provinceId) {
            citySelect.innerHTML = '<option value="">Pilih Provinsi Terlebih Dahulu</option>';
            citySelect.disabled = true;
            if (callback) callback();
            return;
        }

        fetch('/checkout/cities?province_id=' + encodeURIComponent(provinceId))
            .then(function(res) { return res.json(); })
            .then(function(cities) {
                var html = '<option value="">Pilih Kota/Kabupaten</option>';
                cities.forEach(function(c) {
                    var sel = (selectedCityId && String(c.id) === String(selectedCityId)) ? ' selected' : '';
                    html += '<option value="' + c.id + '"' + sel + '>' + c.name + '</option>';
                });
                citySelect.innerHTML = html;
                citySelect.disabled = false;
                if (callback) callback();
            })
            .catch(function(err) {
                console.error('Error loading cities:', err);
                citySelect.innerHTML = '<option value="">Gagal memuat kota</option>';
                citySelect.disabled = false;
            });
    }

    function loadSubDistricts(cityId, selectedSubDistrictId, callback) {
        if (!subDistrictSelect) return;
        subDistrictSelect.innerHTML = '<option value="">Memuat kecamatan/kelurahan...</option>';
        subDistrictSelect.disabled = true;

        if (!cityId) {
            subDistrictSelect.innerHTML = '<option value="">Pilih Kota Terlebih Dahulu</option>';
            subDistrictSelect.disabled = true;
            if (callback) callback();
            return;
        }

        fetch('/checkout/sub-districts?city_id=' + encodeURIComponent(cityId))
            .then(function(res) { return res.json(); })
            .then(function(subDistricts) {
                var html = '<option value="">Pilih Kecamatan/Kelurahan</option>';
                subDistricts.forEach(function(sd) {
                    var sel = (selectedSubDistrictId && String(sd.id) === String(selectedSubDistrictId)) ? ' selected' : '';
                    var label = sd.label || (sd.sub_district + (sd.district ? ' (Kec. ' + sd.district + ')' : '') + (sd.postal_code ? ' - ' + sd.postal_code : ''));
                    html += '<option value="' + sd.id + '" data-postal="' + (sd.postal_code || '') + '"' + sel + '>' + label + '</option>';
                });
                subDistrictSelect.innerHTML = html;
                subDistrictSelect.disabled = false;
                if (callback) callback();
            })
            .catch(function(err) {
                console.error('Error loading sub-districts:', err);
                subDistrictSelect.innerHTML = '<option value="">Gagal memuat kecamatan/kelurahan</option>';
                subDistrictSelect.disabled = false;
            });
    }

    if (provinceSelect) {
        provinceSelect.addEventListener('change', function() {
            var provId = this.value;
            loadCities(provId, null);
        });
    }

    if (citySelect) {
        citySelect.addEventListener('change', function() {
            var cId = this.value;
            var selOpt = citySelect.options[citySelect.selectedIndex];
            if (cityInput && selOpt && selOpt.value) {
                cityInput.value = selOpt.textContent.trim();
            }
            loadSubDistricts(cId, null);
        });
    }

    if (subDistrictSelect) {
        subDistrictSelect.addEventListener('change', function() {
            var selOpt = this.options[this.selectedIndex];
            if (selOpt && postalInput) {
                var postal = selOpt.getAttribute('data-postal');
                if (postal) postalInput.value = postal;
            }
            var data = subDistrictMap[this.value];
            if (data) {
                if (cityInput && !cityInput.value && data.city) cityInput.value = data.city;
                if (postalInput && !postalInput.value && data.postal_code) postalInput.value = data.postal_code;
            }
            if (typeof window.fetchAndUpdateShippingCost === 'function') {
                window.fetchAndUpdateShippingCost();
            }
        });
    }

    window.loadCities = loadCities;
    window.loadSubDistricts = loadSubDistricts;

    var originalFillAddress = window.fillAddress;
    window.fillAddress = function(el) {
        if (originalFillAddress) originalFillAddress(el);
        var savedAddressesEl = document.getElementById('checkout-saved-addresses');
        var addresses = savedAddressesEl ? JSON.parse(savedAddressesEl.textContent || '[]') : [];
        var selected = addresses.find(function(a) { return String(a.id) === String(el.value); });
        if (selected) {
            if (selected.province_id && provinceSelect) {
                provinceSelect.value = selected.province_id;
                loadCities(selected.province_id, selected.city_id, function() {
                    if (selected.city_id) {
                        loadSubDistricts(selected.city_id, selected.sub_district_id, function() {
                            if (subDistrictSelect && selected.sub_district_id) {
                                subDistrictSelect.value = selected.sub_district_id;
                                subDistrictSelect.dispatchEvent(new Event('change'));
                            }
                        });
                    }
                });
            } else if (selected.sub_district_id && subDistrictSelect) {
                subDistrictSelect.value = selected.sub_district_id;
                subDistrictSelect.dispatchEvent(new Event('change'));
            }

            if (postalInput && selected.postal_code) {
                postalInput.value = selected.postal_code;
            }
            if (cityInput && selected.city) {
                cityInput.value = selected.city;
            }
            var addressSelector = document.getElementById('address-selector');
            if (addressSelector) addressSelector.classList.add('hidden');

            if (typeof window.fetchAndUpdateShippingCost === 'function') {
                window.fetchAndUpdateShippingCost();
            }
        }
    };
});
