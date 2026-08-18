function selectVariant(el) {
    document.querySelectorAll('[data-variant-id]').forEach(btn => {
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
}

function selectColor(el) {
    document.querySelectorAll('[data-color-id]').forEach(btn => {
        btn.classList.remove('border-brand-gold', 'bg-brand-light', 'text-brand-dark');
        btn.classList.add('border-brand-muted', 'bg-white', 'text-gray-600');
    });
    el.classList.remove('border-brand-muted', 'bg-white', 'text-gray-600');
    el.classList.add('border-brand-gold', 'bg-brand-light', 'text-brand-dark');
    
    const priceLabel = document.getElementById('price-label');
    if (priceLabel) {
        const selectedVariant = document.querySelector('[data-variant-id].border-brand-gold');
        const variantName = selectedVariant ? selectedVariant.textContent.trim().split('\n')[0] : '';
        const colorName = el.dataset.colorName;
        priceLabel.textContent = 'Harga untuk ukuran: ' + variantName + ', warna: ' + colorName;
    }
    
    const colorInput = document.getElementById('color-id-input');
    if (colorInput) {
        colorInput.value = el.dataset.colorId;
    }
}

function updateQty(change) {
    const input = document.getElementById('quantity-input');
    if (!input) return;
    let val = parseInt(input.value) || 1;
    val = Math.max(1, val + change);
    input.value = val;
}

document.addEventListener('DOMContentLoaded', function() {
    const qtyInput = document.getElementById('quantity-input');
    const addToCartBtn = document.getElementById('add-to-cart-btn');
    
    if (qtyInput) {
        qtyInput.addEventListener('input', function() {
            let val = parseInt(qtyInput.value);
            if (isNaN(val) || val < 1) {
                if (addToCartBtn) addToCartBtn.disabled = true;
            } else {
                if (addToCartBtn) addToCartBtn.disabled = false;
            }
        });

        const validateQty = function() {
            let val = parseInt(qtyInput.value);
            if (isNaN(val) || val < 1) {
                qtyInput.value = 1;
                if (addToCartBtn) addToCartBtn.disabled = false;
            }
        };
        qtyInput.addEventListener('change', validateQty);
        qtyInput.addEventListener('blur', validateQty);
    }

    const forms = document.querySelectorAll('form[action*="cart"]');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const variantInput = document.getElementById('variant-id-input');
            const hasVariants = document.querySelector('[data-variant-id]') !== null;
            
            if (hasVariants && variantInput && !variantInput.value) {
                e.preventDefault();
                alert('Silakan pilih ukuran terlebih dahulu sebelum menambahkan ke keranjang.');
                return;
            }
            
            const colorInput = document.getElementById('color-id-input');
            const hasColors = document.querySelector('[data-color-id]') !== null;
            if (hasColors && colorInput && !colorInput.value) {
                e.preventDefault();
                alert('Silakan pilih warna terlebih dahulu sebelum menambahkan ke keranjang.');
                return;
            }
        });
    });
});
