(function () {
    function formatRupiah(value) {
        return 'Rp ' + Number(value || 0).toLocaleString('id-ID');
    }

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

    async function getFreshCsrfToken() {
        try {
            const res = await fetch('/csrf-token', {
                headers: { 'Accept': 'application/json' },
                cache: 'no-store'
            });
            if (res.ok) {
                const data = await res.json();
                if (data.csrf_token) {
                    const meta = document.querySelector('meta[name="csrf-token"]');
                    if (meta) meta.setAttribute('content', data.csrf_token);
                    return data.csrf_token;
                }
            }
        } catch (e) {
            console.error('Failed to fetch fresh CSRF token:', e);
        }
        return (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
    }

    async function fetchWithCsrfRetry(url, options = {}, retries = 1) {
        let token = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
        options.headers = options.headers || {};
        options.headers['X-CSRF-TOKEN'] = token;
        options.headers['Accept'] = 'application/json';
        options.credentials = 'same-origin';

        let response = await fetch(url, options);

        if (response.status === 419 && retries > 0) {
            // CSRF Token Mismatch - Refresh token and retry once
            const freshToken = await getFreshCsrfToken();
            options.headers['X-CSRF-TOKEN'] = freshToken;
            if (options.body instanceof FormData) {
                options.body.set('_token', freshToken);
            }
            response = await fetch(url, options);
        }

        return response;
    }

    function updateCartUiElements(data) {
        const total = Number(data.cart_total || 0);
        window.currentCartTotal = total;

        const cartFooter = document.getElementById('cart-footer');
        const subtotalEl = document.getElementById('cart-drawer-subtotal');
        const totalWithDiscountEl = document.getElementById('cart-total-with-discount');
        if (cartFooter) cartFooter.setAttribute('data-cart-total', total);
        if (subtotalEl) subtotalEl.textContent = formatRupiah(total);
        if (totalWithDiscountEl) totalWithDiscountEl.textContent = formatRupiah(total);

        const countBadge = document.getElementById('cart-count-badge');
        const headerTotal = document.getElementById('header-cart-total');
        const headerItemsText = document.getElementById('header-cart-items-text');
        if (countBadge) countBadge.textContent = data.cart_count || 0;
        if (headerTotal) headerTotal.textContent = formatRupiah(data.cart_total || 0);
        if (headerItemsText) headerItemsText.textContent = (data.cart_count || 0) + '';

        document.dispatchEvent(new CustomEvent('cart-drawer-updated'));
    }

    window.removeCartItem = async function (button, cartId) {
        if (!cartId) return;

        if (button) {
            button.disabled = true;
            button.style.opacity = '0.4';
        }

        const routeTemplate = document.body.dataset.routeCartRemove || '/cart/remove/__ID__';
        const url = routeTemplate.replace('__ID__', cartId);

        try {
            const response = await fetchWithCsrfRetry(url, {
                method: 'POST'
            });

            const data = await response.json();

            if (response.ok && data.success !== false) {
                const drawerBody = document.getElementById('cart-drawer-body');
                if (drawerBody && data.cart_drawer_html) {
                    drawerBody.innerHTML = data.cart_drawer_html;
                }

                updateCartUiElements(data);

                if (typeof addToast === 'function') {
                    addToast('success', data.message || 'Item berhasil dihapus dari keranjang.');
                }
            } else {
                throw new Error(data.message || 'Gagal menghapus item dari keranjang.');
            }
        } catch (err) {
            console.error('Failed to remove cart item:', err);
            if (typeof addToast === 'function') {
                addToast('error', err.message || 'Terjadi kesalahan saat menghapus item.');
            }
            if (button) {
                button.disabled = false;
                button.style.opacity = '1';
            }
        }
    };

    window.updateCartQuantity = async function (button, change) {
        const cartId = button.dataset.cartId;
        const itemContainer = button.closest('[data-cart-item-id]');
        const quantityElement = itemContainer ? itemContainer.querySelector('.cart-item-quantity') : null;
        if (!quantityElement) return;

        const nextQuantity = Math.max(0, Number(quantityElement.textContent) + change);
        const formData = new FormData();
        formData.append('quantity', nextQuantity);

        const routeCartUpdate = document.body.dataset.routeCartUpdate || '/cart/update/__ID__';
        const url = routeCartUpdate.replace('__ID__', cartId);

        button.disabled = true;

        try {
            const response = await fetchWithCsrfRetry(url, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (response.ok && data.success !== false) {
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

                updateCartUiElements(data);
            } else {
                throw new Error(data.message || 'Gagal memperbarui kuantitas produk.');
            }
        } catch (err) {
            console.error('Failed to update cart quantity:', err);
            if (typeof addToast === 'function') {
                addToast('error', err.message || 'Maaf, terjadi kendala saat memperbarui keranjang.');
            }
        } finally {
            button.disabled = false;
        }
    };
})();
