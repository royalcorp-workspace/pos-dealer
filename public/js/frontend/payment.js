// Reload page when navigating back from Thank You page (BFCache handling)
window.addEventListener('pageshow', function (event) {
    if (event.persisted || (window.performance && window.performance.navigation && window.performance.navigation.type === 2)) {
        window.location.reload();
    }
});

// Modal Dialog Controls for Payment Method Selection
window.openPaymentMethodModal = function () {
    var modal = document.getElementById('payment-method-modal');
    if (modal) {
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }
};

window.closePaymentMethodModal = function () {
    var modal = document.getElementById('payment-method-modal');
    if (modal) {
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }
};

window.selectPaymentMethodOption = function (methodCode) {
    var radio = document.getElementById('payment_method_' + methodCode);
    if (radio) {
        radio.checked = true;
        radio.dispatchEvent(new Event('change', { bubbles: true }));
        setTimeout(function () {
            window.closePaymentMethodModal();
        }, 180);
    }
};

window.processPayment = function () {
    var selectedMethod = document.querySelector('input[name="payment_method"]:checked');
    var validationAlert = document.getElementById('payment-method-validation-error');
    var triggerCard = document.getElementById('payment-method-trigger-card');

    if (!selectedMethod) {
        if (validationAlert) {
            validationAlert.classList.remove('hidden');
            validationAlert.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        if (triggerCard) {
            triggerCard.classList.add('ring-2', 'ring-red-400', 'border-red-400');
        }
        window.dispatchEvent(new CustomEvent('show-toast', { 
            detail: { type: 'warning', message: 'Silakan pilih metode pembayaran terlebih dahulu.' } 
        }));
        
        // Open modal popup automatically so user can pick
        window.openPaymentMethodModal();
        return;
    }

    // Clear validation if method is selected
    if (validationAlert) validationAlert.classList.add('hidden');
    if (triggerCard) triggerCard.classList.remove('ring-2', 'ring-red-400', 'border-red-400');

    var isManualTransfer = selectedMethod.getAttribute('data-is-manual') === '1';
    var categoryType = selectedMethod.getAttribute('data-category-type') || '';
    var productCode = selectedMethod.getAttribute('data-product-code') || '';
    var bankCode = selectedMethod.getAttribute('data-bank-code') || '';

    var container = document.getElementById('payment-container');
    var processUrl = container ? container.dataset.routePaymentProcess : '/payment/process';
    var thankYouUrl = container ? container.dataset.routeThankyou : '/thankyou';
    var orderId = container ? container.dataset.orderId : '';

    var body, headers;
    var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    if (isManualTransfer) {
        var fileInput = document.getElementById('payment_proof');
        window.showLoading();
        body = new FormData();
        body.append('payment_method', selectedMethod.value);
        body.append('order_id', orderId);
        body.append('category_type', categoryType);
        body.append('product_code', productCode);
        body.append('bank_code', bankCode);
        if (fileInput && fileInput.files && fileInput.files.length > 0) {
            body.append('payment_proof', fileInput.files[0]);
        }

        headers = {
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        };
    } else {
        window.showLoading();
        body = JSON.stringify({
            payment_method: selectedMethod.value,
            order_id: orderId,
            category_type: categoryType,
            product_code: productCode,
            bank_code: bankCode
        });
        headers = {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        };
    }

    fetch(processUrl, { 
        method: 'POST', 
        headers: headers, 
        body: body,
        credentials: 'same-origin'
    })
    .then(async function (response) {
        var data = {};
        try {
            data = await response.json();
        } catch (e) {
            data = { success: false, message: 'Respon server tidak dapat diproses (' + response.status + ')' };
        }
        return { ok: response.ok, status: response.status, data: data };
    })
    .then(function (res) {
        window.hideLoading();
        var data = res.data;
        if (res.ok && data && data.success) {
            localStorage.removeItem('selectedCartCoupon');
            localStorage.removeItem('selectedCartCoupons');
            sessionStorage.removeItem('checkout_form_data');
            localStorage.removeItem('checkout_form_data');

            // Cek apakah response meminta membuka iframe / Snap Espay (Khusus QRIS, Debit, Credit Card)
            if (data.open_iframe && data.espay_kit) {
                var modal = document.getElementById('espay-snap-modal');
                var iframe = document.getElementById('sgoplus-iframe');
                var loader = document.getElementById('espay-iframe-loader');
                var manualRedirectBtn = document.getElementById('espay-manual-redirect-btn');

                var targetUrl = data.redirect_url || data.espay_kit.backUrl || thankYouUrl;
                if (manualRedirectBtn) {
                    manualRedirectBtn.href = targetUrl;
                }

                // DIRECT LOCKED CHANNEL URL:
                // Menggunakan data.iframe_url langsung agar Espay langsung membuka tampilan QRIS (QR code)
                // atau Credit Card (form input kartu) tanpa menampilkan kembali menu pilihan channel pembayaran!
                var iframeUrl = data.iframe_url || '';
                if (!iframeUrl && data.espay_kit) {
                    var domain = (data.espay_kit.domain || 'https://sandbox-kit.espay.id').replace(/\/+$/, '');
                    var sig = data.espay_kit.signature || '';
                    var key = data.espay_kit.key || '';
                    var pId = encodeURIComponent(data.espay_kit.paymentId || '');
                    var bUrl = encodeURIComponent(data.espay_kit.backUrl || targetUrl);
                    var bCode = encodeURIComponent(data.espay_kit.bankCode || '');
                    var prCode = encodeURIComponent(data.espay_kit.productCode || '');
                    var cCode = encodeURIComponent(data.espay_kit.commCode || '');
                    var amt = encodeURIComponent(data.espay_kit.amount || '');
                    iframeUrl = domain + "/index/order/?url=" + bUrl + "&key=" + key + "&paymentId=" + pId + "&paymentAmount=" + amt + "&signature=" + sig + "&commCode=" + cCode + "&bankCode=" + bCode + "&productCode=" + prCode;
                }

                if (modal && iframe) {
                    if (loader) {
                        loader.style.opacity = '1';
                        loader.classList.remove('hidden');
                    }

                    iframe.onload = function () {
                        if (loader) {
                            loader.style.opacity = '0';
                            setTimeout(function () {
                                loader.classList.add('hidden');
                            }, 300);
                        }
                    };

                    iframe.src = iframeUrl;
                    modal.classList.remove('hidden');
                    document.body.classList.add('overflow-hidden');

                    if (typeof SGOSignature !== 'undefined' && typeof SGOSignature.receiveForm === 'function') {
                        try {
                            SGOSignature.receiveForm();
                        } catch (e) {
                            console.error('SGOSignature receiveForm error:', e);
                        }
                    }
                    return;
                }
            }

            // Default redirect: Langsung ke Thank You page (Virtual Account, Transfer Manual, dll)
            window.location.href = data.redirect_url || thankYouUrl;
        } else {
            var errorMsg = (data && data.message) ? data.message : 'Terjadi kendala saat memproses pembayaran.';
            window.dispatchEvent(new CustomEvent('show-toast', { detail: { type: 'error', message: errorMsg } }));
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Pembayaran Belum Berhasil',
                    text: errorMsg,
                    confirmButtonColor: '#1e3a8a',
                    confirmButtonText: 'Tutup'
                }).then(function() {
                    if (data && data.redirect_url) {
                        window.location.href = data.redirect_url;
                    }
                });
            } else if (data && data.redirect_url) {
                setTimeout(function() { window.location.href = data.redirect_url; }, 1800);
            }
        }
    })
    .catch(function (err) {
        window.hideLoading();
        console.error('Payment process error:', err);
        var msg = 'Terjadi gangguan koneksi ke server saat memproses transaksi. Silakan coba kembali.';
        window.dispatchEvent(new CustomEvent('show-toast', { detail: { type: 'error', message: msg } }));
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Gangguan Jaringan',
                text: msg,
                confirmButtonColor: '#1e3a8a',
                confirmButtonText: 'Coba Lagi'
            });
        }
    });
};

