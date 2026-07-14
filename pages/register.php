<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (isset($_SESSION['user'])) {
    redirect('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf(_get($_POST, 'csrf_token', ''))) {
        $error = 'Token CSRF tidak valid. Silakan coba lagi.';
    }

    $name = trim(_get($_POST, 'name', ''));
    $email = trim(_get($_POST, 'email', ''));
    $password = _get($_POST, 'password', '');
    $password_confirmation = _get($_POST, 'password_confirmation', '');

    if (!$error) {
        if (empty($name) || empty($email) || empty($password)) {
            $error = 'Semua field wajib diisi.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Format email tidak valid.';
        } elseif (strlen($password) < 6) {
            $error = 'Password minimal 6 karakter.';
        } elseif ($password !== $password_confirmation) {
            $error = 'Konfirmasi password tidak cocok.';
        } else {
            try {
                $db = getDB();
                $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute(array($email));
                if ($stmt->fetch()) {
                    $error = 'Email sudah terdaftar.';
                } else {
                    $hashed = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $db->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'user')");
                    $stmt->execute(array($name, $email, $hashed));
                    flash('success', 'Pendaftaran berhasil. Silakan login.');
                    redirect('login.php');
                }
            } catch (PDOException $e) {
                $error = 'Terjadi kesalahan sistem.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar — <?= APP_NAME ?></title>
    <script>if (localStorage.getItem('darkMode') === 'true') document.documentElement.classList.add('dark');</script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com/3.4.17"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] },
                    colors: {
                        primary: { 50: '#eef2ff', 100: '#e0e7ff', 200: '#c7d2fe', 300: '#a5b4fc', 400: '#818cf8', 500: '#6366f1', 600: '#4f46e5', 700: '#4338ca', 800: '#3730a3', 900: '#312e81' },
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        @keyframes fade-up { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes fade-in { from { opacity: 0; } to { opacity: 1; } }
        @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-20px); } }
        @keyframes pulse-soft { 0%, 100% { opacity: 0.3; } 50% { opacity: 0.6; } }
        .animate-fade-up { animation: fade-up 0.6s ease-out both; }
        .animate-fade-in { animation: fade-in 0.4s ease both; }
        .delay-1 { animation-delay: 0.1s; }
        .delay-2 { animation-delay: 0.2s; }
        .delay-3 { animation-delay: 0.35s; }
        .delay-4 { animation-delay: 0.5s; }
        .animate-float { animation: float 6s ease-in-out infinite; }
        .animate-pulse-soft { animation: pulse-soft 4s ease-in-out infinite; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 font-sans antialiased transition-colors duration-200"
      style="background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 50%, #c7d2fe 100%);">
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-40 -right-40 w-80 h-80 bg-indigo-300/30 rounded-full blur-3xl animate-float"></div>
        <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-purple-300/20 rounded-full blur-3xl animate-float" style="animation-delay: -3s;"></div>
        <div class="absolute top-1/2 left-1/4 w-64 h-64 bg-indigo-200/20 rounded-full blur-3xl animate-pulse-soft"></div>
    </div>

    <div class="relative w-full max-w-md animate-fade-up">
        <div class="bg-white/80 dark:bg-gray-800/90 backdrop-blur-xl rounded-3xl shadow-2xl shadow-indigo-500/10 dark:shadow-black/30 p-8 sm:p-10 border border-white/50 dark:border-gray-700/50">
            <div class="text-center mb-8 animate-fade-in delay-1">
                <div class="w-16 h-16 bg-gradient-to-br from-indigo-600 to-purple-600 rounded-2xl flex items-center justify-center mx-auto mb-5 shadow-xl shadow-indigo-500/30 ring-4 ring-indigo-100 dark:ring-indigo-900/50">
                    <i class="fa-solid fa-user-plus text-white text-2xl"></i>
                </div>
                <h1 class="text-2xl font-extrabold text-gray-800 dark:text-white tracking-tight">Buat Akun Baru</h1>
                <p class="text-gray-500 dark:text-gray-400 text-sm mt-1.5">Daftar untuk mulai menggunakan <?= APP_NAME ?></p>
            </div>

            <?php if ($error): ?>
            <div class="bg-red-50/80 dark:bg-red-900/20 backdrop-blur-sm border border-red-200/80 dark:border-red-800/50 text-red-700 dark:text-red-400 px-4 py-3 rounded-xl mb-5 flex items-center gap-2.5 animate-fade-in text-sm">
                <i class="fa-solid fa-circle-exclamation shrink-0"></i>
                <span><?= escape($error) ?></span>
            </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <?= csrf() ?>
                <div class="animate-fade-in delay-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Nama Lengkap</label>
                    <div class="relative group">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-indigo-500 dark:group-focus-within:text-indigo-400 transition-colors duration-200">
                            <i class="fa-solid fa-user"></i>
                        </span>
                        <input type="text" name="name" value="<?= escape(old('name')) ?>" required
                               class="w-full pl-10 pr-4 py-3 bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 dark:text-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 outline-none transition-all duration-200 placeholder:text-gray-400"
                               placeholder="Masukkan nama lengkap">
                    </div>
                </div>
                <div class="animate-fade-in delay-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Email</label>
                    <div class="relative group">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-indigo-500 dark:group-focus-within:text-indigo-400 transition-colors duration-200">
                            <i class="fa-solid fa-envelope"></i>
                        </span>
                        <input type="email" name="email" value="<?= escape(old('email')) ?>" required
                               class="w-full pl-10 pr-4 py-3 bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 dark:text-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 outline-none transition-all duration-200 placeholder:text-gray-400"
                               placeholder="Masukkan email">
                    </div>
                </div>
                <div class="animate-fade-in delay-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Password</label>
                    <div class="relative group">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-indigo-500 dark:group-focus-within:text-indigo-400 transition-colors duration-200">
                            <i class="fa-solid fa-lock"></i>
                        </span>
                        <input type="password" name="password" required
                               class="w-full pl-10 pr-4 py-3 bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 dark:text-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 outline-none transition-all duration-200 placeholder:text-gray-400"
                               placeholder="Minimal 6 karakter">
                    </div>
                </div>
                <div class="animate-fade-in delay-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Konfirmasi Password</label>
                    <div class="relative group">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-indigo-500 dark:group-focus-within:text-indigo-400 transition-colors duration-200">
                            <i class="fa-solid fa-lock"></i>
                        </span>
                        <input type="password" name="password_confirmation" required
                               class="w-full pl-10 pr-4 py-3 bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 dark:text-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 outline-none transition-all duration-200 placeholder:text-gray-400"
                               placeholder="Ulangi password">
                    </div>
                </div>
                <button type="submit" class="w-full py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold rounded-xl hover:from-indigo-700 hover:to-purple-700 focus:ring-2 focus:ring-indigo-500/50 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-all duration-200 inline-flex items-center justify-center gap-2 shadow-lg shadow-indigo-500/25 animate-fade-in delay-3">
                    <i class="fa-solid fa-user-plus"></i> Daftar
                </button>
            </form>

            <p class="text-center text-sm text-gray-500 dark:text-gray-400 mt-6 animate-fade-in delay-4">
                Sudah punya akun?
                <a href="<?= pageUrl('login.php') ?>" class="text-indigo-600 dark:text-indigo-400 font-semibold hover:text-indigo-700 dark:hover:text-indigo-300 transition-colors">Login</a>
            </p>
        </div>
    </div>
</body>
</html>
