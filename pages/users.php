<?php

$page_title = 'Kelola User';
$require_admin = true;
require_once __DIR__ . '/../includes/auth_check.php';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'user';

        if ($name && $email && $password) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            try {
                $stmt = $db->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $email, $hashed, $role]);
                flash('success', 'User berhasil ditambahkan.');
            } catch (PDOException $e) {
                flash('error', 'Email sudah terdaftar.');
            }
        }
    } elseif ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'user';

        // Cek target user
        $stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $target = $stmt->fetch();

        if (!$target) {
            flash('error', 'User tidak ditemukan.');
            redirect('users.php');
        }

        // Admin tidak bisa ubah password admin lain
        if ($password && $target['role'] === 'admin' && $id != $user['id']) {
            flash('error', 'Tidak bisa mengubah password admin lain.');
            redirect('users.php');
        }

        if ($name && $email) {
            try {
                if ($password) {
                    $hashed = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $db->prepare("UPDATE users SET name = ?, email = ?, role = ?, password = ? WHERE id = ?");
                    $stmt->execute([$name, $email, $role, $hashed, $id]);
                } else {
                    $stmt = $db->prepare("UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?");
                    $stmt->execute([$name, $email, $role, $id]);
                }
                flash('success', 'User berhasil diupdate.');
            } catch (PDOException $e) {
                flash('error', 'Email sudah terdaftar.');
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id == $user['id']) {
            flash('error', 'Tidak bisa menghapus akun sendiri.');
        } else {
            $stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $target = $stmt->fetch();
            if ($target && $target['role'] === 'admin') {
                flash('error', 'Tidak bisa menghapus admin lain.');
            } else {
                $db->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
                flash('success', 'User berhasil dihapus.');
            }
        }
    }
    redirect('users.php');
}

$stmt = $db->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div x-data="users()" class="page-enter">
    <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-4">
        <a href="dashboard.php" class="hover:text-primary-800 dark:hover:text-primary-400 transition">Home</a>
        <i class="fa-solid fa-chevron-right text-xs"></i>
        <span class="text-gray-700 dark:text-gray-200 font-medium">Kelola User</span>
    </nav>

    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-6">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Kelola User</h2>
        <button @click="openModal('create')" class="px-4 py-2 bg-primary-800 text-white rounded-lg hover:bg-primary-900 transition text-sm font-medium inline-flex items-center gap-1.5">
            <i class="fa-solid fa-plus"></i> Tambah User
        </button>
    </div>

    <!-- Desktop Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden hidden md:block">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/50 text-gray-600 dark:text-gray-300">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold">ID</th>
                        <th class="px-4 py-3 text-left font-semibold">Nama</th>
                        <th class="px-4 py-3 text-left font-semibold">Email</th>
                        <th class="px-4 py-3 text-left font-semibold">Role</th>
                        <th class="px-4 py-3 text-left font-semibold">Bergabung</th>
                        <th class="px-4 py-3 text-center font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    <?php foreach ($users as $i => $u): ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition <?= $i % 2 === 0 ? 'bg-white dark:bg-gray-800' : 'bg-gray-50/50 dark:bg-gray-800/50' ?>">
                        <td class="px-4 py-3 font-medium text-gray-800 dark:text-gray-200">#<?= $u['id'] ?></td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300"><?= escape($u['name']) ?></td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400"><?= escape($u['email']) ?></td>
                        <td class="px-4 py-3"><?= getRoleBadge($u['role']) ?></td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400"><?= formatTanggal(explode(' ', $u['created_at'])[0]) ?></td>
                        <td class="px-4 py-3 text-center">
                            <button @click="openModal('edit', <?= $u['id'] ?>, '<?= escape($u['name']) ?>', '<?= escape($u['email']) ?>', '<?= $u['role'] ?>')" title="Edit user" class="p-1.5 text-xs bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400 rounded-lg hover:bg-yellow-200 dark:hover:bg-yellow-900/50 transition">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                            <?php if ($u['id'] !== $user['id'] && $u['role'] !== 'admin'): ?>
                            <form method="POST" class="inline" onsubmit="return confirm('Hapus user <?= escape($u['name']) ?>?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                <button type="submit" title="Hapus user" class="p-1.5 text-xs bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-lg hover:bg-red-200 dark:hover:bg-red-900/50 transition">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Mobile Cards -->
    <div class="md:hidden space-y-3">
        <?php foreach ($users as $u): ?>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-4 transition hover:shadow-md">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center text-primary-800 dark:text-primary-400 font-bold shrink-0">
                    <?= strtoupper(substr($u['name'], 0, 1)) ?>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="font-semibold text-gray-800 dark:text-gray-200 text-sm"><?= escape($u['name']) ?></p>
                    <p class="text-xs text-gray-500 dark:text-gray-400"><?= escape($u['email']) ?></p>
                </div>
                <?= getRoleBadge($u['role']) ?>
            </div>
            <div class="flex items-center justify-between mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                <span class="text-xs text-gray-500 dark:text-gray-400">Bergabung: <?= formatTanggal(explode(' ', $u['created_at'])[0]) ?></span>
                <div class="flex gap-1">
                    <button @click="openModal('edit', <?= $u['id'] ?>, '<?= escape($u['name']) ?>', '<?= escape($u['email']) ?>', '<?= $u['role'] ?>')" title="Edit user" class="p-1.5 text-xs bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400 rounded-lg hover:bg-yellow-200 dark:hover:bg-yellow-900/50 transition">
                        <i class="fa-solid fa-pen"></i>
                    </button>
                    <?php if ($u['id'] !== $user['id'] && $u['role'] !== 'admin'): ?>
                    <form method="POST" class="inline" onsubmit="return confirm('Hapus user <?= escape($u['name']) ?>?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $u['id'] ?>">
                        <button type="submit" title="Hapus user" class="p-1.5 text-xs bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-lg hover:bg-red-200 dark:hover:bg-red-900/50 transition">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Modal -->
