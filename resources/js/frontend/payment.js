window.processPayment = function () {
    var selectedMethod = document.querySelector('input[name="payment_method"]:checked');
    var validationAlert = document.getElementById('payment-method-validation-error');
    var accordionsWrapper = document.getElementById('payment-accordions-wrapper');

    if (!selectedMethod) {
        if (validationAlert) {
            validationAlert.classList.remove('hidden');
            validationAlert.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        if (accordionsWrapper) {
            accordionsWrapper.classList.add('p-2.5', 'rounded-2xl', 'border-2', 'border-red-400', 'bg-red-50/20');
        }
        window.dispatchEvent(new CustomEvent('show-toast', { 
            detail: { type: 'warning', message: 'Silakan pilih saluran metode pembayaran terlebih dahulu.' } 
        }));
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'warning',
                title: 'Pilih Metode Pembayaran',
                text: 'Silakan pilih salah satu saluran pembayaran (Transfer Bank / E-Wallet / QRIS / Kartu Kredit) sebelum melanjutkan.',
                confirmButtonColor: '#1e3a8a',
                confirmButtonText: 'Mengerti'
            });
        }
        return;
    }

    // Clear validation if method is selected
    if (validationAlert) validationAlert.classList.add('hidden');
    if (accordionsWrapper) accordionsWrapper.classList.remove('p-2.5', 'border-2', 'border-red-400', 'bg-red-50/20');

    var isManualTransfer = selectedMethod.getAttribute('data-is-manual') === '1';

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
            order_id: orderId
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
    var detailsContainer = document.getElementById('transfer-manual-details');
    var banksContainer = document.getElementById('instructions-banks-container');
    var chargeRow = document.getElementById('charge-row');
    var chargeAmountLabel = document.getElementById('charge-amount');
    var finalTotalLabel = document.getElementById('final-total');
    
    function toggleDetails() {
        var selected = document.querySelector('input[name="payment_method"]:checked');        
        // 0. Visual Update for Radio Buttons
        document.querySelectorAll('.payment-method-label').forEach(function(label) {
            label.classList.remove('bg-brand-gold/5');
            var circle = label.querySelector('.rounded-full');
            if (circle) {
                circle.classList.remove('border-brand-gold', 'bg-brand-gold');
                circle.classList.add('border-gray-300');
            }
            var svg = label.querySelector('svg');
            if (svg) {
                svg.classList.remove('opacity-100');
                svg.classList.add('opacity-0');
            }
        });

        // 0b. Visual & Badge Update for Shopee Accordion Groups
        var accordionGroups = document.querySelectorAll('.payment-accordion-group');
        accordionGroups.forEach(function(group) {
            var badge = group.querySelector('.category-selected-badge');
            var badgeText = group.querySelector('.badge-text');
            var checkedInGroup = group.querySelector('input[name="payment_method"]:checked');
            
            if (checkedInGroup) {
                var methodName = checkedInGroup.getAttribute('data-method-name') || checkedInGroup.value;
                if (badge && badgeText) {
                    badgeText.textContent = methodName;
                    badge.classList.remove('hidden');
                    badge.classList.add('inline-flex');
                }
                group.classList.add('border-brand-gold', 'ring-2', 'ring-brand-gold/20');
                group.classList.remove('border-gray-200');

                var content = group.querySelector('.payment-accordion-content');
                var chevron = group.querySelector('.accordion-chevron');
                if (content) content.classList.remove('hidden');
                if (chevron) chevron.classList.add('rotate-180');
            } else {
                if (badge) {
                    badge.classList.add('hidden');
                    badge.classList.remove('inline-flex');
                }
                group.classList.remove('border-brand-gold', 'ring-2', 'ring-brand-gold/20');
                group.classList.add('border-gray-200');
            }
        });
        
        if (selected) {
            var label = selected.closest('.payment-method-label');
            if (label) {
                label.classList.add('bg-brand-gold/5');
                var circle = label.querySelector('.rounded-full');
                if (circle) {
                    circle.classList.remove('border-gray-300');
                    circle.classList.add('border-brand-gold', 'bg-brand-gold');
                }
                var svg = label.querySelector('svg');
                if (svg) {
                    svg.classList.remove('opacity-0');
                    svg.classList.add('opacity-100');
                }
            }
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
            } else {
                if (chargeRow) chargeRow.classList.add('hidden');
            }
            
            if (finalTotalLabel) {
                finalTotalLabel.textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(baseTotal + charge);
            }
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
            var btn = document.querySelector('button[onclick="processPayment()"]');
            if(btn) {
                btn.disabled = true;
                btn.classList.add('opacity-50', 'cursor-not-allowed');
                btn.innerHTML = 'Waktu Habis';
            }
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
