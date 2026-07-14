<?php

$page_title = 'Dashboard';
$require_admin = false;
require_once __DIR__ . '/../includes/auth_check.php';

$db = getDB();

// Stats
$stats = $db->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'Tersedia' THEN 1 ELSE 0 END) as tersedia,
        SUM(CASE WHEN status = 'Terpakai' THEN 1 ELSE 0 END) as terpakai,
        SUM(CASE WHEN status = 'Rusak' THEN 1 ELSE 0 END) as rusak,
        SUM(CASE WHEN status = 'Dalam Perbaikan' THEN 1 ELSE 0 END) as dalam_perbaikan
    FROM spareparts WHERE deleted_at IS NULL
")->fetch();

// Recent activity
if (isAdmin()) {
    $recentLogs = $db->query("
        SELECT l.*, s.jenis_sparepart, s.merk, u.name as user_name
        FROM logbooks l
        JOIN spareparts s ON s.id = l.sparepart_id
        JOIN users u ON u.id = l.user_id
        WHERE l.deleted_at IS NULL
        ORDER BY l.created_at DESC LIMIT 5
    ")->fetchAll();
} else {
    $stmt = $db->prepare("
        SELECT l.*, s.jenis_sparepart, s.merk, u.name as user_name
        FROM logbooks l
        JOIN spareparts s ON s.id = l.sparepart_id
        JOIN users u ON u.id = l.user_id
        WHERE l.deleted_at IS NULL AND l.user_id = ?
        ORDER BY l.created_at DESC LIMIT 5
    ");
    $stmt->execute(array($user['id']));
    $recentLogs = $stmt->fetchAll();
}

// Per category stats
$catStats = $db->query("
    SELECT kategori, COUNT(*) as total,
        SUM(CASE WHEN status = 'Tersedia' THEN 1 ELSE 0 END) as tersedia
    FROM spareparts WHERE deleted_at IS NULL
    GROUP BY kategori
")->fetchAll();

// Filters
$where = "WHERE s.deleted_at IS NULL";
$params = array();

if (!empty($_GET['search'])) {
    list($ftWhere, $ftParams) = ftSearch(
        array('s.jenis_sparepart', 's.merk', 's.type_sparepart', 's.serial_number'),
        $_GET['search'],
        's.id'
    );
    $where .= " AND ($ftWhere)";
    $params = array_merge($params, $ftParams);
}
if (!empty($_GET['kategori'])) {
    $where .= " AND s.kategori = ?";
    $params[] = $_GET['kategori'];
}
if (!empty($_GET['jenis'])) {
    $where .= " AND s.jenis_sparepart = ?";
    $params[] = $_GET['jenis'];
}
if (!empty($_GET['status'])) {
    $where .= " AND s.status = ?";
    $params[] = $_GET['status'];
}
if (!empty($_GET['date_from'])) {
    $where .= " AND s.tanggal >= ?";
    $params[] = $_GET['date_from'];
}
if (!empty($_GET['date_to'])) {
    $where .= " AND s.tanggal <= ?";
    $params[] = $_GET['date_to'];
}

// Pagination
list($spareparts, $page, $totalPages) = paginate($db, "SELECT s.* FROM spareparts s $where ORDER BY s.created_at DESC", $params);

$jenisList = $db->query("SELECT nama FROM jenis_spareparts WHERE type IS NULL ORDER BY nama")->fetchAll(PDO::FETCH_COLUMN);
$typeList = $db->query("SELECT nama, type FROM jenis_spareparts WHERE type IS NOT NULL ORDER BY nama, type")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div x-data="dashboard()" class="page-enter">
    <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-4">
        <a href="<?= pageUrl('dashboard.php') ?>" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition">Home</a>
        <i class="fa-solid fa-chevron-right text-xs"></i>
        <span class="text-gray-700 dark:text-gray-200 font-medium">Dashboard</span>
    </nav>

    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-6">
        <h2 class="text-2xl font-extrabold text-gray-800 dark:text-white tracking-tight">Dashboard</h2>
        <div class="flex flex-wrap gap-2">
            <a href="<?= pageUrl('export_csv.php') ?>?<?= http_build_query($_GET) ?>" class="px-4 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-500 text-white rounded-xl hover:from-emerald-700 hover:to-teal-600 transition-all text-sm font-semibold inline-flex items-center gap-2 shadow-md shadow-emerald-500/20">
                <i class="fa-solid fa-file-csv"></i> CSV
            </a>
            <a href="<?= pageUrl('export_pdf.php') ?>?<?= http_build_query($_GET) ?>" class="px-4 py-2.5 bg-gradient-to-r from-red-600 to-rose-500 text-white rounded-xl hover:from-red-700 hover:to-rose-600 transition-all text-sm font-semibold inline-flex items-center gap-2 shadow-md shadow-red-500/20">
                <i class="fa-solid fa-file-pdf"></i> PDF
            </a>
            <?php if (isAdmin()): ?>
            <button @click="openModal('tambah')" class="px-4 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl hover:from-indigo-700 hover:to-purple-700 transition-all text-sm font-semibold inline-flex items-center gap-2 shadow-md shadow-indigo-500/20">
                <i class="fa-solid fa-plus"></i> Tambah Data
            </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">
        <div class="card-hover bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-4">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-indigo-100 to-indigo-200 dark:from-indigo-900/40 dark:to-indigo-800/30 flex items-center justify-center shrink-0 shadow-sm">
                    <i class="fa-solid fa-boxes-stacked text-indigo-600 dark:text-indigo-400"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">Total</p>
                    <p class="text-xl font-extrabold text-gray-800 dark:text-white tracking-tight"><?= $stats['total'] ?></p>
                </div>
            </div>
        </div>
        <div class="card-hover bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-4">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-emerald-100 to-emerald-200 dark:from-emerald-900/40 dark:to-emerald-800/30 flex items-center justify-center shrink-0 shadow-sm">
                    <i class="fa-solid fa-check text-emerald-600 dark:text-emerald-400"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">Tersedia</p>
                    <p class="text-xl font-extrabold text-emerald-600 dark:text-emerald-400 tracking-tight"><?= $stats['tersedia'] ?></p>
                </div>
            </div>
        </div>
        <div class="card-hover bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-4">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-red-100 to-red-200 dark:from-red-900/40 dark:to-red-800/30 flex items-center justify-center shrink-0 shadow-sm">
                    <i class="fa-solid fa-circle-xmark text-red-600 dark:text-red-400"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">Terpakai</p>
                    <p class="text-xl font-extrabold text-red-600 dark:text-red-400 tracking-tight"><?= $stats['terpakai'] ?></p>
                </div>
            </div>
        </div>
        <div class="card-hover bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-4">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-amber-100 to-amber-200 dark:from-amber-900/40 dark:to-amber-800/30 flex items-center justify-center shrink-0 shadow-sm">
                    <i class="fa-solid fa-triangle-exclamation text-amber-600 dark:text-amber-400"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">Rusak</p>
                    <p class="text-xl font-extrabold text-amber-600 dark:text-amber-400 tracking-tight"><?= $stats['rusak'] ?></p>
                </div>
            </div>
        </div>
        <div class="card-hover bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-4">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-blue-100 to-blue-200 dark:from-blue-900/40 dark:to-blue-800/30 flex items-center justify-center shrink-0 shadow-sm">
                    <i class="fa-solid fa-wrench text-blue-600 dark:text-blue-400"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">Perbaikan</p>
                    <p class="text-xl font-extrabold text-blue-600 dark:text-blue-400 tracking-tight"><?= $stats['dalam_perbaikan'] ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-6">
        <div class="xl:col-span-2 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 card-hover">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4 flex items-center gap-2">
                <i class="fa-solid fa-chart-pie text-indigo-500"></i>
                Komposisi Status Sparepart
            </h3>
            <div class="relative" style="max-height: 260px;">
                <canvas id="statusChart"></canvas>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 card-hover">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4 flex items-center gap-2">
                <i class="fa-solid fa-clock-rotate-left text-indigo-500"></i>
                Aktivitas Terbaru
            </h3>
            <div class="space-y-3">
                <?php if (empty($recentLogs)): ?>
                <div class="flex flex-col items-center gap-2 py-8 text-gray-400 dark:text-gray-500">
                    <i class="fa-solid fa-clock text-3xl"></i>
                    <p class="text-sm">Belum ada aktivitas.</p>
                </div>
                <?php else: ?>
                    <?php foreach ($recentLogs as $log): ?>
                    <div class="flex items-start gap-3 pb-3 border-b border-gray-100 dark:border-gray-700 last:border-0 last:pb-0">
                        <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 text-xs font-bold shadow-sm
                            <?= $log['tipe_transaksi'] === 'Barang Masuk' ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400' : '' ?>
                            <?= $log['tipe_transaksi'] === 'Barang Keluar' ? 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400' : '' ?>
                            <?= $log['tipe_transaksi'] === 'Ubah Status' ? 'bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400' : '' ?>
                            <?= $log['tipe_transaksi'] === 'Dalam Perbaikan' ? 'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400' : '' ?>
                            <?= $log['tipe_transaksi'] === 'Permintaan' ? 'bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400' : '' ?>
                        ">
                            <i class="fa-solid
                                <?= $log['tipe_transaksi'] === 'Barang Masuk' ? 'fa-circle-plus' : '' ?>
                                <?= $log['tipe_transaksi'] === 'Barang Keluar' ? 'fa-circle-minus' : '' ?>
                                <?= strpos($log['tipe_transaksi'], 'Status') !== false || $log['tipe_transaksi'] === 'Dalam Perbaikan' ? 'fa-rotate' : '' ?>
                                <?= $log['tipe_transaksi'] === 'Permintaan' ? 'fa-file-import' : '' ?>
                            "></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-semibold text-gray-800 dark:text-gray-200 truncate">
                                <?= escape($log['jenis_sparepart']) ?>
                                <span class="text-gray-400 dark:text-gray-500 font-normal">— <?= escape($log['tipe_transaksi']) ?></span>
                            </p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                                <?= escape($log['user_name']) ?> • <?= formatTanggal($log['tanggal']) ?>
                            </p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-4 mb-6 card-hover">
        <form method="GET" action="index.php" class="flex flex-wrap gap-3 items-end">
            <input type="hidden" name="url" value="dashboard">
            <div>
                <label class="block text-[11px] font-semibold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider">Cari</label>
                <input type="text" name="search" value="<?= escape(_get($_GET, 'search', '')) ?>" placeholder="ID, jenis, merk..." class="px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 dark:text-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 outline-none transition-all duration-200 placeholder:text-gray-400">
            </div>
            <div>
                <label class="block text-[11px] font-semibold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider">Kategori</label>
                <select name="kategori" class="px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 dark:text-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 outline-none transition-all duration-200">
                    <option value="">Semua</option>
                    <option value="Aset" <?= _get($_GET, 'kategori', '') === 'Aset' ? 'selected' : '' ?>>Aset</option>
                    <option value="Non-Aset" <?= _get($_GET, 'kategori', '') === 'Non-Aset' ? 'selected' : '' ?>>Non-Aset</option>
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-semibold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider">Jenis</label>
                <select name="jenis" class="px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 dark:text-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 outline-none transition-all duration-200">
                    <option value="">Semua</option>
                    <?php foreach ($jenisList as $j): ?>
                    <option value="<?= escape($j) ?>" <?= _get($_GET, 'jenis', '') === $j ? 'selected' : '' ?>><?= escape($j) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-semibold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider">Status</label>
                <select name="status" class="px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 dark:text-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 outline-none transition-all duration-200">
                    <option value="">Semua</option>
                    <option value="Tersedia" <?= _get($_GET, 'status', '') === 'Tersedia' ? 'selected' : '' ?>>Tersedia</option>
                    <option value="Terpakai" <?= _get($_GET, 'status', '') === 'Terpakai' ? 'selected' : '' ?>>Terpakai</option>
                    <option value="Rusak" <?= _get($_GET, 'status', '') === 'Rusak' ? 'selected' : '' ?>>Rusak</option>
                    <option value="Dalam Perbaikan" <?= _get($_GET, 'status', '') === 'Dalam Perbaikan' ? 'selected' : '' ?>>Dalam Perbaikan</option>
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-semibold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider">Dari</label>
                <input type="date" name="date_from" value="<?= escape(_get($_GET, 'date_from', '')) ?>" class="px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 dark:text-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 outline-none transition-all duration-200">
            </div>
            <div>
                <label class="block text-[11px] font-semibold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider">Sampai</label>
                <input type="date" name="date_to" value="<?= escape(_get($_GET, 'date_to', '')) ?>" class="px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 dark:text-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 outline-none transition-all duration-200">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-xl text-sm hover:bg-indigo-700 transition-all font-semibold inline-flex items-center gap-1.5 shadow-md shadow-indigo-500/20">
                    <i class="fa-solid fa-filter"></i> Filter
                </button>
                <a href="<?= pageUrl('dashboard.php') ?>" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl text-sm hover:bg-gray-300 dark:hover:bg-gray-600 transition-all font-semibold inline-flex items-center gap-1.5">
                    <i class="fa-solid fa-rotate"></i> Reset
                </a>
            </div>
        </form>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden hidden md:block card-hover">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-gray-700/80 dark:to-gray-700/50 text-gray-600 dark:text-gray-300">
                        <th class="px-4 py-3.5 text-left font-semibold text-[11px] uppercase tracking-wider">ID</th>
                        <th class="px-4 py-3.5 text-left font-semibold text-[11px] uppercase tracking-wider">Jenis</th>
                        <th class="px-4 py-3.5 text-left font-semibold text-[11px] uppercase tracking-wider">Merk</th>
                        <th class="px-4 py-3.5 text-left font-semibold text-[11px] uppercase tracking-wider hidden lg:table-cell">Serial Number</th>
                        <th class="px-4 py-3.5 text-left font-semibold text-[11px] uppercase tracking-wider">Kategori</th>
                        <th class="px-4 py-3.5 text-left font-semibold text-[11px] uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3.5 text-left font-semibold text-[11px] uppercase tracking-wider hidden lg:table-cell">Tanggal</th>
                        <th class="px-4 py-3.5 text-center font-semibold text-[11px] uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    <?php foreach ($spareparts as $i => $sp): ?>
                    <tr class="hover:bg-indigo-50/50 dark:hover:bg-indigo-900/10 transition-all duration-150 <?= $i % 2 === 0 ? 'bg-white dark:bg-gray-800' : 'bg-gray-50/30 dark:bg-gray-800/30' ?>">
                        <td class="px-4 py-3.5 font-semibold text-gray-800 dark:text-gray-200"><?= $sp['id'] ?></td>
                        <td class="px-4 py-3.5 text-gray-700 dark:text-gray-300"><?= escape($sp['jenis_sparepart']) ?></td>
                        <td class="px-4 py-3.5 text-gray-600 dark:text-gray-400"><?= escape(isset($sp['merk']) ? $sp['merk'] : '-') ?></td>
                        <td class="px-4 py-3.5 text-gray-600 dark:text-gray-400 hidden lg:table-cell font-mono text-xs"><?= escape(isset($sp['serial_number']) ? $sp['serial_number'] : '-') ?></td>
                        <td class="px-4 py-3.5"><span class="px-2.5 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300"><?= escape($sp['kategori']) ?></span></td>
                        <td class="px-4 py-3.5"><?= getStatusBadge($sp['status']) ?></td>
                        <td class="px-4 py-3.5 text-gray-500 dark:text-gray-400 hidden lg:table-cell text-xs"><?= formatTanggal($sp['tanggal']) ?></td>
                        <td class="px-4 py-3.5 text-center">
                            <div class="flex justify-center gap-1.5">
                                <button @click="detail(<?= $sp['id'] ?>)" title="Lihat detail" class="p-2 text-xs bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400 rounded-xl hover:bg-indigo-200 dark:hover:bg-indigo-900/50 transition-all hover:scale-110">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                                <?php if (isAdmin()): ?>
                                <button @click="edit(<?= $sp['id'] ?>)" title="Edit data" class="p-2 text-xs bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 rounded-xl hover:bg-amber-200 dark:hover:bg-amber-900/50 transition-all hover:scale-110">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <button @click="hapus(<?= $sp['id'] ?>)" title="Hapus data" class="p-2 text-xs bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-xl hover:bg-red-200 dark:hover:bg-red-900/50 transition-all hover:scale-110">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($spareparts)): ?>
                    <tr>
                        <td colspan="8" class="px-4 py-16 text-center">
                            <div class="flex flex-col items-center gap-3 text-gray-400 dark:text-gray-500">
                                <i class="fa-solid fa-box-open text-5xl opacity-50"></i>
                                <p class="text-sm font-medium">Tidak ada data sparepart.</p>
                                <?php if (isAdmin()): ?>
                                <button @click="openModal('tambah')" class="px-4 py-2 bg-indigo-600 text-white rounded-xl text-sm hover:bg-indigo-700 transition-all font-semibold inline-flex items-center gap-2 shadow-md shadow-indigo-500/20">
                                    <i class="fa-solid fa-plus"></i> Tambah Data
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?= renderPagination($page, $totalPages) ?>
    </div>

    <div class="md:hidden space-y-3">
        <?php if (empty($spareparts)): ?>
        <div class="flex flex-col items-center gap-3 py-16 text-gray-400 dark:text-gray-500">
            <i class="fa-solid fa-box-open text-5xl opacity-50"></i>
            <p class="text-sm font-medium">Tidak ada data sparepart.</p>
            <?php if (isAdmin()): ?>
            <button @click="openModal('tambah')" class="px-4 py-2 bg-indigo-600 text-white rounded-xl text-sm hover:bg-indigo-700 transition-all font-semibold inline-flex items-center gap-2 shadow-md shadow-indigo-500/20">
                <i class="fa-solid fa-plus"></i> Tambah Data
            </button>
            <?php endif; ?>
        </div>
        <?php else: ?>
            <?php foreach ($spareparts as $sp): ?>
            <div class="card-hover bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-4">
                <div class="flex justify-between items-start mb-2">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-gray-800 dark:text-gray-200 text-sm">#<?= $sp['id'] ?></span>
                            <span class="text-[11px] font-medium px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400"><?= escape($sp['kategori']) ?></span>
                        </div>
                        <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mt-0.5"><?= escape($sp['jenis_sparepart']) ?></p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5"><?= escape(isset($sp['merk']) ? $sp['merk'] : '-') ?> <?= $sp['serial_number'] ? '· ' . escape($sp['serial_number']) : '' ?></p>
                    </div>
                    <?= getStatusBadge($sp['status']) ?>
                </div>
                <div class="flex items-center justify-between mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                    <span class="text-xs text-gray-400 dark:text-gray-500"><?= formatTanggal($sp['tanggal']) ?></span>
                    <div class="flex gap-1.5">
                        <button @click="detail(<?= $sp['id'] ?>)" title="Lihat detail" class="p-2 text-xs bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400 rounded-xl hover:bg-indigo-200 dark:hover:bg-indigo-900/50 transition-all hover:scale-110">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                        <?php if (isAdmin()): ?>
                        <button @click="edit(<?= $sp['id'] ?>)" title="Edit data" class="p-2 text-xs bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 rounded-xl hover:bg-amber-200 dark:hover:bg-amber-900/50 transition-all hover:scale-110">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <button @click="hapus(<?= $sp['id'] ?>)" title="Hapus data" class="p-2 text-xs bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-xl hover:bg-red-200 dark:hover:bg-red-900/50 transition-all hover:scale-110">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
        <?= renderPagination($page, $totalPages) ?>
    </div>
</div>

<!-- Tambah Modal -->
<div x-data="{ 
    open: false
}" 
     x-show="open" 
     x-cloak 
     class="fixed inset-0 z-50 flex items-center justify-center p-4"
     @open-tambah-modal.window="open = true"
     x-transition:enter="modal-enter-active"
     x-transition:enter-start="modal-enter"
     x-transition:leave="modal-leave-active"
     x-transition:leave-end="modal-leave">
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="open = false" x-transition:enter="backdrop-enter-active" x-transition:enter-start="backdrop-enter" x-transition:leave="backdrop-leave-active" x-transition:leave-end="backdrop-leave"></div>
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto relative z-10">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center sticky top-0 bg-white dark:bg-gray-800 rounded-t-2xl">
            <h3 class="text-lg font-bold text-gray-800 dark:text-white">Tambah Sparepart</h3>
            <button @click="open = false" class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition">&times;</button>
        </div>
        <form method="POST" action="index.php?url=sparepart&action=store" class="p-6 space-y-4">
            <?= csrf() ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kategori <span class="text-red-500">*</span></label>
                    <select name="kategori" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition">
                        <option value="Aset">Aset</option>
                        <option value="Non-Aset">Non-Aset</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Jenis Sparepart <span class="text-red-500">*</span></label>
                    <select name="jenis_sparepart" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition">
                        <option value="">Pilih Jenis</option>
                        <?php foreach ($jenisList as $j): ?>
                        <option value="<?= escape($j) ?>"><?= escape($j) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type Sparepart <span class="text-red-500">*</span></label>
                    <select name="type_sparepart" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition">
                        <option value="">Pilih Type</option>
                        <?php foreach ($typeList as $t): ?>
                        <option value="<?= escape($t['type']) ?>"><?= escape($t['type']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Quantity <span class="text-red-500">*</span></label>
                    <input type="number" name="quantity" id="input-qty" value="1" min="1" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Serial Number <span class="text-red-500">*</span></label>
                    <div class="flex">
                        <span class="inline-flex items-center px-3 py-2 border border-r-0 border-gray-300 dark:border-gray-600 dark:bg-gray-600 bg-gray-100 text-gray-600 dark:text-gray-300 rounded-l-lg text-sm font-mono select-none">SN</span>
                        <input type="text" name="serial_number" required class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-r-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition font-mono" value="<?= escape(old('serial_number')) ?>">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tanggal <span class="text-red-500">*</span></label>
                    <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Merk <span class="text-red-500">*</span></label>
                    <input type="text" name="merk" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">PIC <span class="text-red-500">*</span></label>
                    <input type="text" name="pic" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Department <span class="text-red-500">*</span></label>
                    <input type="text" name="department" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status <span class="text-red-500">*</span></label>
                    <select name="status" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition">
                        <option value="">Pilih Status</option>
                        <option value="Tersedia">Tersedia</option>
                        <option value="Terpakai">Terpakai</option>
                        <option value="Rusak">Rusak</option>
                        <option value="Dalam Perbaikan">Dalam Perbaikan</option>
                    </select>
                </div>
                <div class="col-span-1 sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Keterangan <span class="text-red-500">*</span></label>
                    <textarea name="keterangan" rows="2" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition"></textarea>
                </div>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" @click="open = false" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition text-sm font-medium">Batal</button>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition text-sm font-medium inline-flex items-center gap-1.5">
                    <i class="fa-solid fa-save"></i> Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const CSRF_TOKEN = '<?= csrfToken() ?>';

    document.addEventListener('alpine:init', () => {
        Alpine.data('dashboard', () => ({
            init() {
                this.loadChart();
            },
            loadChart() {
                const ctx = document.getElementById('statusChart');
                if (!ctx) return;
                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Tersedia', 'Terpakai', 'Rusak', 'Dalam Perbaikan'],
                        datasets: [{
                            data: [<?= $stats['tersedia'] ?: 0 ?>, <?= $stats['terpakai'] ?: 0 ?>, <?= $stats['rusak'] ?: 0 ?>, <?= $stats['dalam_perbaikan'] ?: 0 ?>],
                            backgroundColor: ['#10b981', '#ef4444', '#f59e0b', '#6366f1'],
                            borderWidth: 2,
                            borderColor: document.documentElement.classList.contains('dark') ? '#1f2937' : '#ffffff',
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        cutout: '60%',
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 16,
                                    usePointStyle: true,
                                    pointStyle: 'circle',
                                    color: document.documentElement.classList.contains('dark') ? '#d1d5db' : '#4b5563'
                                }
                            }
                        }
                    }
                });
            },
            openModal(type) {
                if (type === 'tambah') {
                    window.dispatchEvent(new CustomEvent('open-tambah-modal'));
                }
            },
            async detail(id) {
                window.dispatchEvent(new CustomEvent('loading-start'));
                const res = await fetch('index.php?url=sparepart&action=show&id=' + id);
                const data = await res.json();
                window.dispatchEvent(new CustomEvent('loading-end'));
                if (data.success) {
                    var logsHtml = '';
                    if (data.logs && data.logs.length) {
                        logsHtml = '<hr class="my-3 border-gray-200 dark:border-gray-600"><h4 class="text-sm font-semibold mb-2">Riwayat Pemakaian</h4><div class="space-y-1.5 text-xs">';
                        data.logs.forEach(function(l) {
                            logsHtml += '<div class="flex justify-between items-center p-2 rounded bg-gray-50 dark:bg-gray-700/50"><span><strong>' + escapeHtml(l.tipe_transaksi) + '</strong> — ' + escapeHtml(l.pic_penerima || l.user_name || '-') + (l.department ? ' (' + escapeHtml(l.department) + ')' : '') + '</span><span class="text-gray-400">' + l.tanggal + '</span></div>';
                        });
                        logsHtml += '</div>';
                    }
                    darkSwal({
                        title: 'Detail Sparepart ' + data.data.id,
                        html: `
                            <div class="text-left space-y-2 text-sm">
                                <p><strong>Jenis:</strong> ${data.data.jenis_sparepart}</p>
                                <p><strong>Type:</strong> ${data.data.type_sparepart || '-'}</p>
                                <p><strong>Merk:</strong> ${data.data.merk || '-'}</p>
                                <p><strong>Serial Number:</strong> ${data.data.serial_number || '-'}</p>
                                <p><strong>Kategori:</strong> ${data.data.kategori}</p>
                                <p><strong>Status:</strong> ${data.data.status}</p>
                                <p><strong>Quantity:</strong> ${data.data.quantity}</p>
                                <p><strong>PIC:</strong> ${data.data.pic || '-'}</p>
                                <p><strong>Department:</strong> ${data.data.department || '-'}</p>
                                <p><strong>Tanggal:</strong> ${data.data.tanggal}</p>
                                <p><strong>Keterangan:</strong> ${data.data.keterangan || '-'}</p>
                                ${logsHtml}
                            </div>
                        `,
                        confirmButtonColor: '#4f46e5',
                        confirmButtonText: 'Tutup'
                    });
                }
            },
            async edit(id) {
                window.dispatchEvent(new CustomEvent('loading-start'));
                const res = await fetch('index.php?url=sparepart&action=show&id=' + id);
                const data = await res.json();
                window.dispatchEvent(new CustomEvent('loading-end'));
                if (data.success) {
                    darkSwal({
                        title: 'Edit Sparepart ' + data.data.id,
                        html: `
                            <form id="editForm" class="text-left space-y-3">
                                <input type="hidden" name="id" value="${data.data.id}">
                                <input type="hidden" name="csrf_token" value="${CSRF_TOKEN}">
                                <div>
                                    <label class="text-xs font-medium">Kategori</label>
                                    <select name="kategori" class="w-full px-3 py-2 border rounded-lg text-sm">
                                        <option value="Aset" ${data.data.kategori === 'Aset' ? 'selected' : ''}>Aset</option>
                                        <option value="Non-Aset" ${data.data.kategori === 'Non-Aset' ? 'selected' : ''}>Non-Aset</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="text-xs font-medium">Jenis Sparepart</label>
                                    <input type="text" name="jenis_sparepart" value="${escapeHtml(data.data.jenis_sparepart)}" class="w-full px-3 py-2 border rounded-lg text-sm">
                                </div>
                                <div>
                                    <label class="text-xs font-medium">Type</label>
                                    <input type="text" name="type_sparepart" value="${escapeHtml(data.data.type_sparepart || '')}" class="w-full px-3 py-2 border rounded-lg text-sm">
                                </div>
                                <div>
                                    <label class="text-xs font-medium">Serial Number</label>
                                    <input type="text" name="serial_number" value="${escapeHtml(data.data.serial_number || '')}" class="w-full px-3 py-2 border rounded-lg text-sm">
                                </div>
                                <div>
                                    <label class="text-xs font-medium">Quantity</label>
                                    <input type="number" name="quantity" value="${data.data.quantity}" min="1" class="w-full px-3 py-2 border rounded-lg text-sm">
                                </div>
                                <div>
                                    <label class="text-xs font-medium">Merk</label>
                                    <input type="text" name="merk" value="${escapeHtml(data.data.merk || '')}" class="w-full px-3 py-2 border rounded-lg text-sm">
                                </div>
                                <div>
                                    <label class="text-xs font-medium">PIC</label>
                                    <input type="text" name="pic" value="${escapeHtml(data.data.pic || '')}" class="w-full px-3 py-2 border rounded-lg text-sm">
                                </div>
                                <div>
                                    <label class="text-xs font-medium">Department</label>
                                    <input type="text" name="department" value="${escapeHtml(data.data.department || '')}" class="w-full px-3 py-2 border rounded-lg text-sm">
                                </div>
                                <div>
                                    <label class="text-xs font-medium">Status</label>
                                    <select name="status" class="w-full px-3 py-2 border rounded-lg text-sm">
                                        <option value="Tersedia" ${data.data.status === 'Tersedia' ? 'selected' : ''}>Tersedia</option>
                                        <option value="Terpakai" ${data.data.status === 'Terpakai' ? 'selected' : ''}>Terpakai</option>
                                        <option value="Rusak" ${data.data.status === 'Rusak' ? 'selected' : ''}>Rusak</option>
                                        <option value="Dalam Perbaikan" ${data.data.status === 'Dalam Perbaikan' ? 'selected' : ''}>Dalam Perbaikan</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="text-xs font-medium">Keterangan</label>
                                    <textarea name="keterangan" rows="2" class="w-full px-3 py-2 border rounded-lg text-sm">${escapeHtml(data.data.keterangan || '')}</textarea>
                                </div>
                            </form>
                        `,
                        showCancelButton: true,
                        confirmButtonColor: '#4f46e5',
                        cancelButtonText: 'Batal',
                        confirmButtonText: 'Simpan',
                        preConfirm: () => {
                            const form = document.getElementById('editForm');
                            const formData = new FormData(form);
                            return fetch('index.php?url=sparepart&action=update&id=' + id, {
                                method: 'POST',
                                body: formData
                            }).then(r => r.json());
                        }
                    }).then(result => {
                        if (result.isConfirmed && result.value.success) {
                            darkSwal({ icon: 'success', title: 'Berhasil!', text: 'Data sparepart diupdate.', confirmButtonColor: '#4f46e5' }).then(() => location.reload());
                        } else if (result.value) {
                            darkSwal({ icon: 'error', title: 'Error!', text: result.value.message || 'Gagal update.', confirmButtonColor: '#4f46e5' });
                        }
                    });
                }
            },
            hapus(id) {
                darkSwal({
                    title: 'Hapus Sparepart?',
                    text: 'Data akan dihapus secara permanen.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonText: 'Batal',
                    confirmButtonText: 'Ya, Hapus'
                }).then(result => {
                    if (result.isConfirmed) {
                        window.dispatchEvent(new CustomEvent('loading-start'));
                        const formData = new FormData();
                        formData.append('csrf_token', CSRF_TOKEN);
                        fetch('index.php?url=sparepart&action=destroy&id=' + id, {
                            method: 'POST',
                            body: formData
                        })
                            .then(r => r.json())
                            .then(data => {
                                window.dispatchEvent(new CustomEvent('loading-end'));
                                if (data.success) {
                                    darkSwal({ icon: 'success', title: 'Berhasil!', text: 'Sparepart dihapus.', confirmButtonColor: '#4f46e5' }).then(() => location.reload());
                                } else {
                                    darkSwal({ icon: 'error', title: 'Error!', text: data.message || 'Gagal hapus.', confirmButtonColor: '#4f46e5' });
                                }
                            });
                    }
                });
            }
        }));
    });

    function darkSwal(opt) {
        var isDark = document.documentElement.classList.contains('dark');
        return Swal.fire(Object.assign({
            background: isDark ? '#1f2937' : '#ffffff',
            color: isDark ? '#d1d5db' : '#1f2937',
            confirmButtonColor: '#4f46e5',
        }, opt));
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.17.2"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
