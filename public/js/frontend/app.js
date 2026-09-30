const $ = (sel, ctx = document) => ctx.querySelector(sel);
const $$ = (sel, ctx = document) => Array.from(ctx.querySelectorAll(sel));

function getLoadingOverlay() {
    return document.getElementById('loading-overlay');
}

window.addEventListener('beforeunload', () => {
    const overlay = getLoadingOverlay();
    if (overlay) overlay.style.display = 'flex';
});

window.addEventListener('pageshow', (event) => {
    if (event.persisted) {
        hideLoading();
    }
});

window.showLoading = function () {
    const overlay = getLoadingOverlay();
    if (overlay) {
        overlay.style.display = 'flex';
    }
};

window.hideLoading = function () {
    const overlay = getLoadingOverlay();
    if (overlay) {
        overlay.style.display = 'none';
    }
};

document.addEventListener('show-loading', () => {
    showLoading();
});

document.addEventListener('hide-loading', () => {
    hideLoading();
});

document.addEventListener('submit', function (e) {
    if (e.defaultPrevented) return;

    const target = e.target;
    if (target.matches('form[action*="cart/add"]')) {
        e.preventDefault();
        submitCartForm(target);
        return;
    }

    if (!target.matches('form[action*="checkout"], form[action*="login"], form[action*="logout"]')) return;
    setTimeout(hideLoading, 3000);
});

function submitCartForm(form) {
    showLoading();

    fetch(form.action, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: new FormData(form)
    })
    .then(async (response) => {
        const data = await response.json().catch(() => null);
        if (!response.ok) {
            throw new Error(data && data.message ? data.message : 'Gagal menambahkan produk ke keranjang');
        }
        return data;
    })
    .then((data) => {
        hideLoading();
        updateCartHeader(data.cart_count || 0, data.cart_total || 0);
        updateCartDrawer(data.cart_drawer_html || '');
        window.dispatchEvent(new CustomEvent('cart-added', { detail: data, bubbles: true }));
        window.dispatchEvent(new CustomEvent('open-cart', { bubbles: true }));
    })
    .catch((error) => {
        hideLoading();
        console.error('Add to cart error:', error);
        window.dispatchEvent(new CustomEvent('cart-add-failed', { detail: { message: error.message }, bubbles: true }));
    });
}

window.updateCartHeader = function (count, total) {
    const headerTotal = $('#header-cart-total');
    if (headerTotal) {
        headerTotal.textContent = 'Rp ' + Number(total).toLocaleString('id-ID');
    }

    let badge = $('#cart-count-badge');
    if (!badge) {
        const trigger = document.querySelector('button[formaction*="cart/add"], .group.relative .fa-cart-shopping')?.closest('button');
        const iconWrap = trigger?.querySelector('.relative');
        if (iconWrap) {
            badge = document.createElement('span');
            badge.id = 'cart-count-badge';
            badge.className = 'absolute -top-2 -right-2 bg-brand-gold text-white text-[10px] font-bold w-4 h-4 rounded-full flex items-center justify-center shadow-sm';
            iconWrap.appendChild(badge);
        }
    }

    const drawerCount = document.getElementById('cart-drawer-count');
    if (drawerCount) drawerCount.textContent = count;

    if (badge) {
        badge.textContent = count;
    }
};

window.updateCartDrawer = function (html) {
    const drawerBody = $('#cart-drawer-body');
    if (drawerBody && html) {
        const innerScrollEl = drawerBody.querySelector('.overflow-y-auto, [class*="overflow-y-"]');
        const prevInnerScroll = innerScrollEl ? innerScrollEl.scrollTop : 0;
        const prevBodyScroll = drawerBody.scrollTop;
        const parentScrollEl = drawerBody.parentElement;
        const prevParentScroll = parentScrollEl ? parentScrollEl.scrollTop : 0;

        drawerBody.innerHTML = html;

        const restoreScroll = () => {
            if (prevBodyScroll > 0) drawerBody.scrollTop = prevBodyScroll;
            if (prevParentScroll > 0 && parentScrollEl) parentScrollEl.scrollTop = prevParentScroll;
            const newInner = drawerBody.querySelector('.overflow-y-auto, [class*="overflow-y-"]');
            if (newInner && prevInnerScroll > 0) newInner.scrollTop = prevInnerScroll;
        };
        restoreScroll();
        requestAnimationFrame(restoreScroll);
        setTimeout(restoreScroll, 50);

        const footer = $('#cart-footer');
        const newTotal = footer ? Number(footer.dataset.cartTotal || 0) : 0;
        drawerBody.setAttribute('data-cart-total', newTotal);
        window.currentCartTotal = newTotal;
        window.dispatchEvent(new CustomEvent('cart-drawer-updated', { bubbles: true }));
    }
};

