<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sedang Dalam Pemeliharaan - Premium Mattress Gallery</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #0a0f1d;
            color: #F8FAFC;
            overflow: hidden;
            margin: 0;
            padding: 0;
        }
        h1, .font-serif {
            font-family: 'Playfair Display', serif;
        }
        
        /* Stars Animation */
        .stars {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            z-index: 0;
            overflow: hidden;
        }
        .star {
            position: absolute;
            background: #fff;
            border-radius: 50%;
            animation: twinkle infinite ease-in-out;
        }
        @keyframes twinkle {
            0%, 100% { opacity: 0.1; transform: scale(0.8); }
            50% { opacity: 0.8; transform: scale(1.2); box-shadow: 0 0 10px #fff, 0 0 15px rgba(212, 175, 55, 0.5); }
        }

        /* Breathing Logo Glow */
        .breathe {
            animation: breathe 5s infinite ease-in-out;
        }
        @keyframes breathe {
            0%, 100% { transform: translateY(0) scale(1); box-shadow: 0 0 20px rgba(212, 175, 55, 0.1); }
            50% { transform: translateY(-12px) scale(1.03); box-shadow: 0 0 40px rgba(212, 175, 55, 0.4); }
        }
        
        /* Staggered Fade In */
        .fade-in-up {
            animation: fadeInUp 1.2s cubic-bezier(0.2, 0.8, 0.2, 1) forwards;
            opacity: 0;
            transform: translateY(30px);
        }
        .delay-1 { animation-delay: 0.2s; }
        .delay-2 { animation-delay: 0.4s; }
        .delay-3 { animation-delay: 0.6s; }
        
        @keyframes fadeInUp {
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Subtle Gradient Overlay */
        .gradient-overlay {
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at center, rgba(30, 41, 59, 0.3) 0%, rgba(10, 15, 29, 1) 100%);
            z-index: 1;
        }
        
        .content-z {
            position: relative;
            z-index: 10;
        }
        
        .gold-gradient {
            background: linear-gradient(135deg, #FDE047 0%, #D4AF37 50%, #B48B25 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col items-center justify-center relative">
    
    <!-- Animated Stars Background -->
    <div class="stars" id="starsContainer"></div>
    <div class="gradient-overlay"></div>

    <main class="content-z w-full max-w-3xl mx-auto px-6 text-center">
        <!-- Animated Icon -->
        <div class="mb-12 flex justify-center fade-in-up">
            <div class="breathe w-28 h-28 rounded-full bg-slate-900 border border-[#D4AF37]/30 flex items-center justify-center backdrop-blur-sm relative overflow-hidden">
                <!-- Inner glow -->
                <div class="absolute inset-0 bg-[#D4AF37]/10 rounded-full"></div>
                <!-- Premium bed/sleep icon -->
                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-[#D4AF37] relative z-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                </svg>
            </div>
        </div>

        <!-- Typography -->
        <h1 class="text-4xl md:text-5xl lg:text-6xl font-serif text-white mb-6 fade-in-up delay-1 leading-tight tracking-wide">
            Meningkatkan <span class="gold-gradient font-medium italic">Kenyamanan</span> Anda
        </h1>
        
        <p class="text-slate-300/80 text-base md:text-lg lg:text-xl mb-12 fade-in-up delay-2 font-light tracking-wide max-w-xl mx-auto leading-relaxed">
            Sistem kami sedang dalam peningkatan rutin untuk memberikan Anda pengalaman belanja perlengkapan tidur yang lebih eksklusif dan paripurna.
        </p>

        <!-- Animated Badge -->
        <div class="fade-in-up delay-3">
            <div class="inline-block relative group">
                <div class="absolute inset-0 bg-[#D4AF37] blur-md opacity-20 rounded-full transition-opacity duration-1000 group-hover:opacity-40"></div>
                <div class="relative px-8 py-3 bg-slate-900/80 border border-[#D4AF37]/40 text-[#FDE047] rounded-full text-xs md:text-sm font-medium tracking-[0.2em] uppercase backdrop-blur-md shadow-2xl">
                    Sistem Sedang Diperbarui
                </div>
            </div>
        </div>
    </main>

    <!-- Footer Note -->
    <div class="absolute bottom-8 left-0 right-0 text-center content-z fade-in-up delay-3 opacity-50">
        <p class="text-xs text-slate-500 tracking-widest uppercase">IMG International Mattress Gallery</p>
    </div>

    <!-- JS for Stars -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const container = document.getElementById('starsContainer');
            const starCount = window.innerWidth < 768 ? 40 : 80;
            
            for(let i = 0; i < starCount; i++) {
                const star = document.createElement('div');
                star.className = 'star';
                
                const size = Math.random() * 2 + 1;
                const x = Math.random() * 100;
                const y = Math.random() * 100;
                const duration = Math.random() * 4 + 3; // 3s to 7s
                const delay = Math.random() * 5;
                
                star.style.width = `${size}px`;
                star.style.height = `${size}px`;
                star.style.left = `${x}%`;
                star.style.top = `${y}%`;
                star.style.animationDuration = `${duration}s`;
                star.style.animationDelay = `${delay}s`;
                
                container.appendChild(star);
            }
        });
    </script>
</body>
</html>
