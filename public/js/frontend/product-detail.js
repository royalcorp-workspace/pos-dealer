function checkSelection() {
    const variantSelect = document.getElementById('variant-select-dropdown');
    const hasDropdown = variantSelect !== null;
    const hasCards = document.querySelector('.variant-card-btn') !== null;
    const hasLegacyVariants = document.querySelector('.legacy-variant-btn') !== null;
    const hasAttributeGroups = document.querySelectorAll('.attribute-group-container').length > 0;
    const hasColors = document.querySelector('[data-color-id]') !== null;
    
    const variantInput = document.getElementById('variant-id-input');
    const colorInput = document.getElementById('color-id-input');
    
    let isComplete = true;
    let missingPrompt = '';
    
    if (hasDropdown) {
        if (!variantInput || !variantInput.value) {
            isComplete = false;
            missingPrompt = 'Pilih Variasi Terlebih Dahulu';
        } else {
            const selectedOpt = variantSelect.selectedOptions ? variantSelect.selectedOptions[0] : null;
            const stock = selectedOpt ? parseInt(selectedOpt.dataset.variantStock ?? '0') : 0;
            if (stock <= 0) {
                isComplete = false;
                missingPrompt = 'Stok Variasi Ini Habis';
            }
        }
    } else if (hasCards) {
        if (!variantInput || !variantInput.value) {
            isComplete = false;
            missingPrompt = 'Pilih Ukuran Terlebih Dahulu';
        } else {
            const activeCard = document.querySelector(`.variant-card-btn[data-variant-id="${variantInput.value}"]`);
            const stock = activeCard ? parseInt(activeCard.dataset.variantStock ?? '0') : 0;
            if (stock <= 0) {
                isComplete = false;
                missingPrompt = 'Stok Variasi Ini Habis';
            }
        }
    } else if (hasAttributeGroups) {
        if (!variantInput || !variantInput.value) {
            isComplete = false;
            missingPrompt = 'Pilih Variasi Terlebih Dahulu';
        } else {
            let matchedV = window.productVariants ? window.productVariants.find(v => String(v.id) === String(variantInput.value)) : null;
            if (matchedV && (matchedV.stock_quantity <= 0)) {
                isComplete = false;
                missingPrompt = 'Stok Variasi Ini Habis';
            }
        }
    } else if (hasLegacyVariants && (!variantInput || !variantInput.value)) {
        isComplete = false;
        missingPrompt = 'Pilih Ukuran Terlebih Dahulu';
    }
    
    if (isComplete && hasColors && (!colorInput || !colorInput.value)) {
        isComplete = false;
        missingPrompt = 'Pilih Warna Terlebih Dahulu';
    }
    
    const addToCartBtn = document.getElementById('add-to-cart-btn');
    const addToCartText = document.getElementById('add-to-cart-text');
    const qtyInput = document.getElementById('quantity-input');
    const qtyMinusBtn = document.getElementById('qty-minus-btn');
    const qtyPlusBtn = document.getElementById('qty-plus-btn');
    
    if (isComplete) {
        if (addToCartBtn) {
            addToCartBtn.disabled = false;
            addToCartBtn.classList.remove('opacity-40', 'cursor-not-allowed');
        }
        if (addToCartText) {
            addToCartText.textContent = 'Tambah ke Keranjang';
        }
        if (qtyInput) qtyInput.disabled = false;
        if (qtyMinusBtn) qtyMinusBtn.disabled = false;
        if (qtyPlusBtn) qtyPlusBtn.disabled = false;
        
        if (qtyInput && parseInt(qtyInput.value) < 1) {
            if (addToCartBtn) addToCartBtn.disabled = true;
        }
    } else {
        if (addToCartBtn) {
            addToCartBtn.disabled = true;
            addToCartBtn.classList.add('opacity-40', 'cursor-not-allowed');
        }
        if (addToCartText) {
            addToCartText.textContent = missingPrompt || 'Pilih Variasi Terlebih Dahulu';
        }
        if (qtyInput) qtyInput.disabled = true;
        if (qtyMinusBtn) qtyMinusBtn.disabled = true;
        if (qtyPlusBtn) qtyPlusBtn.disabled = true;
    }
}

