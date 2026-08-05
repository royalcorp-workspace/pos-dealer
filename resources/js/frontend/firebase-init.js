document.addEventListener('DOMContentLoaded', function() {
    const configEl = document.getElementById('firebase-config-data');
    if (configEl) {
        try {
            window.firebaseConfig = JSON.parse(configEl.textContent || '{}');
            if (window.firebaseConfig && window.firebaseConfig.apiKey) {
                firebase.initializeApp(window.firebaseConfig);
            }
        } catch(e) {
            console.error('Failed to parse Firebase config', e);
        }
    }
});
