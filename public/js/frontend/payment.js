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

                var iframeUrl = '';
                if (typeof SGOSignature !== 'undefined' && typeof SGOSignature.getIframeURL === 'function') {
                    try {
                        iframeUrl = SGOSignature.getIframeURL(data.espay_kit);
                    } catch (e) {
                        console.error('SGOSignature getIframeURL error:', e);
                    }
                }

                if (!iframeUrl) {
                    var domain = "https://sandbox-kit.espay.id";
                    var sig = data.espay_kit.signature || '';
                    var key = data.espay_kit.key || '';
                    var pId = data.espay_kit.paymentId || '';
                    var bUrl = encodeURIComponent(data.espay_kit.backUrl || thankYouUrl);
                    var bCode = data.espay_kit.bankCode || '';
                    var prCode = data.espay_kit.productCode || '';
                    iframeUrl = domain + "/plugin/merchantkey/?signature=" + sig + "&domain=" + domain + "&key=" + key + "&paymentId=" + pId + "&backUrl=" + bUrl + "&bankCode=" + bCode + "&productCode=" + prCode;
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