let selectedAttributes = {};

function selectAttribute(el) {
    if (!el || el.disabled) return;

    const groupName = el.dataset.attributeGroup;
    const value = el.dataset.attributeValue;
    
    const container = el.closest('.attribute-group-container');
    if (!container) return;

    // Deselect siblings in the same attribute group
    container.querySelectorAll('.attribute-btn').forEach(btn => {
        btn.classList.remove('border-brand-dark', 'bg-brand-light/30', 'ring-2', 'ring-brand-gold/60', 'text-brand-dark', 'shadow-xs');
        btn.classList.add('border-gray-200', 'bg-white', 'text-gray-700');
        const circle = btn.querySelector('.card-radio-circle');
        if (circle) {
            circle.classList.remove('border-brand-dark', 'bg-brand-dark', 'text-white');
            circle.classList.add('border-gray-300');
            circle.innerHTML = '';
        }
    });

    // Select the clicked attribute button
    el.classList.remove('border-gray-200', 'bg-white', 'text-gray-700');
    el.classList.add('border-brand-dark', 'bg-brand-light/30', 'ring-2', 'ring-brand-gold/60', 'text-brand-dark', 'shadow-xs');
    const circle = el.querySelector('.card-radio-circle');
    if (circle) {
        circle.classList.remove('border-gray-300');
        circle.classList.add('border-brand-dark', 'bg-brand-dark', 'text-white');
        circle.innerHTML = '<i class="fa-solid fa-check text-[9px]"></i>';
    }

    // Update group badge text
    const badge = container.querySelector('.selected-attr-badge');
    if (badge) {
        badge.textContent = value;
    }
    
    selectedAttributes[groupName] = value;
    
    // Immediately trigger main stage image update if an image matches this option (Shopee UX)
    if (window.productVariants) {
        const optionWithImg = window.productVariants.find(v => {
            return v.attributes && (v.attributes[groupName] === value || v.attributes[groupName.toLowerCase()] === value) && (v.image_url || (v.images && v.images.length > 0));
        });
        if (optionWithImg && (optionWithImg.image_url || (optionWithImg.images && optionWithImg.images.length > 0))) {
            window.dispatchEvent(new CustomEvent('set-main-image', { detail: { url: optionWithImg.image_url, images: optionWithImg.images } }));
        }
    }
    
    findMatchingVariant();
}

