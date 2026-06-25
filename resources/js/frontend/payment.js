window.processPayment = function () {
    var selectedMethod = document.querySelector('input[name="payment_method"]:checked');
    if (!selectedMethod) {
        alert('Pilih metode pembayaran terlebih dahulu');
        return;
    }
    window.showLoading();
    setTimeout(function () {
        window.location.href = document.body.dataset.routeThankyou || '/thankyou';
    }, 500);
};
