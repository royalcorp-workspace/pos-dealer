document.addEventListener('DOMContentLoaded', function () {
    var courierSelect = document.querySelector('select[name="courier"]');
    var shippingCost = document.getElementById('shipping-cost');
    var voucherDiscount = document.getElementById('voucher-discount');
    var voucherDiscountValue = document.getElementById('voucher-discount-value');
    var totalCost = document.getElementById('total-cost');
    var subtotal = Number(document.getElementById('checkout-subtotal')?.dataset.value || 0);
    var currentShippingCost = Number(document.getElementById('checkout-shipping-cost')?.dataset.value || 25000);
    var selectedCouponDiscount = Number(document.getElementById('checkout-voucher-discount')?.dataset.value || 0);
    var selectedCoupons = document.getElementById('checkout-selected-voucher-codes')?.dataset.value ? document.getElementById('checkout-selected-voucher-codes').dataset.value.split(',') : [];

    function formatRupiah(value) {
        return 'Rp ' + Number(value).toLocaleString('id-ID');
    }

    function updateTotal() {
        var total = Math.max(0, subtotal + currentShippingCost - selectedCouponDiscount);
        if (totalCost) totalCost.textContent = formatRupiah(total);
        if (voucherDiscount) voucherDiscount.textContent = '- ' + formatRupiah(selectedCouponDiscount);
    }

    function restoreCartCoupon() {
        var saved = localStorage.getItem('selectedCartCoupons');
        if (!saved) return;
        try {
            var coupons = JSON.parse(saved);
            if (!Array.isArray(coupons)) return;
            coupons.forEach(function (coupon) {
                var button = document.querySelector('.coupon-card[data-code="' + coupon.code + '"]');
                if (!button) return;
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
    }

    var courierShippingPricesEl = document.getElementById('checkout-courier-shipping-prices');
    var courierShippingPrices = courierShippingPricesEl ? JSON.parse(courierShippingPricesEl.textContent || '{}') : {};

    if (courierSelect) {
        courierSelect.addEventListener('change', function () {
            currentShippingCost = courierShippingPrices[this.value] || 0;
            if (shippingCost) {
                shippingCost.innerHTML = '<span class="text-brand-dark">' + formatRupiah(currentShippingCost) + '</span>';
            }
            updateTotal();
        });
    }

    window.selectCoupon = function (button) {
        var code = button.dataset.code;
        var allowStacking = button.dataset.allowStacking === '1';
        var hasNonStackable = selectedCoupons.some(function (selectedCode) {
            var item = document.querySelector('.coupon-card[data-code="' + selectedCode + '"]');
            return item && item.dataset.allowStacking !== '1';
        });

        if (!allowStacking || hasNonStackable || selectedCoupons.length === 0 || selectedCoupons.includes(code)) {
            selectedCoupons = [];
            document.querySelectorAll('.coupon-card').forEach(function (item) {
                item.classList.remove('border-brand-gold', 'bg-brand-light');
                var label = item.querySelector('.select-coupon-label');
                if (label) label.textContent = 'Pilih';
            });
        }

        if (!selectedCoupons.includes(code)) {
            selectedCoupons.push(code);
        } else {
            selectedCoupons = selectedCoupons.filter(function (selectedCode) { return selectedCode !== code; });
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
        if (voucherCodeEl) voucherCodeEl.value = selectedCoupons.join(',');
        if (voucherCodesEl) voucherCodesEl.value = selectedCoupons.join(',');
        localStorage.setItem('selectedCartCoupons', JSON.stringify(selectedCoupons.map(function (code) { return { code: code }; })));
        if (selectedCoupons.length === 0) {
            selectedCouponDiscount = 0;
            var selectedText = document.getElementById('selected-coupon-text');
            if (selectedText) selectedText.innerHTML = 'Belum ada kupon dipilih.';
        } else {
            var totalDiscount = 0;
            selectedCoupons.forEach(function (code) {
                var button = document.querySelector('.coupon-card[data-code="' + code + '"]');
                if (!button) return;
                var discountType = button.dataset.discountType == '1' || button.dataset.discountType === 'percentage' ? 1 : (button.dataset.discountType == '2' || button.dataset.discountType === 'fixed' ? 2 : (button.dataset.discountType == '3' || button.dataset.discountType === 'shipping' ? 3 : 4));
                var discountValue = parseFloat(button.dataset.discountValue) || 0;
                var maxDiscount = button.dataset.maxDiscount && button.dataset.maxDiscount != '' && button.dataset.maxDiscount != '0' ? Number(button.dataset.maxDiscount) : Infinity;
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

    restoreCartCoupon();
});
