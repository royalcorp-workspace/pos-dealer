(function(){
    if (typeof window.tailwind === 'undefined') {
        window._tailwindQueue = window._tailwindQueue || [];
        window.tailwind = function(){ window._tailwindQueue.push(arguments); };
        var runQueued = function(){
            try {
                if (typeof window.tailwind !== 'function') return;
                if (!window._tailwindQueue || !window._tailwindQueue.length) return;
                var real = window.tailwind;
                if (real && real !== window.tailwind) return;
            } catch(e){}
        };
        window.addEventListener('load', function(){
            if (window._tailwindQueue && window.tailwind && typeof window.tailwind === 'function' && window._tailwindQueue.length) {
                try {
                    var realTailwind = window.tailwind !== arguments.callee ? window.tailwind : null;
                } catch(e){ realTailwind = null }
            }
        });
    }
})();

tailwind.config = {
    theme: {
        fontFamily: {
            sans: ['Inter', 'ui-sans-serif', 'system-ui', '-apple-system', 'sans-serif'],
            serif: ['"Playfair Display"', 'ui-serif', 'Georgia', 'Cambria', 'serif'],
            mono: ['ui-monospace', 'SFMono-Regular', 'Menlo', 'Monaco', 'monospace'],
        },
        extend: {
            colors: {
                brand: {
                    dark: '#2b1d12',
                    darker: '#1a1009',
                    gold: '#c09d6b',
                    'gold-dark': '#ad8a58',
                    light: '#fdfbf7',
                    muted: '#f2ebd9',
                },
            },
        },
    },
    safelist: [
        'hover:shadow-xl',
        'hover:scale-[1.02]',
        'hover:-translate-y-1',
        'group-hover:scale-105',
        'group-hover:scale-110',
        'group-hover:opacity-100',
        'group-hover:translate-x-0',
        'group-hover:text-white',
        'group-hover:bg-brand-dark',
        'group-hover:text-white',
        'group-hover:text-brand-gold-dark',
        'group-hover:bg-brand-gold',
        'hover:text-brand-gold',
        'hover:text-brand-gold-dark',
        'hover:text-brand-dark',
        'hover:border-brand-gold',
        'hover:bg-brand-dark',
        'hover:bg-brand-light',
        'hover:shadow-md',
        'hover:shadow-lg',
    ],
};

(function(){
    try {
        if (window._tailwindQueue && window._tailwindQueue.length && typeof window.tailwind === 'function') {
            window._tailwindQueue.forEach(function(args){
                try { window.tailwind.apply(null, args); } catch(e) { console.warn('replaying tailwind call failed', e); }
            });
            window._tailwindQueue = [];
        }
    } catch(e) { /* noop */ }
})();
