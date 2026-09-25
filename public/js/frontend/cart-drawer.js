
(function () {
    document.addEventListener('cart-drawer-updated', function () {
        const drawerBody = document.getElementById('cart-drawer-body');
        if (drawerBody && drawerBody.dataset.cartTotal) {
            currentCartTotal = Number(drawerBody.dataset.cartTotal || 0);
            window.currentCartTotal = currentCartTotal;
        }
        if (selectedCartCoupon) {
            applySelectedCoupon();
        }
    });

    function getLatestCartTotal() {
        const footer = document.getElementById('cart-footer');
        if (footer && footer.dataset.cartTotal) {
            return Number(footer.dataset.cartTotal || 0);
        }
        const drawerBody = document.getElementById('cart-drawer-body');
        if (drawerBody && drawerBody.dataset.cartTotal) {
            return Number(drawerBody.dataset.cartTotal || 0);
        }
        return 0;
    }

    let currentCartTotal = getLatestCartTotal();
    window.currentCartTotal = currentCartTotal;
    let selectedCartCoupons = [];
    let selectedCartCoupon = null;
    const defaultShipping = 0;

    function formatRupiah(value) {
        return 'Rp ' + Number(value).toLocaleString('id-ID');
    }

    function calculateDiscount(coupon, total = window.currentCartTotal || currentCartTotal) {
        const value = Number(coupon.discountValue || 0);
        const maxDiscount = typeof coupon.maxDiscount === 'number' && !isNaN(coupon.maxDiscount) ? coupon.maxDiscount : Infinity;
        let discount = 0;

        if (coupon.discountType == 1 || coupon.discountType === '1' || coupon.discountType === 'percentage') {
            discount = (total * value) / 100;
            if (maxDiscount !== Infinity) discount = Math.min(discount, maxDiscount);
        } else if (coupon.discountType == 2 || coupon.discountType === '2' || coupon.discountType === 'fixed') {
            discount = Math.min(value, total);
        } else if (coupon.discountType == 3 || coupon.discountType === '3' || coupon.discountType === 'shipping') {
            discount = Math.min(value, defaultShipping);
        }

        return discount;
    }

    function isShippingCouponType(dt) {
        return dt === 'shipping' || dt == 3 || dt === '3' || dt === 'Gratis Ongkir';
    }

    function calculateTotalDiscount() {
        currentCartTotal = getLatestCartTotal();
        window.currentCartTotal = currentCartTotal;
        let totalDiscount = 0;

        selectedCartCoupons.forEach(function (coupon) {
            if (!isShippingCouponType(coupon.discountType)) {
                totalDiscount += calculateDiscount(coupon, currentCartTotal);
            }
        });

        return Math.min(totalDiscount, currentCartTotal);
    }

    function applySelectedCoupon() {
        currentCartTotal = getLatestCartTotal();
        window.currentCartTotal = currentCartTotal;

        // Auto-drop vouchers that no longer meet min_purchase
        let droppedAny = false;
        selectedCartCoupons = selectedCartCoupons.filter(function (c) {
            if (c.minPurchase && c.minPurchase > 0 && currentCartTotal < c.minPurchase) {
                droppedAny = true;
                if (typeof addToast === 'function') {
                    addToast('warning', 'Voucher ' + c.code + ' dilepas karena total belanja kurang dari ' + formatRupiah(c.minPurchase));
                }
                return false;
            }
            return true;
        });

        if (droppedAny) {
            localStorage.setItem('selectedCartCoupons', JSON.stringify(selectedCartCoupons));
            if (selectedCartCoupons.length > 0) {
                localStorage.setItem('selectedCartCoupon', JSON.stringify(selectedCartCoupons[0]));
            } else {
                localStorage.removeItem('selectedCartCoupon');
                localStorage.removeItem('selectedCartCoupons');
            }
            $$('.coupon-option').forEach(function (item) {
                const isSelected = selectedCartCoupons.some(function (c) { return c.code === item.dataset.code; });
                item.classList.toggle('border-brand-gold', isSelected);
                item.classList.toggle('bg-brand-light', isSelected);
                const label = item.querySelector('.coupon-option-label');
                if (label) label.textContent = isSelected ? 'Dipilih' : 'Pilih';
            });
        }

        if (!selectedCartCoupons || selectedCartCoupons.length === 0) {
            selectedCartCoupon = null;
            const selectedEl = document.getElementById('cart-selected-coupon');
            const discountRow = document.getElementById('cart-coupon-discount-row');
            const totalEl = document.getElementById('cart-total-with-discount');
            if (selectedEl) selectedEl.classList.add('hidden');
            if (discountRow) discountRow.classList.add('hidden');
            if (totalEl) totalEl.textContent = formatRupiah(currentCartTotal);

            const couponTriggerTitle = document.getElementById('cart-coupon-trigger-title');
            const couponTriggerCode = document.getElementById('cart-coupon-trigger-code');
            const selectedTitle = document.getElementById('cart-selected-title');
            const selectedDiscount = document.getElementById('cart-selected-discount');
            const couponDiscount = document.getElementById('cart-coupon-discount');
            if (couponTriggerTitle) couponTriggerTitle.textContent = 'Pilih Kupon yang tersedia';
            if (couponTriggerCode) couponTriggerCode.textContent = 'Klik untuk melihat kupon aktif';
            if (selectedTitle) selectedTitle.textContent = '';
            if (selectedDiscount) selectedDiscount.textContent = '';
            if (couponDiscount) couponDiscount.textContent = '';
            return;
        }

        selectedCartCoupon = selectedCartCoupons[0];
        const regularDiscount = calculateTotalDiscount();
        const selectedDiscountEl = document.getElementById('cart-selected-discount');
        const couponDiscountEl = document.getElementById('cart-coupon-discount');
        const totalEl = document.getElementById('cart-total-with-discount');
        const selectedCouponEl = document.getElementById('cart-selected-coupon');
        const discountRow = document.getElementById('cart-coupon-discount-row');
        const couponTriggerTitle = document.getElementById('cart-coupon-trigger-title');
        const couponTriggerCode = document.getElementById('cart-coupon-trigger-code');
        const selectedTitle = document.getElementById('cart-selected-title');

        const regularCoupons = selectedCartCoupons.filter(function (c) { return !isShippingCouponType(c.discountType); });
        const shippingCoupons = selectedCartCoupons.filter(function (c) { return isShippingCouponType(c.discountType); });

        let discountSummary = '';
        if (regularCoupons.length > 0 && shippingCoupons.length > 0) {
            if (regularDiscount > 0) {
                discountSummary = '- ' + formatRupiah(regularDiscount) + ' + Gratis Ongkir';
            } else {
                discountSummary = 'Gratis Ongkir (Dihitung saat checkout)';
            }
        } else if (regularCoupons.length > 0) {
            discountSummary = regularDiscount > 0 ? ('- ' + formatRupiah(regularDiscount)) : 'Diskon Kupon Diterapkan';
        } else if (shippingCoupons.length > 0) {
            discountSummary = 'Gratis Ongkir (Dihitung saat checkout)';
        }

        if (selectedCartCoupons.length === 1) {
            const c = selectedCartCoupons[0];
            if (couponTriggerTitle) couponTriggerTitle.textContent = c.title;
            if (couponTriggerCode) couponTriggerCode.textContent = c.code;
            if (selectedTitle) selectedTitle.textContent = c.title;
        } else {
            const codes = selectedCartCoupons.map(function (c) { return c.code; }).join(', ');
            if (couponTriggerTitle) couponTriggerTitle.textContent = selectedCartCoupons.length + ' Kupon Diterapkan';
            if (couponTriggerCode) couponTriggerCode.textContent = codes;
            if (selectedTitle) selectedTitle.textContent = codes + ' (' + selectedCartCoupons.length + ' kupon)';
        }

        if (selectedDiscountEl) selectedDiscountEl.textContent = discountSummary;
        if (couponDiscountEl) couponDiscountEl.textContent = discountSummary;
        if (totalEl) totalEl.textContent = formatRupiah(Math.max(0, currentCartTotal - regularDiscount));
        if (selectedCouponEl) selectedCouponEl.classList.remove('hidden');
        if (discountRow) discountRow.classList.remove('hidden');
    }

    window.deselectCartCoupon = function () {
        selectedCartCoupons = [];
        selectedCartCoupon = null;
        localStorage.removeItem('selectedCartCoupon');
        localStorage.removeItem('selectedCartCoupons');

        $$('.coupon-option').forEach(function (item) {
            item.classList.remove('border-brand-gold', 'bg-brand-light');
            const label = item.querySelector('.coupon-option-label');
            if (label) label.textContent = 'Pilih';
        });

        const couponTriggerTitle = document.getElementById('cart-coupon-trigger-title');
        const couponTriggerCode = document.getElementById('cart-coupon-trigger-code');
        const selectedTitle = document.getElementById('cart-selected-title');
        const selectedDiscount = document.getElementById('cart-selected-discount');
        const couponDiscount = document.getElementById('cart-coupon-discount');
        const selectedCouponEl = document.getElementById('cart-selected-coupon');
        const discountRow = document.getElementById('cart-coupon-discount-row');

        if (couponTriggerTitle) couponTriggerTitle.textContent = 'Pilih Kupon yang tersedia';
        if (couponTriggerCode) couponTriggerCode.textContent = 'Klik untuk melihat kupon aktif';
        if (selectedTitle) selectedTitle.textContent = '';
        if (selectedDiscount) selectedDiscount.textContent = '';
        if (couponDiscount) couponDiscount.textContent = '';
        if (selectedCouponEl) selectedCouponEl.classList.add('hidden');
        if (discountRow) discountRow.classList.add('hidden');

        applySelectedCoupon();
    };

    window.selectCartCoupon = function (button) {
        currentCartTotal = getLatestCartTotal();
        window.currentCartTotal = currentCartTotal;

        const code = button.dataset.code;
        const isNewShipping = isShippingCouponType(button.dataset.discountType);
        const minPurchase = parseFloat(button.dataset.minPurchase) || 0;

        // Check if already selected -> toggle off
        const existingIdx = selectedCartCoupons.findIndex(function (c) { return c.code === code; });
        if (existingIdx !== -1) {
            selectedCartCoupons.splice(existingIdx, 1);
            button.classList.remove('border-brand-gold', 'bg-brand-light');
            const label = button.querySelector('.coupon-option-label');
            if (label) label.textContent = 'Pilih';

            if (selectedCartCoupons.length === 0) {
                window.deselectCartCoupon();
                return;
            } else {
                localStorage.setItem('selectedCartCoupons', JSON.stringify(selectedCartCoupons));
                localStorage.setItem('selectedCartCoupon', JSON.stringify(selectedCartCoupons[0]));
                applySelectedCoupon();
                return;
            }
        }

        // Validate minimum purchase
        if (minPurchase > 0 && currentCartTotal < minPurchase) {
            if (typeof addToast === 'function') {
                addToast('warning', 'Minimum belanja ' + formatRupiah(minPurchase) + ' untuk menggunakan voucher ini.');
            } else {
                alert('Minimum belanja ' + formatRupiah(minPurchase) + ' untuk menggunakan voucher ini.');
            }
            return;
        }

        const discountTypeNum = button.dataset.discountType === 'percentage' ? 1 : (button.dataset.discountType === 'fixed' ? 2 : 3);
        const coupon = {
            code: button.dataset.code,
            title: button.dataset.title,
            description: button.dataset.description,
            discount: button.dataset.discount,
            discountType: discountTypeNum,
            discountValue: parseFloat(button.dataset.discountValue) || 0,
            maxDiscount: button.dataset.maxDiscount && Number(button.dataset.maxDiscount) > 0 ? Number(button.dataset.maxDiscount) : undefined,
            minPurchase: minPurchase,
            allow_stacking: button.dataset.allowStacking === '1' ? 1 : 0
        };

        // Kombinasi: maksimal 1 voucher gratis ongkir dan 1 voucher diskon biasa
        selectedCartCoupons = selectedCartCoupons.filter(function (c) {
            const isCurrentShipping = isShippingCouponType(c.discountType);
            return isNewShipping ? !isCurrentShipping : isCurrentShipping;
        });
        selectedCartCoupons.push(coupon);

        $$('.coupon-option').forEach(function (item) {
            const isSelected = selectedCartCoupons.some(function (c) { return c.code === item.dataset.code; });
            item.classList.toggle('border-brand-gold', isSelected);
            item.classList.toggle('bg-brand-light', isSelected);
            const label = item.querySelector('.coupon-option-label');
            if (label) label.textContent = isSelected ? 'Dipilih' : 'Pilih';
        });

        localStorage.setItem('selectedCartCoupons', JSON.stringify(selectedCartCoupons));
        localStorage.setItem('selectedCartCoupon', JSON.stringify(selectedCartCoupons[0]));
        selectedCartCoupon = selectedCartCoupons[0];

        applySelectedCoupon();
    };

    window.toggleCartCouponPanel = function () {
        const panel = document.getElementById('cart-coupon-panel');
        const icon = document.getElementById('cart-coupon-icon');

        panel.classList.toggle('hidden');
        icon.classList.toggle('rotate-180');
    };

    window.updateCartQuantity = function (button, change) {
        const cartId = button.dataset.cartId;
        const quantityElement = button.closest('[data-cart-item-id]').querySelector('.cart-item-quantity');
        const nextQuantity = Math.max(0, Number(quantityElement.textContent) + change);
        const formData = new FormData();
        formData.append('quantity', nextQuantity);

        const routeCartUpdate = document.body.dataset.routeCartUpdate;
        fetch(routeCartUpdate.replace('__ID__', cartId), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(function (response) {
            return response.json();
        })
        .then(function (data) {
            const drawerBody = document.getElementById('cart-drawer-body');
            const innerScrollEl = drawerBody ? drawerBody.querySelector('.overflow-y-auto, [class*="overflow-y-"]') : null;
            const prevInnerScroll = innerScrollEl ? innerScrollEl.scrollTop : 0;
            const prevBodyScroll = drawerBody ? drawerBody.scrollTop : 0;
            const parentScrollEl = drawerBody ? drawerBody.parentElement : null;
            const prevParentScroll = parentScrollEl ? parentScrollEl.scrollTop : 0;

            if (drawerBody && data.cart_drawer_html) {
                drawerBody.innerHTML = data.cart_drawer_html;

                const restoreScroll = () => {
                    if (prevBodyScroll > 0 && drawerBody) drawerBody.scrollTop = prevBodyScroll;
                    if (prevParentScroll > 0 && parentScrollEl) parentScrollEl.scrollTop = prevParentScroll;
                    const newInner = drawerBody ? drawerBody.querySelector('.overflow-y-auto, [class*="overflow-y-"]') : null;
                    if (newInner && prevInnerScroll > 0) newInner.scrollTop = prevInnerScroll;
                };

                restoreScroll();
                requestAnimationFrame(restoreScroll);
                setTimeout(restoreScroll, 50);
            }

            currentCartTotal = Number(data.cart_total || 0);
            window.currentCartTotal = currentCartTotal;

            const cartFooter = document.getElementById('cart-footer');
            const subtotalEl = document.getElementById('cart-drawer-subtotal');
            if (cartFooter) cartFooter.setAttribute('data-cart-total', currentCartTotal);
            if (subtotalEl) subtotalEl.textContent = formatRupiah(currentCartTotal);

            applySelectedCoupon();

            const countBadge = document.getElementById('cart-count-badge');
            const headerTotal = document.getElementById('header-cart-total');
            if (countBadge) countBadge.textContent = data.cart_count || 0;
            if (headerTotal) headerTotal.textContent = formatRupiah(data.cart_total || 0);

            document.dispatchEvent(new CustomEvent('cart-drawer-updated'));
        })
        .catch(function (err) {
            console.error('Failed to update cart quantity:', err);
            window.location.reload();
        });
    };

    window.validateAndApplyCartVoucher = function () {
        currentCartTotal = getLatestCartTotal();
        window.currentCartTotal = currentCartTotal;

        var input = document.getElementById('manual-cart-voucher-input');
        var code = input.value.trim().toUpperCase();
        var feedback = document.getElementById('manual-cart-voucher-feedback');
        if (!code) {
            if (feedback) feedback.innerHTML = '<span class="text-red-500">Masukkan kode voucher.</span>';
            return;
        }

        var cartFooter = document.getElementById('cart-footer');
        var productIds = cartFooter ? JSON.parse(cartFooter.dataset.productIds || '[]') : [];
        var categoryIds = cartFooter ? JSON.parse(cartFooter.dataset.categoryIds || '[]') : [];
        var cartTotal = window.currentCartTotal || currentCartTotal;

        if (feedback) feedback.innerHTML = '<span class="text-gray-500">Memvalidasi...</span>';

        fetch('/voucher/validate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                code: code,
                cart_total: cartTotal,
                product_ids: productIds,
                category_ids: categoryIds
            })
        })
        .then(function (response) { return response.json(); })
        .then(function (data) {
            if (data.valid) {
                var existingButton = document.querySelector('.coupon-option[data-code="' + code + '"]');
                if (existingButton) {
                    window.selectCartCoupon(existingButton);
                    if (feedback) feedback.innerHTML = '<span class="text-green-600">Voucher berhasil diterapkan!</span>';
                } else {
                    var discount = data.voucher ? (data.voucher.discount || 0) : 0;
                    var discountType = '1';
                    if (data.voucher && data.voucher.type) {
                        if (data.voucher.type === 'Percentage' || data.voucher.type === 'Persentase') discountType = '1';
                        else if (data.voucher.type === 'Fixed' || data.voucher.type === 'Nominal') discountType = '2';
                        else if (data.voucher.type === 'Shipping' || data.voucher.type === 'Gratis Ongkir') discountType = '3';
                    }
                    var allowStacking = (data.voucher && (data.voucher.allow_stacking || data.voucher.allowStacking)) ? 1 : 0;
                    var coupon = {
                        code: code,
                        title: data.voucher ? (data.voucher.title || code) : code,
                        description: data.voucher ? (data.voucher.description || data.voucher.title || code) : code,
                        discount: discount,
                        discountType: discountType,
                        discountValue: parseFloat(data.voucher ? data.voucher.value : 0) || 0,
                        maxDiscount: (data.voucher && data.voucher.max_discount && Number(data.voucher.max_discount) > 0) ? Number(data.voucher.max_discount) : Infinity,
                        allow_stacking: allowStacking
                    };

                    var isNewShipping = isShippingCouponType(discountType);
                    selectedCartCoupons = selectedCartCoupons.filter(function (c) {
                        const isCurrentShipping = isShippingCouponType(c.discountType);
                        return isNewShipping ? !isCurrentShipping : isCurrentShipping;
                    });
                    selectedCartCoupons.push(coupon);

                    $$('.coupon-option').forEach(function (item) {
                        const isSelected = selectedCartCoupons.some(function (c) { return c.code === item.dataset.code; });
                        item.classList.toggle('border-brand-gold', isSelected);
                        item.classList.toggle('bg-brand-light', isSelected);
                        const label = item.querySelector('.coupon-option-label');
                        if (label) label.textContent = isSelected ? 'Dipilih' : 'Pilih';
                    });

                    selectedCartCoupon = selectedCartCoupons[0];
                    localStorage.setItem('selectedCartCoupons', JSON.stringify(selectedCartCoupons));
                    localStorage.setItem('selectedCartCoupon', JSON.stringify(selectedCartCoupons[0]));

                    applySelectedCoupon();

                    if (feedback) feedback.innerHTML = '<span class="text-green-600">Voucher berhasil diterapkan!</span>';
                }
                if (input) input.value = '';
            } else {
                if (feedback) feedback.innerHTML = '<span class="text-red-500">' + data.message + '</span>';
            }
        })
        .catch(function () {
            if (feedback) feedback.innerHTML = '<span class="text-red-500">Gagal memvalidasi voucher.</span>';
        });
    };

    function restoreSavedCoupon() {
        currentCartTotal = getLatestCartTotal();
        window.currentCartTotal = currentCartTotal;

        // Clear stale voucher if cart is empty
        if (currentCartTotal <= 0) {
            localStorage.removeItem('selectedCartCoupon');
            localStorage.removeItem('selectedCartCoupons');
            selectedCartCoupons = [];
            selectedCartCoupon = null;
            return;
        }

        const saved = localStorage.getItem('selectedCartCoupons') || localStorage.getItem('selectedCartCoupon');
        if (!saved) return;

        try {
            let parsed = JSON.parse(saved);
            if (!Array.isArray(parsed)) {
                parsed = (parsed && parsed.code) ? [parsed] : [];
            }
            selectedCartCoupons = [];

            parsed.forEach(function (coupon) {
                const button = document.querySelector('.coupon-option[data-code="' + coupon.code + '"]');
                if (button) {
                    const allowStacking = button.dataset.allowStacking === '1' || button.dataset.allowStacking === 1 || button.dataset.allowStacking === 'true';
                    const c = {
                        code: button.dataset.code,
                        title: button.dataset.title,
                        description: button.dataset.description,
                        discount: button.dataset.discount,
                        discountType: button.dataset.discountType === 'percentage' ? 1 : (button.dataset.discountType === 'fixed' ? 2 : (button.dataset.discountType === 'shipping' ? 3 : 4)),
                        discountValue: parseFloat(button.dataset.discountValue) || 0,
                        maxDiscount: button.dataset.maxDiscount && Number(button.dataset.maxDiscount) > 0 ? Number(button.dataset.maxDiscount) : undefined,
                        minPurchase: parseFloat(button.dataset.minPurchase) || 0,
                        allow_stacking: allowStacking ? 1 : 0
                    };
                    selectedCartCoupons.push(c);
                    button.classList.add('border-brand-gold', 'bg-brand-light');
                    const label = button.querySelector('.coupon-option-label');
                    if (label) label.textContent = 'Dipilih';
                } else if (coupon && coupon.code) {
                    selectedCartCoupons.push(coupon);
                }
            });

            if (selectedCartCoupons.length > 0) {
                selectedCartCoupon = selectedCartCoupons[0];
                applySelectedCoupon();
            }
        } catch (e) {}
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', restoreSavedCoupon);
    } else {
        restoreSavedCoupon();
    }
})();