document.addEventListener('DOMContentLoaded', function() {
    var radios = document.querySelectorAll('input[name="payment_method"]');
    var triggerCard = document.getElementById('payment-method-trigger-card');
    var selectedCard = document.getElementById('payment-method-selected-card');
    var selectedLogoContainer = document.getElementById('selected-method-logo-display');
    var selectedNameDisplay = document.getElementById('selected-method-name-display');
    var selectedBadgeDisplay = document.getElementById('selected-method-badge-display');
    var selectedChargeDisplay = document.getElementById('selected-method-charge-display');
    var selectedSubDisplay = document.getElementById('selected-method-sub-display');

    var detailsContainer = document.getElementById('transfer-manual-details');
    var banksContainer = document.getElementById('instructions-banks-container');
    var chargeRow = document.getElementById('charge-row');
    var chargeAmountLabel = document.getElementById('charge-amount');
    var finalTotalLabel = document.getElementById('final-total');

    // Close modal on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            window.closePaymentMethodModal();
        }
    });

    // Close modal on backdrop click
    var methodModal = document.getElementById('payment-method-modal');
    if (methodModal) {
        methodModal.addEventListener('click', function(e) {
            if (e.target === methodModal) {
                window.closePaymentMethodModal();
            }
        });
    }

    function toggleDetails() {
        window.toggleDetails = toggleDetails;
        var selected = document.querySelector('input[name="payment_method"]:checked');

        // Update modal option cards visual
        document.querySelectorAll('.payment-method-card').forEach(function(card) {
            var input = card.querySelector('input[name="payment_method"]');
            var indicator = card.querySelector('.method-radio-indicator');
            var dot = card.querySelector('.method-radio-dot');

            if (input && input.checked) {
                card.classList.add('border-brand-gold', 'bg-brand-light/30', 'ring-2', 'ring-brand-gold/30');
                card.classList.remove('border-gray-200');
                if (indicator) {
                    indicator.classList.add('border-brand-gold', 'bg-brand-gold/10');
                    indicator.classList.remove('border-gray-300');
                }
                if (dot) dot.classList.remove('opacity-0');
            } else {
                card.classList.remove('border-brand-gold', 'bg-brand-light/30', 'ring-2', 'ring-brand-gold/30');
                card.classList.add('border-gray-200');
                if (indicator) {
                    indicator.classList.remove('border-brand-gold', 'bg-brand-gold/10');
                    indicator.classList.add('border-gray-300');
                }
                if (dot) dot.classList.add('opacity-0');
            }
        });

        // Update Selected Card on main page
        if (selected) {
            var parentCard = selected.closest('.payment-method-card');
            var logoBadge = parentCard ? parentCard.querySelector('.method-logo-badge') : null;

            if (triggerCard) triggerCard.classList.add('hidden');
            if (selectedCard) selectedCard.classList.remove('hidden');

            if (selectedLogoContainer && logoBadge) {
                selectedLogoContainer.innerHTML = logoBadge.innerHTML;
            }
            if (selectedNameDisplay) {
                selectedNameDisplay.textContent = selected.getAttribute('data-method-name') || selected.value;
            }
            if (selectedBadgeDisplay) {
                var isManual = selected.getAttribute('data-is-manual') === '1';
                selectedBadgeDisplay.textContent = selected.getAttribute('data-method-badge') || (isManual ? 'Verifikasi Manual' : 'Otomatis');
                if (isManual) {
                    selectedBadgeDisplay.className = 'text-[10px] text-amber-700 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded-full font-bold';
                } else {
                    selectedBadgeDisplay.className = 'text-[10px] text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-full font-bold';
                }
            }
            if (selectedSubDisplay) {
                selectedSubDisplay.textContent = selected.getAttribute('data-method-subtitle') || 'Metode pembayaran siap diproses';
            }
        } else {
            if (triggerCard) triggerCard.classList.remove('hidden');
            if (selectedCard) selectedCard.classList.add('hidden');
        }

        // 1. Kalkulasi Charge/Biaya Admin
        if (selected && finalTotalLabel) {
            var baseTotal = parseFloat(finalTotalLabel.getAttribute('data-base-total') || '0');
            var hasCharge = selected.getAttribute('data-has-charge') === '1';
            var chargeType = parseInt(selected.getAttribute('data-charge-type') || '2');
            var chargeValue = parseFloat(selected.getAttribute('data-charge-value') || '0');

            var charge = 0;
            if (hasCharge && chargeValue > 0) {
                if (chargeType === 1) { // Percentage
                    charge = (baseTotal * chargeValue) / 100;
                } else { // Fixed
                    charge = chargeValue;
                }
            }

            if (charge > 0) {
                if (chargeRow) chargeRow.classList.remove('hidden');
                if (chargeAmountLabel) chargeAmountLabel.textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(charge);
                if (selectedChargeDisplay) {
                    selectedChargeDisplay.textContent = '+Biaya Rp ' + new Intl.NumberFormat('id-ID').format(charge);
                    selectedChargeDisplay.classList.remove('hidden');
                }
            } else {
                if (chargeRow) chargeRow.classList.add('hidden');
                if (selectedChargeDisplay) selectedChargeDisplay.classList.add('hidden');
            }

            if (finalTotalLabel) {
                finalTotalLabel.textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(baseTotal + charge);
            }
            var sidebarTotalLabel = document.getElementById('sidebar-final-total');
            if (sidebarTotalLabel) {
                sidebarTotalLabel.textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(baseTotal + charge);
            }
        } else {
            if (chargeRow) chargeRow.classList.add('hidden');
            if (selectedChargeDisplay) selectedChargeDisplay.classList.add('hidden');
        }
        
        // 2. Tampilkan Instruksi Transfer Manual (jika dipilih)
        if (selected && selected.getAttribute('data-is-manual') === '1') {
            var banksData = [];
            try {
                banksData = JSON.parse(selected.getAttribute('data-banks') || '[]');
            } catch (e) {
                // ignore
            }
            
            if (!Array.isArray(banksData) || banksData.length === 0) {
                banksData = [{
                    bank_name: 'BCA',
                    account_number: '123-456-7890',
                    account_holder: 'PT POS Dealer Indonesia'
                }];
            }
            
            var orderTotal = (typeof baseTotal !== 'undefined' && baseTotal > 0) ? (baseTotal + charge) : (detailsContainer ? parseFloat(detailsContainer.getAttribute('data-order-total') || '0') : 0);

            banksContainer.innerHTML = '';
            banksData.forEach(function(bank) {
                var card = document.createElement('div');
                card.className = 'bg-white p-4.5 rounded-2xl border border-brand-muted/80 space-y-3 shadow-2xs mb-3';
                var formattedAmount = new Intl.NumberFormat('id-ID').format(orderTotal);
                card.innerHTML = `
                    <div class="flex justify-between items-center pb-2.5 border-b border-gray-100">
                        <span class="text-gray-500 text-xs font-semibold">Nama Bank</span>
                        <span class="font-extrabold text-brand-dark text-sm bg-brand-light px-2.5 py-0.5 rounded-md border border-brand-muted">${bank.bank_name}</span>
                    </div>
                    <div class="flex justify-between items-center pb-2.5 border-b border-gray-100">
                        <span class="text-gray-500 text-xs font-semibold">Nomor Rekening</span>
                        <div class="flex items-center gap-2">
                            <span class="font-mono font-bold text-brand-dark text-base tracking-wider">${bank.account_number}</span>
                            <button type="button" onclick="navigator.clipboard.writeText('${bank.account_number}'); window.dispatchEvent(new CustomEvent('show-toast', { detail: { type: 'success', message: 'Nomor rekening ${bank.account_number} berhasil disalin!' } }));" class="text-xs bg-brand-gold/15 hover:bg-brand-gold/25 text-brand-gold-dark font-bold px-2 py-1 rounded-lg transition-colors inline-flex items-center gap-1" title="Salin nomor rekening">
                                <i class="fa-regular fa-copy text-[11px]"></i> Salin
                            </button>
                        </div>
                    </div>
                    <div class="flex justify-between items-center pb-2.5 border-b border-gray-100">
                        <span class="text-gray-500 text-xs font-semibold">Atas Nama</span>
                        <span class="font-bold text-gray-800 text-sm">${bank.account_holder}</span>
                    </div>
                    <div class="flex justify-between items-center pt-1">
                        <div>
                            <span class="text-gray-500 text-xs font-semibold block">Total yang Harus Ditransfer</span>
                            <span class="text-[11px] text-amber-700">Pastikan nominal tepat sampai digit terakhir</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="font-extrabold text-brand-gold-dark text-base sm:text-lg">Rp ${formattedAmount}</span>
                            <button type="button" onclick="navigator.clipboard.writeText('${Math.round(orderTotal)}'); window.dispatchEvent(new CustomEvent('show-toast', { detail: { type: 'success', message: 'Nominal transfer berhasil disalin!' } }));" class="text-xs bg-brand-gold/15 hover:bg-brand-gold/25 text-brand-gold-dark font-bold px-2 py-1 rounded-lg transition-colors inline-flex items-center gap-1" title="Salin jumlah transfer">
                                <i class="fa-regular fa-copy text-[11px]"></i>
                            </button>
                        </div>
                    </div>
                `;
                banksContainer.appendChild(card);
            });
            
            if (detailsContainer) detailsContainer.classList.remove('hidden');
        } else {
            if (detailsContainer) detailsContainer.classList.add('hidden');
        }
    }
    
    radios.forEach(function(radio) {
        radio.addEventListener('change', function() {
            var validationAlert = document.getElementById('payment-method-validation-error');
            var accordionsWrapper = document.getElementById('payment-accordions-wrapper');
            if (validationAlert) validationAlert.classList.add('hidden');
            if (accordionsWrapper) accordionsWrapper.classList.remove('p-2.5', 'border-2', 'border-red-400', 'bg-red-50/20');
            toggleDetails();
        });
    });
    
    // Accordion toggle click listener
    document.querySelectorAll('.payment-accordion-group').forEach(function(group) {
        var header = group.querySelector('.payment-accordion-header');
        var content = group.querySelector('.payment-accordion-content');
        var chevron = group.querySelector('.accordion-chevron');
        
        if (header && content) {
            header.addEventListener('click', function(e) {
                var isHidden = content.classList.contains('hidden');
                if (isHidden) {
                    content.classList.remove('hidden');
                    if (chevron) chevron.classList.add('rotate-180');
                } else {
                    content.classList.add('hidden');
                    if (chevron) chevron.classList.remove('rotate-180');
                }
            });
        }
    });

    // Auto-open first accordion if no radio is selected yet
    var checkedInit = document.querySelector('input[name="payment_method"]:checked');
    if (!checkedInit) {
        var firstGroup = document.querySelector('.payment-accordion-group');
        if (firstGroup) {
            var firstContent = firstGroup.querySelector('.payment-accordion-content');
            var firstChevron = firstGroup.querySelector('.accordion-chevron');
            if (firstContent) firstContent.classList.remove('hidden');
            if (firstChevron) firstChevron.classList.add('rotate-180');
        }
    }

    // Trigger initially in case of preselected radio button
    toggleDetails();
});

