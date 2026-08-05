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
    var currentShippingCost = Number(document.getElementById('checkout-shipping-cost')?.dataset.value || 0);
    var productDiscount = Number(document.getElementById('checkout-product-discount')?.dataset.value || 0);
    var selectedCouponDiscount = Number(document.getElementById('checkout-voucher-discount')?.dataset.value || 0);
    var selectedCoupons = document.getElementById('checkout-selected-voucher-codes')?.dataset.value ? document.getElementById('checkout-selected-voucher-codes').dataset.value.split(',').filter(function(v) { return v.trim(); }) : [];
    var manualCouponsData = {};

    function formatRupiah(value) {
        return 'Rp ' + Number(value).toLocaleString('id-ID');
    }

    function updateTotal() {
        var total = Math.max(0, subtotal - productDiscount + currentShippingCost - selectedCouponDiscount);
        if (totalCost) totalCost.textContent = formatRupiah(total);
        if (voucherDiscount) voucherDiscount.textContent = '- ' + formatRupiah(selectedCouponDiscount);
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
            selectedCoupons.forEach(function (code) {
                var button = document.querySelector('.coupon-card[data-code="' + code + '"]');
                var couponData = null;
                if (button) {
                    couponData = {
                        discountType: button.dataset.discountType == '1' || button.dataset.discountType === 'percentage' ? 1 : (button.dataset.discountType == '2' || button.dataset.discountType === 'fixed' ? 2 : (button.dataset.discountType == '3' || button.dataset.discountType === 'shipping' ? 3 : 4)),
                        discountValue: parseFloat(button.dataset.discountValue) || 0,
                        maxDiscount: button.dataset.maxDiscount && Number(button.dataset.maxDiscount) > 0 ? Number(button.dataset.maxDiscount) : Infinity
                    };
                } else if (manualCouponsData[code]) {
                    couponData = manualCouponsData[code];
                }

                if (!couponData) return;

                var discountType = couponData.discountType;
                var discountValue = couponData.discountValue;
                var maxDiscount = couponData.maxDiscount;
                var discount = 0;
                if (discountType === 1) { discount = Math.min((subtotal * discountValue) / 100, maxDiscount); }
                else if (discountType === 2) { discount = Math.min(discountValue, subtotal); }
                else if (discountType === 3) { discount = Math.min(discountValue, currentShippingCost); }
                else if (discountType === 4) { discount = 0; } // Bonus produk tidak mengurangi total
                totalDiscount += Math.max(0, Math.min(discount, subtotal + currentShippingCost));
            });
            selectedCouponDiscount = Math.max(0, Math.min(totalDiscount, subtotal + currentShippingCost));
            var selectedText = document.getElementById('selected-coupon-text');
            if (selectedText) selectedText.innerHTML = 'Kupon dipilih: <strong class="text-brand-dark">' + selectedCoupons.join(', ') + '</strong>';
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
                cart_total: subtotal,
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
                    maxDiscount: maxDiscount
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

    if (!isLoggedIn) {
        var emailInput = document.querySelector('input[name="email"]');
        var phoneInput = document.querySelector('input[name="phone"]');

        function checkUserRegistration(field, value) {
            if (!value) return;
            var payload = {};
            payload[field] = value;

            fetch('/checkout/check-user', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (data.registered) {
                    if (data.customer) {
                        var nameInput = document.querySelector('input[name="name"]');
                        var phoneInput = document.querySelector('input[name="phone"]');
                        var emailInputEl = document.querySelector('input[name="email"]');
                        if (nameInput && data.customer.name) nameInput.value = data.customer.name;
                        if (phoneInput && data.customer.phone) phoneInput.value = data.customer.phone;
                        if (emailInputEl && data.customer.email) emailInputEl.value = data.customer.email;
                    }

                    if (data.address) {
                        var addressInput = document.querySelector('textarea[name="address"]');
                        var postalInput = document.querySelector('input[name="postal_code"]');
                        var cityInput = document.getElementById('city-display') || document.querySelector('input[name="city"]');
                        var subDistrictSelect = document.querySelector('select[name="sub_district_id"]');

                        if (addressInput && data.address.address) addressInput.value = data.address.address;
                        if (postalInput && data.address.postal_code) postalInput.value = data.address.postal_code;
                        if (cityInput && data.address.city) cityInput.value = data.address.city;
                        if (subDistrictSelect && data.address.sub_district_id) {
                            subDistrictSelect.value = data.address.sub_district_id;
                            subDistrictSelect.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                    }

                    window.dispatchEvent(new CustomEvent('open-auth'));
                    var noticeId = 'checkout-login-notice';
                    var notice = document.getElementById(noticeId);
                    if (!notice) {
                        notice = document.createElement('div');
                        notice.id = noticeId;
                        notice.className = 'mt-2 text-sm text-brand-gold font-semibold';
                        notice.textContent = 'Email / Nomor Telepon ini sudah terdaftar. Silakan login terlebih dahulu untuk melanjutkan.';
                        if (emailInput && emailInput.parentNode) {
                            emailInput.parentNode.appendChild(notice);
                        }
                    }
                }
            })
            .catch(function () {});
        }

        if (emailInput) {
            emailInput.addEventListener('blur', function () {
                checkUserRegistration('email', this.value.trim());
            });
        }

        if (phoneInput) {
            phoneInput.addEventListener('blur', function () {
                checkUserRegistration('phone', this.value.trim());
            });
        }
    }

    restoreCartCoupon();
});

document.addEventListener('DOMContentLoaded', function() {
    var subDistrictSelect = document.querySelector('select[name="sub_district_id"]');
    var cityInput = document.getElementById('city-display') || document.querySelector('input[name="city"]');
    var postalInput = document.querySelector('input[name="postal_code"]');
    
    var subDistrictMapEl = document.getElementById('checkout-subdistrict-map');
    var subDistrictMap = subDistrictMapEl ? JSON.parse(subDistrictMapEl.textContent || '{}') : {};

    if (subDistrictSelect && cityInput) {
        subDistrictSelect.addEventListener('change', function() {
            var data = subDistrictMap[this.value];
            if (data) {
                cityInput.value = data.city;
                if (postalInput) postalInput.value = data.postal_code || '';
            }
        });
    }

    var originalFillAddress = window.fillAddress;
    window.fillAddress = function(el) {
        if (originalFillAddress) originalFillAddress(el);
        var savedAddressesEl = document.getElementById('checkout-saved-addresses');
        var addresses = savedAddressesEl ? JSON.parse(savedAddressesEl.textContent || '[]') : [];
        var selected = addresses.find(function(a) { return a.id == el.value; });
        if (selected) {
            if (subDistrictSelect && selected.sub_district_id) {
                subDistrictSelect.value = selected.sub_district_id;
                var data = subDistrictMap[selected.sub_district_id];
                if (data && cityInput) cityInput.value = data.city;
                if (data && postalInput) postalInput.value = data.postal_code || '';
            }
            var addressSelector = document.getElementById('address-selector');
            if (addressSelector) addressSelector.classList.add('hidden');
        }
    };
});