function applyVariantPrice(matchedVariant) {
    if (!matchedVariant) return;

    const priceEl = document.getElementById('product-price');
    const dc = document.getElementById('product-discount-container');
    const strikeEl = document.getElementById('product-strike-price');
    const dbEl = document.getElementById('product-default-badge');
    const ppsEl = document.getElementById('product-pps-badge');

    let finalPrice = parseFloat(matchedVariant.price) || 0;
    let basePrice = parseFloat(matchedVariant.base_price) || 0;
    if (basePrice <= 0 && typeof window.productBasePrice !== 'undefined') {
        basePrice = parseFloat(window.productBasePrice) || 0;
    }
    if (basePrice <= 0) basePrice = finalPrice;

    let originalPrice = finalPrice;
    let defaultBadge = null;
    let ppsBadge = null;
    let strikePrice = null;

    if (basePrice > finalPrice) {
        let pct = Math.round(((basePrice - finalPrice) / basePrice) * 100);
        defaultBadge = pct + '% OFF';
        strikePrice = basePrice;
    }

    if (window.staticPromo) {
        const promo = window.staticPromo;
        strikePrice = (basePrice > finalPrice) ? basePrice : finalPrice;

        if (promo.discount_type === 'fixed') {
            finalPrice = Math.max(0, finalPrice - parseFloat(promo.discount_value));
        } else if (promo.discount_type === 'percentage') {
            finalPrice = Math.max(0, finalPrice - (finalPrice * parseFloat(promo.discount_value) / 100));
        }

        if (strikePrice > 0) {
            let totalPct = Math.round(((strikePrice - finalPrice) / strikePrice) * 100);
            if (basePrice > originalPrice) {
                let ppsPct = Math.round(((originalPrice - finalPrice) / originalPrice) * 100);
                ppsBadge = 'EXTRA ' + ppsPct + '% OFF';
            } else {
                defaultBadge = totalPct + '% OFF';
            }
        }
    }

    if (priceEl) {
        priceEl.textContent = 'Rp ' + Number(finalPrice).toLocaleString('id-ID');

        if (defaultBadge || ppsBadge || (strikePrice && strikePrice > finalPrice)) {
            priceEl.classList.remove('text-brand-dark');
            priceEl.classList.add('text-red-600');
            if (dc) dc.style.display = 'flex';

            if (strikeEl && strikePrice) {
                // Harga coret bukan range, tapi harga variasi tersebut
                strikeEl.textContent = 'Rp ' + Number(strikePrice).toLocaleString('id-ID');
                strikeEl.style.display = 'inline';
            }

            if (dbEl) {
                dbEl.textContent = defaultBadge || '';
                dbEl.style.display = defaultBadge ? 'inline-block' : 'none';
            }

            if (ppsEl) {
                ppsEl.textContent = ppsBadge || '';
                ppsEl.style.display = ppsBadge ? 'inline-block' : 'none';
            }
        } else {
            priceEl.classList.remove('text-red-600');
            priceEl.classList.add('text-brand-dark');
            if (dc) dc.style.display = 'none';
        }
    }
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
                    const selVal = selectedAttributes[key];
                    const vVal = v.attributes[key] !== undefined ? v.attributes[key] : v.attributes[key.toLowerCase()];
                    if (String(vVal).trim() !== String(selVal).trim()) return false;
                }
                return true;
            });
        }
        
        if (matchedVariant) {
            if (variantInput) variantInput.value = matchedVariant.id;

            if (matchedVariant.image_url || (matchedVariant.images && matchedVariant.images.length > 0)) {
                window.dispatchEvent(new CustomEvent('set-main-image', { detail: { url: matchedVariant.image_url, images: matchedVariant.images } }));
            } else {
                window.dispatchEvent(new CustomEvent('set-main-image', { detail: { url: null } }));
            }
            
            applyVariantPrice(matchedVariant);

            // Update live stock indicator
            const stockQty = matchedVariant.stock_quantity !== undefined ? matchedVariant.stock_quantity : 0;
            const stockText = document.getElementById('product-stock-text');
            const stockPill = document.getElementById('product-stock-badge-pill');
            if (stockText) {
                if (stockQty > 10) {
                    stockText.textContent = `Stok Tersedia: ${stockQty} unit`;
                    if (stockPill) stockPill.className = 'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 border-emerald-200/80 text-emerald-800 border text-[11px] font-bold shadow-2xs transition-all';
                } else if (stockQty > 0) {
                    stockText.textContent = `Sisa Terbatas: ${stockQty} unit`;
                    if (stockPill) stockPill.className = 'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-amber-50 border-amber-200/80 text-amber-800 border text-[11px] font-bold shadow-2xs transition-all';
                } else {
                    stockText.textContent = 'Stok Habis';
                    if (stockPill) stockPill.className = 'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-red-50 border-red-200/80 text-red-800 border text-[11px] font-bold shadow-2xs transition-all';
                }
            }
            
            const priceLabel = document.getElementById('price-label');
            if (priceLabel) {
                let displayName = matchedVariant.variant_name || '';
                displayName = displayName.replace(/Full Bed Set/gi, 'Set Kasur + Divan')
                                         .replace(/Fullset/gi, 'Set Kasur + Divan')
                                         .replace(/Mattress Only/gi, 'Kasur Saja');
                if (!displayName) {
                    displayName = Object.entries(selectedAttributes).map(([k,v]) => {
                        let cleanK = k === 'Feel' ? 'Kelengkapan' : k;
                        let cleanV = v;
                        if (String(cleanV).toLowerCase() === 'mattress only') cleanV = 'Kasur Saja';
                        if (String(cleanV).toLowerCase() === 'fullset' || String(cleanV).toLowerCase() === 'full bed set') cleanV = 'Set Kasur + Divan';
                        return `${cleanK}: ${cleanV}`;
                    }).join(', ');
                }
                const activeColor = document.querySelector('[data-color-id].border-brand-dark, [data-color-id].border-brand-gold');
                if (activeColor && activeColor.dataset.colorName) {
                    displayName += ', Warna: ' + activeColor.dataset.colorName;
                }
                priceLabel.textContent = 'Harga resmi untuk: ' + displayName;
            }
        } else {
            if (variantInput) variantInput.value = "";
            const priceLabel = document.getElementById('price-label');
            if (priceLabel) {
                priceLabel.textContent = 'Pilih variasi produk di bawah untuk melihat harga akurat';
            }
            window.dispatchEvent(new CustomEvent('show-toast', { detail: { type: 'warning', message: 'Kombinasi varian ini sedang tidak tersedia' } }));
        }
    } else if (currentSelectedCount > 0 && window.productVariants) {
        if (variantInput) variantInput.value = "";
        const priceLabel = document.getElementById('price-label');
        if (priceLabel) {
            priceLabel.textContent = 'Pilih variasi produk di bawah untuk melihat harga akurat';
        }
        const matchingVariants = window.productVariants.filter(v => {
            if (!v.attributes) return false;
            for (const key in selectedAttributes) {
                const selVal = selectedAttributes[key];
                const vVal = v.attributes[key] !== undefined ? v.attributes[key] : v.attributes[key.toLowerCase()];
                if (String(vVal).trim() !== String(selVal).trim()) return false;
            }
            return true;
        });

        if (matchingVariants.length === 1) {
            applyVariantPrice(matchingVariants[0]);
        } else if (matchingVariants.length > 1) {
            const firstPrice = matchingVariants[0].price;
            const firstBase = matchingVariants[0].base_price;
            const allSame = matchingVariants.every(v => v.price === firstPrice && v.base_price === firstBase);
            if (allSame) {
                applyVariantPrice(matchingVariants[0]);
            }
        }
    } else if (requiredGroupsCount > 0) {
        if (variantInput) variantInput.value = "";
        const priceLabel = document.getElementById('price-label');
        if (priceLabel) {
            priceLabel.textContent = 'Pilih variasi produk di bawah untuk melihat harga akurat';
        }
    }
    
    checkSelection();
}

