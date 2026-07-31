<?php

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/functions.php';

if (isset($_SESSION['user'])) {
    redirect('dashboard.php');
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf(_get($_POST, 'csrf_token', ''))) {
        $error = 'Token CSRF tidak valid. Silakan coba lagi.';
    }

    $email = trim(_get($_POST, 'email', ''));
    $password = _get($_POST, 'password', '');

    if (!$error) {
        if (empty($email) || empty($password)) {
            $error = 'Email dan password wajib diisi.';
        } else {
            try {
                $db = getDB();
                $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
                $stmt->execute(array($email));
                $user = $stmt->fetch();

                if ($user && verifyPassword($password, $user['password'])) {
                    unset($user['password']);
                    session_regenerate_id(true);
                    $_SESSION['user'] = $user;
                    logAudit('login_berhasil', 'Login berhasil: ' . $user['name'] . ' (' . $user['email'] . ')');
                    flash('success', 'Selamat datang, ' . $user['name'] . '!');
                    redirect('dashboard.php');
                } else {
                    logAudit('login_gagal', 'Login gagal: ' . $email);
                    $error = 'Email atau password salah.';
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
    <title>Login — <?= APP_NAME ?></title>
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
                        primary: { 50: '#ecfeff', 100: '#cffafe', 200: '#a5f3fc', 300: '#67e8f9', 400: '#22d3ee', 500: '#00d4ff', 600: '#0891b2', 700: '#0e7490', 800: '#155e75', 900: '#164e63' },
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        @keyframes fade-up { from { opacity: 0; transform: translateY(24px) scale(0.97); } to { opacity: 1; transform: translateY(0) scale(1); } }
        @keyframes fade-in { from { opacity: 0; } to { opacity: 1; } }
        @keyframes aurora-float { 0%, 100% { transform: translate(0, 0) scale(1); } 25% { transform: translate(30px, -20px) scale(1.05); } 50% { transform: translate(-20px, 20px) scale(0.95); } 75% { transform: translate(15px, 10px) scale(1.02); } }
        @keyframes aurora-float-2 { 0%, 100% { transform: translate(0, 0) scale(1); } 33% { transform: translate(-25px, 15px) scale(1.03); } 66% { transform: translate(20px, -25px) scale(0.97); } }
        @keyframes pulse-glow { 0%, 100% { opacity: 0.4; } 50% { opacity: 0.7; } }
        .animate-fade-up { animation: fade-up 0.7s cubic-bezier(0.22, 1, 0.36, 1) both; }
        .animate-fade-in { animation: fade-in 0.5s ease both; }
        .delay-1 { animation-delay: 0.1s; }
        .delay-2 { animation-delay: 0.2s; }
        .delay-3 { animation-delay: 0.35s; }
        .delay-4 { animation-delay: 0.5s; }
        .aurora-orb { animation: aurora-float 12s ease-in-out infinite; }
        .aurora-orb-2 { animation: aurora-float-2 15s ease-in-out infinite; }
        .aurora-orb-3 { animation: aurora-float 18s ease-in-out infinite reverse; }
        .pulse-glow { animation: pulse-glow 4s ease-in-out infinite; }
        .aurora-particle { position: absolute; border-radius: 50%; pointer-events: none; opacity: 0; animation: particle-drift linear infinite; }
        @keyframes particle-drift { 0% { opacity: 0; transform: translateY(0) scale(0); } 10% { opacity: 1; transform: scale(1); } 90% { opacity: 1; } 100% { opacity: 0; transform: translateY(-100vh) scale(0.5); } }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 font-sans antialiased transition-colors duration-300"
      style="background: linear-gradient(135deg, #0a0a0f 0%, #12121a 40%, #0f172a 100%);">
    <!-- Aurora background -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-40 -right-40 w-96 h-96 bg-blue-500/15 rounded-full blur-[120px] aurora-orb"></div>
        <div class="absolute -bottom-40 -left-40 w-[500px] h-[500px] bg-violet-500/10 rounded-full blur-[140px] aurora-orb-2"></div>
        <div class="absolute top-1/3 left-1/4 w-72 h-72 bg-emerald-500/8 rounded-full blur-[100px] aurora-orb-3 pulse-glow"></div>
        <div class="absolute bottom-1/3 right-1/4 w-64 h-64 bg-blue-500/8 rounded-full blur-[80px] aurora-orb pulse-glow" style="animation-delay: -4s;"></div>
        <!-- Particles -->
        <div id="login-particles" class="absolute inset-0"></div>
    </div>

    <div class="relative w-full max-w-md animate-fade-up">
        <div class="bg-white/10 dark:bg-white/5 backdrop-blur-2xl rounded-3xl shadow-2xl shadow-black/20 p-8 sm:p-10 border border-white/10 dark:border-white/5">
            <div class="text-center mb-8 animate-fade-in delay-1">
                <div class="w-16 h-16 rounded-2xl bg-gradient-to-br bg-blue-600 flex items-center justify-center mx-auto mb-5 shadow-xl shadow-blue-500/20 ring-4 ring-blue-400/10">
                    <i class="fa-solid fa-boxes-stacked text-white text-2xl"></i>
                </div>
                <h1 class="text-2xl font-extrabold text-white tracking-tight"><?= APP_NAME ?></h1>
                <p class="text-gray-400 text-sm mt-1.5">Silakan login untuk melanjutkan</p>
            </div>

            <?php if ($error): ?>
            <div class="bg-red-500/10 backdrop-blur-sm border border-red-500/20 text-red-400 px-4 py-3 rounded-xl mb-5 flex items-center gap-2.5 animate-fade-in text-sm">
                <i class="fa-solid fa-circle-exclamation shrink-0"></i>
                <span><?= escape($error) ?></span>
            </div>
            <?php endif; ?>

            <?php if (hasFlash('success')): ?>
            <div class="bg-emerald-500/10 backdrop-blur-sm border border-emerald-500/20 text-emerald-400 px-4 py-3 rounded-xl mb-5 flex items-center gap-2.5 animate-fade-in text-sm">
                <i class="fa-solid fa-circle-check shrink-0"></i>
                <span><?= escape(flash('success')) ?></span>
            </div>
            <?php endif; ?>

            <form method="POST" class="space-y-5">
                <?= csrf() ?>
                <div class="animate-fade-in delay-2">
                    <label class="block text-xs font-semibold text-gray-400 mb-1.5 uppercase tracking-wider">Email</label>
                    <div class="relative group">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-500 group-focus-within:text-blue-400 transition-colors duration-200">
                            <i class="fa-solid fa-envelope"></i>
                        </span>
                        <input type="email" name="email" value="<?= escape($email) ?>" required
                               class="w-full pl-10 pr-4 py-3 bg-white/5 border border-white/10 text-white rounded-xl focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500/50 outline-none transition-all duration-200 placeholder:text-gray-600"
                               placeholder="Masukkan email">
                    </div>
                </div>
                <div class="animate-fade-in delay-2">
                    <label class="block text-xs font-semibold text-gray-400 mb-1.5 uppercase tracking-wider">Password</label>
                    <div class="relative group">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-500 group-focus-within:text-blue-400 transition-colors duration-200">
                            <i class="fa-solid fa-lock"></i>
                        </span>
                        <input type="password" name="password" required
                               class="w-full pl-10 pr-4 py-3 bg-white/5 border border-white/10 text-white rounded-xl focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500/50 outline-none transition-all duration-200 placeholder:text-gray-600"
                               placeholder="Masukkan password">
                    </div>
                </div>
                <button type="submit" class="animate-fade-in delay-3 w-full py-3 bg-gradient-to-r bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 focus:ring-2 focus:ring-blue-500/30 focus:ring-offset-2 focus:ring-offset-transparent transition-all duration-200 inline-flex items-center justify-center gap-2 shadow-lg shadow-blue-500/20 magnetic-btn">
                    <i class="fa-solid fa-right-to-bracket"></i> Login
                </button>
            </form>


        </div>
    </div>

    <script>
    (function() {
        var container = document.getElementById('login-particles');
        if (!container) return;
        var colors = ['rgba(0,212,255,0.5)', 'rgba(139,92,246,0.4)', 'rgba(16,185,129,0.3)', 'rgba(0,212,255,0.2)'];
        for (var i = 0; i < 20; i++) {
            var p = document.createElement('div');
            p.className = 'aurora-particle';
            var size = Math.random() * 4 + 1.5;
            p.style.cssText = 'width:' + size + 'px;height:' + size + 'px;left:' + (Math.random()*100) + '%;bottom:-10px;background:' + colors[Math.floor(Math.random()*colors.length)] + ';animation-duration:' + (Math.random()*12+8) + 's;animation-delay:' + (Math.random()*10) + 's;';
            container.appendChild(p);
        }
    })();
    </script>
</body>
</html>
