<?php

$page_title = 'Dashboard';
$require_admin = false;
require_once __DIR__ . '/../../helpers/auth.php';

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
        SELECT id, jenis_sparepart, type_sparepart, merk, serial_number, kategori, status, pic, department, tanggal, keterangan, image
        FROM spareparts WHERE deleted_at IS NULL AND status = 'Terpakai' AND updated_at >= NOW() - INTERVAL 24 HOUR
        ORDER BY updated_at DESC
    ")->fetchAll();
} else {
    $stmt = $db->prepare("
        SELECT id, jenis_sparepart, type_sparepart, merk, serial_number, kategori, status, pic, department, tanggal, keterangan, image
        FROM spareparts WHERE deleted_at IS NULL AND status = 'Terpakai' AND pic = ? AND updated_at >= NOW() - INTERVAL 24 HOUR
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

// Sorting
$validSorts = array(
    'kategori' => 's.kategori',
    'jenis' => 's.jenis_sparepart',
    'type' => 's.type_sparepart',
    'total' => 'total',
    'tersedia' => 'tersedia',
    'terpakai' => 'terpakai',
    'rusak' => 'rusak',
    'perbaikan' => 'dalam_perbaikan',
    'latest' => 'latest',
    'updated' => 'last_updated'
);
$sortKey = isset($_GET['sort']) && isset($validSorts[$_GET['sort']]) ? $_GET['sort'] : 'latest';
$sortDir = strtoupper(isset($_GET['dir']) && $_GET['dir'] === 'ASC' ? 'ASC' : 'DESC');
$sortCol = $validSorts[$sortKey];

// Group by kategori + jenis_sparepart + type_sparepart
$groupQuery = "SELECT
    s.kategori, s.jenis_sparepart, s.type_sparepart,
    SUM(s.quantity) as total,
    SUM(CASE WHEN s.status = 'Tersedia' THEN s.quantity ELSE 0 END) as tersedia,
    SUM(CASE WHEN s.status = 'Terpakai' THEN s.quantity ELSE 0 END) as terpakai,
    SUM(CASE WHEN s.status = 'Rusak' THEN s.quantity ELSE 0 END) as rusak,
    SUM(CASE WHEN s.status = 'Dalam Perbaikan' THEN s.quantity ELSE 0 END) as dalam_perbaikan,
    GROUP_CONCAT(DISTINCT s.merk ORDER BY s.merk SEPARATOR ', ') as merk_list,
    MIN(s.image) as thumbnail_image,
    MAX(s.updated_at) as last_updated,
    SUBSTRING_INDEX(GROUP_CONCAT(s.pic ORDER BY s.updated_at DESC), ',', 1) as last_pic,
    MAX(s.created_at) as latest
FROM spareparts s
$where
GROUP BY s.kategori, s.jenis_sparepart, s.type_sparepart
ORDER BY $sortCol $sortDir";

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

// Sort URL helper
function sortUrl($key, $currentSort, $currentDir) {
    $newDir = ($currentSort === $key && $currentDir === 'DESC') ? 'ASC' : 'DESC';
    $params = $_GET;
    $params['sort'] = $key;
    $params['dir'] = $newDir;
    unset($params['url']);
    return '?' . http_build_query($params);
}
function sortIcon($key, $currentSort, $currentDir) {
    if ($currentSort !== $key) return '<i class="fa-solid fa-sort text-[9px] ml-1 opacity-30"></i>';
    $arrow = $currentDir === 'DESC' ? 'fa-sort-down' : 'fa-sort-up';
    return '<i class="fa-solid ' . $arrow . ' text-[9px] ml-1 text-indigo-500"></i>';
}

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
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
                Barang yang Dipakai dalam 24 Jam Terakhir
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
                    <div class="mt-2 pt-2 border-t border-gray-100 dark:border-gray-700 space-y-2">
                        <template x-if="item.image">
                            <img :src="'<?= APP_URL ?>/' + item.image"
                                 class="w-full h-32 object-cover rounded-lg border border-gray-200 dark:border-gray-600 cursor-zoom-in hover:opacity-90 transition"
                                 @click="toggleImageZoom('<?= APP_URL ?>/' + item.image, item.jenis_sparepart)"
                                 loading="lazy">
                        </template>
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
            <table class="w-full text-sm dashboard-sortable-table">
                <thead>
                    <tr class="bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-gray-700/80 dark:to-gray-700/50 text-gray-600 dark:text-gray-300">
                        <th class="px-4 py-3.5 text-left font-semibold text-[11px] uppercase tracking-wider w-10 hidden sm:table-cell"></th>
                        <th class="px-4 py-3.5 text-left font-semibold text-[11px] uppercase tracking-wider"><a href="<?= sortUrl('kategori', $sortKey, $sortDir) ?>" class="hover:text-indigo-600 transition inline-flex items-center">Kategori<?= sortIcon('kategori', $sortKey, $sortDir) ?></a></th>
                        <th class="px-4 py-3.5 text-left font-semibold text-[11px] uppercase tracking-wider"><a href="<?= sortUrl('jenis', $sortKey, $sortDir) ?>" class="hover:text-indigo-600 transition inline-flex items-center">Jenis<?= sortIcon('jenis', $sortKey, $sortDir) ?></a></th>
                        <th class="px-4 py-3.5 text-left font-semibold text-[11px] uppercase tracking-wider hidden lg:table-cell"><a href="<?= sortUrl('type', $sortKey, $sortDir) ?>" class="hover:text-indigo-600 transition inline-flex items-center">Type<?= sortIcon('type', $sortKey, $sortDir) ?></a></th>
                        <th class="px-4 py-3.5 text-left font-semibold text-[11px] uppercase tracking-wider hidden md:table-cell"><a href="<?= sortUrl('latest', $sortKey, $sortDir) ?>" class="hover:text-indigo-600 transition inline-flex items-center">Merk<?= sortIcon('latest', $sortKey, $sortDir) ?></a></th>
                        <th class="px-4 py-3.5 text-center font-semibold text-[11px] uppercase tracking-wider"><a href="<?= sortUrl('total', $sortKey, $sortDir) ?>" class="hover:text-indigo-600 transition inline-flex items-center justify-center">Total<?= sortIcon('total', $sortKey, $sortDir) ?></a></th>
                        <th class="px-4 py-3.5 text-center font-semibold text-[11px] uppercase tracking-wider"><a href="<?= sortUrl('tersedia', $sortKey, $sortDir) ?>" class="hover:text-indigo-600 transition inline-flex items-center justify-center gap-1"><span class="inline-block w-2 h-2 rounded-full bg-emerald-500"></span>T<?= sortIcon('tersedia', $sortKey, $sortDir) ?></a></th>
                        <th class="px-4 py-3.5 text-center font-semibold text-[11px] uppercase tracking-wider"><a href="<?= sortUrl('terpakai', $sortKey, $sortDir) ?>" class="hover:text-indigo-600 transition inline-flex items-center justify-center gap-1"><span class="inline-block w-2 h-2 rounded-full bg-red-500"></span>P<?= sortIcon('terpakai', $sortKey, $sortDir) ?></a></th>
                        <th class="px-4 py-3.5 text-center font-semibold text-[11px] uppercase tracking-wider"><a href="<?= sortUrl('rusak', $sortKey, $sortDir) ?>" class="hover:text-indigo-600 transition inline-flex items-center justify-center gap-1"><span class="inline-block w-2 h-2 rounded-full bg-amber-500"></span>R<?= sortIcon('rusak', $sortKey, $sortDir) ?></a></th>
                        <th class="px-4 py-3.5 text-center font-semibold text-[11px] uppercase tracking-wider"><a href="<?= sortUrl('perbaikan', $sortKey, $sortDir) ?>" class="hover:text-indigo-600 transition inline-flex items-center justify-center gap-1"><span class="inline-block w-2 h-2 rounded-full bg-blue-500"></span>PR<?= sortIcon('perbaikan', $sortKey, $sortDir) ?></a></th>
                        <th class="px-4 py-3.5 text-center font-semibold text-[11px] uppercase tracking-wider hidden sm:table-cell">PIC</th>
                        <th class="px-4 py-3.5 text-center font-semibold text-[11px] uppercase tracking-wider hidden lg:table-cell"><a href="<?= sortUrl('updated', $sortKey, $sortDir) ?>" class="hover:text-indigo-600 transition inline-flex items-center justify-center">Update<?= sortIcon('updated', $sortKey, $sortDir) ?></a></th>
                        <th class="px-4 py-3.5 text-center font-semibold text-[11px] uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    <?php foreach ($groups as $i => $g): ?>
                    <?php
                        $typeLabel = $g['type_sparepart'] ?: '-';
                        $typeKey = urlencode(isset($g['type_sparepart']) ? $g['type_sparepart'] : '');
                        $thumbSrc = !empty($g['thumbnail_image']) ? '../' . $g['thumbnail_image'] : '';
                        $lastUpdate = $g['last_updated'] ? date('d/m/Y', strtotime($g['last_updated'])) : '-';
                        $lastPic = $g['last_pic'] ?: '-';
                    ?>
                    <tr class="group-row hover:bg-indigo-50/80 dark:hover:bg-indigo-900/15 transition-all duration-150 cursor-pointer <?= $i % 2 === 0 ? 'bg-white dark:bg-gray-800' : 'bg-gray-50/60 dark:bg-gray-700/20' ?>"
                        onclick="event.preventDefault(); document.querySelector('[data-detail-btn=\'<?= $i ?>\']').click();">
                        <td class="px-3 py-3 text-center hidden sm:table-cell no-label">
                            <?php if ($thumbSrc): ?>
                            <img src="<?= escape($thumbSrc) ?>" class="w-9 h-9 rounded-lg object-cover border border-gray-200 dark:border-gray-600 shadow-sm cursor-zoom-in hover:scale-110 transition" alt="" loading="lazy" onclick="event.stopPropagation(); toggleImageZoom('<?= escape('../' . $g['thumbnail_image']) ?>', '<?= escape($g['jenis_sparepart']) ?>')">
                            <?php else: ?>
                            <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600 flex items-center justify-center">
                                <i class="fa-solid fa-box text-gray-400 dark:text-gray-500 text-xs"></i>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td data-label="Kategori" class="px-4 py-3"><span class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 ring-1 ring-gray-200 dark:ring-gray-600"><?= escape($g['kategori']) ?></span></td>
                        <td data-label="Jenis" class="px-4 py-3 font-bold text-gray-900 dark:text-white text-[13px]"><?= escape($g['jenis_sparepart']) ?></td>
                        <td data-label="Type" class="px-4 py-3 text-gray-600 dark:text-gray-400 text-xs hidden lg:table-cell"><?= escape($typeLabel) ?></td>
                        <td data-label="Merk" class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs hidden md:table-cell"><?= escape($g['merk_list'] ?: '-') ?></td>
                        <td data-label="Total" class="px-4 py-3 text-center">
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-gray-100 dark:bg-gray-700 text-sm font-extrabold text-gray-800 dark:text-gray-200"><?= $g['total'] ?></span>
                        </td>
                        <td data-label="Tersedia" class="px-4 py-3 text-center">
                            <?php if ($g['tersedia'] > 0): ?>
                            <span class="inline-flex items-center px-2 py-0.5 text-xs font-bold rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-400 ring-1 ring-emerald-600/20 dark:ring-emerald-400/30"><i class="fa-solid fa-check mr-1 text-[8px]"></i><?= $g['tersedia'] ?></span>
                            <?php else: ?>
                            <span class="text-gray-300 dark:text-gray-600 text-xs font-bold">0</span>
                            <?php endif; ?>
                        </td>
                        <td data-label="Terpakai" class="px-4 py-3 text-center">
                            <?php if ($g['terpakai'] > 0): ?>
                            <span class="inline-flex items-center px-2 py-0.5 text-xs font-bold rounded-full bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-400 ring-1 ring-red-600/20 dark:ring-red-400/30"><i class="fa-solid fa-arrow-right-from-bracket mr-1 text-[8px]"></i><?= $g['terpakai'] ?></span>
                            <?php else: ?>
                            <span class="text-gray-300 dark:text-gray-600 text-xs font-bold">0</span>
                            <?php endif; ?>
                        </td>
                        <td data-label="Rusak" class="px-4 py-3 text-center">
                            <?php if ($g['rusak'] > 0): ?>
                            <span class="inline-flex items-center px-2 py-0.5 text-xs font-bold rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-400 ring-1 ring-amber-600/20 dark:ring-amber-400/30"><i class="fa-solid fa-triangle-exclamation mr-1 text-[8px]"></i><?= $g['rusak'] ?></span>
                            <?php else: ?>
                            <span class="text-gray-300 dark:text-gray-600 text-xs font-bold">0</span>
                            <?php endif; ?>
                        </td>
                        <td data-label="Perbaikan" class="px-4 py-3 text-center">
                            <?php if ($g['dalam_perbaikan'] > 0): ?>
                            <span class="inline-flex items-center px-2 py-0.5 text-xs font-bold rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-400 ring-1 ring-blue-600/20 dark:ring-blue-400/30"><i class="fa-solid fa-wrench mr-1 text-[8px]"></i><?= $g['dalam_perbaikan'] ?></span>
                            <?php else: ?>
                            <span class="text-gray-300 dark:text-gray-600 text-xs font-bold">0</span>
                            <?php endif; ?>
                        </td>
                        <td data-label="PIC" class="px-4 py-3 text-center text-xs text-gray-600 dark:text-gray-400 hidden sm:table-cell font-medium">
                            <span class="truncate max-w-[100px] block" title="<?= escape($lastPic) ?>"><?= escape($lastPic) ?></span>
                        </td>
                        <td data-label="Update" class="px-4 py-3 text-center text-xs text-gray-500 dark:text-gray-500 hidden lg:table-cell font-mono">
                            <?= $lastUpdate ?>
                        </td>
                        <td data-label="Aksi" class="px-4 py-3 text-center no-label">
                            <div class="flex items-center justify-center gap-1" onclick="event.stopPropagation();">
                                <button data-detail-btn="<?= $i ?>" @click="detailGroup('<?= escape($g['kategori']) ?>', '<?= escape($g['jenis_sparepart']) ?>', '<?= $typeKey ?>')" title="Lihat detail" class="p-2 text-xs bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-900/50 transition-all hover:scale-110">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                                <button @click="detailGroup('<?= escape($g['kategori']) ?>', '<?= escape($g['jenis_sparepart']) ?>', '<?= $typeKey ?>')" title="Lihat detail" class="p-2 text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-all hover:scale-110">
                                    <i class="fa-solid fa-arrow-right"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($groups)): ?>
                    <tr>
                        <td colspan="13" class="px-4 py-16 text-center">
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
    open: false,
    addFotoPreview: '',
    addFotoError: '',
    MAX_FILE_SIZE: 2 * 1024 * 1024,
    handleAddFoto(e) {
        var file = e.target.files[0];
        if (!file) { this.addFotoPreview = ''; return; }
        if (file.size > this.MAX_FILE_SIZE) {
            this.addFotoError = 'Ukuran foto ' + (file.size / (1024 * 1024)).toFixed(2) + 'MB melebihi batas 2MB.';
            e.target.value = '';
            this.addFotoPreview = '';
            return;
        }
        this.addFotoError = '';
        var self = this;
        var reader = new FileReader();
        reader.onload = function(ev) { self.addFotoPreview = ev.target.result; };
        reader.readAsDataURL(file);
    }
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
        <form method="POST" action="index.php?url=sparepart&action=store" class="p-6 space-y-4" enctype="multipart/form-data">
            <?= csrf() ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kategori <span class="text-red-500">*</span></label>
                    <select name="kategori" id="add-kategori" required onchange="toggleAddQty(); toggleAddSerial(); filterAddJenis()"
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
                <div id="add-serial-wrap">
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
                <div class="col-span-1 sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Foto <span class="text-gray-400 font-normal">(Opsional)</span></label>
                    <div class="flex items-start gap-4">
                        <div class="flex-1">
                            <input type="file" name="image" accept="image/jpeg,image/png,image/webp"
                                   @change="handleAddFoto($event)"
                                   class="w-full text-sm text-gray-500 dark:text-gray-400 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-indigo-900/30 dark:file:text-indigo-400 dark:hover:file:bg-indigo-900/50 transition">
                        </div>
                        <div x-show="addFotoPreview" x-cloak class="shrink-0">
                            <img :src="addFotoPreview" class="w-16 h-16 rounded-lg object-cover border border-gray-200 dark:border-gray-600 shadow-sm">
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 mt-1">Format: JPG, PNG, WebP. Maksimal 2MB.</p>
                    <p x-show="addFotoError" x-cloak class="text-xs text-red-500 dark:text-red-400 mt-1 flex items-center gap-1"><i class="fa-solid fa-circle-exclamation"></i><span x-text="addFotoError"></span></p>
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

    function toggleAddSerial() {
        const kat = document.getElementById('add-kategori').value;
        const wrap = document.getElementById('add-serial-wrap');
        const sn = wrap.querySelector('input[name="serial_number"]');
        if (kat === 'Non-Aset') {
            wrap.style.display = 'none';
            sn.required = false;
            sn.removeAttribute('required');
        } else {
            wrap.style.display = 'block';
            sn.required = true;
        }
    }
    toggleAddSerial();

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
        try {
            var res = await fetch(url);
            var data = await res.json();
        } catch(e) {
            window.dispatchEvent(new CustomEvent('loading-end'));
            darkSwal({ icon: 'error', title: 'Koneksi Gagal', text: 'Tidak bisa memuat data. Periksa koneksi internet.', confirmButtonColor: '#4f46e5' });
            return;
        }
        window.dispatchEvent(new CustomEvent('loading-end'));

        if (!data.success) {
            darkSwal({ icon: 'error', title: 'Error!', text: data.message || 'Gagal memuat data.', confirmButtonColor: '#4f46e5' });
            return;
        }

        const isAdmin = <?= isAdmin() ? 'true' : 'false' ?>;
        const s = data.stats || {};
        const curPage = data.page || 1;
        const searchQ = q || '';
        const APP_URL = '<?= APP_URL ?>';

        var esc = function(t) { var d = document.createElement('div'); d.textContent = t; return d.innerHTML; };

        function statusBadge(status) {
            var map = {
                'Tersedia': 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-400 ring-1 ring-emerald-600/20',
                'Terpakai': 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-400 ring-1 ring-red-600/20',
                'Rusak': 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-400 ring-1 ring-amber-600/20',
                'Dalam Perbaikan': 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-400 ring-1 ring-blue-600/20'
            };
            var icons = { 'Tersedia': 'fa-check', 'Terpakai': 'fa-arrow-right-from-bracket', 'Rusak': 'fa-triangle-exclamation', 'Dalam Perbaikan': 'fa-wrench' };
            var cls = map[status] || 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300';
            var icon = icons[status] || 'fa-circle';
            return '<span class="inline-flex items-center px-2 py-0.5 text-[11px] font-bold rounded-full ' + cls + '"><i class="fa-solid ' + icon + ' mr-1 text-[8px]"></i>' + esc(status) + '</span>';
        }

        // Header: badges summary
        let html = '<div class="flex flex-wrap gap-2 mb-3">';
        html += '<span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-xl bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 shadow-sm"><i class="fa-solid fa-cubes text-[10px]"></i>Total ' + (s.total || data.items.length) + '</span>';
        html += '<span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-xl bg-emerald-50 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400 ring-1 ring-emerald-600/20"><i class="fa-solid fa-check-circle text-[10px]"></i>' + (s.tersedia || 0) + ' Tersedia</span>';
        html += '<span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-xl bg-red-50 text-red-700 dark:bg-red-900/40 dark:text-red-400 ring-1 ring-red-600/20"><i class="fa-solid fa-arrow-right-from-bracket text-[10px]"></i>' + (s.terpakai || 0) + ' Terpakai</span>';
        if ((s.rusak || 0) > 0) html += '<span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-xl bg-amber-50 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400 ring-1 ring-amber-600/20"><i class="fa-solid fa-triangle-exclamation text-[10px]"></i>' + (s.rusak || 0) + ' Rusak</span>';
        if ((s.dalam_perbaikan || 0) > 0) html += '<span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-xl bg-blue-50 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400 ring-1 ring-blue-600/20"><i class="fa-solid fa-wrench text-[10px]"></i>' + (s.dalam_perbaikan || 0) + ' Perbaikan</span>';
        html += '</div>';

        // Search
        html += '<div class="flex gap-2 mb-3">';
        html += '<div class="relative flex-1"><i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>';
        html += '<input type="text" id="group-search-input" value="' + esc(searchQ) + '" placeholder="Cari SN, merk, PIC..." class="w-full pl-8 pr-3 py-2 border border-gray-200 dark:border-gray-600 dark:bg-gray-700/50 dark:text-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 outline-none transition" onkeydown="if(event.key===\'Enter\'){var v=this.value;renderGroupModal(\'' + esc(kategori) + '\',\'' + esc(jenis) + '\',\'' + esc(type) + '\',1,v)}">';
        html += '</div>';
        html += '<button onclick="renderGroupModal(\'' + esc(kategori) + '\',\'' + esc(jenis) + '\',\'' + esc(type) + '\',1,document.getElementById(\'group-search-input\').value)" class="px-4 py-2 bg-indigo-600 text-white rounded-xl text-sm hover:bg-indigo-700 transition font-semibold shadow-sm"><i class="fa-solid fa-search mr-1"></i>Cari</button>';
        html += '</div>';

        // Table
        html += '<div class="overflow-x-auto max-h-[28rem] overflow-y-auto border border-gray-200 dark:border-gray-700 rounded-xl">';
        html += '<table class="w-full text-sm">';
        html += '<thead class="sticky top-0 z-10"><tr class="bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-gray-700 dark:to-gray-600/80">';
        html += '<th class="px-3 py-3 text-center font-semibold text-[10px] uppercase tracking-wider text-gray-500 dark:text-gray-400 w-10">#</th>';
        html += '<th class="px-3 py-3 text-center font-semibold text-[10px] uppercase tracking-wider text-gray-500 dark:text-gray-400 w-12 hidden sm:table-cell">Foto</th>';
        html += '<th class="px-3 py-3 text-left font-semibold text-[10px] uppercase tracking-wider text-gray-500 dark:text-gray-400">SN / Qty</th>';
        html += '<th class="px-3 py-3 text-left font-semibold text-[10px] uppercase tracking-wider text-gray-500 dark:text-gray-400 hidden md:table-cell">Merk</th>';
        html += '<th class="px-3 py-3 text-center font-semibold text-[10px] uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</th>';
        html += '<th class="px-3 py-3 text-left font-semibold text-[10px] uppercase tracking-wider text-gray-500 dark:text-gray-400 hidden lg:table-cell">PIC</th>';
        html += '<th class="px-3 py-3 text-left font-semibold text-[10px] uppercase tracking-wider text-gray-500 dark:text-gray-400 hidden lg:table-cell">Dept</th>';
        html += '<th class="px-3 py-3 text-center font-semibold text-[10px] uppercase tracking-wider text-gray-500 dark:text-gray-400 hidden xl:table-cell">Update</th>';
        html += '<th class="px-3 py-3 text-center font-semibold text-[10px] uppercase tracking-wider text-gray-500 dark:text-gray-400">Aksi</th>';
        html += '</tr></thead><tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">';

        data.items.forEach(function(sp, idx) {
            var rowNum = ((curPage - 1) * 20) + idx + 1;
            var snDisplay = sp.serial_number ? '<span class="font-mono font-semibold text-gray-800 dark:text-gray-200">' + esc(sp.serial_number.replace(/^SN-/, '')) + '</span>' : '<span class="text-xs text-gray-400 italic font-medium bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded">qty: ' + sp.quantity + '</span>';
            var thumbHtml = '';
            if (sp.image) {
                thumbHtml = '<img src="' + APP_URL + '/' + esc(sp.image) + '" class="w-9 h-9 rounded-lg object-cover border border-gray-200 dark:border-gray-600 shadow-sm cursor-zoom-in hover:scale-110 transition" loading="lazy" alt="" onclick="event.stopPropagation(); toggleImageZoom(\'' + APP_URL + '/' + esc(sp.image) + '\', \'' + esc(sp.jenis_sparepart || '') + '\')">';
            } else {
                thumbHtml = '<div class="w-9 h-9 rounded-lg bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600 flex items-center justify-center"><i class="fa-solid fa-box text-gray-400 text-xs"></i></div>';
            }
            var rowBg = idx % 2 === 0 ? 'bg-white dark:bg-gray-800' : 'bg-gray-50/50 dark:bg-gray-700/20';

            var actionBtns = '<div class="flex items-center justify-center gap-1">';
            actionBtns += '<button onclick="showDetail(' + sp.id + ')" class="p-1.5 text-xs bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-900/50 transition" title="Detail"><i class="fa-solid fa-circle-info"></i></button>';
            if (isAdmin) {
                actionBtns += '<button onclick="showHistory(' + sp.id + ')" class="p-1.5 text-xs bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 rounded-lg hover:bg-blue-200 dark:hover:bg-blue-900/50 transition" title="Riwayat"><i class="fa-solid fa-clock-rotate-left"></i></button>';
                actionBtns += '<button onclick="showEdit(' + sp.id + ')" class="p-1.5 text-xs bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 rounded-lg hover:bg-amber-200 dark:hover:bg-amber-900/50 transition" title="Edit"><i class="fa-solid fa-pen"></i></button>';
                actionBtns += '<button onclick="showHapus(' + sp.id + ')" class="p-1.5 text-xs bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-lg hover:bg-red-200 dark:hover:bg-red-900/50 transition" title="Hapus"><i class="fa-solid fa-trash"></i></button>';
            }
            actionBtns += '</div>';

            var updateDate = sp.updated_at ? new Date(sp.updated_at).toLocaleDateString('id-ID', {day:'2-digit',month:'short',year:'numeric'}) : '-';

            html += '<tr class="hover:bg-indigo-50/60 dark:hover:bg-indigo-900/10 transition ' + rowBg + '">';
            html += '<td class="px-3 py-3 text-center text-gray-400 dark:text-gray-500 font-mono text-xs">' + rowNum + '</td>';
            html += '<td class="px-3 py-3 text-center hidden sm:table-cell">' + thumbHtml + '</td>';
            html += '<td class="px-3 py-3">' + snDisplay + '</td>';
            html += '<td class="px-3 py-3 text-gray-600 dark:text-gray-400 text-xs hidden md:table-cell">' + esc(sp.merk || '-') + '</td>';
            html += '<td class="px-3 py-3 text-center">' + statusBadge(sp.status) + '</td>';
            html += '<td class="px-3 py-3 text-gray-600 dark:text-gray-400 text-xs hidden lg:table-cell font-medium">' + esc(sp.pic || '-') + '</td>';
            html += '<td class="px-3 py-3 text-gray-500 dark:text-gray-500 text-xs hidden lg:table-cell">' + esc(sp.department || '-') + '</td>';
            html += '<td class="px-3 py-3 text-center text-[11px] text-gray-400 dark:text-gray-500 font-mono hidden xl:table-cell">' + updateDate + '</td>';
            html += '<td class="px-3 py-3">' + actionBtns + '</td>';
            html += '</tr>';
        });

        if (data.items.length === 0) {
            html += '<tr><td colspan="9" class="px-4 py-12 text-center"><div class="flex flex-col items-center gap-2 text-gray-400"><i class="fa-solid fa-box-open text-3xl opacity-50"></i><p class="text-sm font-medium">Tidak ada item ditemukan.</p></div></td></tr>';
        }

        html += '</tbody></table></div>';

        // Pagination
        if (data.totalPages > 1) {
            html += '<div class="flex items-center justify-between mt-3 text-xs text-gray-500 dark:text-gray-400">';
            html += '<span class="font-medium">' + data.totalItems + ' item | Hal. ' + curPage + '/' + data.totalPages + '</span>';
            html += '<div class="flex gap-1">';
            if (curPage > 1) {
                html += '<button onclick="renderGroupModal(\'' + esc(kategori) + '\',\'' + esc(jenis) + '\',\'' + esc(type) + '\',' + (curPage - 1) + ',\'' + esc(searchQ) + '\')" class="px-2.5 py-1 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition font-medium"><i class="fa-solid fa-chevron-left text-[10px]"></i></button>';
            }
            for (var p = Math.max(1, curPage - 2); p <= Math.min(data.totalPages, curPage + 2); p++) {
                html += '<button onclick="renderGroupModal(\'' + esc(kategori) + '\',\'' + esc(jenis) + '\',\'' + esc(type) + '\',' + p + ',\'' + esc(searchQ) + '\')" class="w-7 h-7 rounded-lg text-xs font-bold transition ' + (p === curPage ? 'bg-indigo-600 text-white shadow-md shadow-indigo-500/30' : 'bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300') + '">' + p + '</button>';
            }
            if (curPage < data.totalPages) {
                html += '<button onclick="renderGroupModal(\'' + esc(kategori) + '\',\'' + esc(jenis) + '\',\'' + esc(type) + '\',' + (curPage + 1) + ',\'' + esc(searchQ) + '\')" class="px-2.5 py-1 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition font-medium"><i class="fa-solid fa-chevron-right text-[10px]"></i></button>';
            }
            html += '</div></div>';
        }

        darkSwal({
            title: '<span class="flex items-center gap-2"><span class="w-8 h-8 rounded-lg bg-indigo-100 dark:bg-indigo-900/40 flex items-center justify-center"><i class="fa-solid fa-layer-group text-indigo-600 dark:text-indigo-400 text-sm"></i></span><span>' + esc(data.kategori) + ' — ' + esc(data.jenis) + (data.type && data.type !== '-' ? ' <span class="text-sm font-normal text-gray-400">(' + esc(data.type) + ')</span>' : '') + '</span></span>',
            html: html,
            confirmButtonColor: '#4f46e5',
            confirmButtonText: '<i class="fa-solid fa-xmark mr-1"></i>Tutup',
            width: '900px',
            customClass: { popup: 'detail-group-modal' }
        });
    }

    async function showDetail(id) {
        window.dispatchEvent(new CustomEvent('loading-start'));
        try {
            var res = await fetch('index.php?url=sparepart&action=show&id=' + id);
            var data = await res.json();
        } catch(e) {
            window.dispatchEvent(new CustomEvent('loading-end'));
            darkSwal({ icon: 'error', title: 'Koneksi Gagal', text: 'Tidak bisa memuat data.', confirmButtonColor: '#4f46e5' });
            return;
        }
        window.dispatchEvent(new CustomEvent('loading-end'));
        if (!data.success) {
            darkSwal({ icon: 'error', title: 'Error!', text: data.message || 'Data tidak ditemukan.', confirmButtonColor: '#4f46e5' });
            return;
        }

        const isAdmin = <?= isAdmin() ? 'true' : 'false' ?>;
        const sp = data.data;
        const logs = data.logs || [];
        const APP_URL = '<?= APP_URL ?>';
        var esc = function(t) { var d = document.createElement('div'); d.textContent = t; return d.innerHTML; };

        // Status config
        var statusMap = {
            'Tersedia': { color: 'emerald', icon: 'fa-check-circle', bg: 'from-emerald-50 to-emerald-100/50 dark:from-emerald-900/20 dark:to-emerald-900/10' },
            'Terpakai': { color: 'red', icon: 'fa-arrow-right-from-bracket', bg: 'from-red-50 to-red-100/50 dark:from-red-900/20 dark:to-red-900/10' },
            'Rusak': { color: 'amber', icon: 'fa-triangle-exclamation', bg: 'from-amber-50 to-amber-100/50 dark:from-amber-900/20 dark:to-amber-900/10' },
            'Dalam Perbaikan': { color: 'blue', icon: 'fa-wrench', bg: 'from-blue-50 to-blue-100/50 dark:from-blue-900/20 dark:to-blue-900/10' }
        };
        var st = statusMap[sp.status] || { color: 'gray', icon: 'fa-circle-question', bg: 'from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-600' };

        // Photo
        var photoHtml = '';
        if (sp.image) {
            photoHtml = '<img src="' + APP_URL + '/' + esc(sp.image) + '" class="w-full h-full object-cover rounded-2xl cursor-zoom-in" alt="" onclick="event.stopPropagation(); toggleImageZoom(\'' + APP_URL + '/' + esc(sp.image) + '\', \'' + esc(sp.jenis_sparepart || '') + '\')">';
        } else {
            photoHtml = '<div class="w-full h-full flex flex-col items-center justify-center text-gray-300 dark:text-gray-600"><i class="fa-solid fa-image text-4xl mb-2"></i><span class="text-xs">Tidak ada foto</span></div>';
        }

        // Action buttons
        var actionsHtml = '';
        actionsHtml += '<button onclick="showHistory(' + sp.id + ')" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 rounded-xl text-xs font-semibold hover:bg-blue-100 dark:hover:bg-blue-900/50 transition"><i class="fa-solid fa-clock-rotate-left"></i>Riwayat</button>';
        if (isAdmin) {
            actionsHtml += '<button onclick="showEdit(' + sp.id + ')" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 rounded-xl text-xs font-semibold hover:bg-amber-100 dark:hover:bg-amber-900/50 transition"><i class="fa-solid fa-pen"></i>Edit</button>';
            actionsHtml += '<button onclick="showHapus(' + sp.id + ')" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-xl text-xs font-semibold hover:bg-red-100 dark:hover:bg-red-900/50 transition"><i class="fa-solid fa-trash"></i>Hapus</button>';
        }

        // Info items
        var infoItems = [
            { label: 'Serial Number', value: sp.serial_number || '-', icon: 'fa-barcode', mono: true },
            { label: 'Quantity', value: sp.quantity || '1', icon: 'fa-hashtag' },
            { label: 'Kategori', value: sp.kategori, icon: 'fa-tag' },
            { label: 'PIC', value: sp.pic || '-', icon: 'fa-user' },
            { label: 'Department', value: sp.department || '-', icon: 'fa-building' },
            { label: 'Tanggal Masuk', value: sp.tanggal || '-', icon: 'fa-calendar' },
            { label: 'Keterangan', value: sp.keterangan || '-', icon: 'fa-comment', full: true }
        ];

        var infoHtml = '<div class="grid grid-cols-2 gap-x-4 gap-y-2.5">';
        infoItems.forEach(function(item) {
            var colClass = item.full ? 'col-span-2' : '';
            var valClass = item.mono ? 'font-mono font-semibold text-gray-800 dark:text-white' : 'font-medium text-gray-700 dark:text-gray-200';
            infoHtml += '<div class="' + colClass + '"><p class="text-[10px] uppercase tracking-wider text-gray-400 dark:text-gray-500 font-semibold mb-0.5 flex items-center gap-1"><i class="fa-solid ' + item.icon + ' text-[8px]"></i>' + item.label + '</p><p class="text-sm ' + valClass + ' truncate" title="' + esc(item.value) + '">' + esc(item.value) + '</p></div>';
        });
        infoHtml += '</div>';

        // Log timeline
        var logHtml = '';
        if (logs.length > 0) {
            logHtml = '<div class="mt-4 border-t border-gray-100 dark:border-gray-700 pt-4">';
            logHtml += '<h4 class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-3 flex items-center gap-1.5"><i class="fa-solid fa-timeline text-[10px]"></i>Riwayat Pemakaian <span class="text-gray-300 dark:text-gray-600">(' + logs.length + ')</span></h4>';
            logHtml += '<div class="space-y-0 max-h-48 overflow-y-auto pr-1">';
            var tipeBadge = {
                'Barang Masuk': 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400',
                'Barang Keluar': 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400',
                'Ubah Status': 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-400',
                'Dalam Perbaikan': 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400'
            };
            logs.forEach(function(l, i) {
                var badge = tipeBadge[l.tipe_transaksi] || 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300';
                var isLast = i === logs.length - 1;
                logHtml += '<div class="flex gap-3 relative">';
                logHtml += '<div class="flex flex-col items-center"><div class="w-2.5 h-2.5 rounded-full bg-indigo-400 dark:bg-indigo-500 mt-1.5 shrink-0 ring-2 ring-white dark:ring-gray-800"></div>' + (isLast ? '' : '<div class="w-px flex-1 bg-gray-200 dark:bg-gray-700 my-1"></div></div>');
                logHtml += '<div class="pb-3 flex-1 min-w-0"><div class="flex items-center gap-2 flex-wrap"><span class="px-1.5 py-0.5 text-[10px] font-bold rounded-full ' + badge + '">' + esc(l.tipe_transaksi) + '</span>';
                logHtml += '<span class="text-xs text-gray-500 dark:text-gray-400">' + esc(l.pic_penerima || l.user_name || '-') + (l.department ? ' <span class="text-gray-400">(' + esc(l.department) + ')</span>' : '') + '</span>';
                logHtml += '<span class="text-[10px] text-gray-400 dark:text-gray-500 ml-auto font-mono whitespace-nowrap">' + (l.waktu || l.tanggal) + '</span></div>';
                if (l.keterangan_log) logHtml += '<p class="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5 truncate" title="' + esc(l.keterangan_log) + '">' + esc(l.keterangan_log.substring(0, 80)) + '</p>';
                logHtml += '</div></div>';
            });
            logHtml += '</div></div>';
        }

        // Build full modal
        var modalHtml = '<div class="text-left">';
        // Header: photo + title + status
        modalHtml += '<div class="flex gap-4 mb-4">';
        modalHtml += '<div class="w-32 h-32 rounded-2xl overflow-hidden bg-gradient-to-br ' + st.bg + ' shrink-0 border border-gray-100 dark:border-gray-700 shadow-sm">' + photoHtml + '</div>';
        modalHtml += '<div class="flex-1 min-w-0">';
        modalHtml += '<p class="text-lg font-extrabold text-gray-900 dark:text-white leading-tight mb-1">' + esc(sp.jenis_sparepart) + (sp.type_sparepart ? ' <span class="text-sm font-normal text-gray-400">(' + esc(sp.type_sparepart) + ')</span>' : '') + '</p>';
        modalHtml += '<p class="text-sm text-gray-500 dark:text-gray-400 mb-2">' + esc(sp.merk || '-') + '</p>';
        modalHtml += '<span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-xl bg-' + st.color + '-100 text-' + st.color + '-700 dark:bg-' + st.color + '-900/40 dark:text-' + st.color + '-400 ring-1 ring-' + st.color + '-600/20"><i class="fa-solid ' + st.icon + ' text-[10px]"></i>' + esc(sp.status) + '</span>';
        modalHtml += '<div class="flex items-center gap-2 mt-3">' + actionsHtml + '</div>';
        modalHtml += '</div></div>';
        // Info grid
        modalHtml += infoHtml;
        // Log timeline
        modalHtml += logHtml;
        modalHtml += '</div>';

        darkSwal({
            title: false,
            html: modalHtml,
            confirmButtonColor: '#4f46e5',
            confirmButtonText: '<i class="fa-solid fa-xmark mr-1"></i>Tutup',
            width: '600px',
            customClass: { popup: 'detail-item-modal', container: 'detail-item-container' }
        });
    }

    async function showHistory(id) {
        window.dispatchEvent(new CustomEvent('loading-start'));
        try {
            var res = await fetch('index.php?url=sparepart&action=show&id=' + id);
            var data = await res.json();
        } catch(e) {
            window.dispatchEvent(new CustomEvent('loading-end'));
            darkSwal({ icon: 'error', title: 'Koneksi Gagal', text: 'Tidak bisa memuat riwayat.', confirmButtonColor: '#4f46e5' });
            return;
        }
        window.dispatchEvent(new CustomEvent('loading-end'));
        if (data.success) {
            var allLogs = data.logs || [];
            var perPage = 8;
            var currentPage = 1;

            var tipeBadge = {
                'Barang Masuk': 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400 ring-1 ring-emerald-600/20',
                'Barang Keluar': 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400 ring-1 ring-red-600/20',
                'Ubah Status': 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-400 ring-1 ring-yellow-600/20',
                'Dalam Perbaikan': 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400 ring-1 ring-blue-600/20',
                'Dipinjam': 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-400 ring-1 ring-purple-600/20',
                'Dikembalikan': 'bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-400 ring-1 ring-teal-600/20',
                'Dihapus': 'bg-gray-200 text-gray-700 dark:bg-gray-600 dark:text-gray-300 ring-1 ring-gray-500/20'
            };
            var tipeIcon = {
                'Barang Masuk': 'fa-arrow-down',
                'Barang Keluar': 'fa-arrow-up',
                'Ubah Status': 'fa-pen',
                'Dalam Perbaikan': 'fa-wrench',
                'Dipinjam': 'fa-hand-holding',
                'Dikembalikan': 'fa-hand-holding-hand',
                'Dihapus': 'fa-trash'
            };

            function renderHistory() {
                var totalPages = Math.max(1, Math.ceil(allLogs.length / perPage));
                if (currentPage > totalPages) currentPage = totalPages;
                var start = (currentPage - 1) * perPage;
                var pageLogs = allLogs.slice(start, start + perPage);

                var html = '';
                if (allLogs.length === 0) {
                    html = '<div class="flex flex-col items-center gap-3 py-10 text-gray-400 dark:text-gray-500"><i class="fa-solid fa-clock-rotate-left text-4xl opacity-40"></i><p class="text-sm font-medium">Belum ada riwayat transaksi.</p></div>';
                } else {
                    // Summary bar
                    html += '<div class="flex items-center gap-3 mb-4 text-xs">';
                    html += '<span class="font-bold text-gray-700 dark:text-gray-300">' + allLogs.length + ' transaksi</span>';
                    var tipeCounts = {};
                    allLogs.forEach(function(l) { tipeCounts[l.tipe_transaksi] = (tipeCounts[l.tipe_transaksi] || 0) + 1; });
                    Object.keys(tipeCounts).forEach(function(t) {
                        var badge = tipeBadge[t] || 'bg-gray-100 text-gray-600';
                        html += '<span class="px-1.5 py-0.5 rounded-full text-[10px] font-bold ' + badge + '">' + t + ': ' + tipeCounts[t] + '</span>';
                    });
                    html += '</div>';

                    // Timeline
                    html += '<div class="space-y-0 max-h-[32rem] overflow-y-auto pr-1">';
                    pageLogs.forEach(function(l, i) {
                        var badge = tipeBadge[l.tipe_transaksi] || 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300';
                        var icon = tipeIcon[l.tipe_transaksi] || 'fa-circle';
                        var isLast = i === pageLogs.length - 1 && currentPage === totalPages;
                        html += '<div class="flex gap-3 relative">';
                        html += '<div class="flex flex-col items-center"><div class="w-7 h-7 rounded-full bg-gradient-to-br from-indigo-100 to-indigo-200 dark:from-indigo-900/40 dark:to-indigo-800/30 flex items-center justify-center shrink-0 ring-2 ring-white dark:ring-gray-800"><i class="fa-solid ' + icon + ' text-indigo-600 dark:text-indigo-400 text-[10px]"></i></div>' + (isLast ? '' : '<div class="w-px flex-1 bg-gray-200 dark:bg-gray-700 my-1"></div></div>');
                        html += '<div class="pb-4 flex-1 min-w-0">';
                        html += '<div class="flex items-center gap-2 flex-wrap mb-1"><span class="px-2 py-0.5 text-[11px] font-bold rounded-full ' + badge + '">' + esc(l.tipe_transaksi) + '</span>';
                        html += '<span class="text-[10px] text-gray-400 dark:text-gray-500 font-mono ml-auto">' + (l.waktu || l.tanggal) + '</span></div>';
                        html += '<div class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400"><i class="fa-solid fa-user text-[9px] text-gray-400"></i>' + esc(l.pic_penerima || l.user_name || '-');
                        if (l.department) html += ' <span class="text-gray-400 dark:text-gray-500">/</span> <i class="fa-solid fa-building text-[9px] text-gray-400"></i> ' + esc(l.department);
                        html += '</div>';
                        if (l.keterangan_log) html += '<p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1 bg-gray-50 dark:bg-gray-700/30 rounded-lg px-2.5 py-1.5 leading-relaxed" title="' + esc(l.keterangan_log) + '">' + esc(l.keterangan_log.substring(0, 120)) + (l.keterangan_log.length > 120 ? '...' : '') + '</p>';
                        html += '</div></div>';
                    });
                    html += '</div>';

                    // Pagination
                    html += '<div class="flex items-center justify-between mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">';
                    html += '<span class="text-xs text-gray-400 dark:text-gray-500 font-medium">Hal. ' + currentPage + '/' + totalPages + '</span>';
                    html += '<div class="flex items-center gap-1.5">';
                    if (currentPage > 1) {
                        html += '<button onclick="window._histPage=' + (currentPage - 1) + ';showHistoryRefresh(' + id + ')" class="w-7 h-7 flex items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition text-gray-600 dark:text-gray-300"><i class="fa-solid fa-chevron-left text-[10px]"></i></button>';
                    }
                    // Page input
                    html += '<input type="number" id="histPageInput" value="' + currentPage + '" min="1" max="' + totalPages + '" class="w-12 h-7 text-center text-xs border border-gray-200 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-gray-200 focus:ring-1 focus:ring-indigo-500 outline-none">';
                    html += '<button onclick="window._histPage=parseInt(document.getElementById(\'histPageInput\').value)||1;showHistoryRefresh(' + id + ')" class="h-7 px-2.5 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 transition text-[11px] font-bold"><i class="fa-solid fa-arrow-right text-[10px]"></i></button>';
                    if (currentPage < totalPages) {
                        html += '<button onclick="window._histPage=' + (currentPage + 1) + ';showHistoryRefresh(' + id + ')" class="w-7 h-7 flex items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition text-gray-600 dark:text-gray-300"><i class="fa-solid fa-chevron-right text-[10px]"></i></button>';
                    }
                    html += '</div></div>';
                }

                darkSwal({
                    title: '<span class="flex items-center gap-2"><span class="w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center"><i class="fa-solid fa-clock-rotate-left text-blue-600 dark:text-blue-400 text-sm"></i></span>Riwayat Sparepart #' + id + '</span>',
                    html: html,
                    confirmButtonColor: '#4f46e5',
                    confirmButtonText: '<i class="fa-solid fa-xmark mr-1"></i>Tutup',
                    width: '550px',
                    customClass: { popup: 'history-modal' },
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

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
