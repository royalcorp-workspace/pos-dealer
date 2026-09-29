(function () {
    window.switchLanguage = function (locale) {
        fetch('/lang/' + locale, {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                window.location.reload();
            }
        })
        .catch(function() { window.location.reload(); });
    };

    var currentPath = window.location.pathname;
    var langLinks = document.querySelectorAll('[href*="/lang/"]');
    langLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            var href = this.getAttribute('href');
            var match = href.match(/\/lang\/(id|en)/);
            if (match) {
                e.preventDefault();
                window.switchLanguage(match[1]);
            }
        });
    });
})();
