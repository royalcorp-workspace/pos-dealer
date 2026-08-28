window.processPayment = function () {
    var selectedMethod = document.querySelector('input[name="payment_method"]:checked');
    if (!selectedMethod) {
        window.dispatchEvent(new CustomEvent('show-toast', { detail: { type: 'warning', message: 'Pilih metode pembayaran terlebih dahulu' } }));
        return;
    }

    var isManualTransfer = selectedMethod.getAttribute('data-is-manual') === '1';

    var container = document.getElementById('payment-container');
    var processUrl = container ? container.dataset.routePaymentProcess : '/payment/process';
    var thankYouUrl = container ? container.dataset.routeThankyou : '/thankyou';
    var orderId = container ? container.dataset.orderId : '';

    var body, headers;

    if (isManualTransfer) {
        var fileInput = document.getElementById('payment_proof');
        if (!fileInput || fileInput.files.length === 0) {
            window.dispatchEvent(new CustomEvent('show-toast', { detail: { type: 'warning', message: 'Silakan upload bukti transfer terlebih dahulu.' } }));
            return;
        }

        window.showLoading();
        body = new FormData();
        body.append('payment_method', selectedMethod.value);
        body.append('order_id', orderId);
        body.append('payment_proof', fileInput.files[0]);

        headers = {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        };
    } else {
        window.showLoading();
        body = JSON.stringify({
            payment_method: selectedMethod.value,
            order_id: orderId
        });
        headers = {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        };
    }

    fetch(processUrl, { method: 'POST', headers: headers, body: body })
    .then(function (response) { return response.json(); })
    .then(function (data) {
        window.hideLoading();
        if (data.success) {
            localStorage.removeItem('selectedCartCoupon');
            localStorage.removeItem('selectedCartCoupons');
            
            // Redirect biasa (tanpa Iframe Snap)
            window.location.href = data.redirect_url || thankYouUrl;
        } else {
            window.dispatchEvent(new CustomEvent('show-toast', { detail: { type: 'error', message: data.message || 'Gagal memproses pembayaran.' } }));
        }
    })
    .catch(function () {
        window.hideLoading();
        window.dispatchEvent(new CustomEvent('show-toast', { detail: { type: 'error', message: 'Gagal memproses pembayaran.' } }));
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
        // 0. Visual Update for Radio Buttons (Fallback for browsers without :has() support)
        document.querySelectorAll('.payment-method-label').forEach(function(label) {
            label.classList.remove('border-brand-gold', 'bg-brand-gold/5');
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
        
        if (selected) {
            var label = selected.closest('.payment-method-label');
            if (label) {
                label.classList.add('border-brand-gold', 'bg-brand-gold/5');
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
            
            var orderTotal = detailsContainer ? detailsContainer.getAttribute('data-order-total') : '0';

            banksContainer.innerHTML = '';
            banksData.forEach(function(bank) {
                var card = document.createElement('div');
                card.className = 'bg-white p-4 rounded-xl border border-brand-muted space-y-2 shadow-sm mb-4';
                card.innerHTML = `
                    <div class="flex justify-between items-center border-b pb-2">
                        <span class="text-gray-500 text-xs">Bank</span>
                        <span class="font-bold text-brand-dark">${bank.bank_name}</span>
                    </div>
                    <div class="flex justify-between items-center border-b pb-2">
                        <span class="text-gray-500 text-xs">No. Rekening</span>
                        <span class="font-bold text-brand-dark font-mono text-base">${bank.account_number}</span>
                    </div>
                    <div class="flex justify-between items-center border-b pb-2">
                        <span class="text-gray-500 text-xs">Atas Nama</span>
                        <span class="font-bold text-brand-dark">${bank.account_holder}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-500 text-xs">Total Transfer</span>
                        <span class="font-extrabold text-brand-gold-dark text-base">Rp ${new Intl.NumberFormat('id-ID').format(orderTotal)}</span>
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
        radio.addEventListener('change', toggleDetails);
    });
    
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
