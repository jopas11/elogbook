<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 — Halaman Tidak Ditemukan</title>
    <script>if (localStorage.getItem('darkMode') === 'true') document.documentElement.classList.add('dark');</script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com/3.4.17"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                        mono: ['JetBrains Mono', 'monospace'],
                    },
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        @keyframes fade-up { from { opacity: 0; transform: translateY(24px) scale(0.97); } to { opacity: 1; transform: translateY(0) scale(1); } }
        @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-12px); } }
        @keyframes aurora-float { 0%, 100% { transform: translate(0, 0) scale(1); } 25% { transform: translate(30px, -20px) scale(1.05); } 50% { transform: translate(-20px, 20px) scale(0.95); } 75% { transform: translate(15px, 10px) scale(1.02); } }
        @keyframes glitch {
            0%, 100% { transform: translate(0); }
            20% { transform: translate(-2px, 2px); }
            40% { transform: translate(-2px, -2px); }
            60% { transform: translate(2px, 2px); }
            80% { transform: translate(2px, -2px); }
        }
        .animate-fade-up { animation: fade-up 0.7s cubic-bezier(0.22, 1, 0.36, 1) both; }
        .animate-float { animation: float 4s ease-in-out infinite; }
        .delay-1 { animation-delay: 0.1s; }
        .delay-2 { animation-delay: 0.2s; }
        .aurora-orb { animation: aurora-float 12s ease-in-out infinite; }
    </style>
</head>
<body class="bg-[#0a0a0f] h-screen flex items-center justify-center p-4 transition-colors duration-300 overflow-hidden">
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-40 -right-40 w-96 h-96 bg-cyan-500/10 rounded-full blur-[120px] aurora-orb"></div>
        <div class="absolute -bottom-40 -left-40 w-[500px] h-[500px] bg-violet-500/8 rounded-full blur-[140px] aurora-orb" style="animation-delay: -4s;"></div>
    </div>

    <div class="text-center max-w-md relative animate-fade-up">
        <div class="text-[10rem] font-extrabold leading-none font-mono" style="background: linear-gradient(135deg, rgba(0,212,255,0.15), rgba(139,92,246,0.1)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; animation: float 4s ease-in-out infinite;">404</div>
        <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-cyan-500/10 to-violet-500/10 flex items-center justify-center mx-auto -mt-16 mb-6 shadow-lg ring-1 ring-white/5 animate-float" style="animation-delay: -1s;">
            <i class="fa-solid fa-ghost text-cyan-400 text-3xl"></i>
        </div>
        <h1 class="text-2xl font-bold text-white mb-2 animate-fade-up delay-1">Halaman Tidak Ditemukan</h1>
        <p class="text-gray-500 mb-8 animate-fade-up delay-1">Halaman yang Anda cari tidak tersedia atau telah dipindahkan.</p>
        <a href="<?= pageUrl('dashboard.php') ?>" class="animate-fade-up delay-2 inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-cyan-500 to-violet-500 text-white rounded-xl hover:from-cyan-400 hover:to-violet-400 transition-all font-semibold shadow-lg shadow-cyan-500/20 magnetic-btn">
            <i class="fa-solid fa-arrow-left"></i> Kembali ke Dashboard
        </a>
    </div>
</body>
</html>
