<?php

$page_title = 'Permintaan';
$require_admin = false;
require_once __DIR__ . '/../includes/auth_check.php';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sparepart_id'])) {
    $sparepartId = (int)$_POST['sparepart_id'];
    $pic = trim($_POST['pic'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $tanggal = $_POST['tanggal'] ?? date('Y-m-d');
    $keterangan = trim($_POST['keterangan'] ?? '');

    $stmt = $db->prepare("SELECT * FROM spareparts WHERE id = ? AND status = 'Tersedia' AND deleted_at IS NULL");
    $stmt->execute([$sparepartId]);
    $sparepart = $stmt->fetch();

    if (!$sparepart) {
        flash('error', 'Sparepart tidak tersedia.');
        redirect('permintaan.php');
    }

    $db->prepare("UPDATE spareparts SET status = 'Terpakai', pic = ?, department = ? WHERE id = ?")->execute([$pic, $department, $sparepartId]);

    $stmt = $db->prepare("INSERT INTO logbooks (sparepart_id, user_id, tipe_transaksi, pic_penerima, department, tanggal, keterangan_log) VALUES (?, ?, 'Permintaan', ?, ?, ?, ?)");
    $stmt->execute([$sparepartId, $user['id'], $pic, $department, $tanggal, $keterangan ?: 'Permintaan: ' . $sparepart['jenis_sparepart']]);

    flash('success', 'Permintaan berhasil diajukan.');
    redirect('permintaan.php');
}

$search = $_GET['search'] ?? '';
$where = "WHERE l.deleted_at IS NULL";
$params = [];
if ($search) {
    $where .= " AND (s.jenis_sparepart LIKE ? OR l.pic_penerima LIKE ?)";
    $s = '%' . $search . '%';
    $params = [$s, $s];
}
if (!empty($_GET['date_from'])) {
    $where .= " AND l.tanggal >= ?";
    $params[] = $_GET['date_from'];
}
if (!empty($_GET['date_to'])) {
    $where .= " AND l.tanggal <= ?";
    $params[] = $_GET['date_to'];
}

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

$countStmt = $db->prepare("SELECT COUNT(*) FROM logbooks l JOIN spareparts s ON s.id = l.sparepart_id $where");
$countStmt->execute($params);
$totalItems = $countStmt->fetchColumn();
$totalPages = ceil($totalItems / $perPage);

$stmt = $db->prepare("SELECT l.*, s.jenis_sparepart, s.merk FROM logbooks l JOIN spareparts s ON s.id = l.sparepart_id $where ORDER BY l.created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$permintaans = $stmt->fetchAll();

$available = $db->query("SELECT id, jenis_sparepart, merk, serial_number FROM spareparts WHERE status = 'Tersedia' AND deleted_at IS NULL ORDER BY jenis_sparepart")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div x-data="permintaan()" class="page-enter">
    <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-4">
        <a href="dashboard.php" class="hover:text-primary-800 dark:hover:text-primary-400 transition">Home</a>
        <i class="fa-solid fa-chevron-right text-xs"></i>
        <span class="text-gray-700 dark:text-gray-200 font-medium">Permintaan</span>
    </nav>

    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-6">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Permintaan</h2>
        <button @click="openAjukan()" class="px-4 py-2 bg-primary-800 text-white rounded-lg hover:bg-primary-900 transition text-sm font-medium inline-flex items-center gap-1.5">
            <i class="fa-solid fa-plus"></i> Ajukan Permintaan
        </button>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-4 mb-6">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1 font-medium">Cari</label>
                <input type="text" name="search" value="<?= escape($search) ?>" placeholder="Jenis, PIC..." class="px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 outline-none transition">
            </div>
            <div>
                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1 font-medium">Dari Tanggal</label>
                <input type="date" name="date_from" value="<?= escape($_GET['date_from'] ?? '') ?>" class="px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 outline-none transition">
            </div>
            <div>
                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1 font-medium">Sampai Tanggal</label>
                <input type="date" name="date_to" value="<?= escape($_GET['date_to'] ?? '') ?>" class="px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 outline-none transition">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="px-4 py-2 bg-primary-800 text-white rounded-lg text-sm hover:bg-primary-900 transition font-medium inline-flex items-center gap-1.5">
                    <i class="fa-solid fa-filter"></i> Filter
                </button>
                <a href="permintaan.php" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg text-sm hover:bg-gray-300 dark:hover:bg-gray-600 transition font-medium inline-flex items-center gap-1.5">
                    <i class="fa-solid fa-rotate"></i> Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Desktop Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden hidden md:block">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/50 text-gray-600 dark:text-gray-300">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold">ID</th>
                        <th class="px-4 py-3 text-left font-semibold">Sparepart</th>
                        <th class="px-4 py-3 text-left font-semibold">PIC</th>
                        <th class="px-4 py-3 text-left font-semibold">Department</th>
                        <th class="px-4 py-3 text-left font-semibold">Tanggal</th>
                        <th class="px-4 py-3 text-left font-semibold">Keterangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    <?php foreach ($permintaans as $i => $p): ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition <?= $i % 2 === 0 ? 'bg-white dark:bg-gray-800' : 'bg-gray-50/50 dark:bg-gray-800/50' ?>">
                        <td class="px-4 py-3 font-medium text-gray-800 dark:text-gray-200">#<?= $p['id'] ?></td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300"><?= escape($p['jenis_sparepart']) ?> <span class="text-gray-400">(<?= escape($p['merk'] ?? '-') ?>)</span></td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400"><?= escape($p['pic_penerima'] ?? '-') ?></td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400"><?= escape($p['department'] ?? '-') ?></td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400"><?= formatTanggal($p['tanggal']) ?></td>
                        <td class="px-4 py-3 max-w-xs truncate text-gray-600 dark:text-gray-400"><?= escape($p['keterangan_log']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($permintaans)): ?>
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center">
                            <div class="flex flex-col items-center gap-2 text-gray-400 dark:text-gray-500">
                                <i class="fa-solid fa-file-import text-4xl"></i>
                                <p class="text-sm">Belum ada permintaan.</p>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if ($totalPages > 1): ?>
        <div class="flex justify-between items-center px-4 py-3 border-t border-gray-100 dark:border-gray-700">
            <p class="text-sm text-gray-500 dark:text-gray-400">Halaman <?= $page ?> dari <?= $totalPages ?></p>
            <div class="flex gap-1">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?>&<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"
                   class="px-3 py-1 rounded-lg text-sm font-medium transition <?= $i === $page ? 'bg-primary-800 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' ?>">
                    <?= $i ?>
                </a>
                <?php endfor; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Mobile Cards -->
    <div class="md:hidden space-y-3">
        <?php if (empty($permintaans)): ?>
        <div class="flex flex-col items-center gap-2 py-12 text-gray-400 dark:text-gray-500">
            <i class="fa-solid fa-file-import text-4xl"></i>
            <p class="text-sm">Belum ada permintaan.</p>
        </div>
        <?php else: ?>
            <?php foreach ($permintaans as $p): ?>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-4 transition hover:shadow-md">
                <div class="flex justify-between items-start mb-2">
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold text-gray-800 dark:text-gray-200 text-sm">#<?= $p['id'] ?> — <?= escape($p['jenis_sparepart']) ?></p>
                        <p class="text-xs text-gray-500 dark:text-gray-400"><?= escape($p['merk'] ?? '-') ?></p>
                    </div>
                    <span class="text-xs bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400 px-2 py-0.5 rounded-full font-medium">Permintaan</span>
                </div>
                <div class="grid grid-cols-2 gap-2 text-xs text-gray-600 dark:text-gray-400 mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                    <div><span class="text-gray-500 dark:text-gray-500">PIC:</span> <?= escape($p['pic_penerima'] ?? '-') ?></div>
                    <div><span class="text-gray-500 dark:text-gray-500">Dept:</span> <?= escape($p['department'] ?? '-') ?></div>
                    <div><span class="text-gray-500 dark:text-gray-500">Tanggal:</span> <?= formatTanggal($p['tanggal']) ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
        <?php if ($totalPages > 1): ?>
        <div class="flex justify-center gap-1 pt-2">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?page=<?= $i ?>&<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"
               class="px-3 py-1.5 rounded-lg text-sm font-medium transition <?= $i === $page ? 'bg-primary-800 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' ?>">
                <?= $i ?>
            </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Ajukan Permintaan Modal -->
<div x-data="{ open: false }"
     x-show="open" x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center p-4"
     @open-ajukan-permintaan.window="open = true"
     x-transition:enter="modal-enter-active" x-transition:enter-start="modal-enter"
     x-transition:leave="modal-leave-active" x-transition:leave-end="modal-leave">
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="open = false" x-transition:enter="backdrop-enter-active" x-transition:enter-start="backdrop-enter" x-transition:leave="backdrop-leave-active" x-transition:leave-end="backdrop-leave"></div>
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-lg relative z-10 max-h-[90vh] overflow-y-auto">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center sticky top-0 bg-white dark:bg-gray-800 rounded-t-2xl">
            <h3 class="text-lg font-bold text-gray-800 dark:text-white">Ajukan Permintaan</h3>
            <button @click="open = false" class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition">&times;</button>
        </div>
        <form method="POST" class="p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Pilih Sparepart <span class="text-red-500">*</span></label>
                <select name="sparepart_id" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 outline-none transition">
                    <option value="">-- Pilih --</option>
                    <?php foreach ($available as $a): ?>
                    <option value="<?= $a['id'] ?>">#<?= $a['id'] ?> — <?= escape($a['jenis_sparepart']) ?> (<?= escape($a['merk'] ?? '-') ?>)<?= $a['serial_number'] ? ' — ' . escape($a['serial_number']) : '' ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (empty($available)): ?>
                <p class="text-xs text-red-500 mt-1">Tidak ada sparepart tersedia.</p>
                <?php endif; ?>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">PIC <span class="text-red-500">*</span></label>
                <input type="text" name="pic" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 outline-none transition">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Department <span class="text-red-500">*</span></label>
                <input type="text" name="department" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 outline-none transition">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tanggal <span class="text-red-500">*</span></label>
                <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 outline-none transition">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Keterangan</label>
                <textarea name="keterangan" rows="2" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 outline-none transition"></textarea>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" @click="open = false" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition text-sm font-medium">Batal</button>
                <button type="submit" class="px-4 py-2 bg-primary-800 text-white rounded-lg hover:bg-primary-900 transition text-sm font-medium inline-flex items-center gap-1.5">
                    <i class="fa-solid fa-paper-plane"></i> Ajukan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('permintaan', () => ({
            openAjukan() {
                window.dispatchEvent(new CustomEvent('open-ajukan-permintaan'));
            }
        }));
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>