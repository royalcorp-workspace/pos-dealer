
window.selectVariant = function (el) {
    document.querySelectorAll('[data-variant-id]').forEach(function (btn) {
        btn.classList.remove('border-brand-gold', 'bg-brand-light', 'text-brand-dark');
        btn.classList.add('border-brand-muted', 'bg-white', 'text-gray-600');
    });
    el.classList.remove('border-brand-muted', 'bg-white', 'text-gray-600');
    el.classList.add('border-brand-gold', 'bg-brand-light', 'text-brand-dark');

    const priceEl = document.getElementById('product-price');
    const priceLabel = document.getElementById('price-label');

    if (priceEl && el.dataset.variantPrice) {
        priceEl.textContent = 'Rp ' + Number(el.dataset.variantPrice).toLocaleString('id-ID');
    }

    if (priceLabel) {
        const variantName = el.textContent.trim().split('\n')[0];
        priceLabel.textContent = 'Harga untuk ukuran: ' + variantName;
    }

    const variantInput = document.getElementById('variant-id-input');
    if (variantInput) {
        variantInput.value = el.dataset.variantId;
    }
};

window.updateQty = function (change) {
    const input = document.getElementById('quantity-input');
    if (!input) return;
    let val = parseInt(input.value) || 1;
    val = Math.max(1, val + change);
    input.value = val;
};