window.updateWishlistBadge = function (targetCount) {
    const countBadge = document.getElementById('wishlist-count-badge');
    const headerIcon = document.getElementById('wishlist-icon');
    const wishlistLink = document.getElementById('wishlist-link');
    
    let nextCount = targetCount;
    if (typeof targetCount !== 'number') {
        const currentCount = countBadge ? parseInt(countBadge.textContent || '0', 10) : 0;
        nextCount = Math.max(0, currentCount);
    }
    nextCount = Math.max(0, nextCount);

    if (headerIcon) {
        if (nextCount > 0) {
            headerIcon.classList.remove('fa-regular', 'text-gray-700');
            headerIcon.classList.add('fa-solid', 'text-red-500');
        } else {
            headerIcon.classList.remove('fa-solid', 'text-red-500');
            headerIcon.classList.add('fa-regular', 'text-gray-700');
        }
    }

    if (wishlistLink) {
        wishlistLink.setAttribute('aria-label', `Wishlist (${nextCount} Produk)`);
    }

    if (countBadge) {
        countBadge.textContent = nextCount;
        if (nextCount > 0) {
            countBadge.classList.remove('hidden');
            countBadge.classList.add('scale-125');
            setTimeout(() => countBadge.classList.remove('scale-125'), 200);
        } else {
            countBadge.classList.add('hidden');
        }
    }
};

window.openProductReview = function (event, productId) {
    const holder = event && event.currentTarget ? event.currentTarget.closest('[data-product-review]') : null;
    const productEl = holder || (productId ? document.querySelector(`[data-product-id="${productId}"]`) : null);
    const product = productEl ? JSON.parse(productEl.getAttribute('data-product-review')) : null;

    if (!product) {
        return;
    }

    const body = $('body');
    if (body && body.__x) {
        body.__x.$data.selectedProductForReview = product;
    }

    const modal = document.querySelector('[data-review-modal]');
    if (modal && modal.__x) {
        modal.__x.$data.selectedProductForReview = product;
    }

    window.dispatchEvent(new CustomEvent('open-review', { detail: product, bubbles: true }));
};