// Initialize countdown timer
document.addEventListener('DOMContentLoaded', function() {
    var countdownEl = document.getElementById('payment-countdown');
    if (!countdownEl) return;
    
    var createdStr = countdownEl.getAttribute('data-created');
    if (!createdStr) return;
    
    // Set expiration to 24 hours after creation
    var createdAt = new Date(createdStr).getTime();
    var expireAt = createdAt + (24 * 60 * 60 * 1000);
    
    function updateTimer() {
        var now = new Date().getTime();
        var distance = expireAt - now;
        
        if (distance < 0) {
            countdownEl.innerHTML = "00:00:00";
            countdownEl.classList.add('text-gray-400');
            countdownEl.classList.remove('text-red-600');
            // Show expired message or disable payment button if needed
            var btns = document.querySelectorAll('button[onclick="processPayment()"]');
            btns.forEach(function(btn) {
                btn.disabled = true;
                btn.classList.add('opacity-50', 'cursor-not-allowed');
                btn.innerHTML = 'Waktu Habis';
            });
            return;
        }
        
        var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        var seconds = Math.floor((distance % (1000 * 60)) / 1000);
        
        hours = hours < 10 ? "0" + hours : hours;
        minutes = minutes < 10 ? "0" + minutes : minutes;
        seconds = seconds < 10 ? "0" + seconds : seconds;
        
        countdownEl.innerHTML = hours + ":" + minutes + ":" + seconds;
    }
    
    updateTimer();
    setInterval(updateTimer, 1000);
});