function selectVariant(el) {
    document.querySelectorAll('.legacy-variant-btn').forEach(btn => {
        btn.classList.remove('border-brand-dark', 'bg-brand-dark', 'text-white', 'shadow-md', 'scale-102', 'ring-2', 'ring-brand-gold/40');
        btn.classList.add('border-gray-200', 'bg-white', 'text-gray-700');
    });
    el.classList.remove('border-gray-200', 'bg-white', 'text-gray-700');
    el.classList.add('border-brand-dark', 'bg-brand-dark', 'text-white', 'shadow-md', 'scale-102', 'ring-2', 'ring-brand-gold/40');
    
    const variantId = el.dataset.variantId;
    const variantInput = document.getElementById('variant-id-input');
    if (variantInput) {
        variantInput.value = variantId;
    }

    let vObj = null;
    if (window.productVariants) {
        vObj = window.productVariants.find(v => String(v.id) === String(variantId));
    }

    if (vObj) {
        applyVariantPrice(vObj);
        if (vObj.image_url || (vObj.images && vObj.images.length > 0)) {
            window.dispatchEvent(new CustomEvent('set-main-image', { detail: { url: vObj.image_url, images: vObj.images } }));
        } else {
            window.dispatchEvent(new CustomEvent('set-main-image', { detail: { url: null } }));
        }
    } else {
        const dummyVariant = {
            price: el.dataset.variantPrice || 0,
            base_price: el.dataset.variantOriginalPrice || el.dataset.variantPrice || 0,
        };
        applyVariantPrice(dummyVariant);
    }

    const priceLabel = document.getElementById('price-label');
    if (priceLabel) {
        let variantName = el.textContent.trim().split('\n')[0];
        variantName = variantName.replace(/Full Bed Set/gi, 'Set Kasur + Divan')
                                 .replace(/Fullset/gi, 'Set Kasur + Divan')
                                 .replace(/Mattress Only/gi, 'Kasur Saja');
        const activeColor = document.querySelector('[data-color-id].border-brand-dark, [data-color-id].border-brand-gold');
        if (activeColor && activeColor.dataset.colorName) {
            priceLabel.textContent = 'Harga resmi untuk: ' + variantName + ', Warna: ' + activeColor.dataset.colorName;
        } else {
            priceLabel.textContent = 'Harga resmi untuk: ' + variantName;
        }
    }
    
    checkSelection();
}

