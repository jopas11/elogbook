<?php

$page_title = 'Ubah Status';
$require_admin = true;
require_once __DIR__ . '/../../helpers/auth.php';

$db = getDB();

$search = _get($_GET, 'search', '');
$filterStatus = _get($_GET, 'status', '');
$where = "WHERE deleted_at IS NULL";
$params = array();
if ($search) {
    $searchTerm = '%' . $search . '%';
    $where .= " AND (id LIKE ? OR jenis_sparepart LIKE ? OR merk LIKE ? OR type_sparepart LIKE ? OR serial_number LIKE ?)";
    $params = array_merge($params, array($searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm));
}
if ($filterStatus) {
    $where .= " AND status = ?";
    $params[] = $filterStatus;
}
$baseQuery = "SELECT * FROM spareparts $where ORDER BY created_at DESC";
list($spareparts, $page, $totalPages) = paginate($db, $baseQuery, $params);

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div x-data="ubahStatus()" class="page-enter">
    <nav class="flex items-center gap-2 text-base text-gray-500 dark:text-gray-400 mb-4">
        <a href="<?= pageUrl('dashboard.php') ?>" class="hover:text-cyan-500 dark:hover:text-cyan-400 transition">Home</a>
        <i class="fa-solid fa-chevron-right text-xs"></i>
        <span class="text-gray-700 dark:text-gray-200 font-medium">Ubah Status</span>
    </nav>

    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-white gradient-text">Ubah Status Sparepart</h2>
    </div>

    <div class="glass-panel p-4 mb-6">
        <form method="GET" action="index.php" class="flex flex-wrap gap-3 items-end">
            <input type="hidden" name="url" value="ubah_status">
            <div>
                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1 font-medium">Cari Sparepart</label>
                <input type="text" name="search" value="<?= escape($search) ?>" placeholder="ID, jenis, merk..." class="px-3 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 dark:text-gray-200 rounded-xl text-base focus:ring-2 focus:ring-cyan-500/30 focus:border-cyan-500/50 outline-none transition-all duration-200">
            </div>
            <div>
                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1 font-medium">Filter Status</label>
                <select name="status" class="px-3 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 dark:text-gray-200 rounded-xl text-base focus:ring-2 focus:ring-cyan-500/30 focus:border-cyan-500/50 outline-none transition-all duration-200">
                    <option value="">Semua Status</option>
                    <option value="Tersedia" <?= $filterStatus === 'Tersedia' ? 'selected' : '' ?>>Tersedia</option>
                    <option value="Terpakai" <?= $filterStatus === 'Terpakai' ? 'selected' : '' ?>>Terpakai</option>
                    <option value="Rusak" <?= $filterStatus === 'Rusak' ? 'selected' : '' ?>>Rusak</option>
                    <option value="Dalam Perbaikan" <?= $filterStatus === 'Dalam Perbaikan' ? 'selected' : '' ?>>Dalam Perbaikan</option>
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-gradient-to-r from-cyan-500 to-violet-500 text-white rounded-lg text-sm hover:from-cyan-400 hover:to-violet-400 transition font-medium inline-flex items-center gap-1.5 magnetic-btn">
                <i class="fa-solid fa-filter"></i> Filter
            </button>
            <?php if ($search || $filterStatus): ?>
            <a href="<?= pageUrl('ubah_status.php') ?>" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg text-sm hover:bg-gray-300 dark:hover:bg-gray-600 transition font-medium inline-flex items-center gap-1.5">
                <i class="fa-solid fa-rotate"></i> Reset
            </a>
            <?php endif; ?>
        </form>
    </div>

    <div class="glass-panel overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm responsive-table">
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
                        <td data-label="ID" class="font-medium text-gray-800 dark:text-gray-200">#<?= $sp['id'] ?></td>
                        <td data-label="Jenis" class="text-gray-700 dark:text-gray-300"><?= escape($sp['jenis_sparepart']) ?></td>
                        <td data-label="Merk" class="text-gray-600 dark:text-gray-400"><?= escape(isset($sp['merk']) ? $sp['merk'] : '-') ?></td>
                        <td data-label="SN" class="text-gray-600 dark:text-gray-400 hidden lg:table-cell"><?= escape(isset($sp['serial_number']) ? $sp['serial_number'] : '-') ?></td>
                        <td data-label="Status"><?= getStatusBadge($sp['status']) ?></td>
                        <td data-label="Aksi" class="text-center">
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
        <?= renderPagination($page, $totalPages) ?>
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
    <div class="glass-panel-strong rounded-2xl shadow-xl w-full max-w-lg relative z-10 max-h-[90vh] overflow-y-auto">
        <div class="px-6 py-4 border-b border-black/5 dark:border-white/5 flex justify-between items-center sticky top-0 glass-panel-strong rounded-t-2xl">
            <h3 class="text-lg font-bold text-gray-800 dark:text-white">Ubah Status — <span x-text="sp.jenis"></span></h3>
            <button @click="open = false" class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition">&times;</button>
        </div>
        <form method="POST" class="p-6 space-y-4">
            <?= csrf() ?>
            <input type="hidden" name="sparepart_id" :value="sp.id">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status Saat Ini</label>
                <p class="text-sm" x-html="statusBadge(sp.status_lama)"></p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status Baru <span class="text-red-500">*</span></label>
                <select name="status_baru" required class="w-full px-3 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 dark:text-gray-200 rounded-xl text-base focus:ring-2 focus:ring-cyan-500/30 focus:border-cyan-500/50 outline-none transition-all duration-200">
                    <option value="Tersedia">Tersedia</option>
                    <option value="Terpakai">Terpakai</option>
                    <option value="Rusak">Rusak</option>
                    <option value="Dalam Perbaikan">Dalam Perbaikan</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tanggal <span class="text-red-500">*</span></label>
                <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required class="w-full px-3 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 dark:text-gray-200 rounded-xl text-base focus:ring-2 focus:ring-cyan-500/30 focus:border-cyan-500/50 outline-none transition-all duration-200">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">PIC</label>
                <input type="text" name="pic" class="w-full px-3 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 dark:text-gray-200 rounded-xl text-base focus:ring-2 focus:ring-cyan-500/30 focus:border-cyan-500/50 outline-none transition-all duration-200">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Department</label>
                <input type="text" name="department" class="w-full px-3 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 dark:text-gray-200 rounded-xl text-base focus:ring-2 focus:ring-cyan-500/30 focus:border-cyan-500/50 outline-none transition-all duration-200">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Keterangan</label>
                <textarea name="keterangan" rows="2" class="w-full px-3 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 dark:text-gray-200 rounded-xl text-base focus:ring-2 focus:ring-cyan-500/30 focus:border-cyan-500/50 outline-none transition-all duration-200"></textarea>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" @click="open = false" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition text-sm font-medium">Batal</button>
                <button type="submit" class="px-4 py-2 bg-gradient-to-r from-cyan-500 to-violet-500 text-white rounded-lg hover:from-cyan-400 hover:to-violet-400 transition text-sm font-medium inline-flex items-center gap-1.5 magnetic-btn">
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
                    'Tersedia': 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400',
                    'Terpakai': 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400',
                    'Rusak': 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400',
                    'Dalam Perbaikan': 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400'
                };
                const color = colors[status] || 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
                return `<span class="px-2 py-1 text-xs font-medium rounded-full ${color}">${status}</span>`;
            }
        }));
    });
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