// Espay Snap Modal Listeners
document.addEventListener('DOMContentLoaded', function() {
    var closeEspayModalBtn = document.getElementById('close-espay-modal-btn');
    if (closeEspayModalBtn) {
        closeEspayModalBtn.addEventListener('click', function () {
            var manualRedirectBtn = document.getElementById('espay-manual-redirect-btn');
            var container = document.getElementById('payment-container');
            var targetUrl = (manualRedirectBtn && manualRedirectBtn.href && manualRedirectBtn.href !== '#') 
                ? manualRedirectBtn.href 
                : (container ? container.dataset.routeThankyou : '/thankyou');

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Tutup Jendela Pembayaran?',
                    text: 'Pesanan Anda telah disimpan di sistem. Anda dapat melihat instruksi dan memeriksa status pembayaran di halaman rincian pesanan.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#1e3a8a',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Ya, Lihat Rincian Pesanan',
                    cancelButtonText: 'Lanjutkan Bayar'
                }).then(function (result) {
                    if (result.isConfirmed) {
                        window.location.href = targetUrl;
                    }
                });
            } else {
                if (confirm('Pesanan Anda sudah tersimpan. Buka rincian pesanan?')) {
                    window.location.href = targetUrl;
                }
            }
        });
    }

    // Handle messages from Espay Snap Iframe
    window.addEventListener('message', function (event) {
        if (!event.data) return;
        try {
            var msg = typeof event.data === 'string' ? JSON.parse(event.data) : event.data;
            if (msg.status === 'success' || msg.status === 'paid' || msg.action === 'close' || msg.type === 'close') {
                var manualRedirectBtn = document.getElementById('espay-manual-redirect-btn');
                var container = document.getElementById('payment-container');
                var targetUrl = (manualRedirectBtn && manualRedirectBtn.href && manualRedirectBtn.href !== '#') 
                    ? manualRedirectBtn.href 
                    : (container ? container.dataset.routeThankyou : '/thankyou');
                window.location.href = targetUrl;
            }
        } catch (e) {
            // Not JSON or non-object message, safely ignore
        }
    });
});


