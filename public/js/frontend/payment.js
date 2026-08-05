window.processPayment = function () {
    var selectedMethod = document.querySelector('input[name="payment_method"]:checked');
    if (!selectedMethod) {
        alert('Pilih metode pembayaran terlebih dahulu');
        return;
    }
    
    var container = document.getElementById('payment-container');
    var processUrl = container ? container.dataset.routePaymentProcess : '/payment/process';
    var thankYouUrl = container ? container.dataset.routeThankyou : '/thankyou';

    window.showLoading();

    var isManualTransfer = selectedMethod.getAttribute('data-is-manual') === '1';
    var body, headers;

    if (isManualTransfer) {
        var fileInput = document.getElementById('payment_proof');
        if (!fileInput || fileInput.files.length === 0) {
            alert('Silakan upload bukti transfer terlebih dahulu.');
            window.hideLoading();
            return;
        }

        body = new FormData();
        body.append('payment_method', selectedMethod.value);
        body.append('payment_proof', fileInput.files[0]);

        headers = {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        };
    } else {
        body = JSON.stringify({
            payment_method: selectedMethod.value
        });
        headers = {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        };
    }

    fetch(processUrl, {
        method: 'POST',
        headers: headers,
        body: body
    })
    .then(function (response) { return response.json(); })
    .then(function (data) {
        window.hideLoading();
        if (data.success) {
            // Clear localStorage on successful payment
            localStorage.removeItem('selectedCartCoupon');
            localStorage.removeItem('selectedCartCoupons');
            window.location.href = data.redirect_url || thankYouUrl;
        } else {
            alert(data.message || 'Gagal memproses pembayaran.');
        }
    })
    .catch(function () {
        window.hideLoading();
        alert('Gagal memproses pembayaran. Silakan coba lagi.');
    });
};

document.addEventListener('DOMContentLoaded', function() {
    var radios = document.querySelectorAll('input[name="payment_method"]');
    var detailsContainer = document.getElementById('transfer-manual-details');
    var banksContainer = document.getElementById('instructions-banks-container');
    
    function toggleDetails() {
        var selected = document.querySelector('input[name="payment_method"]:checked');
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
