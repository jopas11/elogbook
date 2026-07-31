<?php

$page_title = 'Profile';
$require_admin = false;
require_once __DIR__ . '/../../helpers/auth.php';

$db = getDB();

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div x-data="{ showDeleteModal: false }" class="page-enter">
    <nav class="flex items-center gap-2 text-base text-gray-500 dark:text-gray-400 mb-4">
        <a href="<?= pageUrl('dashboard.php') ?>" class="hover:text-blue-600 dark:hover:text-blue-400 transition">Home</a>
        <i class="fa-solid fa-chevron-right text-xs"></i>
        <span class="text-gray-700 dark:text-gray-200 font-medium">Profile</span>
    </nav>

    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-white gradient-text">Profile Saya</h2>
    </div>

    <div class="glass-panel p-6 card-hover">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
            <i class="fa-solid fa-key text-blue-600"></i>
            Ubah Password
        </h3>
            <form method="POST" class="space-y-4">
                <?= csrf() ?>
                <input type="hidden" name="action" value="update_password">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Password Saat Ini</label>
                    <input type="password" name="current_password" required class="w-full px-3 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 dark:text-gray-200 rounded-xl text-base focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500/50 outline-none transition-all duration-200">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Password Baru</label>
                    <input type="password" name="new_password" required minlength="6" class="w-full px-3 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 dark:text-gray-200 rounded-xl text-base focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500/50 outline-none transition-all duration-200">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Konfirmasi Password Baru</label>
                    <input type="password" name="new_password_confirmation" required class="w-full px-3 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 dark:text-gray-200 rounded-xl text-base focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500/50 outline-none transition-all duration-200">
                </div>
                <button type="submit" class="magnetic-btn px-4 py-2 bg-gradient-to-r bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 transition font-medium inline-flex items-center gap-1.5">
                    <i class="fa-solid fa-key"></i> Ubah Password
                </button>
            </form>
        </div>

    <div class="mt-6 glass-panel p-6 border border-red-200 dark:border-red-900 card-hover">
        <h3 class="text-lg font-semibold text-red-600 dark:text-red-400 mb-2">Hapus Akun</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Setelah akun dihapus, semua data akan hilang permanen.</p>
        <button @click="showDeleteModal = true" class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700 transition font-medium inline-flex items-center gap-1.5">
            <i class="fa-solid fa-trash"></i> Hapus Akun
        </button>
    </div>

    <div x-show="showDeleteModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4"
         x-transition:enter="modal-enter-active" x-transition:enter-start="modal-enter"
         x-transition:leave="modal-leave-active" x-transition:leave-end="modal-leave">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showDeleteModal = false"></div>
        <div class="glass-panel rounded-2xl shadow-xl w-full max-w-md relative z-10 p-6">
            <h3 class="text-lg font-bold text-red-600 dark:text-red-400 mb-4">Konfirmasi Hapus Akun</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Masukkan password untuk menghapus akun.</p>
            <form method="POST">
                <?= csrf() ?>
                <input type="hidden" name="action" value="delete_account">
                <input type="password" name="password" required placeholder="Password Anda" class="px-3 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 dark:text-gray-200 rounded-xl text-base focus:ring-2 focus:ring-red-500 outline-none transition-all duration-200 mb-4">
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showDeleteModal = false" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg text-sm hover:bg-gray-300 dark:hover:bg-gray-600 transition font-medium">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700 transition font-medium inline-flex items-center gap-1.5">
                        <i class="fa-solid fa-trash"></i> Hapus Akun
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>