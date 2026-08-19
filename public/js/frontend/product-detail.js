function checkSelection() {
    const hasLegacyVariants = document.querySelector('.legacy-variant-btn') !== null;
    const hasAttributeGroups = document.querySelectorAll('.attribute-group-container').length > 0;
    const hasColors = document.querySelector('[data-color-id]') !== null;
    
    const variantInput = document.getElementById('variant-id-input');
    const colorInput = document.getElementById('color-id-input');
    
    let isComplete = true;
    if ((hasLegacyVariants || hasAttributeGroups) && (!variantInput || !variantInput.value)) {
        isComplete = false;
    }
    if (hasColors && (!colorInput || !colorInput.value)) {
        isComplete = false;
    }
    
    const addToCartBtn = document.getElementById('add-to-cart-btn');
    const qtyInput = document.getElementById('quantity-input');
    const qtyMinusBtn = document.getElementById('qty-minus-btn');
    const qtyPlusBtn = document.getElementById('qty-plus-btn');
    
    if (isComplete) {
        if (addToCartBtn) addToCartBtn.disabled = false;
        if (qtyInput) qtyInput.disabled = false;
        if (qtyMinusBtn) qtyMinusBtn.disabled = false;
        if (qtyPlusBtn) qtyPlusBtn.disabled = false;
        
        if (qtyInput && parseInt(qtyInput.value) < 1) {
            if (addToCartBtn) addToCartBtn.disabled = true;
        }
    } else {
        if (addToCartBtn) addToCartBtn.disabled = true;
        if (qtyInput) qtyInput.disabled = true;
        if (qtyMinusBtn) qtyMinusBtn.disabled = true;
        if (qtyPlusBtn) qtyPlusBtn.disabled = true;
    }
}

let selectedAttributes = {};

function selectAttribute(el) {
    const groupName = el.dataset.attributeGroup;
    const value = el.dataset.attributeValue;
    
    const container = el.closest('.attribute-group-container');
    container.querySelectorAll('.attribute-btn').forEach(btn => {
        btn.classList.remove('border-brand-gold', 'bg-brand-light', 'text-brand-dark');
        btn.classList.add('border-brand-muted', 'bg-white', 'text-gray-600');
    });
    el.classList.remove('border-brand-muted', 'bg-white', 'text-gray-600');
    el.classList.add('border-brand-gold', 'bg-brand-light', 'text-brand-dark');
    
    selectedAttributes[groupName] = value;
    findMatchingVariant();
}

function findMatchingVariant() {
    const requiredGroupsCount = document.querySelectorAll('.attribute-group-container').length;
    const currentSelectedCount = Object.keys(selectedAttributes).length;
    const variantInput = document.getElementById('variant-id-input');
    
    if (requiredGroupsCount > 0 && currentSelectedCount === requiredGroupsCount) {
        let matchedVariant = null;
        if (window.productVariants) {
            matchedVariant = window.productVariants.find(v => {
                if (!v.attributes) return false;
                for (const key in selectedAttributes) {
                    if (v.attributes[key] !== selectedAttributes[key]) return false;
                }
                return true;
            });
        }
        
        if (matchedVariant) {
            if (variantInput) variantInput.value = matchedVariant.id;
            
            const priceEl = document.getElementById('product-price');
            const priceLabel = document.getElementById('price-label');
            
            let finalPrice = parseFloat(matchedVariant.price) || 0;
            if (window.staticPromo) {
                const promo = window.staticPromo;
                if (promo.discount_type == 1) {
                    finalPrice = Math.max(0, finalPrice - parseFloat(promo.discount_value));
                } else if (promo.discount_type == 2) {
                    finalPrice = Math.max(0, finalPrice - (finalPrice * parseFloat(promo.discount_value) / 100));
                }
            }
            
            if (priceEl) {
                priceEl.textContent = 'Rp ' + Number(finalPrice).toLocaleString('id-ID');
            }
            
            if (priceLabel) {
                let selectionText = Object.entries(selectedAttributes).map(([k,v]) => `${k}: ${v}`).join(', ');
                priceLabel.textContent = 'Harga untuk ' + selectionText;
            }
        } else {
            if (variantInput) variantInput.value = "";
            window.dispatchEvent(new CustomEvent('show-toast', { detail: { type: 'warning', message: 'Kombinasi varian ini sedang tidak tersedia' } }));
        }
    } else if (requiredGroupsCount > 0) {
        if (variantInput) variantInput.value = "";
    }
    
    checkSelection();
}

function selectVariant(el) {
    document.querySelectorAll('.legacy-variant-btn').forEach(btn => {
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
        priceLabel.textContent = 'Harga untuk variasi: ' + variantName;
    }
    
    const variantInput = document.getElementById('variant-id-input');
    if (variantInput) {
        variantInput.value = el.dataset.variantId;
    }
    
    checkSelection();
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
        let variantName = '';
        if (Object.keys(selectedAttributes).length > 0) {
            variantName = Object.entries(selectedAttributes).map(([k,v]) => `${k}: ${v}`).join(', ');
        } else {
            const selectedVariant = document.querySelector('.legacy-variant-btn.border-brand-gold');
            variantName = selectedVariant ? selectedVariant.textContent.trim().split('\n')[0] : '';
        }
        
        const colorName = el.dataset.colorName;
        if (variantName) {
            priceLabel.textContent = 'Harga untuk variasi: ' + variantName + ', warna: ' + colorName;
        } else {
            priceLabel.textContent = 'Harga untuk warna: ' + colorName;
        }
    }
    
    const colorInput = document.getElementById('color-id-input');
    if (colorInput) {
        colorInput.value = el.dataset.colorId;
    }
    
    checkSelection();
}

function updateQty(change) {
    const input = document.getElementById('quantity-input');
    if (!input) return;
    let val = parseInt(input.value) || 1;
    val = Math.max(1, val + change);
    input.value = val;
    checkSelection();
}

document.addEventListener('DOMContentLoaded', function() {
    const qtyInput = document.getElementById('quantity-input');
    const addToCartBtn = document.getElementById('add-to-cart-btn');
    
    if (qtyInput) {
        qtyInput.addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '');
            if (this.value.length > 0 && this.value.startsWith('0')) {
                this.value = this.value.replace(/^0+/, '');
            }
            
            let val = parseInt(this.value);
            if (isNaN(val) || val < 1) {
                if (addToCartBtn) addToCartBtn.disabled = true;
            } else {
                checkSelection();
            }
        });

        const validateQty = function() {
            let val = parseInt(qtyInput.value);
            if (isNaN(val) || val < 1) {
                qtyInput.value = 1;
            }
            checkSelection();
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
                window.dispatchEvent(new CustomEvent('show-toast', { detail: { type: 'warning', message: 'Silakan pilih ukuran terlebih dahulu sebelum menambahkan ke keranjang.' } }));
                return;
            }
            
            const colorInput = document.getElementById('color-id-input');
            const hasColors = document.querySelector('[data-color-id]') !== null;
            if (hasColors && colorInput && !colorInput.value) {
                e.preventDefault();
                window.dispatchEvent(new CustomEvent('show-toast', { detail: { type: 'warning', message: 'Silakan pilih warna terlebih dahulu sebelum menambahkan ke keranjang.' } }));
                return;
            }
        });
    });
});
