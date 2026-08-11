(function () {
    function fetchNotifications() {
        var listEl = document.getElementById('notification-list');
        if (!listEl) return;

        fetch('/notifications', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            var notifications = data.notifications || [];
            if (notifications.length === 0) {
                listEl.innerHTML = '<div class="p-4 text-center text-sm text-gray-500">Belum ada notifikasi.</div>';
                return;
            }

            var html = '';
            notifications.forEach(function (n) {
                html += '<div class="p-3 border-b border-gray-50 last:border-0">' +
                    '<div class="flex gap-3">' +
                    '<div class="flex-1">' +
                    '<p class="text-sm font-medium text-gray-800">' + (n.title || '') + '</p>' +
                    '<p class="text-xs text-gray-500 mt-0.5">' + (n.message || '') + '</p>' +
                    '<span class="text-xs text-gray-400">' + (n.published_at || '') + '</span>' +
                    '</div>' +
                    (n.is_read ? '' : '<span class="w-2 h-2 bg-brand-gold rounded-full flex-shrink-0 mt-0.5"></span>') +
                    '</div></div>';
            });
            listEl.innerHTML = html;
        })
        .catch(function () {
            listEl.innerHTML = '<div class="p-4 text-center text-sm text-gray-500">Gagal memuat notifikasi.</div>';
        });
    }

    window.fetchNotifications = fetchNotifications;

    window.markAllRead = function () {
        fetch('/notifications/read-all', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') || {}).content,
                'Accept': 'application/json',
            },
        })
        .then(function (r) { return r.json(); })
        .then(function () {
            fetchNotifications();
            var badge = document.querySelector('#notification-list').closest('[x-data]');
            var countBadge = document.querySelector('header .bg-red-500');
            if (countBadge && countBadge.closest('button[aria-label="Notifikasi"]')) {
                countBadge.style.display = 'none';
            }
        });
    };

    document.addEventListener('cart-updated', function (e) {
        var count = (e.detail && e.detail.count) || 0;
        var total = (e.detail && e.detail.total) || 0;

        var countBadge = document.getElementById('cart-count-badge');
        if (countBadge) countBadge.textContent = count;

        var headerTotal = document.getElementById('header-cart-total');
        if (headerTotal) {
            headerTotal.textContent = 'Rp ' + Number(total).toLocaleString('id-ID');
        }

        var cartDrawerBody = document.getElementById('cart-drawer-body');
        if (cartDrawerBody) {
            cartDrawerBody.setAttribute('data-cart-total', total);
        }
    });

    // Auto-load notifications on page load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', fetchNotifications);
    } else {
        fetchNotifications();
    }
})();