window.selectVariantCard = function(el) {
    if (!el || el.disabled) return;

    // Deselect all variant cards
    document.querySelectorAll('.variant-card-btn').forEach(btn => {
        btn.classList.remove('border-brand-dark', 'ring-2', 'ring-brand-gold/60', 'bg-brand-light/30', 'shadow-md');
        if (!btn.disabled) {
            btn.classList.add('bg-white', 'border-gray-200');
        }
        const circle = btn.querySelector('.card-radio-circle');
        if (circle) {
            circle.classList.remove('border-brand-dark', 'bg-brand-dark', 'text-white');
            circle.classList.add('border-gray-300');
            circle.innerHTML = '';
        }
    });

    // Activate selected card
    el.classList.remove('bg-white', 'border-gray-200');
    el.classList.add('border-brand-dark', 'ring-2', 'ring-brand-gold/60', 'bg-brand-light/30', 'shadow-md');
    const activeCircle = el.querySelector('.card-radio-circle');
    if (activeCircle) {
        activeCircle.classList.remove('border-gray-300');
        activeCircle.classList.add('border-brand-dark', 'bg-brand-dark', 'text-white');
        activeCircle.innerHTML = '<i class="fa-solid fa-check text-[10px]"></i>';
    }

    const variantId = el.dataset.variantId;
    const variantInput = document.getElementById('variant-id-input');
    if (variantInput) {
        variantInput.value = variantId;
    }

    let vObj = null;
    if (window.productVariants) {
        vObj = window.productVariants.find(v => String(v.id) === String(variantId));
    }

    if (vObj) {
        applyVariantPrice(vObj);
        if (vObj.image_url || (vObj.images && vObj.images.length > 0)) {
            window.dispatchEvent(new CustomEvent('set-main-image', { detail: { url: vObj.image_url, images: vObj.images } }));
        } else if (el.dataset.variantImage) {
            window.dispatchEvent(new CustomEvent('set-main-image', { detail: { url: el.dataset.variantImage } }));
        } else {
            window.dispatchEvent(new CustomEvent('set-main-image', { detail: { url: null } }));
        }
    } else {
        const dummyVariant = {
            price: el.dataset.variantPrice || 0,
            base_price: el.dataset.variantOriginalPrice || el.dataset.variantPrice || 0,
        };
        applyVariantPrice(dummyVariant);
        if (el.dataset.variantImage) {
            window.dispatchEvent(new CustomEvent('set-main-image', { detail: { url: el.dataset.variantImage } }));
        }
    }

    const priceLabel = document.getElementById('price-label');
    let variantName = el.dataset.variantName || el.textContent.trim().split('\n')[0];
    variantName = variantName.replace(/Full Bed Set/gi, 'Set Kasur + Divan')
                             .replace(/Fullset/gi, 'Set Kasur + Divan')
                             .replace(/Mattress Only/gi, 'Kasur Saja');
    if (priceLabel) {
        const activeColor = document.querySelector('[data-color-id].border-brand-dark, [data-color-id].border-brand-gold');
        if (activeColor && activeColor.dataset.colorName) {
            priceLabel.textContent = 'Harga resmi untuk: ' + variantName + ', Warna: ' + activeColor.dataset.colorName;
        } else {
            priceLabel.textContent = 'Harga resmi untuk: ' + variantName;
        }
    }

    // Update Live Stock Status Badge
    const stock = vObj && vObj.stock_quantity !== undefined ? parseInt(vObj.stock_quantity) : parseInt(el.dataset.variantStock ?? 0);
    const pill = document.getElementById('product-stock-badge-pill');
    const dotCore = document.getElementById('product-stock-core');
    const dotPing = document.getElementById('product-stock-ping');
    const stockText = document.getElementById('product-stock-text');
    const qtyInput = document.getElementById('quantity-input');

    if (pill && stockText && dotCore && dotPing) {
        if (stock > 10) {
            pill.className = 'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 border border-emerald-200/80 text-[11px] font-bold text-emerald-800 shadow-2xs transition-all';
            dotPing.className = 'animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75';
            dotCore.className = 'relative inline-flex rounded-full h-2 w-2 bg-emerald-600';
            stockText.textContent = `Stok Tersedia: ${stock} unit`;
            if (qtyInput) qtyInput.max = stock;
        } else if (stock > 0) {
            pill.className = 'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-amber-50 border border-amber-200/80 text-[11px] font-bold text-amber-800 shadow-2xs transition-all';
            dotPing.className = 'animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75';
            dotCore.className = 'relative inline-flex rounded-full h-2 w-2 bg-amber-600';
            stockText.textContent = `Sisa Terbatas: ${stock} unit!`;
            if (qtyInput) qtyInput.max = stock;
        } else {
            pill.className = 'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-red-50 border border-red-200/80 text-[11px] font-bold text-red-800 shadow-2xs transition-all';
            dotPing.className = 'hidden';
            dotCore.className = 'relative inline-flex rounded-full h-2 w-2 bg-red-600';
            stockText.textContent = 'Stok Habis';
        }
    }

    checkSelection();
};

