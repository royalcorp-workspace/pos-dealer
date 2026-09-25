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
        if (voucherDiscount) {
            if (selectedCouponDiscount > 0) {
                voucherDiscount.textContent = '- ' + formatRupiah(selectedCouponDiscount);
            } else if (selectedCoupons.some(function(code) { return checkCouponIsShipping(code); })) {
                voucherDiscount.textContent = currentShippingCost > 0 ? 'Gratis Ongkir' : 'Gratis Ongkir (Pilih kurir)';
            } else {
                voucherDiscount.textContent = '- Rp 0';
            }
        }

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
        var saved = localStorage.getItem('selectedCartCoupons') || localStorage.getItem('selectedCartCoupon');
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
    var courierShippingDetailsEl = document.getElementById('checkout-courier-shipping-details');
    var courierShippingDetails = courierShippingDetailsEl ? JSON.parse(courierShippingDetailsEl.textContent || '{}') : {};

    function updateShippingUI(courier, cost, details) {
        currentShippingCost = cost;
        if (shippingCost) {
            shippingCost.innerHTML = '<span class="text-brand-dark">' + formatRupiah(currentShippingCost) + '</span>';
        }
        var shippingLabel = document.getElementById('checkout-shipping-label');
        if (shippingLabel) {
            var labelText = 'Shipping';
            if (courier) {
                labelText += ' (' + courier.toUpperCase();
                if (details && details.has_fixed_items && details.has_dimension_items) {
                    labelText += ' - Tetap + ' + (details.billable_weight || 1) + ' kg';
                } else if (details && details.has_fixed_items) {
                    labelText += ' - Tetap';
                } else if (details && details.is_calculated && details.billable_weight) {
                    labelText += ' - ' + details.billable_weight + ' kg';
                }
                labelText += ')';
            }
            shippingLabel.textContent = labelText;
        }

        var etaBadge = document.getElementById('courier-eta-badge');
        var etaText = document.getElementById('courier-eta-text');
        var etaSource = document.getElementById('courier-eta-source');
        var summaryEtaRow = document.getElementById('checkout-shipping-eta-row');
        var summaryEtaVal = document.getElementById('checkout-shipping-eta-val');

        var etaLabel = details && details.eta_label ? details.eta_label : '';
        var etaSrc = details && details.eta_source ? details.eta_source : '';

        if (etaBadge && etaText && etaSource) {
            if (courier && etaLabel && details && details.is_available !== false) {
                etaText.textContent = etaLabel;
                etaSource.textContent = etaSrc === 'biteship' ? 'Biteship' : (etaSrc === 'store' ? 'Kurir Toko' : 'Estimasi');
                etaBadge.classList.remove('hidden');
            } else {
                etaBadge.classList.add('hidden');
            }
        }

        if (summaryEtaRow && summaryEtaVal) {
            if (courier && etaLabel && details && details.is_available !== false) {
                summaryEtaVal.textContent = etaLabel;
                summaryEtaRow.classList.remove('hidden');
            } else {
                summaryEtaRow.classList.add('hidden');
            }
        }

        updateSelectedCouponDisplay();
        updateTotal();
    }

    function fetchAndUpdateShippingCost() {
        var subDistrictSelect = document.querySelector('select[name="sub_district_id"]');
        var subDistrictId = subDistrictSelect ? subDistrictSelect.value : '';
        var courier = courierSelect ? courierSelect.value : '';

        var alertEl = document.getElementById('courier-unavailable-alert');
        var alertMsg = document.getElementById('courier-unavailable-message');
        var submitBtn = document.querySelector('button[type="submit"]');

        if (!courierSelect || !courier) {
            if (alertEl) alertEl.classList.add('hidden');
            if (submitBtn) submitBtn.disabled = false;
            updateShippingUI('', 0, null);
            return;
        }

        // If subdistrict is empty, fallback to pre-rendered price
        if (!subDistrictId) {
            var fallbackCost = courierShippingPrices[courier] || 0;
            var fallbackDetails = courierShippingDetails[courier] || null;
            if (alertEl) alertEl.classList.add('hidden');
            if (submitBtn) submitBtn.disabled = false;
            updateShippingUI(courier, fallbackCost, fallbackDetails);
            return;
        }

        // Fetch accurate shipping cost for all couriers given this subdistrict
        fetch('/checkout/calculate-shipping?courier=all&sub_district_id=' + encodeURIComponent(subDistrictId))
            .then(function (res) { return res.json(); })
            .then(function (res) {
                if (res.success && res.data) {
                    var allData = res.data;
                    var selectedCourierData = null;

                    Object.keys(allData).forEach(function (cCode) {
                        var data = allData[cCode];
                        courierShippingPrices[cCode] = data.shipping_cost;
                        courierShippingDetails[cCode] = data;

                        var opt = courierSelect.querySelector('option[value="' + cCode + '"]');
                        if (opt) {
                            var rawName = opt.getAttribute('data-courier-name') || opt.textContent.split(' - ')[0].trim();
                            if (!opt.getAttribute('data-courier-name')) {
                                opt.setAttribute('data-courier-name', rawName);
                            }

                            if (data.is_available === false) {
                                opt.disabled = true;
                                opt.textContent = rawName + ' - Di Luar Jangkauan (Tidak Melayani Wilayah Ini)';
                            } else {
                                opt.disabled = false;
                                var weightText = '';
                                if (data.has_fixed_items && data.has_dimension_items) {
                                    weightText = ' (Tetap + ' + (data.billable_weight || 1) + ' kg)';
                                } else if (data.has_fixed_items) {
                                    weightText = ' (Ongkir Tetap)';
                                } else if (data.is_calculated && data.billable_weight > 0) {
                                    weightText = ' (' + data.billable_weight + ' kg)';
                                } else {
                                    weightText = ' (Tarif Tetap)';
                                }
                                var etaText = data.eta_label ? ' | Estimasi tiba: ' + data.eta_label : '';
                                opt.textContent = rawName + ' - ' + formatRupiah(data.shipping_cost) + weightText + etaText;
                            }
                        }

                        if (cCode.toLowerCase() === courier.toLowerCase()) {
                            selectedCourierData = data;
                        }
                    });

                    if (selectedCourierData && selectedCourierData.is_available === false) {
                        if (alertEl) {
                            alertEl.classList.remove('hidden');
                            if (alertMsg) alertMsg.textContent = selectedCourierData.message || 'Kurir yang dipilih belum melayani pengiriman ke kota/wilayah tujuan ini.';
                        }
                        updateShippingUI(courier, 0, selectedCourierData);
                        if (submitBtn) submitBtn.disabled = true;
                    } else {
                        if (alertEl) alertEl.classList.add('hidden');
                        var cost = selectedCourierData ? selectedCourierData.shipping_cost : (courierShippingPrices[courier] || 0);
                        updateShippingUI(courier, cost, selectedCourierData);
                        if (submitBtn) submitBtn.disabled = false;
                    }
                }
            })
            .catch(function () {
                var fallbackCost = courierShippingPrices[courier] || 0;
                var fallbackDetails = courierShippingDetails[courier] || null;
                updateShippingUI(courier, fallbackCost, fallbackDetails);
            });
    }

    window.fetchAndUpdateShippingCost = fetchAndUpdateShippingCost;

    if (courierSelect) {
        courierSelect.addEventListener('change', fetchAndUpdateShippingCost);
    }

    function isShippingCouponType(dt) {
        return dt === 'shipping' || dt == 3 || dt === '3' || dt === 'Gratis Ongkir';
    }

    function checkCouponIsShipping(code) {
        var card = document.querySelector('.coupon-card[data-code="' + code + '"]');
        if (card) {
            return isShippingCouponType(card.dataset.discountType);
        }
        if (manualCouponsData[code]) {
            return isShippingCouponType(manualCouponsData[code].discountType);
        }
        return false;
    }

    window.selectCoupon = function (button) {
        var code = button.dataset.code;
        var isNewShipping = isShippingCouponType(button.dataset.discountType);
        var minPurchase = parseFloat(button.dataset.minPurchase || 0);

        if (selectedCoupons.includes(code)) {
            selectedCoupons = selectedCoupons.filter(function (selectedCode) { return selectedCode !== code; });
        } else {
            // Check minimum purchase against subtotal
            var currentBase = Math.max(0, subtotal - promoDiscount - productDiscount);
            if (minPurchase > 0 && currentBase < minPurchase) {
                if (typeof addToast === 'function') {
                    addToast('warning', 'Minimum belanja ' + formatRupiah(minPurchase) + ' untuk menggunakan voucher ini.');
                } else {
                    alert('Minimum belanja ' + formatRupiah(minPurchase) + ' untuk menggunakan voucher ini.');
                }
                return;
            }

            // Kombinasi: maksimal 1 voucher gratis ongkir dan 1 voucher biasa
            selectedCoupons = selectedCoupons.filter(function (selectedCode) {
                var isCurrentShipping = checkCouponIsShipping(selectedCode);
                return isNewShipping ? !isCurrentShipping : isCurrentShipping;
            });
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

                var allowStacking = (data.voucher.allow_stacking || data.voucher.allowStacking) ? 1 : 0;
                manualCouponsData[code] = {
                    discountType: discountType,
                    discountValue: discountVal,
                    maxDiscount: maxDiscount,
                    allowStacking: allowStacking,
                    products: data.voucher.products || []
                };

                var isNewShipping = isShippingCouponType(discountType);
                selectedCoupons = selectedCoupons.filter(function (selectedCode) {
                    var isCurrentShipping = checkCouponIsShipping(selectedCode);
                    return isNewShipping ? !isCurrentShipping : isCurrentShipping;
                });

                if (!selectedCoupons.includes(code)) {
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
    if (courierSelect && courierSelect.value && courierShippingPrices[courierSelect.value] !== undefined) {
        currentShippingCost = courierShippingPrices[courierSelect.value];
        var initDetails = courierShippingDetails[courierSelect.value] || null;
        updateShippingUI(courierSelect.value, currentShippingCost, initDetails);
    } else {
        updateTotal();
    }
});

document.addEventListener('DOMContentLoaded', function() {
    var provinceSelect = document.getElementById('checkout-province') || document.querySelector('select[name="province_id"]');
    var citySelect = document.getElementById('checkout-city') || document.querySelector('select[name="city_id"]');
    var subDistrictSelect = document.getElementById('checkout-sub-district') || document.querySelector('select[name="sub_district_id"]');
    var cityInput = document.getElementById('city-display') || document.querySelector('input[name="city"]');
    var postalInput = document.querySelector('input[name="postal_code"]');
    
    var subDistrictMapEl = document.getElementById('checkout-subdistrict-map');
    var subDistrictMap = subDistrictMapEl ? JSON.parse(subDistrictMapEl.textContent || '{}') : {};

    function initSelect2() {
        if (window.jQuery && typeof window.jQuery.fn.select2 === 'function') {
            var $j = window.jQuery;
            var $prov = $j('#checkout-province');
            var $city = $j('#checkout-city');
            var $sub = $j('#checkout-sub-district');

            if ($prov.length && !$prov.hasClass('select2-hidden-accessible')) {
                $prov.select2({
                    placeholder: 'Pilih Provinsi',
                    allowClear: false,
                    width: '100%'
                });
            }

            if ($city.length && !$city.hasClass('select2-hidden-accessible')) {
                $city.select2({
                    placeholder: 'Pilih Kota/Kabupaten',
                    allowClear: false,
                    width: '100%'
                });
            }

            if ($sub.length && !$sub.hasClass('select2-hidden-accessible')) {
                $sub.select2({
                    placeholder: 'Pilih Kecamatan/Kelurahan',
                    allowClear: false,
                    width: '100%'
                });
            }
        }
    }

    initSelect2();
    setTimeout(initSelect2, 150);

    function syncSelect2(selectElement) {
        if (window.jQuery && typeof window.jQuery.fn.select2 === 'function' && selectElement) {
            var $el = window.jQuery(selectElement);
            if ($el.length) {
                if (!$el.hasClass('select2-hidden-accessible')) {
                    var ph = $el.attr('id') === 'checkout-province' ? 'Pilih Provinsi' :
                             ($el.attr('id') === 'checkout-city' ? 'Pilih Kota/Kabupaten' : 'Pilih Kecamatan/Kelurahan');
                    $el.select2({ placeholder: ph, allowClear: false, width: '100%' });
                }
                $el.prop('disabled', selectElement.disabled);
                $el.val(selectElement.value).trigger('change.select2');
            }
        }
    }

    var lastLoadedProvId = null;
    var lastLoadedCityId = null;

    function loadCities(provinceId, selectedCityId, callback) {
        if (!citySelect) return;
        citySelect.innerHTML = '<option value="">Memuat kota/kabupaten...</option>';
        citySelect.disabled = true;
        syncSelect2(citySelect);

        if (subDistrictSelect) {
            subDistrictSelect.innerHTML = '<option value="">Pilih Kota Terlebih Dahulu</option>';
            subDistrictSelect.disabled = true;
            syncSelect2(subDistrictSelect);
        }

        if (!provinceId) {
            citySelect.innerHTML = '<option value="">Pilih Provinsi Terlebih Dahulu</option>';
            citySelect.disabled = true;
            syncSelect2(citySelect);
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
                if (selectedCityId) {
                    citySelect.value = selectedCityId;
                    lastLoadedCityId = selectedCityId;
                    var selCityObj = cities.find(function(c) { return String(c.id) === String(selectedCityId); });
                    if (selCityObj && cityInput) {
                        cityInput.value = selCityObj.name;
                    }
                }
                syncSelect2(citySelect);
                if (callback) callback();
            })
            .catch(function(err) {
                console.error('Error loading cities:', err);
                citySelect.innerHTML = '<option value="">Gagal memuat kota</option>';
                citySelect.disabled = false;
                syncSelect2(citySelect);
            });
    }

    function loadSubDistricts(cityId, selectedSubDistrictId, callback) {
        if (!subDistrictSelect) return;
        subDistrictSelect.innerHTML = '<option value="">Memuat kecamatan/kelurahan...</option>';
        subDistrictSelect.disabled = true;
        syncSelect2(subDistrictSelect);

        if (!cityId) {
            subDistrictSelect.innerHTML = '<option value="">Pilih Kota Terlebih Dahulu</option>';
            subDistrictSelect.disabled = true;
            syncSelect2(subDistrictSelect);
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
                if (selectedSubDistrictId) {
                    subDistrictSelect.value = selectedSubDistrictId;
                    var selSdObj = subDistricts.find(function(sd) { return String(sd.id) === String(selectedSubDistrictId); });
                    if (selSdObj && postalInput && !postalInput.value && selSdObj.postal_code) {
                        postalInput.value = selSdObj.postal_code;
                    }
                }
                syncSelect2(subDistrictSelect);
                if (callback) callback();
            })
            .catch(function(err) {
                console.error('Error loading sub-districts:', err);
                subDistrictSelect.innerHTML = '<option value="">Gagal memuat kecamatan/kelurahan</option>';
                subDistrictSelect.disabled = false;
                syncSelect2(subDistrictSelect);
            });
    }

    function applyAddressData(data, callback) {
        if (!data) return;

        if (data.name) {
            var nInput = document.querySelector('input[name="name"]');
            if (nInput && !nInput.value) {
                nInput.value = data.name;
                nInput.dispatchEvent(new Event('input'));
            }
        }
        if (data.phone) {
            var pInput = document.querySelector('input[name="phone"]');
            if (pInput && !pInput.value) {
                pInput.value = data.phone;
                pInput.dispatchEvent(new Event('input'));
            }
        }
        if (data.email) {
            var eInput = document.querySelector('input[name="email"]');
            if (eInput && !eInput.value) {
                eInput.value = data.email;
                eInput.dispatchEvent(new Event('input'));
            }
        }
        if (data.address) {
            var addrInput = document.querySelector('textarea[name="address"]');
            if (addrInput) {
                addrInput.value = data.address;
                addrInput.dispatchEvent(new Event('input'));
            }
        }
        if (data.postal_code) {
            if (postalInput) {
                postalInput.value = data.postal_code;
                postalInput.dispatchEvent(new Event('input'));
            }
        }

        var provId = data.province_id;
        var cityId = data.city_id;
        var subDistrictId = data.sub_district_id;
        var cityName = data.city || data.city_name;

        if (cityName && cityInput) {
            cityInput.value = cityName;
        }

        if (provId && provinceSelect) {
            provinceSelect.value = provId;
            lastLoadedProvId = provId;
            syncSelect2(provinceSelect);

            loadCities(provId, cityId, function() {
                if (cityId && citySelect) {
                    citySelect.value = cityId;
                    lastLoadedCityId = cityId;
                    syncSelect2(citySelect);

                    var selOpt = citySelect.options[citySelect.selectedIndex];
                    if (cityInput && selOpt && selOpt.value) {
                        cityInput.value = selOpt.textContent.trim();
                    }

                    loadSubDistricts(cityId, subDistrictId, function() {
                        if (subDistrictId && subDistrictSelect) {
                            subDistrictSelect.value = subDistrictId;
                            syncSelect2(subDistrictSelect);
                            var subOpt = subDistrictSelect.options[subDistrictSelect.selectedIndex];
                            if (subOpt && postalInput && !postalInput.value) {
                                var p = subOpt.getAttribute('data-postal');
                                if (p) postalInput.value = p;
                            }
                        }
                        if (typeof window.fetchAndUpdateShippingCost === 'function') {
                            window.fetchAndUpdateShippingCost();
                        }
                        if (typeof callback === 'function') callback();
                    });
                } else {
                    if (typeof callback === 'function') callback();
                }
            });
        } else if (subDistrictId && subDistrictSelect) {
            subDistrictSelect.value = subDistrictId;
            syncSelect2(subDistrictSelect);
            if (typeof window.fetchAndUpdateShippingCost === 'function') {
                window.fetchAndUpdateShippingCost();
            }
            if (typeof callback === 'function') callback();
        } else {
            if (typeof callback === 'function') callback();
        }
    }

    if (window.jQuery && typeof window.jQuery.fn.select2 === 'function') {
        var $j = window.jQuery;
        $j('#checkout-province').on('change', function(e) {
            var provId = $j(this).val();
            if (provId === lastLoadedProvId) return;
            lastLoadedProvId = provId;
            loadCities(provId, null);
        });

        $j('#checkout-city').on('change', function(e) {
            var cId = $j(this).val();
            if (cId === lastLoadedCityId) return;
            lastLoadedCityId = cId;
            var selOpt = $j('#checkout-city option:selected');
            if (cityInput && cId && selOpt.length) {
                cityInput.value = selOpt.text().trim();
            }
            loadSubDistricts(cId, null);
        });

        $j('#checkout-sub-district').on('change', function(e) {
            var val = $j(this).val();
            var selOpt = $j('#checkout-sub-district option:selected');
            if (selOpt.length && postalInput) {
                var postal = selOpt.attr('data-postal');
                if (postal) postalInput.value = postal;
            }
            var data = subDistrictMap[val];
            if (data) {
                if (cityInput && !cityInput.value && data.city) cityInput.value = data.city;
                if (postalInput && !postalInput.value && data.postal_code) postalInput.value = data.postal_code;
            }
            if (typeof window.fetchAndUpdateShippingCost === 'function') {
                window.fetchAndUpdateShippingCost();
            }
        });
    } else {
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
    }

    window.loadCities = loadCities;
    window.loadSubDistricts = loadSubDistricts;
    window.applyAddressData = applyAddressData;

    var originalFillAddress = window.fillAddress;
    window.fillAddress = function(el) {
        if (originalFillAddress) originalFillAddress(el);
        var savedAddressesEl = document.getElementById('checkout-saved-addresses');
        var addresses = savedAddressesEl ? JSON.parse(savedAddressesEl.textContent || '[]') : [];
        var selected = addresses.find(function(a) { return String(a.id) === String(el.value); });
        if (selected) {
            applyAddressData(selected);
            var addressSelector = document.getElementById('address-selector');
            if (addressSelector) addressSelector.classList.add('hidden');
        }
    };
});
