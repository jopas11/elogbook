<?php

$page_title = 'Profile';
$require_admin = false;
require_once __DIR__ . '/../includes/auth_check.php';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        if ($name && $email) {
            try {
                $stmt = $db->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
                $stmt->execute([$name, $email, $user['id']]);
                $_SESSION['user']['name'] = $name;
                $_SESSION['user']['email'] = $email;
                flash('success', 'Profile berhasil diupdate.');
            } catch (PDOException $e) {
                flash('error', 'Email sudah digunakan.');
            }
        }
    } elseif ($action === 'update_password') {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['new_password_confirmation'] ?? '';

        $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user['id']]);
        $data = $stmt->fetch();

        if (!password_verify($current, $data['password'])) {
            flash('error', 'Password saat ini salah.');
        } elseif (strlen($new) < 6) {
            flash('error', 'Password baru minimal 6 karakter.');
        } elseif ($new !== $confirm) {
            flash('error', 'Konfirmasi password tidak cocok.');
        } else {
            $hashed = password_hash($new, PASSWORD_DEFAULT);
            $db->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hashed, $user['id']]);
            flash('success', 'Password berhasil diubah.');
        }
    } elseif ($action === 'delete_account') {
        $password = $_POST['password'] ?? '';
        $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user['id']]);
        $data = $stmt->fetch();

        if (password_verify($password, $data['password'])) {
            $db->prepare("DELETE FROM users WHERE id = ?")->execute([$user['id']]);
            session_destroy();
            redirect('login.php');
        } else {
            flash('error', 'Password salah.');
        }
    }
    redirect('profile.php');
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div x-data="{ showDeleteModal: false }" class="page-enter">
    <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-4">
        <a href="dashboard.php" class="hover:text-primary-800 dark:hover:text-primary-400 transition">Home</a>
        <i class="fa-solid fa-chevron-right text-xs"></i>
        <span class="text-gray-700 dark:text-gray-200 font-medium">Profile</span>
    </nav>

    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Profile Saya</h2>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Informasi Profile</h3>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="update_profile">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nama Lengkap</label>
                    <input type="text" name="name" value="<?= escape($user['name']) ?>" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 outline-none transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
                    <input type="email" name="email" value="<?= escape($user['email']) ?>" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 outline-none transition">
                </div>
                <button type="submit" class="px-4 py-2 bg-primary-800 text-white rounded-lg text-sm hover:bg-primary-900 transition font-medium inline-flex items-center gap-1.5">
                    <i class="fa-solid fa-save"></i> Simpan Profile
                </button>
            </form>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Ubah Password</h3>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="update_password">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Password Saat Ini</label>
                    <input type="password" name="current_password" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 outline-none transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Password Baru</label>
                    <input type="password" name="new_password" required minlength="6" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 outline-none transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Konfirmasi Password Baru</label>
                    <input type="password" name="new_password_confirmation" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 outline-none transition">
                </div>
                <button type="submit" class="px-4 py-2 bg-primary-800 text-white rounded-lg text-sm hover:bg-primary-900 transition font-medium inline-flex items-center gap-1.5">
                    <i class="fa-solid fa-key"></i> Ubah Password
                </button>
            </form>
        </div>
    </div>

    <div class="mt-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-red-200 dark:border-red-900">
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
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-md relative z-10 p-6">
            <h3 class="text-lg font-bold text-red-600 dark:text-red-400 mb-4">Konfirmasi Hapus Akun</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Masukkan password untuk menghapus akun.</p>
            <form method="POST">
                <input type="hidden" name="action" value="delete_account">
                <input type="password" name="password" required placeholder="Password Anda" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-red-500 outline-none transition mb-4">
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>