window.selectVariantFromDropdown = function(selectEl) {
    if (!selectEl) return;
    const selectedOption = selectEl.options[selectEl.selectedIndex];
    if (!selectedOption || !selectedOption.value) return;

    const variantId = selectedOption.value;
    const variantInput = document.getElementById('variant-id-input');
    if (variantInput) {
        variantInput.value = variantId;
    }

    let vObj = null;
    if (window.productVariants) {
        vObj = window.productVariants.find(v => String(v.id) === String(variantId));
    }

    if (vObj) {
        applyVariantPrice(vObj);
        if (vObj.image_url || (vObj.images && vObj.images.length > 0)) {
            window.dispatchEvent(new CustomEvent('set-main-image', { detail: { url: vObj.image_url, images: vObj.images } }));
        } else {
            window.dispatchEvent(new CustomEvent('set-main-image', { detail: { url: null } }));
        }
    } else {
        const dummyVariant = {
            price: selectedOption.dataset.variantPrice || 0,
            base_price: selectedOption.dataset.variantOriginalPrice || selectedOption.dataset.variantPrice || 0,
        };
        applyVariantPrice(dummyVariant);
    }

    const priceLabel = document.getElementById('price-label');
    const variantName = selectedOption.dataset.variantName || selectedOption.text.trim().split('•')[0].trim();
    if (priceLabel) {
        const activeColor = document.querySelector('[data-color-id].border-brand-dark, [data-color-id].border-brand-gold');
        if (activeColor && activeColor.dataset.colorName) {
            priceLabel.textContent = 'Harga untuk: ' + variantName + ', Warna: ' + activeColor.dataset.colorName;
        } else {
            priceLabel.textContent = 'Harga untuk: ' + variantName;
        }
    }

    // Update Live Stock Status Badge
    const stock = vObj && vObj.stock_quantity !== undefined ? parseInt(vObj.stock_quantity) : parseInt(selectedOption.dataset.variantStock ?? 0);
    const pill = document.getElementById('product-stock-badge-pill');
    const dotCore = document.getElementById('product-stock-core');
    const dotPing = document.getElementById('product-stock-ping');
    const stockText = document.getElementById('product-stock-text');
    const qtyInput = document.getElementById('quantity-input');

    if (pill && stockText && dotCore && dotPing) {
        if (stock > 10) {
            pill.className = 'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 border border-emerald-200/80 text-[11px] font-bold text-emerald-800 shadow-2xs transition-all';
            dotPing.className = 'animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75';
            dotCore.className = 'relative inline-flex rounded-full h-2 w-2 bg-emerald-600';
            stockText.textContent = `Stok Tersedia: ${stock} unit`;
            if (qtyInput) qtyInput.max = stock;
        } else if (stock > 0) {
            pill.className = 'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-amber-50 border border-amber-200/80 text-[11px] font-bold text-amber-800 shadow-2xs transition-all';
            dotPing.className = 'animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75';
            dotCore.className = 'relative inline-flex rounded-full h-2 w-2 bg-amber-600';
            stockText.textContent = `Sisa Terbatas: ${stock} unit!`;
            if (qtyInput) qtyInput.max = stock;
        } else {
            pill.className = 'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-red-50 border border-red-200/80 text-[11px] font-bold text-red-800 shadow-2xs transition-all';
            dotPing.className = 'hidden';
            dotCore.className = 'relative inline-flex rounded-full h-2 w-2 bg-red-600';
            stockText.textContent = 'Stok Habis';
        }
    }

    checkSelection();
};