window.toggleWishlist = function (el) {
    const productId = el.dataset.productId;
    const icon = el.querySelector('i');
    const prevClasses = icon ? icon.className : '';
    
    // Micro loading on the icon itself instead of blocking the entire screen
    if (icon) {
        icon.className = 'fa-solid fa-spinner fa-spin text-brand-gold';
    }
    el.disabled = true;

    const routeCartToggleWishlist = document.body.dataset.routeCartToggleWishlist || '/cart/toggle-wishlist';
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') 
        || document.querySelector('input[name="_token"]')?.value 
        || '';

    fetch(routeCartToggleWishlist, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        },
        body: JSON.stringify({ product_id: productId })
    })
    .then((r) => {
        return r.json().then(data => ({ status: r.status, ok: r.ok, data }));
    })
    .then(({ status, ok, data }) => {
        el.disabled = false;
        
        if (status === 401 || data.require_login) {
            if (icon) icon.className = prevClasses;
            window.dispatchEvent(new CustomEvent('show-toast', { detail: { type: 'warning', message: data.message || 'Silakan login terlebih dahulu.' } }));
            window.dispatchEvent(new CustomEvent('open-auth'));
            return;
        }

        if (!ok || !data.success) {
            if (icon) icon.className = prevClasses;
            window.dispatchEvent(new CustomEvent('show-toast', { detail: { type: 'error', message: data.message || 'Terjadi kesalahan sistem.' } }));
            return;
        }

        // Update all wishlist buttons for this product on the page
        const matchingButtons = document.querySelectorAll(`[data-wishlist-btn][data-product-id="${productId}"], button[data-product-id="${productId}"]`);
        matchingButtons.forEach(btn => {
            if (btn.hasAttribute('data-product-review') || btn.classList.contains('product-card__rating')) {
                return;
            }

            const btnIcon = btn.querySelector('i');
            const isDetailBtn = btn.classList.contains('sm:h-13') || btn.classList.contains('rounded-2xl');

            if (data.in_wishlist) {
                // ACTIVE STATE
                if (btnIcon) {
                    const isLarge = isDetailBtn || btnIcon.classList.contains('text-lg');
                    btnIcon.className = `fa-solid fa-heart text-red-500 transition-transform duration-200 scale-110 ${isLarge ? 'text-lg' : 'text-xs sm:text-sm'}`;
                }
                if (isDetailBtn) {
                    btn.classList.remove('border-gray-200', 'bg-white', 'text-gray-400');
                    btn.classList.add('border-red-300', 'bg-red-50/60', 'text-red-500', 'shadow-sm');
                } else {
                    btn.classList.remove('bg-white/90', 'text-gray-400', 'border-gray-200/80', 'border-gray-200', 'text-gray-700');
                    btn.classList.add('bg-red-50', 'text-red-500', 'border', 'border-red-200', 'shadow-sm');
                }
                btn.setAttribute('aria-label', 'Hapus dari favorit');
                btn.setAttribute('title', 'Hapus dari Wishlist');
            } else {
                // INACTIVE STATE
                if (btnIcon) {
                    const isLarge = isDetailBtn || btnIcon.classList.contains('text-lg');
                    btnIcon.className = `fa-regular fa-heart text-gray-400 transition-transform duration-200 ${isLarge ? 'text-lg' : 'text-xs sm:text-sm'}`;
                }
                if (isDetailBtn) {
                    btn.classList.remove('border-red-300', 'bg-red-50/60', 'text-red-500', 'shadow-sm');
                    btn.classList.add('border-gray-200', 'bg-white', 'text-gray-400');
                } else {
                    btn.classList.remove('bg-red-50', 'text-red-500', 'border-red-200', 'shadow-sm');
                    btn.classList.add('bg-white/90', 'text-gray-400', 'border', 'border-gray-200/80');
                }
                btn.setAttribute('aria-label', 'Tambah ke favorit');
                btn.setAttribute('title', 'Tambah ke Wishlist');

                // If on Wishlist page (/wishlist), smoothly dim the removed product card
                if (window.location.pathname.includes('/wishlist')) {
                    const card = btn.closest('.product-card') || btn.closest('.group');
                    if (card) {
                        card.style.transition = 'opacity 0.4s ease, filter 0.4s ease';
                        card.style.opacity = '0.35';
                        card.style.filter = 'grayscale(80%)';
                    }
                }
            }
        });

        if (typeof data.count === 'number') {
            updateWishlistBadge(data.count);
        } else {
            const currentBadge = document.getElementById('wishlist-count-badge');
            const cur = currentBadge ? parseInt(currentBadge.textContent || '0', 10) : 0;
            updateWishlistBadge(cur + (data.in_wishlist ? 1 : -1));
        }

        window.dispatchEvent(new CustomEvent('show-toast', { 
            detail: { 
                type: 'success', 
                message: data.in_wishlist ? 'Berhasil ditambahkan ke wishlist' : 'Berhasil dihapus dari wishlist' 
            } 
        }));
    })
    .catch((err) => { 
        el.disabled = false;
        if (icon) icon.className = prevClasses;
        console.error('Wishlist error:', err); 
        window.dispatchEvent(new CustomEvent('show-toast', { detail: { type: 'error', message: 'Koneksi terputus atau terjadi kesalahan.' } }));
    });
};

function initHeroMotion() {
    try {
        if (window.motion && typeof window.motion.animate === 'function') {
            const m = window.motion;
            const badge = document.querySelector('.hero-badge');
            if (badge) m.animate(badge, { opacity: [0, 1], transform: ['translateY(20px)', 'translateY(0)'] }, { duration: 500, easing: 'cubic-bezier(.4,0,.2,1)', delay: 0 });
            const title = document.querySelector('.hero-title');
            if (title) m.animate(title, { opacity: [0, 1], transform: ['translateY(20px)', 'translateY(0)'] }, { duration: 500, easing: 'cubic-bezier(.4,0,.2,1)', delay: 100 });
            const copy = document.querySelector('.hero-copy');
            if (copy) m.animate(copy, { opacity: [0, 1], transform: ['translateY(20px)', 'translateY(0)'] }, { duration: 500, easing: 'cubic-bezier(.4,0,.2,1)', delay: 200 });
            const cta = document.querySelector('.hero-cta');
            if (cta) m.animate(cta, { opacity: [0, 1], transform: ['translateY(20px)', 'translateY(0)'] }, { duration: 500, easing: 'cubic-bezier(.4,0,.2,1)', delay: 300 });
            const image = document.querySelector('.hero-image');
            if (image) m.animate(image, { opacity: [0, 1], transform: ['scale(0.95)', 'scale(1)'] }, { duration: 700, easing: 'cubic-bezier(.4,0,.2,1)', delay: 200 });
            return;
        }
    } catch (e) {
        console.warn('Motion init failed', e);
    }
}