// ==========================================
// VOUCHER PAYMENT HANDLING
// ==========================================
window.toggleAvailableVouchersDropdown = function() {
    var dropdown = document.getElementById('available-vouchers-dropdown');
    var chevron = document.getElementById('available-vouchers-dropdown-chevron');
    if (dropdown) {
        var isHidden = dropdown.classList.contains('hidden');
        if (isHidden) {
            dropdown.classList.remove('hidden');
            if (chevron) chevron.classList.add('rotate-180');
        } else {
            dropdown.classList.add('hidden');
            if (chevron) chevron.classList.remove('rotate-180');
        }
    }
};

window.applyPaymentVoucher = async function(code) {
    if (!code) return;
    var feedbackEl = document.getElementById('payment-voucher-feedback');
    var btn = document.getElementById('btn-apply-voucher');
    if (feedbackEl) feedbackEl.innerHTML = '<span class="text-gray-500 font-medium"><i class="fa-solid fa-spinner fa-spin mr-1"></i> Memvalidasi voucher...</span>';
    if (btn) btn.disabled = true;

    try {
        var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        var response = await fetch('/payment/apply-voucher', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ code: code }),
            credentials: 'same-origin'
        });

        if (response.status === 419) {
            var resToken = await fetch('/csrf-token', { headers: { 'Accept': 'application/json' } });
            var freshData = await resToken.json();
            if (freshData.csrf_token) {
                document.querySelector('meta[name="csrf-token"]')?.setAttribute('content', freshData.csrf_token);
                csrfToken = freshData.csrf_token;
            }
            response = await fetch('/payment/apply-voucher', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ code: code }),
                credentials: 'same-origin'
            });
        }

        var data = await response.json();

        if (response.ok && data.success) {
            if (feedbackEl) feedbackEl.innerHTML = '<span class="text-emerald-600 font-bold"><i class="fa-solid fa-circle-check mr-1"></i> ' + (data.message || 'Voucher berhasil digunakan!') + '</span>';
            var manualInput = document.getElementById('manual-payment-voucher-input');
            if (manualInput) manualInput.value = '';

            window.updatePaymentVoucherUI(data);

            if (typeof addToast === 'function') {
                addToast('success', data.message || 'Voucher berhasil diterapkan.');
            }
        } else {
            var msg = data.message || 'Gagal menerapkan voucher.';
            if (feedbackEl) feedbackEl.innerHTML = '<span class="text-red-500 font-medium"><i class="fa-solid fa-circle-exclamation mr-1"></i> ' + msg + '</span>';
            if (typeof addToast === 'function') {
                addToast('error', msg);
            }
        }
    } catch (err) {
        console.error('Error applying voucher:', err);
        if (feedbackEl) feedbackEl.innerHTML = '<span class="text-red-500 font-medium">Terjadi kesalahan koneksi. Silakan coba lagi.</span>';
    } finally {
        if (btn) btn.disabled = false;
    }
};

