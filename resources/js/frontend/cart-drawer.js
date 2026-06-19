
(function () {
    const cartTotalEl = document.getElementById('cart-drawer-cart-total');
    const cartTotal = cartTotalEl ? Number(cartTotalEl.dataset.cartTotal || 0) : 0;
    let currentCartTotal = cartTotal;
    window.currentCartTotal = currentCartTotal;
    let selectedCartCoupon = null;
    const defaultShipping = 25000;

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

    window.selectCartCoupon = function (button) {
        const discountTypeNum = button.dataset.discountType === 'percentage' ? 1 : (button.dataset.discountType === 'fixed' ? 2 : 3);
        const coupon = {
            code: button.dataset.code,
            title: button.dataset.title,
            description: button.dataset.description,
            discount: button.dataset.discount,
            discountType: discountTypeNum,
            discountValue: parseFloat(button.dataset.discountValue) || 0,
            maxDiscount: button.dataset.maxDiscount && button.dataset.maxDiscount != '' ? Number(button.dataset.maxDiscount) : undefined
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
                if (!document.querySelector('[data-cart-item-id]') && document.getElementById('cart-footer')) {
                    document.getElementById('cart-footer').classList.add('hidden');
                }
            } else {
                quantityElement.textContent = data.cart[cartId].quantity;
            }

            currentCartTotal = Number(data.cart_total || 0);
            window.currentCartTotal = currentCartTotal;
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

    function restoreSavedCoupon() {
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
                    maxDiscount: button.dataset.maxDiscount && button.dataset.maxDiscount != '' ? Number(button.dataset.maxDiscount) : undefined
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
            }
        } catch (e) {}
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', restoreSavedCoupon);
    } else {
        restoreSavedCoupon();
    }
})();