function selectColor(el) {
    if (!el || el.disabled) return;

    const colorId = el.dataset.colorId;
    const colorName = el.dataset.colorName;

    // Deselect other color buttons
    document.querySelectorAll('[data-color-id]').forEach(btn => {
        btn.classList.remove('border-brand-dark', 'bg-brand-light/30', 'ring-2', 'ring-brand-gold/60', 'text-brand-dark', 'shadow-xs');
        btn.classList.add('border-gray-200', 'bg-white', 'text-gray-700');
        const circle = btn.querySelector('.card-radio-circle');
        if (circle) {
            circle.classList.remove('border-brand-dark', 'bg-brand-dark', 'text-white');
            circle.classList.add('border-gray-300');
            circle.innerHTML = '';
        }
    });

    // Select clicked color button
    el.classList.remove('border-gray-200', 'bg-white', 'text-gray-700');
    el.classList.add('border-brand-dark', 'bg-brand-light/30', 'ring-2', 'ring-brand-gold/60', 'text-brand-dark', 'shadow-xs');
    const circle = el.querySelector('.card-radio-circle');
    if (circle) {
        circle.classList.remove('border-gray-300');
        circle.classList.add('border-brand-dark', 'bg-brand-dark', 'text-white');
        circle.innerHTML = '<i class="fa-solid fa-check text-[9px]"></i>';
    }

    // Update selected color badge text
    const badge = document.getElementById('selected-color-badge');
    if (badge) {
        badge.textContent = colorName;
    }

    const colorInput = document.getElementById('color-id-input');
    if (colorInput) {
        colorInput.value = colorId;
    }

    const priceLabel = document.getElementById('price-label');
    if (priceLabel) {
        let variantName = '';
        const vInput = document.getElementById('variant-id-input');
        if (vInput && vInput.value && window.productVariants) {
            const curV = window.productVariants.find(v => String(v.id) === String(vInput.value));
            if (curV && curV.variant_name) {
                variantName = curV.variant_name;
            }
        }
        if (!variantName) {
            if (typeof selectedAttributes !== 'undefined' && Object.keys(selectedAttributes).length > 0) {
                variantName = Object.entries(selectedAttributes).map(([k,v]) => {
                    let cleanK = k === 'Feel' ? 'Kelengkapan' : k;
                    let cleanV = v;
                    if (String(cleanV).toLowerCase() === 'mattress only') cleanV = 'Kasur Saja';
                    if (String(cleanV).toLowerCase() === 'fullset' || String(cleanV).toLowerCase() === 'full bed set') cleanV = 'Set Kasur + Divan';
                    return `${cleanK}: ${cleanV}`;
                }).join(', ');
            } else if (document.getElementById('variant-select-dropdown')) {
                const vSel = document.getElementById('variant-select-dropdown');
                const opt = vSel.options[vSel.selectedIndex];
                variantName = opt && opt.value ? (opt.dataset.variantName || opt.text.split('•')[0].trim()) : '';
            } else if (document.querySelector('.variant-card-btn.border-brand-dark')) {
                const selectedCard = document.querySelector('.variant-card-btn.border-brand-dark');
                variantName = selectedCard.dataset.variantName || selectedCard.textContent.trim().split('\n')[0];
            } else {
                const selectedVariant = document.querySelector('.legacy-variant-btn.border-brand-dark, .legacy-variant-btn.border-brand-gold');
                variantName = selectedVariant ? selectedVariant.textContent.trim().split('\n')[0] : '';
            }
        }

        variantName = variantName.replace(/Full Bed Set/gi, 'Set Kasur + Divan')
                                 .replace(/Fullset/gi, 'Set Kasur + Divan')
                                 .replace(/Mattress Only/gi, 'Kasur Saja');

        if (variantName) {
            priceLabel.textContent = 'Harga resmi untuk: ' + variantName + ', Warna: ' + colorName;
        } else {
            priceLabel.textContent = 'Harga resmi untuk Warna: ' + colorName;
        }
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
                window.dispatchEvent(new CustomEvent('show-toast', { detail: { type: 'warning', message: 'Silakan pilih variasi produk terlebih dahulu sebelum menambahkan ke keranjang.' } }));
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

    // Ensure nothing is checked by default on page load / refresh
    selectedAttributes = {};
    const variantInput = document.getElementById('variant-id-input');
    if (variantInput) variantInput.value = '';
    const colorInput = document.getElementById('color-id-input');
    if (colorInput) colorInput.value = '';

    const priceLabel = document.getElementById('price-label');
    const hasAnyOption = document.querySelector('.attribute-btn, .variant-card-btn, .legacy-variant-btn');
    if (priceLabel && hasAnyOption) {
        priceLabel.textContent = 'Pilih variasi produk di bawah untuk melihat harga akurat';
    }

    document.querySelectorAll('.attribute-btn, .color-btn, .variant-card-btn, .legacy-variant-btn').forEach(btn => {
        btn.classList.remove('border-brand-dark', 'bg-brand-light/30', 'ring-2', 'ring-brand-gold/60', 'text-brand-dark', 'shadow-xs', 'bg-brand-dark', 'text-white', 'shadow-md', 'scale-102');
        if (!btn.disabled) {
            btn.classList.add('border-gray-200', 'bg-white', 'text-gray-700');
        }
        const circle = btn.querySelector('.card-radio-circle');
        if (circle) {
            circle.classList.remove('border-brand-dark', 'bg-brand-dark', 'text-white');
            circle.classList.add('border-gray-300');
            circle.innerHTML = '';
        }
    });

    document.querySelectorAll('.selected-attr-badge, .selected-color-badge').forEach(b => {
        b.textContent = '';
    });

    checkSelection();
});
