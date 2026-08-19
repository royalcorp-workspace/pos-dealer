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

    if (badge) {
        badge.textContent = count;
    }
};

window.updateCartDrawer = function (html) {
    const drawerBody = $('#cart-drawer-body');
    if (drawerBody && html) {
        drawerBody.innerHTML = html;
        const footer = $('#cart-footer');
        const newTotal = footer ? Number(footer.dataset.cartTotal || 0) : 0;
        drawerBody.setAttribute('data-cart-total', newTotal);
        window.currentCartTotal = newTotal;
        window.dispatchEvent(new CustomEvent('cart-drawer-updated', { bubbles: true }));
    }
};

window.updateWishlistBadge = function (delta) {
    const countBadge = $('#wishlist-count-badge');
    const headerIcon = $('#wishlist-icon');
    const wishlistLink = $('#wishlist-link');
    const currentCount = countBadge ? parseInt(countBadge.textContent || '0', 10) : 0;
    const nextCount = Math.max(0, currentCount + delta);

    if (headerIcon) {
        headerIcon.classList.toggle('fa-solid', nextCount > 0);
        headerIcon.classList.toggle('fa-regular', nextCount === 0);
        headerIcon.classList.toggle('text-brand-gold', nextCount > 0);
        headerIcon.classList.toggle('text-brand-dark', nextCount === 0);
    }

    if (wishlistLink) {
        wishlistLink.setAttribute('aria-label', `Wishlist (${nextCount} Produk)`);
    }

    if (nextCount > 0) {
        let badge = countBadge;
        if (!badge && headerIcon?.parentElement) {
            badge = document.createElement('span');
            badge.id = 'wishlist-count-badge';
            badge.className = 'absolute -top-1 -right-1 bg-brand-gold text-white text-[10px] font-bold min-w-[1rem] h-4 px-1 rounded-full flex items-center justify-center shadow-sm';
            headerIcon.parentElement.appendChild(badge);
        }

        if (badge) {
            badge.textContent = nextCount;
            badge.classList.remove('hidden');
        }
    } else if (countBadge) {
        countBadge.classList.add('hidden');
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
    showLoading();
    const routeCartToggleWishlist = document.body.dataset.routeCartToggleWishlist;
    fetch(routeCartToggleWishlist, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        },
        body: JSON.stringify({ product_id: productId })
    })
    .then((r) => {
        return r.json().then(data => ({ status: r.status, ok: r.ok, data }));
    })
    .then(({ status, ok, data }) => {
        hideLoading();
        
        if (status === 401 || data.require_login) {
            window.dispatchEvent(new CustomEvent('show-toast', { detail: { type: 'warning', message: data.message || 'Silakan login terlebih dahulu.' } }));
            window.dispatchEvent(new CustomEvent('open-auth'));
            return;
        }

        if (!ok || !data.success) {
            window.dispatchEvent(new CustomEvent('show-toast', { detail: { type: 'error', message: data.message || 'Terjadi kesalahan sistem.' } }));
            return;
        }

        const icon = el.querySelector('i');
        if (icon) {
            if (data.in_wishlist) {
                icon.classList.remove('fa-regular');
                icon.classList.add('fa-solid', 'text-brand-gold');
            } else {
                icon.classList.remove('fa-solid', 'text-brand-gold');
                icon.classList.add('fa-regular');
            }
        }
        updateWishlistBadge(data.in_wishlist ? 1 : -1);
    })
    .catch((err) => { 
        hideLoading(); 
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

    fetch(route + '?offset=' + offset + '&limit=4', {
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
        if (data.count < 4) {
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