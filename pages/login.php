<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (isset($_SESSION['user'])) {
    redirect('dashboard.php');
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Email dan password wajib diisi.';
    } else {
        try {
            $db = getDB();
            $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                unset($user['password']);
                $_SESSION['user'] = $user;
                flash('success', 'Selamat datang, ' . $user['name'] . '!');
                redirect('dashboard.php');
            } else {
                $error = 'Email atau password salah.';
            }
        } catch (PDOException $e) {
            $error = 'Terjadi kesalahan sistem.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — <?= APP_NAME ?></title>
    <script>if (localStorage.getItem('darkMode') === 'true') document.documentElement.classList.add('dark');</script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        @keyframes fade-up { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes fade-in { from { opacity: 0; } to { opacity: 1; } }
        .animate-fade-up { animation: fade-up 0.5s ease-out both; }
        .animate-fade-in { animation: fade-in 0.3s ease both; }
        .delay-1 { animation-delay: 0.1s; }
        .delay-2 { animation-delay: 0.2s; }
        .delay-3 { animation-delay: 0.3s; }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-900 dark:to-gray-800 min-h-screen flex items-center justify-center p-4 transition-colors duration-200">
    <div class="w-full max-w-md animate-fade-up">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8">
            <div class="text-center mb-8 animate-fade-in delay-1">
                <div class="w-16 h-16 bg-primary-800 rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg shadow-primary-800/20">
                    <i class="fa-solid fa-boxes-stacked text-white text-2xl"></i>
                </div>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white"><?= APP_NAME ?></h1>
                <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">Silakan login untuk melanjutkan</p>
            </div>

            <?php if ($error): ?>
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-400 px-4 py-3 rounded-lg mb-4 flex items-center gap-2 animate-fade-in">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?= escape($error) ?></span>
            </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <div class="animate-fade-in delay-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
                    <div class="relative">
                        <i class="fa-solid fa-envelope absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input type="email" name="email" value="<?= escape($email) ?>" required
                               class="w-full pl-10 pr-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent outline-none transition"
                               placeholder="Masukkan email">
                    </div>
                </div>
                <div class="animate-fade-in delay-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Password</label>
                    <div class="relative">
                        <i class="fa-solid fa-lock absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input type="password" name="password" required
                               class="w-full pl-10 pr-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent outline-none transition"
                               placeholder="Masukkan password">
                    </div>
                </div>
                <button type="submit" class="w-full bg-primary-800 text-white py-2.5 rounded-lg font-medium hover:bg-primary-900 transition animate-fade-in delay-3 inline-flex items-center justify-center gap-2">
                    <i class="fa-solid fa-right-to-bracket"></i> Login
                </button>
            </form>

            <p class="text-center text-sm text-gray-500 dark:text-gray-400 mt-6 animate-fade-in delay-3">
                Belum punya akun?
                <a href="<?= pageUrl('register.php') ?>" class="text-primary-800 dark:text-primary-400 font-medium hover:underline">Daftar</a>
            </p>
        </div>
    </div>
</body>
</html>