window.applyManualPaymentVoucher = function() {
    var input = document.getElementById('manual-payment-voucher-input');
    var code = input ? input.value.trim().toUpperCase() : '';
    if (!code) {
        var feedback = document.getElementById('payment-voucher-feedback');
        if (feedback) feedback.innerHTML = '<span class="text-red-500 font-medium">Masukkan kode voucher terlebih dahulu.</span>';
        return;
    }
    window.applyPaymentVoucher(code);
};

window.removePaymentVoucher = async function(code) {
    if (!code) return;
    try {
        var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        var response = await fetch('/payment/remove-voucher', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ code: code }),
            credentials: 'same-origin'
        });

        if (response.status === 419) {
            var resToken = await fetch('/csrf-token', { headers: { 'Accept': 'application/json' } });
            var freshData = await resToken.json();
            if (freshData.csrf_token) {
                document.querySelector('meta[name="csrf-token"]')?.setAttribute('content', freshData.csrf_token);
                csrfToken = freshData.csrf_token;
            }
            response = await fetch('/payment/remove-voucher', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ code: code }),
                credentials: 'same-origin'
            });
        }

        var data = await response.json();

        if (response.ok && data.success) {
            var feedbackEl = document.getElementById('payment-voucher-feedback');
            if (feedbackEl) feedbackEl.innerHTML = '<span class="text-gray-500">' + (data.message || 'Voucher dihapus.') + '</span>';

            window.updatePaymentVoucherUI(data);

            if (typeof addToast === 'function') {
                addToast('info', data.message || 'Voucher berhasil dihapus.');
            }
        }
    } catch (err) {
        console.error('Error removing voucher:', err);
    }
};

