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

    fetch(processUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            payment_method: selectedMethod.value
        })
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
