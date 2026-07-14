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
