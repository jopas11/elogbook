<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 — Halaman Tidak Ditemukan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        @keyframes fade-up { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }
        .animate-fade-up { animation: fade-up 0.6s ease-out both; }
        .animate-float { animation: float 3s ease-in-out infinite; }
        .delay-1 { animation-delay: 0.1s; }
        .delay-2 { animation-delay: 0.2s; }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-900 dark:to-gray-800 h-screen flex items-center justify-center p-4 transition-colors duration-200">
    <div class="text-center max-w-md animate-fade-up">
        <div class="text-9xl font-bold text-primary-800/20 dark:text-primary-400/10 animate-float">404</div>
        <div class="w-20 h-20 bg-primary-100 dark:bg-primary-900/30 rounded-full flex items-center justify-center mx-auto -mt-12 mb-6 shadow-lg">
            <i class="fa-solid fa-map-pin text-primary-800 dark:text-primary-400 text-3xl"></i>
        </div>
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white mb-2 animate-fade-up delay-1">Halaman Tidak Ditemukan</h1>
        <p class="text-gray-500 dark:text-gray-400 mb-8 animate-fade-up delay-1">Halaman yang Anda cari tidak tersedia atau telah dipindahkan.</p>
        <a href="dashboard.php" class="inline-flex items-center gap-2 px-6 py-3 bg-primary-800 text-white rounded-xl hover:bg-primary-900 transition font-medium shadow-lg shadow-primary-800/20 animate-fade-up delay-2">
            <i class="fa-solid fa-arrow-left"></i> Kembali ke Dashboard
        </a>
    </div>
</body>
</html>