<div x-data="{ open: false, form: { action: '', title: '', id: 0, name: '', email: '', role: 'user' } }"
     x-show="open" x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center p-4"
     @open-user-modal.window="open = true; form = $event.detail"
     x-transition:enter="modal-enter-active" x-transition:enter-start="modal-enter"
     x-transition:leave="modal-leave-active" x-transition:leave-end="modal-leave">
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="open = false" x-transition:enter="backdrop-enter-active" x-transition:enter-start="backdrop-enter" x-transition:leave="backdrop-leave-active" x-transition:leave-end="backdrop-leave"></div>
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-md relative z-10">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-800 dark:text-white" x-text="form.title"></h3>
            <button @click="open = false" class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition">&times;</button>
        </div>
        <form method="POST" class="p-6 space-y-4">
            <input type="hidden" name="action" :value="form.action">
            <input type="hidden" name="id" :value="form.id">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nama <span class="text-red-500">*</span></label>
                <input type="text" name="name" x-model="form.name" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 outline-none transition">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email <span class="text-red-500">*</span></label>
                <input type="email" name="email" x-model="form.email" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 outline-none transition">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Password
                    <span x-show="form.action === 'create'" class="text-red-500">*</span>
                    <span x-show="form.action === 'update'" class="text-gray-400 text-xs">(kosongkan jika tidak diubah)</span>
                </label>
                <input type="password" name="password" :required="form.action === 'create'" minlength="6" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 outline-none transition">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Role</label>
                <select name="role" x-model="form.role" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 outline-none transition">
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" @click="open = false" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition text-sm font-medium">Batal</button>
                <button type="submit" class="px-4 py-2 bg-primary-800 text-white rounded-lg hover:bg-primary-900 transition text-sm font-medium inline-flex items-center gap-1.5">
                    <i class="fa-solid fa-save"></i> Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('users', () => ({
            openModal(mode, id = 0, name = '', email = '', role = 'user') {
                let action = mode === 'create' ? 'create' : 'update';
                let title = mode === 'create' ? 'Tambah User Baru' : 'Edit User';
                window.dispatchEvent(new CustomEvent('open-user-modal', {
                    detail: { action, title, id, name, email, role }
                }));
            }
        }));
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>