window.updatePaymentVoucherUI = function(data) {
    if (!data) return;

    // 1. Update Applied Vouchers Container & List
    var container = document.getElementById('payment-applied-vouchers-container');
    var list = document.getElementById('payment-applied-vouchers-list');
    var appliedVouchers = data.applied_vouchers || [];

    if (appliedVouchers.length > 0) {
        if (container) container.classList.remove('hidden');
        if (list) {
            list.innerHTML = appliedVouchers.map(function(av) {
                var isShipping = Boolean(av.is_shipping);
                return `
                    <div class="flex items-center justify-between bg-emerald-50/70 border border-emerald-200 rounded-xl p-3 text-xs sm:text-sm text-emerald-950" data-voucher-code="${av.code}">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                                <i class="fa-solid ${isShipping ? 'fa-truck-fast' : 'fa-percent'} text-xs"></i>
                            </div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="font-mono font-black text-emerald-800 uppercase tracking-wider">${av.code}</span>
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full ${isShipping ? 'bg-blue-100 text-blue-700' : 'bg-emerald-100 text-emerald-700'}">
                                        ${isShipping ? 'Gratis Ongkir' : 'Diskon Produk'}
                                    </span>
                                </div>
                                <p class="text-xs text-gray-600 truncate mt-0.5">${av.title || 'Potongan Diskon'}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 shrink-0 ml-3">
                            <span class="font-bold text-emerald-700">${av.discount_formatted || ('Rp ' + Number(av.discount || 0).toLocaleString('id-ID'))}</span>
                            <button type="button" onclick="removePaymentVoucher('${av.code}')" class="text-red-500 hover:text-red-700 p-1.5 rounded-lg hover:bg-red-50 transition-colors" title="Hapus voucher">
                                <i class="fa-solid fa-trash-can text-xs"></i>
                            </button>
                        </div>
                    </div>
                `;
            }).join('');
        }
    } else {
        if (container) container.classList.add('hidden');
        if (list) list.innerHTML = '';
    }

    // 2. Update Breakdown Rows
    var prodRow = document.getElementById('payment-product-voucher-row');
    var prodAmount = document.getElementById('payment-product-voucher-amount');
    var prodLabel = document.getElementById('payment-product-voucher-label');
    if (prodRow && prodAmount) {
        if (Number(data.product_voucher_discount || 0) > 0) {
            prodRow.classList.remove('hidden');
            prodAmount.textContent = data.product_voucher_formatted || ('- Rp ' + Number(data.product_voucher_discount).toLocaleString('id-ID'));
            var prodVoucher = appliedVouchers.find(function(v) { return !v.is_shipping; });
            if (prodLabel) prodLabel.textContent = prodVoucher ? '(' + prodVoucher.code + ')' : '';
        } else {
            prodRow.classList.add('hidden');
            if (prodLabel) prodLabel.textContent = '';
        }
    }

    var shipRow = document.getElementById('payment-shipping-voucher-row');
    var shipAmount = document.getElementById('payment-shipping-voucher-amount');
    var shipLabel = document.getElementById('payment-shipping-voucher-label');
    if (shipRow && shipAmount) {
        if (Number(data.shipping_voucher_discount || 0) > 0) {
            shipRow.classList.remove('hidden');
            shipAmount.textContent = data.shipping_voucher_formatted || ('- Rp ' + Number(data.shipping_voucher_discount).toLocaleString('id-ID'));
            var shipVoucher = appliedVouchers.find(function(v) { return Boolean(v.is_shipping); });
            if (shipLabel) shipLabel.textContent = shipVoucher ? '(' + shipVoucher.code + ')' : '';
        } else {
            shipRow.classList.add('hidden');
            if (shipLabel) shipLabel.textContent = '';
        }
    }

    // 3. Update Final Total Base & Trigger Recalculate
    var finalTotalEl = document.getElementById('final-total');
    if (finalTotalEl) {
        finalTotalEl.setAttribute('data-base-total', data.total);
        finalTotalEl.textContent = data.total_formatted || ('Rp ' + Number(data.total).toLocaleString('id-ID'));
    }

    var sidebarTotalEl = document.getElementById('sidebar-final-total');
    if (sidebarTotalEl) {
        sidebarTotalEl.textContent = data.total_formatted || ('Rp ' + Number(data.total).toLocaleString('id-ID'));
    }

    if (typeof window.toggleDetails === 'function') {
        window.toggleDetails();
    }

    // 4. Update Available Vouchers Modal Buttons
    var modalItems = document.querySelectorAll('.voucher-card-item');
    var appliedCodes = (data.voucher_codes || []).map(function(c) { return c.toUpperCase(); });
    modalItems.forEach(function(item) {
        var vCode = (item.dataset.voucherCode || '').toUpperCase();
        var isApplied = appliedCodes.includes(vCode);
        var actionCol = item.querySelector('.voucher-card-action');
        if (isApplied) {
            item.classList.add('border-emerald-500', 'bg-emerald-50/40', 'ring-1', 'ring-emerald-500/30');
            item.classList.remove('border-gray-200', 'bg-white');
            if (actionCol) {
                actionCol.innerHTML = `
                    <button type="button" onclick="removePaymentVoucher('${vCode}')" class="px-3 py-1.5 rounded-lg bg-emerald-100 text-emerald-800 font-bold text-xs hover:bg-red-50 hover:text-red-600 transition-colors flex items-center gap-1 group">
                        <i class="fa-solid fa-check text-[11px] group-hover:hidden"></i>
                        <i class="fa-solid fa-trash-can text-[11px] hidden group-hover:inline"></i>
                        <span class="group-hover:hidden">Diterapkan</span>
                        <span class="hidden group-hover:inline">Hapus</span>
                    </button>
                `;
            }
        } else {
            item.classList.remove('border-emerald-500', 'bg-emerald-50/40', 'ring-1', 'ring-emerald-500/30');
            item.classList.add('border-gray-200', 'bg-white');
            if (actionCol && !actionCol.querySelector('button[disabled]')) {
                actionCol.innerHTML = `
                    <button type="button" onclick="applyPaymentVoucher('${vCode}')" class="px-3.5 py-1.5 rounded-lg bg-brand-dark text-brand-gold font-bold text-xs hover:bg-brand-darker transition-colors shadow-2xs">
                        Gunakan
                    </button>
                `;
            }
        }
    });
};
