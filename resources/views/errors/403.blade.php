<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Akses Ditolak - IMG</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            light: '#FAF7ED',
                            gold: '#D4AF37',
                            'gold-dark': '#B8972E',
                            dark: '#1C1C1C',
                            darker: '#111111'
                        }
                    }
                }
            }
        }
    </script>
    <style>
        @keyframes float-gentle {
            0% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-12px) rotate(1deg); }
            100% { transform: translateY(0px) rotate(0deg); }
        }
        @keyframes lock-shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-4px); }
            20%, 40%, 60%, 80% { transform: translateX(4px); }
        }
        @keyframes pulse-glow {
            0% { box-shadow: 0 0 0 0 rgba(212, 175, 55, 0.4); }
            70% { box-shadow: 0 0 0 15px rgba(212, 175, 55, 0); }
            100% { box-shadow: 0 0 0 0 rgba(212, 175, 55, 0); }
        }
        @keyframes fade-slide-up {
            0% { opacity: 0; transform: translateY(20px); }
            100% { opacity: 1; transform: translateY(0); }
        }
        .animate-float { animation: float-gentle 6s ease-in-out infinite; }
        .animate-shake-hover:hover { animation: lock-shake 0.5s ease-in-out; }
        .animate-pulse-glow { animation: pulse-glow 2.5s infinite; }
        .animate-fade-1 { animation: fade-slide-up 0.6s ease-out forwards; }
        .animate-fade-2 { animation: fade-slide-up 0.6s ease-out 0.2s forwards; opacity: 0; }
        .animate-fade-3 { animation: fade-slide-up 0.6s ease-out 0.4s forwards; opacity: 0; }
        .animate-fade-4 { animation: fade-slide-up 0.6s ease-out 0.6s forwards; opacity: 0; }
        body { margin: 0; font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; }
    </style>
</head>
<body class="bg-white">
    <div class="container mx-auto px-4 md:px-6 py-12 h-screen flex items-center justify-center relative overflow-hidden">
        <!-- Decorative background blobs -->
        <div class="absolute top-1/3 left-1/3 w-72 h-72 bg-brand-gold/10 rounded-full blur-3xl -z-10 animate-float" style="animation-delay: -1s;"></div>
        <div class="absolute bottom-1/3 right-1/4 w-80 h-80 bg-brand-dark/5 rounded-full blur-3xl -z-10 animate-float" style="animation-delay: -3s;"></div>

        <div class="max-w-4xl mx-auto text-center z-10">
            <div class="inline-flex items-center justify-center w-24 h-24 rounded-full bg-brand-light text-brand-gold mb-8 animate-float animate-pulse-glow border border-brand-gold/20 shadow-sm animate-shake-hover cursor-not-allowed">
                <i class="fa-solid fa-lock w-10 h-10 text-3xl text-brand-gold"></i>
            </div>

            <p class="text-brand-gold-dark font-bold tracking-[0.2em] uppercase text-sm mb-4 animate-fade-1">403 Forbidden</p>

            <h1 class="text-4xl md:text-5xl font-extrabold text-brand-dark tracking-tight mb-6 animate-fade-2">
                Akses Terkunci
            </h1>

            <p class="text-gray-500 text-lg max-w-2xl mx-auto leading-relaxed animate-fade-3">
                Maaf, Anda tidak memiliki izin untuk memasuki halaman ini. Ibarat ruangan VIP, halaman ini membutuhkan akses khusus. Mari kembali ke area utama untuk melihat koleksi kasur dan <i>springbed</i> kami.
            </p>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-4 mt-10 animate-fade-4">
                <a href="{{ route('home') }}" class="px-8 py-4 rounded-full font-bold text-white bg-brand-dark hover:bg-brand-darker transition-all shadow-lg shadow-brand-dark/20" style="text-decoration:none;">
                    Kembali ke Beranda
                </a>
                <a href="{{ route('products.index') ?? url('/products') }}" class="px-8 py-4 rounded-full font-bold text-brand-dark bg-white border-2 border-brand-dark hover:bg-brand-dark hover:text-white transition-all" style="text-decoration:none;">
                    Lihat Koleksi Kasur
                </a>
            </div>
        </div>
    </div>
</body>
</html>
