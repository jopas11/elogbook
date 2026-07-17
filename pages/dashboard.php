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

// Barang yang Dipakai
if (isAdmin()) {
    $dipakai = $db->query("
        SELECT id, jenis_sparepart, type_sparepart, merk, serial_number, kategori, status, pic, department, tanggal, keterangan
        FROM spareparts WHERE deleted_at IS NULL AND status = 'Terpakai'
        ORDER BY updated_at DESC
    ")->fetchAll();
} else {
    $stmt = $db->prepare("
        SELECT id, jenis_sparepart, type_sparepart, merk, serial_number, kategori, status, pic, department, tanggal, keterangan
        FROM spareparts WHERE deleted_at IS NULL AND status = 'Terpakai' AND pic = ?
        ORDER BY updated_at DESC
    ");
    $stmt->execute(array($user['name']));
    $dipakai = $stmt->fetchAll();
}

$userPicName = $user['name'];

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

// Group by kategori + jenis_sparepart + type_sparepart
$groupQuery = "SELECT
    s.kategori, s.jenis_sparepart, s.type_sparepart,
    COUNT(*) as total,
    SUM(CASE WHEN s.status = 'Tersedia' THEN 1 ELSE 0 END) as tersedia,
    SUM(CASE WHEN s.status = 'Terpakai' THEN 1 ELSE 0 END) as terpakai,
    SUM(CASE WHEN s.status = 'Rusak' THEN 1 ELSE 0 END) as rusak,
    SUM(CASE WHEN s.status = 'Dalam Perbaikan' THEN 1 ELSE 0 END) as dalam_perbaikan,
    GROUP_CONCAT(DISTINCT s.merk ORDER BY s.merk SEPARATOR ', ') as merk_list,
    MAX(s.created_at) as latest
FROM spareparts s
$where
GROUP BY s.kategori, s.jenis_sparepart, s.type_sparepart
ORDER BY latest DESC";

list($groups, $page, $totalPages) = paginate($db, $groupQuery, $params, 10);

$jenisRows = $db->query("SELECT nama, kategori FROM jenis_spareparts WHERE type IS NULL ORDER BY nama")->fetchAll();
$jenisList = array_column($jenisRows, 'nama');
$jenisGrouped = [];
foreach ($jenisRows as $j) {
    $jenisGrouped[$j['kategori']][] = $j['nama'];
}
$typeList = $db->query("SELECT nama, kategori, type FROM jenis_spareparts WHERE type IS NOT NULL ORDER BY nama, type")->fetchAll();
$typesByJenisKategori = [];
foreach ($typeList as $t) {
    $key = $t['kategori'] . '||' . $t['nama'];
    $typesByJenisKategori[$key][] = $t['type'];
}

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
        <div class="flex items-center gap-3">
            <h2 class="text-2xl font-extrabold text-gray-800 dark:text-white tracking-tight">Dashboard</h2>
            <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium"
                 :class="liveIndicator ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400' : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400'">
                <span class="relative flex h-2 w-2" x-show="liveIndicator">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                </span>
                <i x-show="!liveIndicator" class="fa-solid fa-circle text-xs text-gray-400"></i>
                <span x-text="liveIndicator ? 'Live' : 'Offline'"></span>
            </div>
        </div>
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

    <div x-show="lastUpdate" class="text-xs text-gray-400 dark:text-gray-500 mb-2 flex items-center gap-1.5">
        <i class="fa-solid fa-rotate text-[10px]" :class="refreshing ? 'fa-spin' : ''"></i>
        <span>Terakhir diperbarui: <span x-text="lastUpdate"></span></span>
        <button @click="forceRefresh()" class="text-indigo-500 hover:text-indigo-700 dark:hover:text-indigo-400 transition ml-1" title="Refresh sekarang">
            <i class="fa-solid fa-arrows-rotate text-xs"></i>
        </button>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">
        <template x-for="card in statCards" :key="card.key">
            <div class="card-hover bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-4 overflow-hidden relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0 shadow-sm"
                         :class="card.iconBg">
                        <i :class="card.icon + ' ' + card.iconColor"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs text-gray-500 dark:text-gray-400 font-medium" x-text="card.label"></p>
                        <p class="text-xl font-extrabold tracking-tight truncate"
                           :class="card.textColor"
                           x-text="card.value.toLocaleString()">
                        </p>
                    </div>
                </div>
                <div class="absolute bottom-0 left-0 right-0 h-0.5 bg-gradient-to-r"
                     :class="card.barGradient"
                     :style="'width: ' + card.barWidth + '%'"></div>
            </div>
        </template>
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

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 mb-6 card-hover">
        <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-2">
                <i class="fa-solid fa-bag-shopping text-indigo-500"></i>
                Barang yang Dipakai
                <span class="text-xs font-normal text-gray-400 dark:text-gray-500">(<span x-text="dipakai.length"></span> item)</span>
            </h3>
            <div class="flex items-center gap-2">
                <span class="text-[10px] text-gray-400 dark:text-gray-500" x-show="dipakai.length > 0">
                    <i class="fa-solid fa-rotate mr-0.5" :class="refreshing ? 'fa-spin' : ''"></i>
                    Auto-refresh
                </span>
            </div>
        </div>
        <div x-show="dipakai.length === 0" class="flex flex-col items-center gap-2 py-8 text-gray-400 dark:text-gray-500">
            <i class="fa-solid fa-box-open text-3xl"></i>
            <p class="text-sm">Tidak ada barang yang dipakai.</p>
        </div>
        <div x-show="dipakai.length > 0" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3 p-4" style="max-height: 480px; overflow-y: auto;">
            <template x-for="(item, idx) in dipakai" :key="item.id">
                <div class="relative group bg-gradient-to-br from-gray-50 to-white dark:from-gray-700/50 dark:to-gray-800 rounded-xl border border-gray-200 dark:border-gray-600 p-4 transition-all duration-300 hover:shadow-lg hover:border-indigo-300 dark:hover:border-indigo-500/50 hover:-translate-y-0.5">
                    <div class="flex items-start justify-between mb-2">
                        <div class="flex items-center gap-2 min-w-0">
                            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-red-100 to-red-200 dark:from-red-900/40 dark:to-red-800/30 flex items-center justify-center shrink-0 relative">
                                <i class="fa-solid fa-box text-red-600 dark:text-red-400 text-xs"></i>
                                <span x-show="item.pic === userPicName"
                                      class="absolute -top-1 -right-1 w-3.5 h-3.5 bg-emerald-500 rounded-full flex items-center justify-center"
                                      title="Barang Anda">
                                    <i class="fa-solid fa-check text-white" style="font-size: 6px;"></i>
                                </span>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-800 dark:text-gray-200 truncate" x-text="item.jenis_sparepart"></p>
                                <p class="text-xs text-gray-400 dark:text-gray-500 truncate" x-text="item.merk || '-'"></p>
                            </div>
                        </div>
                        <span class="shrink-0 inline-flex items-center px-2 py-0.5 text-xs font-semibold rounded-full"
                              :class="statusBadgeClass(item.status)" x-text="item.status"></span>
                    </div>
                    <div class="grid grid-cols-2 gap-x-3 gap-y-1 text-xs mt-2">
                        <div class="text-gray-500 dark:text-gray-400 flex items-center gap-1">
                            <i class="fa-solid fa-barcode text-[10px]"></i>
                            <span class="font-mono font-medium text-gray-700 dark:text-gray-300" x-text="(item.serial_number || '').replace(/^SN-/, '')"></span>
                        </div>
                        <div class="text-gray-500 dark:text-gray-400 flex items-center gap-1">
                            <i class="fa-solid fa-tag text-[10px]"></i>
                            <span x-text="item.kategori"></span>
                        </div>
                        <div class="text-gray-500 dark:text-gray-400 flex items-center gap-1">
                            <i class="fa-solid fa-user text-[10px]"></i>
                            <span class="font-medium text-gray-700 dark:text-gray-300" x-text="item.pic || '-'"></span>
                        </div>
                        <div class="text-gray-500 dark:text-gray-400 flex items-center gap-1">
                            <i class="fa-solid fa-building text-[10px]"></i>
                            <span x-text="item.department || '-'"></span>
                        </div>
                        <div class="text-gray-500 dark:text-gray-400 flex items-center gap-1 col-span-2">
                            <i class="fa-solid fa-calendar text-[10px]"></i>
                            <span x-text="item.tanggal"></span>
                        </div>
                    </div>
                    <div class="mt-2 pt-2 border-t border-gray-100 dark:border-gray-700">
                        <p class="text-xs text-gray-400 dark:text-gray-500 truncate" x-text="item.keterangan ? 'Keterangan: ' + item.keterangan : '-'"></p>
                    </div>
                    <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
                        <span class="text-[10px] text-gray-400 bg-white dark:bg-gray-700 px-1.5 py-0.5 rounded shadow-sm">
                            #<span x-text="idx + 1"></span>
                        </span>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-4 mb-6 card-hover">
        <form method="GET" action="index.php" class="flex flex-wrap gap-3 items-end">
            <input type="hidden" name="url" value="dashboard">
            <div>
                <label class="block text-[11px] font-semibold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider">Cari</label>
                <input type="text" name="search" value="<?= escape(_get($_GET, 'search', '')) ?>" placeholder="Cari SN, jenis, merk..." class="px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 dark:text-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 outline-none transition-all duration-200 placeholder:text-gray-400">
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

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden card-hover">
        <div class="overflow-x-auto">
            <table class="w-full text-sm responsive-table">
                <thead>
                    <tr class="bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-gray-700/80 dark:to-gray-700/50 text-gray-600 dark:text-gray-300">
                        <th class="px-4 py-3.5 text-left font-semibold text-[11px] uppercase tracking-wider">Kategori</th>
                        <th class="px-4 py-3.5 text-left font-semibold text-[11px] uppercase tracking-wider">Jenis</th>
                        <th class="px-4 py-3.5 text-left font-semibold text-[11px] uppercase tracking-wider">Type</th>
                        <th class="px-4 py-3.5 text-left font-semibold text-[11px] uppercase tracking-wider">Merk</th>
                        <th class="px-4 py-3.5 text-center font-semibold text-[11px] uppercase tracking-wider">Total</th>
                        <th class="px-4 py-3.5 text-center font-semibold text-[11px] uppercase tracking-wider" title="Tersedia">T</th>
                        <th class="px-4 py-3.5 text-center font-semibold text-[11px] uppercase tracking-wider" title="Terpakai">P</th>
                        <th class="px-4 py-3.5 text-center font-semibold text-[11px] uppercase tracking-wider" title="Rusak">R</th>
                        <th class="px-4 py-3.5 text-center font-semibold text-[11px] uppercase tracking-wider" title="Dalam Perbaikan">PR</th>
                        <th class="px-4 py-3.5 text-center font-semibold text-[11px] uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    <?php foreach ($groups as $i => $g): ?>
                    <?php
                        $typeLabel = $g['type_sparepart'] ?: '-';
                        $typeKey = urlencode($g['type_sparepart'] ?? '');
                    ?>
                    <tr class="hover:bg-indigo-50/50 dark:hover:bg-indigo-900/10 transition-all duration-150 <?= $i % 2 === 0 ? 'bg-white dark:bg-gray-800' : 'bg-gray-50/30 dark:bg-gray-800/30' ?>">
                        <td data-label="Kategori"><span class="px-2.5 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300"><?= escape($g['kategori']) ?></span></td>
                        <td data-label="Jenis" class="font-semibold text-gray-800 dark:text-gray-200"><?= escape($g['jenis_sparepart']) ?></td>
                        <td data-label="Type" class="text-gray-600 dark:text-gray-400 text-xs"><?= escape($typeLabel) ?></td>
                        <td data-label="Merk" class="text-gray-600 dark:text-gray-400 text-xs"><?= escape($g['merk_list'] ?: '-') ?></td>
                        <td data-label="Total" class="text-center font-bold text-gray-800 dark:text-gray-200"><?= $g['total'] ?></td>
                        <td data-label="Tersedia" class="text-center"><span class="text-emerald-600 dark:text-emerald-400 font-semibold"><?= $g['tersedia'] ?></span></td>
                        <td data-label="Terpakai" class="text-center"><span class="text-red-600 dark:text-red-400 font-semibold"><?= $g['terpakai'] ?></span></td>
                        <td data-label="Rusak" class="text-center"><span class="text-amber-600 dark:text-amber-400 font-semibold"><?= $g['rusak'] ?></span></td>
                        <td data-label="Perbaikan" class="text-center"><span class="text-blue-600 dark:text-blue-400 font-semibold"><?= $g['dalam_perbaikan'] ?></span></td>
                        <td data-label="Aksi" class="text-center">
                            <button @click="detailGroup('<?= escape($g['kategori']) ?>', '<?= escape($g['jenis_sparepart']) ?>', '<?= $typeKey ?>')" title="Lihat detail grup" class="p-2 text-xs bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400 rounded-xl hover:bg-indigo-200 dark:hover:bg-indigo-900/50 transition-all hover:scale-110">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($groups)): ?>
                    <tr>
                        <td colspan="10" class="px-4 py-16 text-center">
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
        <?= renderPagination($page, $totalPages, array(), 10) ?>
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
                    <select name="kategori" id="add-kategori" required onchange="toggleAddQty(); filterAddJenis()"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition">
                        <option value="">Pilih Kategori</option>
                        <option value="Aset">Aset</option>
                        <option value="Non-Aset">Non-Aset</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Jenis Sparepart <span class="text-red-500">*</span></label>
                    <select name="jenis_sparepart" id="add-jenis" required onchange="filterAddType()"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition">
                        <option value="">Pilih Kategori dulu</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type Sparepart</label>
                    <select name="type_sparepart" id="add-type"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition">
                        <option value="">Pilih Jenis dulu</option>
                    </select>
                </div>
                <div id="add-qty-wrap">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Quantity <span class="text-red-500">*</span></label>
                    <input type="number" name="quantity" id="input-qty" value="1" min="1" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Serial Number <span class="text-red-500">*</span></label>
                    <div class="flex">
                        <span class="inline-flex items-center px-3 py-2 border border-r-0 border-gray-300 dark:border-gray-600 dark:bg-gray-600 bg-gray-100 text-gray-600 dark:text-gray-300 rounded-l-lg text-sm font-mono select-none">SN</span>
                        <input type="text" name="serial_number" required class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-r-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition font-mono" value="<?= escape(old('serial_number')) ?>" placeholder="Pisahkan dengan koma jika lebih dari 1">
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
                <input type="hidden" name="status" value="Tersedia">
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

    const JENIS_DATA = <?= json_encode($jenisGrouped) ?>;
    const TYPE_DATA = <?= json_encode($typesByJenisKategori) ?>;

    function toggleAddQty() {
        const kat = document.getElementById('add-kategori').value;
        const wrap = document.getElementById('add-qty-wrap');
        const qty = document.getElementById('input-qty');
        if (kat === 'Aset') {
            wrap.style.display = 'none';
            qty.required = false;
        } else {
            wrap.style.display = 'block';
            qty.required = true;
        }
    }
    toggleAddQty();

    function filterAddJenis() {
        const kat = document.getElementById('add-kategori').value;
        const sel = document.getElementById('add-jenis');
        const typeSel = document.getElementById('add-type');
        sel.innerHTML = '<option value="">Pilih Jenis</option>';
        typeSel.innerHTML = '<option value="">Pilih Jenis dulu</option>';
        if (kat && JENIS_DATA[kat]) {
            JENIS_DATA[kat].forEach(function(j) {
                sel.innerHTML += '<option value="' + j.replace(/&/g,'&amp;').replace(/</g,'&lt;') + '">' + j.replace(/&/g,'&amp;').replace(/</g,'&lt;') + '</option>';
            });
        }
    }

    function filterAddType() {
        const kat = document.getElementById('add-kategori').value;
        const jenis = document.getElementById('add-jenis').value;
        const sel = document.getElementById('add-type');
        sel.innerHTML = '<option value="">Pilih Type</option>';
        const key = kat + '||' + jenis;
        if (jenis && TYPE_DATA[key]) {
            TYPE_DATA[key].forEach(function(t) {
                sel.innerHTML += '<option value="' + t.replace(/&/g,'&amp;').replace(/</g,'&lt;') + '">' + t.replace(/&/g,'&amp;').replace(/</g,'&lt;') + '</option>';
            });
        }
    }

    function filterEditJenis(kat, currentJenis, currentType) {
        const sel = document.getElementById('edit-jenis');
        sel.innerHTML = '<option value="">Pilih Jenis</option>';
        if (kat && JENIS_DATA[kat]) {
            JENIS_DATA[kat].forEach(function(j) {
                sel.innerHTML += '<option value="' + j.replace(/&/g,'&amp;').replace(/</g,'&lt;') + '"' + (j === currentJenis ? ' selected' : '') + '>' + j.replace(/&/g,'&amp;').replace(/</g,'&lt;') + '</option>';
            });
        }
        filterEditType(kat, currentJenis, currentType);
    }

    function filterEditType(kat, jenis, currentType) {
        const sel = document.getElementById('edit-type');
        sel.innerHTML = '<option value="">Pilih Type</option>';
        const key = kat + '||' + jenis;
        if (jenis && TYPE_DATA[key]) {
            TYPE_DATA[key].forEach(function(t) {
                sel.innerHTML += '<option value="' + t.replace(/&/g,'&amp;').replace(/</g,'&lt;') + '"' + (t === currentType ? ' selected' : '') + '>' + t.replace(/&/g,'&amp;').replace(/</g,'&lt;') + '</option>';
            });
        }
    }

    document.addEventListener('alpine:init', () => {
        Alpine.data('dashboard', () => ({
            // Real-time stats
            stats: {
                total: <?= $stats['total'] ?: 0 ?>,
                tersedia: <?= $stats['tersedia'] ?: 0 ?>,
                terpakai: <?= $stats['terpakai'] ?: 0 ?>,
                rusak: <?= $stats['rusak'] ?: 0 ?>,
                dalam_perbaikan: <?= $stats['dalam_perbaikan'] ?: 0 ?>
            },
            dipakai: <?= json_encode($dipakai) ?>,
            userPicName: '<?= escape($userPicName) ?>',
            lastUpdate: '',
            refreshing: false,
            liveIndicator: true,
            pollTimer: null,
            chart: null,

            get statCards() {
                const s = this.stats;
                const maxVal = Math.max(s.total, 1);
                return [
                    { key: 'total', label: 'Total', value: s.total, icon: 'fa-solid fa-boxes-stacked', iconBg: 'bg-gradient-to-br from-indigo-100 to-indigo-200 dark:from-indigo-900/40 dark:to-indigo-800/30', iconColor: 'text-indigo-600 dark:text-indigo-400', textColor: 'text-gray-800 dark:text-white', barWidth: (s.total / maxVal) * 100, barGradient: 'from-indigo-500 to-indigo-400' },
                    { key: 'tersedia', label: 'Tersedia', value: s.tersedia, icon: 'fa-solid fa-check', iconBg: 'bg-gradient-to-br from-emerald-100 to-emerald-200 dark:from-emerald-900/40 dark:to-emerald-800/30', iconColor: 'text-emerald-600 dark:text-emerald-400', textColor: 'text-emerald-600 dark:text-emerald-400', barWidth: (s.tersedia / maxVal) * 100, barGradient: 'from-emerald-500 to-emerald-400' },
                    { key: 'terpakai', label: 'Terpakai', value: s.terpakai, icon: 'fa-solid fa-circle-xmark', iconBg: 'bg-gradient-to-br from-red-100 to-red-200 dark:from-red-900/40 dark:to-red-800/30', iconColor: 'text-red-600 dark:text-red-400', textColor: 'text-red-600 dark:text-red-400', barWidth: (s.terpakai / maxVal) * 100, barGradient: 'from-red-500 to-red-400' },
                    { key: 'rusak', label: 'Rusak', value: s.rusak, icon: 'fa-solid fa-triangle-exclamation', iconBg: 'bg-gradient-to-br from-amber-100 to-amber-200 dark:from-amber-900/40 dark:to-amber-800/30', iconColor: 'text-amber-600 dark:text-amber-400', textColor: 'text-amber-600 dark:text-amber-400', barWidth: (s.rusak / maxVal) * 100, barGradient: 'from-amber-500 to-amber-400' },
                    { key: 'perbaikan', label: 'Perbaikan', value: s.dalam_perbaikan, icon: 'fa-solid fa-wrench', iconBg: 'bg-gradient-to-br from-blue-100 to-blue-200 dark:from-blue-900/40 dark:to-blue-800/30', iconColor: 'text-blue-600 dark:text-blue-400', textColor: 'text-blue-600 dark:text-blue-400', barWidth: (s.dalam_perbaikan / maxVal) * 100, barGradient: 'from-blue-500 to-blue-400' },
                ];
            },

            init() {
                this.loadChart();
                this.startPolling();
            },

            startPolling() {
                this.fetchRealtime();
                this.pollTimer = setInterval(() => this.fetchRealtime(), 15000);
            },

            async fetchRealtime() {
                if (this.refreshing) return;
                this.refreshing = true;
                try {
                    const res = await fetch('index.php?url=sparepart&action=realtime_dashboard');
                    const data = await res.json();
                    if (data.success) {
                        this.stats = data.stats;
                        this.dipakai = data.dipakai;
                        this.lastUpdate = data.server_time;
                        this.liveIndicator = true;
                        this.updateChart();
                    }
                } catch (e) {
                    this.liveIndicator = false;
                } finally {
                    this.refreshing = false;
                }
            },

            forceRefresh() {
                this.fetchRealtime();
            },

            loadChart() {
                const ctx = document.getElementById('statusChart');
                if (!ctx) return;
                this.chart = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Tersedia', 'Terpakai', 'Rusak', 'Dalam Perbaikan'],
                        datasets: [{
                            data: [this.stats.tersedia, this.stats.terpakai, this.stats.rusak, this.stats.dalam_perbaikan],
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

            updateChart() {
                if (!this.chart) return;
                this.chart.data.datasets[0].data = [
                    this.stats.tersedia,
                    this.stats.terpakai,
                    this.stats.rusak,
                    this.stats.dalam_perbaikan
                ];
                this.chart.update('none');
            },

            openModal(type) {
                if (type === 'tambah') {
                    window.dispatchEvent(new CustomEvent('open-tambah-modal'));
                }
            },

            async detailGroup(kategori, jenis, type) {
                renderGroupModal(kategori, jenis, type, 1, '');
            },

            statusBadgeClass(status) {
                const map = {
                    'Tersedia': 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400 ring-1 ring-emerald-600/10 dark:ring-emerald-400/20',
                    'Terpakai': 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400 ring-1 ring-red-600/10 dark:ring-red-400/20',
                    'Rusak': 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400 ring-1 ring-amber-600/10 dark:ring-amber-400/20',
                    'Dalam Perbaikan': 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400 ring-1 ring-blue-600/10 dark:ring-blue-400/20',
                };
                return map[status] || 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 ring-1 ring-gray-600/10 dark:ring-gray-400/20';
            }
        }));
    });

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function darkSwal(opt) {
        var isDark = document.documentElement.classList.contains('dark');
        return Swal.fire(Object.assign({
            background: isDark ? '#1f2937' : '#ffffff',
            color: isDark ? '#d1d5db' : '#1f2937',
            confirmButtonColor: '#4f46e5',
        }, opt));
    }

    async function renderGroupModal(kategori, jenis, type, page, q) {
        window.dispatchEvent(new CustomEvent('loading-start'));
        const url = 'index.php?url=sparepart&action=list_by_group&kategori=' + encodeURIComponent(kategori) + '&jenis=' + encodeURIComponent(jenis) + '&type=' + encodeURIComponent(type) + '&page=' + (page || 1) + (q ? '&q=' + encodeURIComponent(q) : '');
        const res = await fetch(url);
        const data = await res.json();
        window.dispatchEvent(new CustomEvent('loading-end'));

        if (!data.success) {
            darkSwal({ icon: 'error', title: 'Error!', text: data.message || 'Gagal memuat data.', confirmButtonColor: '#4f46e5' });
            return;
        }

        const isAdmin = <?= isAdmin() ? 'true' : 'false' ?>;
        const s = data.stats || {};
        const curPage = data.page || 1;
        const searchQ = q || '';

        var esc = function(t) { var d = document.createElement('div'); d.textContent = t; return d.innerHTML; };

        // Summary badges
        let html = '<div class="flex flex-wrap gap-1.5 mb-2 justify-center">';
        html += '<span class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">Total ' + (s.total || data.items.length) + '</span>';
        html += '<span class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400">Tersedia ' + (s.tersedia || 0) + '</span>';
        html += '<span class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400">Terpakai ' + (s.terpakai || 0) + '</span>';
        html += '<span class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400">Rusak ' + (s.rusak || 0) + '</span>';
        html += '<span class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400">Perbaikan ' + (s.dalam_perbaikan || 0) + '</span>';
        html += '</div>';

        // Search
        html += '<div class="flex gap-2 mb-3">';
        html += '<input type="text" id="group-search-input" value="' + esc(searchQ) + '" placeholder="Cari SN atau merk..." class="flex-1 px-3 py-1.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg text-xs focus:ring-2 focus:ring-indigo-500 outline-none transition" onkeydown="if(event.key===\'Enter\'){var v=this.value;renderGroupModal(\'' + kategori + '\',\'' + jenis + '\',\'' + type + '\',1,v)}">';
        html += '<button onclick="renderGroupModal(\'' + kategori + '\',\'' + jenis + '\',\'' + type + '\',1,document.getElementById(\'group-search-input\').value)" class="px-3 py-1.5 bg-indigo-600 text-white rounded-lg text-xs hover:bg-indigo-700 transition font-medium"><i class="fa-solid fa-search"></i></button>';
        html += '</div>';

        // Table
        html += '<div class="overflow-x-auto max-h-80 overflow-y-auto border border-gray-200 dark:border-gray-700 rounded-lg">';
        html += '<table class="w-full text-xs">';
        html += '<thead class="sticky top-0 bg-gray-50 dark:bg-gray-700 z-10"><tr>';
        html += '<th class="px-2 py-2 text-left font-semibold">#</th>';
        html += '<th class="px-2 py-2 text-left font-semibold">SN</th>';
        html += '<th class="px-2 py-2 text-left font-semibold">Merk</th>';
        html += '<th class="px-2 py-2 text-left font-semibold">Status</th>';
        html += '<th class="px-2 py-2 text-left font-semibold">PIC</th>';
        html += '<th class="px-2 py-2 text-left font-semibold">Department</th>';
        html += '<th class="px-2 py-2 text-left font-semibold">Tanggal</th>';
        html += '<th class="px-2 py-2 text-left font-semibold hidden sm:table-cell">Keterangan</th>';
        html += '<th class="px-2 py-2 text-center font-semibold">Aksi</th>';
        html += '</tr></thead><tbody class="divide-y divide-gray-100 dark:divide-gray-700">';

        data.items.forEach(function(sp, idx) {
            var badgeClass = {
                'Tersedia': 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400',
                'Terpakai': 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400',
                'Rusak': 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400',
                'Dalam Perbaikan': 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400'
            }[sp.status] || 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300';

            var actionBtns = '';
            if (isAdmin) {
                actionBtns += '<button onclick="showHistory(' + sp.id + ')" class="p-1 text-xs bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 rounded hover:bg-blue-200 dark:hover:bg-blue-900/50 transition" title="Riwayat"><i class="fa-solid fa-clock-rotate-left"></i></button> ';
                actionBtns += '<button onclick="showEdit(' + sp.id + ')" class="p-1 text-xs bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 rounded hover:bg-amber-200 dark:hover:bg-amber-900/50 transition" title="Edit"><i class="fa-solid fa-pen"></i></button> ';
                actionBtns += '<button onclick="showHapus(' + sp.id + ')" class="p-1 text-xs bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded hover:bg-red-200 dark:hover:bg-red-900/50 transition" title="Hapus"><i class="fa-solid fa-trash"></i></button>';
            }

            var ket = sp.keterangan || '';
            if (ket.length > 40) ket = ket.substring(0, 40) + '...';

            html += '<tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">';
            html += '<td class="px-2 py-2 text-gray-500 dark:text-gray-400 font-mono">' + (((curPage - 1) * 20) + idx + 1) + '</td>';
            html += '<td class="px-2 py-2 font-mono text-gray-800 dark:text-gray-200 font-semibold">' + esc((sp.serial_number || '').replace(/^SN-/, '')) + '</td>';
            html += '<td class="px-2 py-2 text-gray-600 dark:text-gray-400">' + esc(sp.merk || '-') + '</td>';
            html += '<td class="px-2 py-2"><span class="inline-flex items-center px-1.5 py-0.5 text-xs font-semibold rounded-full ' + badgeClass + '">' + esc(sp.status) + '</span></td>';
            html += '<td class="px-2 py-2 text-gray-600 dark:text-gray-400">' + esc(sp.pic || '-') + '</td>';
            html += '<td class="px-2 py-2 text-gray-600 dark:text-gray-400">' + esc(sp.department || '-') + '</td>';
            html += '<td class="px-2 py-2 text-gray-500 dark:text-gray-400 whitespace-nowrap">' + esc(sp.tanggal) + '</td>';
            html += '<td class="px-2 py-2 text-gray-400 dark:text-gray-500 hidden sm:table-cell max-w-[120px] truncate" title="' + esc(sp.keterangan || '') + '">' + esc(ket || '-') + '</td>';
            html += '<td class="px-2 py-2 text-center whitespace-nowrap">' + actionBtns + '</td>';
            html += '</tr>';
        });

        html += '</tbody></table></div>';

        // Pagination
        if (data.totalPages > 1) {
            html += '<div class="flex items-center justify-between mt-3 text-xs text-gray-500 dark:text-gray-400">';
            html += '<span>Halaman ' + curPage + ' dari ' + data.totalPages + ' (' + data.totalItems + ' item)</span>';
            html += '<div class="flex gap-1">';
            if (curPage > 1) {
                html += '<button onclick="renderGroupModal(\'' + kategori + '\',\'' + jenis + '\',\'' + type + '\',' + (curPage - 1) + ',\'' + esc(searchQ) + '\')" class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition"><i class="fa-solid fa-chevron-left"></i></button>';
            }
            for (var p = Math.max(1, curPage - 2); p <= Math.min(data.totalPages, curPage + 2); p++) {
                html += '<button onclick="renderGroupModal(\'' + kategori + '\',\'' + jenis + '\',\'' + type + '\',' + p + ',\'' + esc(searchQ) + '\')" class="px-2 py-1 rounded transition ' + (p === curPage ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600') + '">' + p + '</button>';
            }
            if (curPage < data.totalPages) {
                html += '<button onclick="renderGroupModal(\'' + kategori + '\',\'' + jenis + '\',\'' + type + '\',' + (curPage + 1) + ',\'' + esc(searchQ) + '\')" class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition"><i class="fa-solid fa-chevron-right"></i></button>';
            }
            html += '</div></div>';
        }

        darkSwal({
            title: data.kategori + ' — ' + data.jenis + (data.type ? ' (' + data.type + ')' : ''),
            html: html,
            confirmButtonColor: '#4f46e5',
            confirmButtonText: 'Tutup',
            width: '850px'
        });
    }

    async function showDetail(id) {
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
    }

    async function showHistory(id) {
        window.dispatchEvent(new CustomEvent('loading-start'));
        const res = await fetch('index.php?url=sparepart&action=show&id=' + id);
        const data = await res.json();
        window.dispatchEvent(new CustomEvent('loading-end'));
        if (data.success) {
            var allLogs = data.logs || [];
            var perPage = 10;
            var currentPage = 1;

            function renderHistory() {
                var totalPages = Math.max(1, Math.ceil(allLogs.length / perPage));
                if (currentPage > totalPages) currentPage = totalPages;
                var start = (currentPage - 1) * perPage;
                var pageLogs = allLogs.slice(start, start + perPage);

                var html = '';
                if (allLogs.length === 0) {
                    html = '<p class="text-center text-gray-400 dark:text-gray-500 py-4">Belum ada riwayat.</p>';
                } else {
                    html += '<div class="space-y-1.5 text-sm max-h-96 overflow-y-auto">';
                    pageLogs.forEach(function(l) {
                        var badge = 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300';
                        if (l.tipe_transaksi === 'Barang Masuk') badge = 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400';
                        else if (l.tipe_transaksi === 'Barang Keluar') badge = 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400';
                        else if (l.tipe_transaksi === 'Ubah Status') badge = 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400';
                        else if (l.tipe_transaksi === 'Dalam Perbaikan') badge = 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400';
                        html += '<div class="flex items-center justify-between p-2.5 rounded-lg bg-gray-50 dark:bg-gray-700/50">';
                        html += '<div class="flex items-center gap-2"><span class="px-1.5 py-0.5 text-xs font-semibold rounded-full ' + badge + '">' + escapeHtml(l.tipe_transaksi) + '</span>';
                        html += '<span class="text-gray-600 dark:text-gray-300">' + escapeHtml(l.pic_penerima || l.user_name || '-') + (l.department ? ' (' + escapeHtml(l.department) + ')' : '') + '</span></div>';
                        html += '<span class="text-xs text-gray-400">' + (l.waktu || l.tanggal) + '</span></div>';
                    });
                    html += '</div>';

                    html += '<div class="flex items-center justify-center gap-3 mt-4 text-sm">';
                    html += '<button onclick="window._histPage=' + currentPage + ';window._histPage--;showHistoryRefresh(' + id + ')" class="px-3 py-1 rounded bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-300 dark:hover:bg-gray-500 transition' + (currentPage <= 1 ? ' opacity-50 pointer-events-none' : '') + '">« Prev</button>';
                    html += '<span class="text-gray-500 dark:text-gray-400">Halaman</span>';
                    html += '<input type="number" id="histPageInput" value="' + currentPage + '" min="1" max="' + totalPages + '" class="w-14 text-center border rounded px-1 py-0.5 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">';
                    html += '<button onclick="window._histPage=parseInt(document.getElementById(\'histPageInput\').value)||1;showHistoryRefresh(' + id + ')" class="px-3 py-1 rounded bg-indigo-600 text-white hover:bg-indigo-700 transition text-sm">Run</button>';
                    html += '<button onclick="window._histPage=' + currentPage + ';window._histPage++;showHistoryRefresh(' + id + ')" class="px-3 py-1 rounded bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-300 dark:hover:bg-gray-500 transition' + (currentPage >= totalPages ? ' opacity-50 pointer-events-none' : '') + '">Next »</button>';
                    html += '<span class="text-gray-400 text-xs">' + allLogs.length + ' total</span>';
                    html += '</div>';
                }

                darkSwal({
                    title: 'Riwayat Sparepart #' + id,
                    html: html,
                    confirmButtonColor: '#4f46e5',
                    confirmButtonText: 'Tutup',
                    didOpen: function() {
                        var inp = document.getElementById('histPageInput');
                        if (inp) inp.addEventListener('keydown', function(e) { if (e.key === 'Enter') { window._histPage = parseInt(this.value) || 1; showHistoryRefresh(id); } });
                    }
                });
            }

            window.showHistoryRefresh = function(i) {
                currentPage = window._histPage || 1;
                renderHistory();
            };

            renderHistory();
        }
    }

    async function showEdit(id) {
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
                            <select name="kategori" id="edit-kategori" class="w-full px-3 py-2 border rounded-lg text-sm" onchange="filterEditJenis(this.value, document.getElementById('edit-jenis').value, document.getElementById('edit-type').value)">
                                <option value="Aset" ${data.data.kategori === 'Aset' ? 'selected' : ''}>Aset</option>
                                <option value="Non-Aset" ${data.data.kategori === 'Non-Aset' ? 'selected' : ''}>Non-Aset</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-medium">Jenis Sparepart</label>
                            <select name="jenis_sparepart" id="edit-jenis" class="w-full px-3 py-2 border rounded-lg text-sm" onchange="filterEditType(document.getElementById('edit-kategori').value, this.value, document.getElementById('edit-type').value)">
                                <option value="">Pilih Jenis</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-medium">Type</label>
                            <select name="type_sparepart" id="edit-type" class="w-full px-3 py-2 border rounded-lg text-sm">
                                <option value="">Pilih Type</option>
                            </select>
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
                didOpen: () => {
                    filterEditJenis(data.data.kategori, data.data.jenis_sparepart, data.data.type_sparepart || '');
                },
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
    }

    function showHapus(id) {
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
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.17.2"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
