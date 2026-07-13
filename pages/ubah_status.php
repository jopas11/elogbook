<?php

$page_title = 'Ubah Status';
$require_admin = false;
require_once __DIR__ . '/../includes/auth_check.php';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sparepartId = (int)($_POST['sparepart_id'] ?? 0);
    $statusBaru = $_POST['status_baru'] ?? '';
    $tanggal = $_POST['tanggal'] ?? date('Y-m-d');
    $pic = trim($_POST['pic'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $keterangan = trim($_POST['keterangan'] ?? '');

    $validStatus = ['Tersedia', 'Terpakai', 'Rusak', 'Dalam Perbaikan'];
    if (!in_array($statusBaru, $validStatus)) {
        flash('error', 'Status tidak valid.');
        redirect('ubah_status.php');
    }

    $stmt = $db->prepare("SELECT * FROM spareparts WHERE id = ? AND deleted_at IS NULL");
    $stmt->execute([$sparepartId]);
    $sparepart = $stmt->fetch();

    if (!$sparepart) {
        flash('error', 'Sparepart tidak ditemukan.');
        redirect('ubah_status.php');
    }

    $statusLama = $sparepart['status'];

    $db->prepare("UPDATE spareparts SET status = ?, pic = ?, department = ?, keterangan = ? WHERE id = ?")->execute([$statusBaru, $pic, $department, $keterangan, $sparepartId]);

    $tipeTransaksi = $statusBaru === 'Dalam Perbaikan' ? 'Dalam Perbaikan' : 'Ubah Status';
    $stmt = $db->prepare("INSERT INTO logbooks (sparepart_id, user_id, tipe_transaksi, pic_penerima, department, tanggal, keterangan_log) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$sparepartId, $user['id'], $tipeTransaksi, $pic, $department, $tanggal, "Status berubah dari $statusLama ke $statusBaru. $keterangan"]);

    flash('success', 'Status sparepart berhasil diubah.');
    redirect('ubah_status.php');
}

$search = $_GET['search'] ?? '';
$where = "WHERE deleted_at IS NULL";
$params = [];
if ($search) {
    $where .= " AND (jenis_sparepart LIKE ? OR merk LIKE ? OR CAST(id AS CHAR) LIKE ?)";
    $s = '%' . $search . '%';
    $params = [$s, $s, $s];
}
$stmt = $db->prepare("SELECT * FROM spareparts $where ORDER BY created_at DESC");
$stmt->execute($params);
$spareparts = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div x-data="ubahStatus()" class="page-enter">
    <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-4">
        <a href="dashboard.php" class="hover:text-primary-800 dark:hover:text-primary-400 transition">Home</a>
        <i class="fa-solid fa-chevron-right text-xs"></i>
        <span class="text-gray-700 dark:text-gray-200 font-medium">Ubah Status</span>
    </nav>

    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Ubah Status Sparepart</h2>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-4 mb-6">
        <form method="GET" class="flex gap-3 items-end">
            <div class="flex-1">
                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1 font-medium">Cari Sparepart</label>
                <input type="text" name="search" value="<?= escape($search) ?>" placeholder="ID, jenis, merk..." class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 outline-none transition">
            </div>
            <button type="submit" class="px-4 py-2 bg-primary-800 text-white rounded-lg text-sm hover:bg-primary-900 transition font-medium inline-flex items-center gap-1.5">
                <i class="fa-solid fa-search"></i> Cari
            </button>
            <?php if ($search): ?>
            <a href="ubah_status.php" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg text-sm hover:bg-gray-300 dark:hover:bg-gray-600 transition font-medium inline-flex items-center gap-1.5">
                <i class="fa-solid fa-rotate"></i> Reset
            </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Desktop Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden hidden md:block">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/50 text-gray-600 dark:text-gray-300">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold">ID</th>
                        <th class="px-4 py-3 text-left font-semibold">Jenis</th>
                        <th class="px-4 py-3 text-left font-semibold">Merk</th>
                        <th class="px-4 py-3 text-left font-semibold hidden lg:table-cell">SN</th>
                        <th class="px-4 py-3 text-left font-semibold">Status</th>
                        <th class="px-4 py-3 text-center font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    <?php foreach ($spareparts as $i => $sp): ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition <?= $i % 2 === 0 ? 'bg-white dark:bg-gray-800' : 'bg-gray-50/50 dark:bg-gray-800/50' ?>">
                        <td class="px-4 py-3 font-medium text-gray-800 dark:text-gray-200">#<?= $sp['id'] ?></td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300"><?= escape($sp['jenis_sparepart']) ?></td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400"><?= escape($sp['merk'] ?? '-') ?></td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400 hidden lg:table-cell"><?= escape($sp['serial_number'] ?? '-') ?></td>
                        <td class="px-4 py-3"><?= getStatusBadge($sp['status']) ?></td>
                        <td class="px-4 py-3 text-center">
                            <button @click="openUbah(<?= $sp['id'] ?>, '<?= escape($sp['jenis_sparepart']) ?>', '<?= escape($sp['status']) ?>')" class="px-3 py-1.5 bg-blue-600 text-white rounded-lg text-xs hover:bg-blue-700 transition font-medium inline-flex items-center gap-1">
                                <i class="fa-solid fa-arrows-rotate"></i> Ubah Status
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($spareparts)): ?>
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center">
                            <div class="flex flex-col items-center gap-2 text-gray-400 dark:text-gray-500">
                                <i class="fa-solid fa-arrows-rotate text-4xl"></i>
                                <p class="text-sm">Tidak ada data.</p>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Mobile Cards -->
    <div class="md:hidden space-y-3">
        <?php if (empty($spareparts)): ?>
        <div class="flex flex-col items-center gap-2 py-12 text-gray-400 dark:text-gray-500">
            <i class="fa-solid fa-arrows-rotate text-4xl"></i>
            <p class="text-sm">Tidak ada data.</p>
        </div>
        <?php else: ?>
            <?php foreach ($spareparts as $sp): ?>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-4 transition hover:shadow-md">
                <div class="flex justify-between items-start mb-2">
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold text-gray-800 dark:text-gray-200 text-sm">#<?= $sp['id'] ?> — <?= escape($sp['jenis_sparepart']) ?></p>
                        <p class="text-xs text-gray-500 dark:text-gray-400"><?= escape($sp['merk'] ?? '-') ?> <?= $sp['serial_number'] ? '| ' . escape($sp['serial_number']) : '' ?></p>
                    </div>
                    <?= getStatusBadge($sp['status']) ?>
                </div>
                <button @click="openUbah(<?= $sp['id'] ?>, '<?= escape($sp['jenis_sparepart']) ?>', '<?= escape($sp['status']) ?>')" class="w-full mt-2 px-3 py-2 bg-blue-600 text-white rounded-lg text-xs hover:bg-blue-700 transition font-medium inline-flex items-center justify-center gap-1.5">
                    <i class="fa-solid fa-arrows-rotate"></i> Ubah Status
                </button>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Ubah Status Modal -->
<div x-data="{ open: false, sp: {} }"
     x-show="open" x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center p-4"
     @open-ubah-status.window="open = true; sp = $event.detail"
     x-transition:enter="modal-enter-active" x-transition:enter-start="modal-enter"
     x-transition:leave="modal-leave-active" x-transition:leave-end="modal-leave">
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="open = false" x-transition:enter="backdrop-enter-active" x-transition:enter-start="backdrop-enter" x-transition:leave="backdrop-leave-active" x-transition:leave-end="backdrop-leave"></div>
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-lg relative z-10 max-h-[90vh] overflow-y-auto">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center sticky top-0 bg-white dark:bg-gray-800 rounded-t-2xl">
            <h3 class="text-lg font-bold text-gray-800 dark:text-white">Ubah Status — <span x-text="sp.jenis"></span></h3>
            <button @click="open = false" class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition">&times;</button>
        </div>
        <form method="POST" class="p-6 space-y-4">
            <input type="hidden" name="sparepart_id" :value="sp.id">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status Saat Ini</label>
                <p class="text-sm" x-html="statusBadge(sp.status_lama)"></p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status Baru <span class="text-red-500">*</span></label>
                <select name="status_baru" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 outline-none transition">
                    <option value="Tersedia">Tersedia</option>
                    <option value="Terpakai">Terpakai</option>
                    <option value="Rusak">Rusak</option>
                    <option value="Dalam Perbaikan">Dalam Perbaikan</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tanggal <span class="text-red-500">*</span></label>
                <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 outline-none transition">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">PIC</label>
                <input type="text" name="pic" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 outline-none transition">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Department</label>
                <input type="text" name="department" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 outline-none transition">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Keterangan</label>
                <textarea name="keterangan" rows="2" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 outline-none transition"></textarea>
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
        Alpine.data('ubahStatus', () => ({
            openUbah(id, jenis, status) {
                window.dispatchEvent(new CustomEvent('open-ubah-status', {
                    detail: { id, jenis, status_lama: status }
                }));
            },
            statusBadge(status) {
                const colors = {
                    'Tersedia': 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                    'Terpakai': 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                    'Rusak': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                    'Dalam Perbaikan': 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400'
                };
                const color = colors[status] || 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
                return `<span class="px-2 py-1 text-xs font-medium rounded-full ${color}">${status}</span>`;
            }
        }));
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>