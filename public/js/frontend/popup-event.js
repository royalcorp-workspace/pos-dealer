(function () {
    var dismissed = false;
    var popup = document.querySelector('[data-popup-event]');
    var cookieName = 'popup_event_dismissed';

    function getCookie(name) {
        var match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
        return match ? decodeURIComponent(match[2]) : null;
    }

    function setCookie(name, value, days) {
        var expires = '';
        if (days) {
            var date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            expires = '; expires=' + date.toUTCString();
        }
        document.cookie = name + '=' + value + expires + '; path=/';
    }

    function hidePopup() {
        if (popup) {
            popup.style.display = 'none';
        }
        setCookie(cookieName, '1', 1);
    }

    function showPopup() {
        if (popup) {
            popup.style.display = 'block';
        }
    }

    if (!popup) return;

    var dismissedValue = getCookie(cookieName);
    if (dismissedValue === '1') {
        hidePopup();
        return;
    }

    // Delay showing popup by 1 second after page load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            setTimeout(showPopup, 1000);
        });
    } else {
        setTimeout(showPopup, 1000);
    }

    var closeBtn = popup.querySelector('[data-popup-close]');
    if (closeBtn) {
        closeBtn.addEventListener('click', hidePopup);
    }

    // Close when clicking outside
    popup.addEventListener('click', function (e) {
        if (e.target === popup) {
            hidePopup();
        }
    });

    window.hidePopupEvent = hidePopup;
})();
