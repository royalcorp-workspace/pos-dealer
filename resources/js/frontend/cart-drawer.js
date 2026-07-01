
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

    function applySelectedCoupon() {
        currentCartTotal = getLatestCartTotal();
        window.currentCartTotal = currentCartTotal;

        if (!selectedCartCoupon) {
            const selectedEl = document.getElementById('cart-selected-coupon');
            const discountRow = document.getElementById('cart-coupon-discount-row');
            const totalEl = document.getElementById('cart-total-with-discount');
            if (selectedEl) selectedEl.classList.add('hidden');
            if (discountRow) discountRow.classList.add('hidden');
            if (totalEl) totalEl.textContent = formatRupiah(currentCartTotal);
            return;
        }

        const discount = calculateDiscount(selectedCartCoupon);
        const selectedDiscountEl = document.getElementById('cart-selected-discount');
        const couponDiscountEl = document.getElementById('cart-coupon-discount');
        const totalEl = document.getElementById('cart-total-with-discount');
        const selectedCouponEl = document.getElementById('cart-selected-coupon');
        const discountRow = document.getElementById('cart-coupon-discount-row');

        if (selectedDiscountEl) selectedDiscountEl.textContent = '- ' + formatRupiah(discount);
        if (couponDiscountEl) couponDiscountEl.textContent = '- ' + formatRupiah(discount);
        if (totalEl) totalEl.textContent = formatRupiah(Math.max(0, currentCartTotal - discount));
        if (selectedCouponEl) selectedCouponEl.classList.remove('hidden');
        if (discountRow) discountRow.classList.remove('hidden');
    }

    window.deselectCartCoupon = function () {
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

        if (selectedCartCoupon && selectedCartCoupon.code === button.dataset.code) {
            window.deselectCartCoupon();
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
            maxDiscount: button.dataset.maxDiscount && Number(button.dataset.maxDiscount) > 0 ? Number(button.dataset.maxDiscount) : undefined
        };

        $$('.coupon-option').forEach(function (item) {
            item.classList.remove('border-brand-gold', 'bg-brand-light');
            const label = item.querySelector('.coupon-option-label');
            if (label) label.textContent = 'Pilih';
        });

        button.classList.add('border-brand-gold', 'bg-brand-light');
        const label = button.querySelector('.coupon-option-label');
        if (label) label.textContent = 'Dipilih';

        const discount = calculateDiscount(coupon);
        selectedCartCoupon = coupon;
        localStorage.setItem('selectedCartCoupon', JSON.stringify(coupon));

        const couponTriggerTitle = document.getElementById('cart-coupon-trigger-title');
        const couponTriggerCode = document.getElementById('cart-coupon-trigger-code');
        const selectedTitle = document.getElementById('cart-selected-title');
        const selectedDiscount = document.getElementById('cart-selected-discount');
        const couponDiscount = document.getElementById('cart-coupon-discount');

        if (couponTriggerTitle) couponTriggerTitle.textContent = coupon.title;
        if (couponTriggerCode) couponTriggerCode.textContent = coupon.code;
        if (selectedTitle) selectedTitle.textContent = coupon.title;
        if (selectedDiscount) selectedDiscount.textContent = '- ' + formatRupiah(discount);
        if (couponDiscount) couponDiscount.textContent = '- ' + formatRupiah(discount);
        applySelectedCoupon();
        const selectedCouponEl = document.getElementById('cart-selected-coupon');
        const discountRow = document.getElementById('cart-coupon-discount-row');
        if (selectedCouponEl) selectedCouponEl.classList.remove('hidden');
        if (discountRow) discountRow.classList.remove('hidden');
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
            if (!data.cart || !data.cart[cartId]) {
                const itemRow = button.closest('[data-cart-item-id]');
                if (itemRow) itemRow.remove();
                if (!document.querySelector('[data-cart-item-id]')) {
                    if (document.getElementById('cart-footer')) {
                        document.getElementById('cart-footer').classList.add('hidden');
                    }
                    window.deselectCartCoupon();
                }
            } else {
                quantityElement.textContent = data.cart[cartId].quantity;
            }

            currentCartTotal = Number(data.cart_total || 0);
            window.currentCartTotal = currentCartTotal;

            const drawerBody = document.getElementById('cart-drawer-body');
            const cartFooter = document.getElementById('cart-footer');
            const subtotalEl = document.getElementById('cart-drawer-subtotal');
            if (drawerBody) drawerBody.setAttribute('data-cart-total', currentCartTotal);
            if (cartFooter) cartFooter.setAttribute('data-cart-total', currentCartTotal);
            if (subtotalEl) subtotalEl.textContent = formatRupiah(currentCartTotal);

            applySelectedCoupon();

            const countBadge = document.getElementById('cart-count-badge');
            const headerTotal = document.getElementById('header-cart-total');
            if (countBadge) countBadge.textContent = data.cart_count || 0;
            if (headerTotal) headerTotal.textContent = formatRupiah(data.cart_total || 0);
        })
        .catch(function () {
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
                    $$('.coupon-option').forEach(function (item) {
                        item.classList.remove('border-brand-gold', 'bg-brand-light');
                        const label = item.querySelector('.coupon-option-label');
                        if (label) label.textContent = 'Pilih';
                    });

                    var discount = data.voucher ? (data.voucher.discount || 0) : 0;
                    var discountType = '1';
                    if (data.voucher && data.voucher.type) {
                        if (data.voucher.type === 'Percentage' || data.voucher.type === 'Persentase') discountType = '1';
                        else if (data.voucher.type === 'Fixed' || data.voucher.type === 'Nominal') discountType = '2';
                        else if (data.voucher.type === 'Shipping' || data.voucher.type === 'Gratis Ongkir') discountType = '3';
                    }
                    var coupon = {
                        code: code,
                        title: data.voucher ? (data.voucher.title || code) : code,
                        description: data.voucher ? (data.voucher.description || data.voucher.title || code) : code,
                        discount: discount,
                        discountType: discountType,
                        discountValue: parseFloat(data.voucher ? data.voucher.value : 0) || 0,
                        maxDiscount: (data.voucher && data.voucher.max_discount && Number(data.voucher.max_discount) > 0) ? Number(data.voucher.max_discount) : Infinity
                    };
                    selectedCartCoupon = coupon;
                    localStorage.setItem('selectedCartCoupon', JSON.stringify(coupon));

                    const couponTriggerTitle = document.getElementById('cart-coupon-trigger-title');
                    const couponTriggerCode = document.getElementById('cart-coupon-trigger-code');
                    const selectedTitle = document.getElementById('cart-selected-title');
                    if (couponTriggerTitle) couponTriggerTitle.textContent = coupon.title;
                    if (couponTriggerCode) couponTriggerCode.textContent = coupon.code;
                    if (selectedTitle) selectedTitle.textContent = coupon.title;

                    const selectedDiscountEl = document.getElementById('cart-selected-discount');
                    const couponDiscountEl = document.getElementById('cart-coupon-discount');
                    const totalEl = document.getElementById('cart-total-with-discount');
                    const selectedCouponEl = document.getElementById('cart-selected-coupon');
                    const discountRow = document.getElementById('cart-coupon-discount-row');
                    if (selectedDiscountEl) selectedDiscountEl.textContent = '- ' + formatRupiah(discount);
                    if (couponDiscountEl) couponDiscountEl.textContent = '- ' + formatRupiah(discount);
                    if (totalEl) totalEl.textContent = formatRupiah(Math.max(0, currentCartTotal - discount));
                    if (selectedCouponEl) selectedCouponEl.classList.remove('hidden');
                    if (discountRow) discountRow.classList.remove('hidden');

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
            return;
        }

        const saved = localStorage.getItem('selectedCartCoupon');
        if (!saved) return;

        try {
            const coupon = JSON.parse(saved);
            const button = document.querySelector('.coupon-option[data-code="' + coupon.code + '"]');
            if (button) {
                selectedCartCoupon = {
                    code: button.dataset.code,
                    title: button.dataset.title,
                    description: button.dataset.description,
                    discount: button.dataset.discount,
                    discountType: button.dataset.discountType === 'percentage' ? 1 : (button.dataset.discountType === 'fixed' ? 2 : (button.dataset.discountType === 'shipping' ? 3 : 4)),
                    discountValue: parseFloat(button.dataset.discountValue) || 0,
                    maxDiscount: button.dataset.maxDiscount && Number(button.dataset.maxDiscount) > 0 ? Number(button.dataset.maxDiscount) : undefined
                };
                button.classList.add('border-brand-gold', 'bg-brand-light');
                const label = button.querySelector('.coupon-option-label');
                if (label) label.textContent = 'Dipilih';
                const couponTriggerTitle = document.getElementById('cart-coupon-trigger-title');
                const couponTriggerCode = document.getElementById('cart-coupon-trigger-code');
                const selectedTitle = document.getElementById('cart-selected-title');
                if (couponTriggerTitle) couponTriggerTitle.textContent = selectedCartCoupon.title;
                if (couponTriggerCode) couponTriggerCode.textContent = selectedCartCoupon.code;
                if (selectedTitle) selectedTitle.textContent = selectedCartCoupon.title;
                applySelectedCoupon();
            } else {
                selectedCartCoupon = coupon;
                const couponTriggerTitle = document.getElementById('cart-coupon-trigger-title');
                const couponTriggerCode = document.getElementById('cart-coupon-trigger-code');
                const selectedTitle = document.getElementById('cart-selected-title');
                if (couponTriggerTitle) couponTriggerTitle.textContent = selectedCartCoupon.title;
                if (couponTriggerCode) couponTriggerCode.textContent = selectedCartCoupon.code;
                if (selectedTitle) selectedTitle.textContent = selectedCartCoupon.title;
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