window.addEventListener('load', initHeroMotion);
window.addEventListener('DOMContentLoaded', initHeroMotion);

window.addToCart = function (productId) {
    const csrfToken = $('meta[name="csrf-token"]').content;
    const fd = new FormData();
    fd.append('_token', csrfToken);
    fd.append('product_id', productId);
    fd.append('quantity', '1');

    showLoading();
    const routeCartAdd = document.body.dataset.routeCartAdd;
    fetch(routeCartAdd, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: fd
    })
    .then(async (r) => {
        const d = await r.json().catch(() => null);
        if (!r.ok) throw new Error(d && d.message ? d.message : 'Gagal menambahkan produk');
        return d;
    })
    .then((d) => {
        hideLoading();
        updateCartHeader(d.cart_count || 0, d.cart_total || 0);
        updateCartDrawer(d.cart_drawer_html || '');
        window.dispatchEvent(new CustomEvent('cart-added', { detail: d, bubbles: true }));
        window.dispatchEvent(new CustomEvent('open-cart', { bubbles: true }));
    })
    .catch((err) => {
        hideLoading();
        window.dispatchEvent(new CustomEvent('cart-add-failed', { detail: { message: err.message }, bubbles: true }));
    });
};

document.addEventListener('click', function (e) {
    const btn = e.target.closest('.load-more-btn');
    if (!btn) return;

    e.preventDefault();
    const route = btn.dataset.route;
    const offset = parseInt(btn.dataset.offset || '8', 10);
    const productsGrid = document.querySelector('.recommended-products-grid');

    if (!route || !productsGrid) return;

    showLoading();
    btn.disabled = true;
    btn.style.opacity = '0.6';

    const limit = parseInt(btn.dataset.limit || '10', 10);
    fetch(route + '?offset=' + offset + '&limit=' + limit, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(r => r.json())
    .then(data => {
        hideLoading();
        if (data.html) {
            const temp = document.createElement('div');
            temp.innerHTML = data.html;
            temp.querySelectorAll('.product-card').forEach(el => {
                productsGrid.appendChild(el);
            });
            btn.dataset.offset = offset + data.count;
        }
        if (data.count < limit) {
            btn.style.display = 'none';
        }
        btn.disabled = false;
        btn.style.opacity = '1';
    })
    .catch(err => {
        hideLoading();
        btn.disabled = false;
        btn.style.opacity = '1';
        console.error('Load more error:', err);
    });
});

document.addEventListener('click', function (e) {
    const catalogBtn = e.target.closest('#catalog-load-more-btn');
    if (!catalogBtn) return;

    e.preventDefault();
    const nextPageUrl = catalogBtn.dataset.nextPageUrl;
    const gridContainer = document.querySelector('.catalog-products-grid');
    const listContainer = document.querySelector('.catalog-products-list');

    if (!nextPageUrl || (!gridContainer && !listContainer)) return;

    showLoading();
    catalogBtn.disabled = true;
    catalogBtn.style.opacity = '0.6';

    const url = new URL(nextPageUrl, window.location.origin);
    if (window.location.protocol === 'https:') {
        url.protocol = 'https:';
    }
    url.searchParams.set('load_more', '1');

    fetch(url.toString(), {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(r => {
        if (!r.ok) throw new Error('Gagal memuat produk');
        return r.json();
    })
    .then(data => {
        hideLoading();
        catalogBtn.disabled = false;
        catalogBtn.style.opacity = '1';

        if (data.grid_html && gridContainer) {
            gridContainer.insertAdjacentHTML('beforeend', data.grid_html);
        }

        if (data.list_html && listContainer) {
            listContainer.insertAdjacentHTML('beforeend', data.list_html);
        }

        if (data.next_page_url) {
            catalogBtn.dataset.nextPageUrl = data.next_page_url;
        } else {
            const container = document.getElementById('catalog-load-more-container');
            if (container) container.style.display = 'none';
            catalogBtn.style.display = 'none';
        }
    })
    .catch((err) => {
        hideLoading();
        catalogBtn.disabled = false;
        catalogBtn.style.opacity = '1';
        console.error('Catalog load more error:', err);
        window.dispatchEvent(new CustomEvent('show-toast', { detail: { type: 'error', message: err.message || 'Gagal memuat produk' } }));
    });
});