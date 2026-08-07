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

// Barang yang Dipakai
if (isAdmin()) {
    $dipakai = $db->query("
        SELECT id, jenis_sparepart, type_sparepart, merk, serial_number, kategori, status, pic, department, tanggal, keterangan, image, quantity
        FROM spareparts WHERE deleted_at IS NULL AND status = 'Terpakai' AND updated_at >= NOW() - INTERVAL 24 HOUR
        ORDER BY updated_at DESC
    ")->fetchAll();
} else {
    $stmt = $db->prepare("
        SELECT id, jenis_sparepart, type_sparepart, merk, serial_number, kategori, status, pic, department, tanggal, keterangan, image, quantity
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
    GROUP_CONCAT(DISTINCT s.serial_number ORDER BY s.serial_number SEPARATOR ', ') as sn_list,
    MIN(s.image) as thumbnail_image,
    MAX(s.updated_at) as last_updated,
    SUBSTRING_INDEX(GROUP_CONCAT(s.pic ORDER BY s.updated_at DESC), ',', 1) as last_pic,
    MAX(s.created_at) as latest,
    (
        SELECT CONCAT(l.tipe_transaksi, '|', u.name, '|', DATE_FORMAT(l.created_at, '%d/%m/%Y %H:%i'), '|', COALESCE(l.keterangan_log, ''))
        FROM logbooks l
        JOIN spareparts s2 ON s2.id = l.sparepart_id
        JOIN users u ON u.id = l.user_id
        WHERE s2.deleted_at IS NULL
          AND s2.kategori = s.kategori
          AND s2.jenis_sparepart = s.jenis_sparepart
          AND (s2.type_sparepart = s.type_sparepart OR (s2.type_sparepart IS NULL AND s.type_sparepart IS NULL))
        ORDER BY l.created_at DESC
        LIMIT 1
    ) as last_log
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

$merkRows = $db->query("SELECT kategori, jenis_sparepart, type_sparepart, merk FROM sparepart_merks ORDER BY kategori, jenis_sparepart, type_sparepart, merk")->fetchAll();
$merksByJenisType = [];
foreach ($merkRows as $m) {
    $key = $m['kategori'] . '||' . $m['jenis_sparepart'] . '||' . ($m['type_sparepart'] ?: '');
    $merksByJenisType[$key][] = $m['merk'];
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
    return '<i class="fa-solid ' . $arrow . ' text-[9px] ml-1 text-blue-600"></i>';
}

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div x-data="dashboard()" class="page-enter relative z-10">
    <div id="dashboard-particles" class="fixed inset-0 pointer-events-none z-0"></div>
    <nav class="flex items-center gap-2 text-base text-gray-500 dark:text-gray-400 mb-4 relative z-10">
        <a href="<?= pageUrl('dashboard.php') ?>" class="hover:text-blue-600 dark:hover:text-blue-400 transition">Home</a>
        <i class="fa-solid fa-chevron-right text-xs"></i>
        <span class="text-gray-700 dark:text-gray-200 font-medium">Dashboard</span>
    </nav>

    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-6">
        <div class="flex items-center gap-3">
            <h2 class="text-3xl font-extrabold text-gray-800 dark:text-white tracking-tight gradient-text">Dashboard</h2>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="<?= pageUrl('export_csv.php') ?>?<?= http_build_query($_GET) ?>" class="px-3 py-2 sm:px-5 sm:py-3 bg-gradient-to-r from-emerald-600 to-teal-500 text-white rounded-xl hover:from-emerald-500 hover:to-teal-400 transition-all text-sm sm:text-base font-semibold inline-flex items-center gap-1.5 sm:gap-2 shadow-md shadow-emerald-500/20 magnetic-btn">
                <i class="fa-solid fa-file-csv"></i> <span class="hidden xs:inline">CSV</span>
            </a>
            <a href="<?= pageUrl('export_pdf.php') ?>?<?= http_build_query($_GET) ?>" class="px-3 py-2 sm:px-5 sm:py-3 bg-gradient-to-r from-red-600 to-rose-500 text-white rounded-xl hover:from-red-500 hover:to-rose-400 transition-all text-sm sm:text-base font-semibold inline-flex items-center gap-1.5 sm:gap-2 shadow-md shadow-red-500/20 magnetic-btn">
                <i class="fa-solid fa-file-pdf"></i> <span class="hidden xs:inline">PDF</span>
            </a>
            <?php if (isAdmin()): ?>
            <button @click="openModal('tambah')" class="px-3 py-2 sm:px-5 sm:py-3 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-all text-sm sm:text-base font-semibold inline-flex items-center gap-1.5 sm:gap-2 shadow-md shadow-blue-500/20 magnetic-btn">
                <i class="fa-solid fa-plus"></i> <span class="hidden sm:inline">Tambah Data</span><span class="sm:hidden">Tambah</span>
            </button>
            <?php endif; ?>
        </div>
    </div>

    <div x-show="lastUpdate" class="text-xs text-gray-400 dark:text-gray-500 mb-2 flex items-center gap-1.5">
        <i class="fa-solid fa-rotate text-[10px]" :class="refreshing ? 'fa-spin' : ''"></i>
        <span>Terakhir diperbarui: <span x-text="lastUpdate"></span></span>
        <button @click="forceRefresh()" class="text-blue-600 hover:text-blue-400 transition ml-1" title="Refresh sekarang">
            <i class="fa-solid fa-arrows-rotate text-xs"></i>
        </button>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">
        <template x-for="card in statCards" :key="card.key">
            <div class="card-hover glass-panel rounded-xl p-5 overflow-hidden relative">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0 text-lg"
                         :class="card.iconBg">
                        <i :class="card.icon + ' ' + card.iconColor"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[11px] text-gray-500 dark:text-gray-400 font-semibold uppercase tracking-wider" x-text="card.label"></p>
                        <p class="text-2xl font-extrabold tracking-tight truncate counter-value font-mono"
                           :class="card.textColor"
                           x-text="card.value.toLocaleString()">
                        </p>
                    </div>
                </div>
                <div class="absolute bottom-0 left-0 right-0 h-0.5 bg-gradient-to-r opacity-60"
                     :class="card.barGradient"
                     :style="'width: ' + card.barWidth + '%'"></div>
            </div>
        </template>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-6">
        <div class="xl:col-span-2 glass-panel rounded-xl p-6 card-hover">
            <h3 class="text-base font-semibold text-gray-700 dark:text-gray-300 mb-4 flex items-center gap-2">
                <i class="fa-solid fa-chart-pie text-blue-600"></i>
                Komposisi Status Sparepart
            </h3>
            <div class="relative" style="max-height: 280px;">
                <canvas id="statusChart"></canvas>
            </div>
        </div>

        <div class="glass-panel rounded-xl p-6 card-hover">
            <h3 class="text-base font-semibold text-gray-700 dark:text-gray-300 mb-4 flex items-center gap-2">
                <i class="fa-solid fa-clock-rotate-left text-blue-600"></i>
                Aktivitas Terbaru
            </h3>
            <div class="space-y-3">
                <?php if (empty($recentLogs)): ?>
                <div class="flex flex-col items-center gap-2 py-8 text-gray-400 dark:text-gray-500">
                    <i class="fa-solid fa-clock text-3xl"></i>
                    <p class="text-base">Belum ada aktivitas.</p>
                </div>
                <?php else: ?>
                    <?php foreach ($recentLogs as $log): ?>
                    <div class="flex items-start gap-3 pb-3 border-b border-gray-100 dark:border-gray-700 last:border-0 last:pb-0">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 text-sm font-bold shadow-sm
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
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-200 truncate">
                                <?= escape($log['jenis_sparepart']) ?>
                                <span class="text-gray-400 dark:text-gray-500 font-normal">— <?= escape($log['tipe_transaksi']) ?></span>
                            </p>
                            <p class="text-sm text-gray-400 dark:text-gray-500 mt-0.5">
                                <?= escape($log['user_name']) ?> • <?= formatTanggal($log['tanggal']) ?>
                            </p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="glass-panel rounded-xl mb-6 card-hover">
        <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
            <h3 class="text-base font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-2">
                <i class="fa-solid fa-bag-shopping text-blue-600"></i>
                Barang yang Dipakai dalam 24 Jam Terakhir
                <span class="text-sm font-normal text-gray-400 dark:text-gray-500">(<span x-text="dipakai.length"></span> item)</span>
            </h3>
            <div class="flex items-center gap-2">
                <span class="text-xs text-gray-400 dark:text-gray-500" x-show="dipakai.length > 0">
                    <i class="fa-solid fa-rotate mr-0.5" :class="refreshing ? 'fa-spin' : ''"></i>
                    Auto-refresh
                </span>
            </div>
        </div>
        <div x-show="dipakai.length === 0" class="flex flex-col items-center gap-2 py-8 text-gray-400 dark:text-gray-500">
            <i class="fa-solid fa-box-open text-3xl"></i>
            <p class="text-base">Tidak ada barang yang dipakai.</p>
        </div>
        <div x-show="dipakai.length > 0" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3 p-4" style="max-height: 480px; overflow-y: auto;">
            <template x-for="(item, idx) in dipakai" :key="item.id">
                <div class="relative group bg-gradient-to-br from-gray-50 to-white dark:from-gray-700/50 dark:to-gray-800 rounded-xl border border-gray-200 dark:border-gray-600 p-4 transition-all duration-300 hover:shadow-lg hover:border-indigo-300 dark:hover:border-indigo-500/50 hover:-translate-y-0.5">
                    <div class="flex items-start justify-between mb-2">
                        <div class="flex items-center gap-2 min-w-0">
                            <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-red-100 to-red-200 dark:from-red-900/40 dark:to-red-800/30 flex items-center justify-center shrink-0 relative">
                                <i class="fa-solid fa-box text-red-600 dark:text-red-400 text-sm"></i>
                                <span x-show="item.pic === userPicName"
                                      class="absolute -top-1 -right-1 w-4 h-4 bg-emerald-500 rounded-full flex items-center justify-center"
                                      title="Barang Anda">
                                    <i class="fa-solid fa-check text-white" style="font-size: 8px;"></i>
                                </span>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-800 dark:text-gray-200 truncate" x-text="item.jenis_sparepart"></p>
                                <p class="text-sm text-gray-400 dark:text-gray-500 truncate" x-text="item.merk || '-'"></p>
                            </div>
                        </div>
                        <span class="shrink-0 inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-full"
                              :class="statusBadgeClass(item.status)" x-text="item.status"></span>
                    </div>
                    <div class="grid grid-cols-2 gap-x-3 gap-y-1 text-sm mt-2">
                        <div class="text-gray-500 dark:text-gray-400 flex items-center gap-1">
                            <i class="fa-solid text-xs" :class="item.kategori === 'Non-Aset' ? 'fa-hashtag' : 'fa-barcode'"></i>
                            <span class="font-medium text-gray-700 dark:text-gray-300" :class="item.kategori !== 'Non-Aset' && 'font-mono'" x-text="item.kategori === 'Non-Aset' ? ('QTY: ' + (item.quantity || 0)) : (item.serial_number || '').replace(/^SN-/, '')"></span>
                        </div>
                        <div class="text-gray-500 dark:text-gray-400 flex items-center gap-1">
                            <i class="fa-solid fa-tag text-xs"></i>
                            <span x-text="item.kategori"></span>
                        </div>
                        <div class="text-gray-500 dark:text-gray-400 flex items-center gap-1">
                            <i class="fa-solid fa-user text-xs"></i>
                            <span class="font-medium text-gray-700 dark:text-gray-300" x-text="item.pic || '-'"></span>
                        </div>
                        <div class="text-gray-500 dark:text-gray-400 flex items-center gap-1">
                            <i class="fa-solid fa-building text-xs"></i>
                            <span x-text="item.department || '-'"></span>
                        </div>
                        <div class="text-gray-500 dark:text-gray-400 flex items-center gap-1 col-span-2">
                            <i class="fa-solid fa-calendar text-xs"></i>
                            <span x-text="item.tanggal"></span>
                        </div>
                    </div>
                    <div class="mt-2 pt-2 border-t border-gray-100 dark:border-gray-700 space-y-2">
                        <template x-if="imgList(item.image).length > 0">
                            <div class="flex flex-wrap gap-1.5">
                                <template x-for="(img, i) in imgList(item.image).slice(0, 3)" :key="i">
                                    <img :src="'<?= rtrim(APP_URL, '/') ?>/' + img"
                                         class="w-16 h-16 object-cover rounded-lg border border-gray-200 dark:border-gray-600 cursor-zoom-in hover:opacity-90 transition"
                                         @click="toggleImageZoom('<?= rtrim(APP_URL, '/') ?>/' + img, item.jenis_sparepart)"
                                         loading="lazy">
                                </template>
                                <span x-show="imgList(item.image).length > 3"
                                      class="w-16 h-16 rounded-lg border border-gray-200 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-xs font-bold text-gray-500 dark:text-gray-300"
                                      x-text="'+' + (imgList(item.image).length - 3)"></span>
                            </div>
                        </template>
                        <p class="text-sm text-gray-400 dark:text-gray-500 truncate" x-text="item.keterangan ? 'Keterangan: ' + item.keterangan : '-'"></p>
                    </div>
                    <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
                        <span class="text-xs text-gray-400 bg-white dark:bg-gray-700 px-1.5 py-0.5 rounded shadow-sm">
                            #<span x-text="idx + 1"></span>
                        </span>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <div class="glass-panel rounded-xl p-5 mb-6 card-hover">
        <form method="GET" action="index.php" class="flex flex-wrap gap-3 items-end">
            <input type="hidden" name="url" value="dashboard">
            <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider">Cari</label>
                <input type="text" name="search" value="<?= escape(_get($_GET, 'search', '')) ?>" placeholder="Cari SN, jenis, merk..." class="px-3 py-2.5 bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 dark:text-gray-200 rounded-xl text-base focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500/50 outline-none transition-all duration-200 placeholder:text-gray-400">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider">Kategori</label>
                <select name="kategori" class="px-3 py-2.5 bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 dark:text-gray-200 rounded-xl text-base focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500/50 outline-none transition-all duration-200">
                    <option value="">Semua</option>
                    <option value="Aset" <?= _get($_GET, 'kategori', '') === 'Aset' ? 'selected' : '' ?>>Aset</option>
                    <option value="Non-Aset" <?= _get($_GET, 'kategori', '') === 'Non-Aset' ? 'selected' : '' ?>>Non-Aset</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider">Jenis</label>
                <select name="jenis" class="px-3 py-2.5 bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 dark:text-gray-200 rounded-xl text-base focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500/50 outline-none transition-all duration-200">
                    <option value="">Semua</option>
                    <?php foreach ($jenisList as $j): ?>
                    <option value="<?= escape($j) ?>" <?= _get($_GET, 'jenis', '') === $j ? 'selected' : '' ?>><?= escape($j) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider">Status</label>
                <select name="status" class="px-3 py-2.5 bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 dark:text-gray-200 rounded-xl text-base focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500/50 outline-none transition-all duration-200">
                    <option value="">Semua</option>
                    <option value="Tersedia" <?= _get($_GET, 'status', '') === 'Tersedia' ? 'selected' : '' ?>>Tersedia</option>
                    <option value="Terpakai" <?= _get($_GET, 'status', '') === 'Terpakai' ? 'selected' : '' ?>>Terpakai</option>
                    <option value="Rusak" <?= _get($_GET, 'status', '') === 'Rusak' ? 'selected' : '' ?>>Rusak</option>
                    <option value="Dalam Perbaikan" <?= _get($_GET, 'status', '') === 'Dalam Perbaikan' ? 'selected' : '' ?>>Dalam Perbaikan</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider">Dari</label>
                <input type="date" name="date_from" value="<?= escape(_get($_GET, 'date_from', '')) ?>" class="px-3 py-2.5 bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 dark:text-gray-200 rounded-xl text-base focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500/50 outline-none transition-all duration-200">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider">Sampai</label>
                <input type="date" name="date_to" value="<?= escape(_get($_GET, 'date_to', '')) ?>" class="px-3 py-2.5 bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 dark:text-gray-200 rounded-xl text-base focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500/50 outline-none transition-all duration-200">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="px-5 py-2.5 bg-blue-600 text-white rounded-xl text-base hover:bg-blue-700 transition-all font-semibold inline-flex items-center gap-1.5 shadow-md shadow-blue-500/20 magnetic-btn">
                    <i class="fa-solid fa-filter"></i> Filter
                </button>
                <a href="<?= pageUrl('dashboard.php') ?>" class="px-5 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl text-base hover:bg-gray-300 dark:hover:bg-gray-600 transition-all font-semibold inline-flex items-center gap-1.5">
                    <i class="fa-solid fa-rotate"></i> Reset
                </a>
            </div>
        </form>
    </div>

    <div class="glass-panel rounded-xl overflow-hidden card-hover">
        <div class="overflow-x-auto">
            <table class="w-full text-base dashboard-sortable-table">
                <thead>
                    <tr class="bg-gradient-to-r from-blue-500/5 to-violet-500/5 dark:from-white/5 dark:to-white/3 text-gray-600 dark:text-gray-300">
                        <th class="px-4 py-4 text-left font-semibold text-xs uppercase tracking-wider w-10 hidden sm:table-cell"></th>
                        <th class="px-4 py-4 text-left font-semibold text-xs uppercase tracking-wider"><a href="<?= sortUrl('kategori', $sortKey, $sortDir) ?>" class="hover:text-blue-600 transition inline-flex items-center">Kategori<?= sortIcon('kategori', $sortKey, $sortDir) ?></a></th>
                        <th class="px-4 py-4 text-left font-semibold text-xs uppercase tracking-wider"><a href="<?= sortUrl('jenis', $sortKey, $sortDir) ?>" class="hover:text-blue-600 transition inline-flex items-center">Jenis<?= sortIcon('jenis', $sortKey, $sortDir) ?></a></th>
                        <th class="px-4 py-4 text-left font-semibold text-xs uppercase tracking-wider hidden lg:table-cell"><a href="<?= sortUrl('type', $sortKey, $sortDir) ?>" class="hover:text-blue-600 transition inline-flex items-center">Type<?= sortIcon('type', $sortKey, $sortDir) ?></a></th>
                        <th class="px-4 py-4 text-left font-semibold text-xs uppercase tracking-wider hidden md:table-cell"><a href="<?= sortUrl('latest', $sortKey, $sortDir) ?>" class="hover:text-blue-600 transition inline-flex items-center">Merk<?= sortIcon('latest', $sortKey, $sortDir) ?></a></th>
                        <th class="px-4 py-4 text-left font-semibold text-xs uppercase tracking-wider hidden xl:table-cell">SN</th>
                        <th class="px-4 py-4 text-center font-semibold text-xs uppercase tracking-wider"><a href="<?= sortUrl('total', $sortKey, $sortDir) ?>" class="hover:text-blue-600 transition inline-flex items-center justify-center">Total<?= sortIcon('total', $sortKey, $sortDir) ?></a></th>
                        <th class="px-4 py-4 text-center font-semibold text-xs uppercase tracking-wider"><a href="<?= sortUrl('tersedia', $sortKey, $sortDir) ?>" class="hover:text-blue-600 transition inline-flex items-center justify-center gap-1"><span class="inline-block w-2.5 h-2.5 rounded-full bg-emerald-500"></span>T<?= sortIcon('tersedia', $sortKey, $sortDir) ?></a></th>
                        <th class="px-4 py-4 text-center font-semibold text-xs uppercase tracking-wider"><a href="<?= sortUrl('terpakai', $sortKey, $sortDir) ?>" class="hover:text-blue-600 transition inline-flex items-center justify-center gap-1"><span class="inline-block w-2.5 h-2.5 rounded-full bg-red-500"></span>P<?= sortIcon('terpakai', $sortKey, $sortDir) ?></a></th>
                        <th class="px-4 py-4 text-center font-semibold text-xs uppercase tracking-wider"><a href="<?= sortUrl('rusak', $sortKey, $sortDir) ?>" class="hover:text-blue-600 transition inline-flex items-center justify-center gap-1"><span class="inline-block w-2.5 h-2.5 rounded-full bg-amber-500"></span>R<?= sortIcon('rusak', $sortKey, $sortDir) ?></a></th>
                        <th class="px-4 py-4 text-center font-semibold text-xs uppercase tracking-wider"><a href="<?= sortUrl('perbaikan', $sortKey, $sortDir) ?>" class="hover:text-blue-600 transition inline-flex items-center justify-center gap-1"><span class="inline-block w-2.5 h-2.5 rounded-full bg-blue-500"></span>PR<?= sortIcon('perbaikan', $sortKey, $sortDir) ?></a></th>
                        <th class="px-4 py-4 text-center font-semibold text-xs uppercase tracking-wider hidden sm:table-cell">PIC</th>
                        <th class="px-4 py-4 text-left font-semibold text-xs uppercase tracking-wider hidden xl:table-cell">Log Terakhir</th>
                        <th class="px-4 py-4 text-center font-semibold text-xs uppercase tracking-wider hidden lg:table-cell"><a href="<?= sortUrl('updated', $sortKey, $sortDir) ?>" class="hover:text-blue-600 transition inline-flex items-center justify-center">Update<?= sortIcon('updated', $sortKey, $sortDir) ?></a></th>
                        <th class="px-4 py-4 text-center font-semibold text-xs uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    <?php foreach ($groups as $i => $g): ?>
                    <?php
                        $typeLabel = $g['type_sparepart'] ?: '-';
                        // ** PERBAIKAN: gunakan type mentah, jangan urlencode, cukup escape HTML
                        $typeRaw = isset($g['type_sparepart']) ? $g['type_sparepart'] : '';
                        $thumbArr = parseImages(isset($g['thumbnail_image']) ? $g['thumbnail_image'] : '');
                        $thumbSrc = $thumbArr ? imageUrl($thumbArr[0]) : '';
                        $lastUpdate = $g['last_updated'] ? date('d/m/Y', strtotime($g['last_updated'])) : '-';
                        $lastPic = $g['last_pic'] ?: '-';
                    ?>
                    <tr class="group-row hover:bg-indigo-50/80 dark:hover:bg-indigo-900/15 transition-all duration-150 cursor-pointer <?= $i % 2 === 0 ? 'bg-white dark:bg-gray-800' : 'bg-gray-50/60 dark:bg-gray-700/20' ?>"
                        onclick="event.preventDefault(); document.querySelector('[data-detail-btn=\'<?= $i ?>\']').click();">
                        <td class="px-3 py-4 text-center hidden sm:table-cell no-label">
                            <?php if ($thumbSrc): ?>
                            <img src="<?= escape($thumbSrc) ?>" class="w-10 h-10 rounded-lg object-cover border border-gray-200 dark:border-gray-600 shadow-sm cursor-zoom-in hover:scale-110 transition" alt="" loading="lazy" onclick="event.stopPropagation(); toggleImageZoom('<?= escape($thumbSrc) ?>', '<?= escape($g['jenis_sparepart']) ?>')">
                            <?php else: ?>
                            <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600 flex items-center justify-center">
                                <i class="fa-solid fa-box text-gray-400 dark:text-gray-500 text-sm"></i>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td data-label="Kategori" class="px-4 py-4"><span class="px-2.5 py-1 text-sm font-semibold rounded-lg bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 ring-1 ring-gray-200 dark:ring-gray-600"><?= escape($g['kategori']) ?></span></td>
                        <td data-label="Jenis" class="px-4 py-4 font-bold text-gray-900 dark:text-white text-base"><?= escape($g['jenis_sparepart']) ?></td>
                        <td data-label="Type" class="px-4 py-4 text-gray-600 dark:text-gray-400 text-sm hidden lg:table-cell"><?= escape($typeLabel) ?></td>
                        <td data-label="Merk" class="px-4 py-4 text-gray-500 dark:text-gray-400 text-sm hidden md:table-cell"><?= escape($g['merk_list'] ?: '-') ?></td>
                        <td data-label="SN" class="px-4 py-4 text-xs text-gray-600 dark:text-gray-400 hidden xl:table-cell max-w-[180px]">
                            <?php
                            $snList = $g['sn_list'] ?: '';
                            $snItems = $snList ? explode(', ', $snList) : array();
                            $snCount = count($snItems);
                            if ($g['kategori'] === 'Non-Aset'):
                            ?>
                                <span class="font-medium bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded">QTY: <?= $g['total'] ?></span>
                            <?php elseif ($snCount > 0): ?>
                                <?php if ($snCount <= 3): ?>
                                    <div class="font-mono leading-relaxed">
                                    <?php foreach ($snItems as $sn): ?>
                                        <?php $snClean = preg_replace('/^SN-/i', '', $sn); ?>
                                        <span class="block truncate" title="<?= escape($snClean) ?>"><?= escape($snClean) ?></span>
                                    <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <?php $snFirstClean = preg_replace('/^SN-/i', '', $snItems[0]); ?>
                                    <span class="font-mono block" title="<?= escape($snFirstClean) ?>"><?= escape($snFirstClean) ?></span>
                                    <span class="text-gray-400 dark:text-gray-500">+<?= $snCount - 1 ?> lainnya</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-gray-300 dark:text-gray-600">-</span>
                            <?php endif; ?>
                        </td>
                        <td data-label="Total" class="px-4 py-4 text-center">
                            <span class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-gray-100 dark:bg-gray-700 text-base font-extrabold text-gray-800 dark:text-gray-200"><?= $g['total'] ?></span>
                        </td>
                        <td data-label="Tersedia" class="px-4 py-4 text-center">
                            <?php if ($g['tersedia'] > 0): ?>
                            <span class="inline-flex items-center px-2.5 py-1 text-sm font-bold rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-400 ring-1 ring-emerald-600/20 dark:ring-emerald-400/30"><i class="fa-solid fa-check mr-1 text-xs"></i><?= $g['tersedia'] ?></span>
                            <?php else: ?>
                            <span class="text-gray-300 dark:text-gray-600 text-sm font-bold">0</span>
                            <?php endif; ?>
                        </td>
                        <td data-label="Terpakai" class="px-4 py-4 text-center">
                            <?php if ($g['terpakai'] > 0): ?>
                            <span class="inline-flex items-center px-2.5 py-1 text-sm font-bold rounded-full bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-400 ring-1 ring-red-600/20 dark:ring-red-400/30"><i class="fa-solid fa-arrow-right-from-bracket mr-1 text-xs"></i><?= $g['terpakai'] ?></span>
                            <?php else: ?>
                            <span class="text-gray-300 dark:text-gray-600 text-sm font-bold">0</span>
                            <?php endif; ?>
                        </td>
                        <td data-label="Rusak" class="px-4 py-4 text-center">
                            <?php if ($g['rusak'] > 0): ?>
                            <span class="inline-flex items-center px-2.5 py-1 text-sm font-bold rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-400 ring-1 ring-amber-600/20 dark:ring-amber-400/30"><i class="fa-solid fa-triangle-exclamation mr-1 text-xs"></i><?= $g['rusak'] ?></span>
                            <?php else: ?>
                            <span class="text-gray-300 dark:text-gray-600 text-sm font-bold">0</span>
                            <?php endif; ?>
                        </td>
                        <td data-label="Perbaikan" class="px-4 py-4 text-center">
                            <?php if ($g['dalam_perbaikan'] > 0): ?>
                            <span class="inline-flex items-center px-2.5 py-1 text-sm font-bold rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-400 ring-1 ring-blue-600/20 dark:ring-blue-400/30"><i class="fa-solid fa-wrench mr-1 text-xs"></i><?= $g['dalam_perbaikan'] ?></span>
                            <?php else: ?>
                            <span class="text-gray-300 dark:text-gray-600 text-sm font-bold">0</span>
                            <?php endif; ?>
                        </td>
                        <td data-label="PIC" class="px-4 py-4 text-center text-sm text-gray-600 dark:text-gray-400 hidden sm:table-cell font-medium">
                            <span class="truncate max-w-[120px] block" title="<?= escape($lastPic) ?>"><?= escape($lastPic) ?></span>
                        </td>
                        <td data-label="Log Terakhir" class="px-4 py-4 text-xs text-gray-500 dark:text-gray-400 hidden xl:table-cell max-w-[150px]">
                            <?php
                            $lastLog = $g['last_log'] ?: '';
                            if ($lastLog):
                                $logParts = explode('|', $lastLog);
                                $logTipe = isset($logParts[0]) ? $logParts[0] : '';
                                $logUser = isset($logParts[1]) ? $logParts[1] : '';
                                $logDate = isset($logParts[2]) ? $logParts[2] : '';
                                $logBadge = 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300';
                                switch ($logTipe) {
                                    case 'Barang Masuk':
                                        $logBadge = 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400';
                                        break;
                                    case 'Barang Keluar':
                                        $logBadge = 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400';
                                        break;
                                    case 'Ubah Status':
                                    case 'Dalam Perbaikan':
                                        $logBadge = 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400';
                                        break;
                                    case 'Dipinjam':
                                        $logBadge = 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-400';
                                        break;
                                    case 'Dikembalikan':
                                        $logBadge = 'bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-400';
                                        break;
                                    case 'Permintaan':
                                        $logBadge = 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400';
                                        break;
                                }
                            ?>
                                <span class="inline-block px-1.5 py-0.5 text-[10px] font-bold rounded-full <?= $logBadge ?>"><?= escape($logTipe) ?></span>
                                <span class="block truncate text-[10px] text-gray-400 dark:text-gray-500 mt-0.5" title="<?= escape($logUser . ' • ' . $logDate) ?>"><?= escape($logUser) ?></span>
                                <span class="block text-[10px] text-gray-400 dark:text-gray-500 font-mono"><?= escape($logDate) ?></span>
                            <?php else: ?>
                                <span class="text-gray-300 dark:text-gray-600">-</span>
                            <?php endif; ?>
                        </td>
                        <td data-label="Update" class="px-4 py-4 text-center text-sm text-gray-500 dark:text-gray-500 hidden lg:table-cell font-mono">
                            <?= $lastUpdate ?>
                        </td>
                        <td data-label="Aksi" class="px-2 sm:px-4 py-4 text-center no-label">
                            <div class="flex items-center justify-center gap-1" onclick="event.stopPropagation();">
                                <button data-detail-btn="<?= $i ?>" @click="detailGroup('<?= escape($g['kategori']) ?>', '<?= escape($g['jenis_sparepart']) ?>', '<?= escape($typeRaw) ?>')" title="Lihat detail" class="p-1.5 sm:p-2.5 text-sm bg-blue-500/10 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400 rounded-lg hover:bg-blue-500/20 dark:hover:bg-blue-500/20 transition-all hover:scale-110">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                                <button @click="detailGroup('<?= escape($g['kategori']) ?>', '<?= escape($g['jenis_sparepart']) ?>', '<?= escape($typeRaw) ?>')" title="Lihat detail" class="hidden sm:inline-flex p-1.5 sm:p-2.5 text-sm bg-gray-100 dark:bg-white/5 text-gray-600 dark:text-gray-400 rounded-lg hover:bg-gray-200 dark:hover:bg-white/10 transition-all hover:scale-110">
                                    <i class="fa-solid fa-arrow-right"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($groups)): ?>
                    <tr>
                        <td colspan="15" class="px-4 py-16 text-center">
                            <div class="flex flex-col items-center gap-3 text-gray-400 dark:text-gray-500">
                                <i class="fa-solid fa-box-open text-5xl opacity-50"></i>
                                <p class="text-base font-medium">Tidak ada data sparepart.</p>
                                <?php if (isAdmin()): ?>
                                <button @click="openModal('tambah')" class="px-5 py-3 bg-blue-600 text-white rounded-xl text-base hover:bg-blue-700 transition-all font-semibold inline-flex items-center gap-2 shadow-md shadow-blue-500/20 magnetic-btn">
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
    addFotoPreviews: [],
    addFotoError: '',
    MAX_FILE_SIZE: 2 * 1024 * 1024,
    MAX_PHOTOS: 5,
    handleAddFoto(e) {
        var files = Array.prototype.slice.call(e.target.files || []);
        if (!files.length) { this.addFotoPreviews = []; return; }
        var keep = [];
        var self = this;
        var failBig = false;
        files.forEach(function(file) {
            if (file.size > self.MAX_FILE_SIZE) { failBig = true; return; }
            var reader = new FileReader();
            reader.onload = function(ev) {
                keep.push(ev.target.result);
                if (keep.length + self.addFotoPreviews.length > self.MAX_PHOTOS) {
                    self.addFotoError = 'Maksimal ' + self.MAX_PHOTOS + ' foto per barang.';
                    self.$refs.fileFoto.value = '';
                    return;
                }
                self.addFotoError = '';
                self.addFotoPreviews = self.addFotoPreviews.concat(keep);
            };
            reader.readAsDataURL(file);
        });
        if (failBig) {
            this.addFotoError = 'Ada foto melebihi batas 2MB per foto.';
        }
    },
     hapusFoto(i) {
        this.addFotoPreviews.splice(i, 1);
        if (!this.addFotoPreviews.length) { this.$refs.fileFoto.value = ''; }
    }
}" 
     @open-tambah-modal.window="open = true"
     @keydown.escape.window="open = false"
     class="contents">
<template x-if="open">
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4"
         x-transition:enter="modal-enter-active"
         x-transition:enter-start="modal-enter"
         x-transition:leave="modal-leave-active"
         x-transition:leave-end="modal-leave">
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="open = false" x-transition:enter="backdrop-enter-active" x-transition:enter-start="backdrop-enter" x-transition:leave="backdrop-leave-active" x-transition:leave-end="backdrop-leave"></div>
    <div class="glass-panel-strong rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto relative z-10">
        <div class="px-6 py-4 border-b border-black/5 dark:border-white/5 flex justify-between items-center sticky top-0 glass-panel-strong rounded-t-2xl">
            <h3 class="text-xl font-bold text-gray-800 dark:text-white">Tambah Sparepart</h3>
            <button @click="open = false" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition text-xl">&times;</button>
        </div>
        <form method="POST" action="index.php?url=sparepart&action=store" class="p-6 space-y-5" enctype="multipart/form-data">
            <?= csrf() ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-base font-medium text-gray-700 dark:text-gray-300 mb-1.5">Kategori <span class="text-red-500">*</span></label>
                    <select name="kategori" id="add-kategori" required onchange="toggleAddQty(); toggleAddSerial(); filterAddJenis(); filterAddMerk()"
                            class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg text-base focus:ring-2 focus:ring-indigo-500 outline-none transition">
                        <option value="">Pilih Kategori</option>
                        <option value="Aset">Aset</option>
                        <option value="Non-Aset">Non-Aset</option>
                    </select>
                </div>
                <div>
                    <label class="block text-base font-medium text-gray-700 dark:text-gray-300 mb-1.5">Jenis Sparepart <span class="text-red-500">*</span></label>
                    <select name="jenis_sparepart" id="add-jenis" required onchange="filterAddType(); filterAddMerk()"
                            class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg text-base focus:ring-2 focus:ring-indigo-500 outline-none transition">
                        <option value="">Pilih Kategori dulu</option>
                    </select>
                </div>
                <div>
                    <label class="block text-base font-medium text-gray-700 dark:text-gray-300 mb-1.5">Type Sparepart</label>
                    <select name="type_sparepart" id="add-type" onchange="filterAddMerk()"
                            class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg text-base focus:ring-2 focus:ring-indigo-500 outline-none transition">
                        <option value="">Pilih Jenis dulu</option>
                    </select>
                </div>
                <div id="add-qty-wrap">
                    <label class="block text-base font-medium text-gray-700 dark:text-gray-300 mb-1.5">Quantity <span class="text-red-500">*</span></label>
                    <input type="number" name="quantity" id="input-qty" value="1" min="1" required class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg text-base focus:ring-2 focus:ring-indigo-500 outline-none transition">
                </div>
                <div id="add-serial-wrap" class="col-span-1 sm:col-span-2">
                    <label class="block text-base font-medium text-gray-700 dark:text-gray-300 mb-1.5">Serial Number <span class="text-red-500">*</span></label>
                    <div class="flex items-center gap-2">
                        <div class="flex flex-1 min-w-0">
                            <span class="inline-flex items-center px-3 py-2.5 border border-r-0 border-gray-300 dark:border-gray-600 dark:bg-gray-600 bg-gray-100 text-gray-600 dark:text-gray-300 rounded-l-lg text-base font-mono select-none">SN</span>
                            <input type="text" name="serial_number" id="add-sn-input" required class="flex-1 min-w-0 px-3 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-r-lg text-base focus:ring-2 focus:ring-indigo-500 outline-none transition font-mono" value="<?= escape(old('serial_number')) ?>" placeholder="Pisahkan dengan koma jika lebih dari 1">
                        </div>
                        <div class="relative shrink-0" x-data="{ openScanDrop: false }" @click.outside="openScanDrop = false">
                            <button type="button" @click="openScanDrop = !openScanDrop" class="px-3 py-2.5 bg-emerald-500 text-white rounded-lg text-sm font-semibold hover:bg-emerald-600 transition inline-flex items-center gap-1.5" title="Scan barcode/QR">
                                <i class="fa-solid fa-barcode"></i> Scan <i class="fa-solid fa-caret-down text-[10px] ml-0.5"></i>
                            </button>
                            <div x-show="openScanDrop" x-cloak x-transition
                                 class="absolute right-0 top-full mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl shadow-xl z-50 min-w-[180px] overflow-hidden">
                                <button type="button" @click="openScanDrop = false; openSnScanner()" class="w-full text-left px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 flex items-center gap-2.5 transition">
                                    <i class="fa-solid fa-camera text-emerald-500"></i> Kamera
                                </button>
                                <button type="button" @click="openScanDrop = false; document.getElementById('add-sn-photo').click()" class="w-full text-left px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 flex items-center gap-2.5 transition">
                                    <i class="fa-solid fa-image text-blue-500"></i> Upload Foto
                                </button>
                            </div>
                            <input type="file" id="add-sn-photo" accept="image/*" class="hidden" onchange="scanSnFromPhoto(this)">
                        </div>
                    </div>
                </div>
                <div>
                    <label class="block text-base font-medium text-gray-700 dark:text-gray-300 mb-1.5">Tanggal <span class="text-red-500">*</span></label>
                    <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg text-base focus:ring-2 focus:ring-indigo-500 outline-none transition">
                </div>
                <div id="add-merk-wrap">
                    <label class="block text-base font-medium text-gray-700 dark:text-gray-300 mb-1.5">Merk <span class="text-red-500">*</span></label>
                    <select id="add-merk" name="merk" required
                            class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg text-base focus:ring-2 focus:ring-indigo-500 outline-none transition">
                        <option value="">Pilih kategori & jenis dulu</option>
                    </select>
                </div>
                <div>
                    <label class="block text-base font-medium text-gray-700 dark:text-gray-300 mb-1.5">PIC <span class="text-red-500">*</span></label>
                    <input type="text" name="pic" required class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg text-base focus:ring-2 focus:ring-indigo-500 outline-none transition">
                </div>
                <div>
                    <label class="block text-base font-medium text-gray-700 dark:text-gray-300 mb-1.5">Department <span class="text-red-500">*</span></label>
                    <input type="text" name="department" required class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg text-base focus:ring-2 focus:ring-indigo-500 outline-none transition">
                </div>
                <input type="hidden" name="status" value="Tersedia">
                <div class="col-span-1 sm:col-span-2">
                    <label class="block text-base font-medium text-gray-700 dark:text-gray-300 mb-1.5">Keterangan <span class="text-red-500">*</span></label>
                    <textarea name="keterangan" rows="2" required class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg text-base focus:ring-2 focus:ring-indigo-500 outline-none transition"></textarea>
                </div>
                <div class="col-span-1 sm:col-span-2">
                    <label class="block text-base font-medium text-gray-700 dark:text-gray-300 mb-1.5">Foto <span class="text-gray-400 font-normal">(Opsional)</span></label>
                    <div class="flex items-start gap-4">
                        <div class="flex-1">
                            <input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple x-ref="fileFoto"
                                   @change="handleAddFoto($event)"
                                   class="w-full text-base text-gray-500 dark:text-gray-400 file:mr-3 file:py-2.5 file:px-5 file:rounded-lg file:border-0 file:text-base file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-indigo-900/30 dark:file:text-indigo-400 dark:hover:file:bg-indigo-900/50 transition">
                        </div>
                        <div x-show="addFotoPreviews.length > 0" x-cloak class="flex flex-wrap gap-2 shrink-0">
                            <template x-for="(prev, i) in addFotoPreviews" :key="i">
                                <div class="relative">
                                    <img :src="prev" class="w-16 h-16 rounded-lg object-cover border border-gray-200 dark:border-gray-600 shadow-sm cursor-zoom-in hover:scale-105 transition" @click="toggleImageZoom(prev, 'Preview Foto')">
                                    <div class="absolute -top-2 -right-2 flex gap-1">
                                        <button type="button" @click.stop="toggleImageZoom(prev, 'Preview Foto')" title="Perbesar foto"
                                                class="w-5 h-5 bg-blue-600 text-white rounded-full flex items-center justify-center text-xs hover:bg-blue-700 transition shadow-md">
                                            <i class="fa-solid fa-magnifying-glass-plus"></i>
                                        </button>
                                        <button type="button" @click.stop="hapusFoto(i)" title="Hapus foto"
                                                class="w-5 h-5 bg-red-500 text-white rounded-full flex items-center justify-center text-xs hover:bg-red-600 transition shadow-md">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                    <p class="text-sm text-gray-400 mt-1">Format: JPG, PNG, WebP. Maksimal 2MB per foto, hingga 5 foto.</p>
                    <p x-show="addFotoError" x-cloak class="text-sm text-red-500 dark:text-red-400 mt-1 flex items-center gap-1"><i class="fa-solid fa-circle-exclamation"></i><span x-text="addFotoError"></span></p>
                </div>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" @click="open = false" class="px-5 py-2.5 glass-panel text-gray-700 dark:text-gray-300 rounded-lg hover:bg-black/5 dark:hover:bg-white/5 transition text-base font-medium">Batal</button>
                <button type="submit" class="px-5 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-base font-medium inline-flex items-center gap-1.5 magnetic-btn">
                    <i class="fa-solid fa-save"></i> Simpan
                </button>
            </div>
        </form>
    </div>
    </div>
</template>
</div>

<!-- Scanner Overlay -->
<div id="sn-scanner-overlay" class="fixed inset-0 z-[9999] bg-black/80 backdrop-blur-sm flex items-center justify-center p-4" style="display:none;">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
            <h4 class="font-bold text-gray-800 dark:text-white text-sm"><i class="fa-solid fa-barcode text-emerald-500 mr-1.5"></i>Scan Barcode / QR</h4>
            <button onclick="closeSnScanner()" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="p-3">
            <div id="sn-reader" class="rounded-xl overflow-hidden"></div>
        </div>
        <div class="px-4 pb-4">
            <p class="text-xs text-gray-400 dark:text-gray-500 text-center">Arahkan kamera ke barcode/QR pada barang</p>
        </div>
    </div>
</div>

<script>
    const CSRF_TOKEN = '<?= csrfToken() ?>';

    const JENIS_DATA = <?= json_encode($jenisGrouped) ?>;
    const TYPE_DATA = <?= json_encode($typesByJenisKategori) ?>;
    const MERK_DATA = <?= json_encode($merksByJenisType) ?>;

    // --- FUNGSI GLOBAL: toggleImageZoom ---
    function toggleImageZoom(src, title) {
        Swal.fire({
            imageUrl: src,
            imageAlt: title || 'Sparepart',
            showConfirmButton: false,
            showCloseButton: true,
            width: 'auto',
            background: 'transparent',
            customClass: {
                popup: 'zoom-image-popup'
            }
        });
    }

    // --- FUNGSI GLOBAL: imgList (parse multi image JSON/string) ---
    function imgList(val) {
        if (!val) return [];
        if (Array.isArray(val)) return val;
        if (typeof val === 'string') {
            var t = val.trim();
            if (t.charAt(0) === '[') {
                try {
                    var arr = JSON.parse(t);
                    return Array.isArray(arr) ? arr.filter(Boolean) : [t];
                } catch (e) { return [t]; }
            }
            return [t];
        }
        return [];
    }

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
        filterAddMerk();
    }

    function filterAddMerk() {
        var kat = document.getElementById('add-kategori').value;
        var jenis = document.getElementById('add-jenis').value;
        var typeEl = document.getElementById('add-type');
        var type = typeEl ? typeEl.value : '';
        var merkWrap = document.getElementById('add-merk-wrap');
        var sel = document.getElementById('add-merk');
        if (!merkWrap || !sel) return;

        var merks = [];
        if (kat && jenis) {
            var keyWithType = kat + '||' + jenis + '||' + type;
            var keyNoType = kat + '||' + jenis + '||';
            if (MERK_DATA[keyWithType]) {
                merks = MERK_DATA[keyWithType];
            } else if (MERK_DATA[keyNoType]) {
                merks = MERK_DATA[keyNoType];
            }
        }

        merkWrap.style.display = 'block';
        sel.innerHTML = '<option value="">Pilih Merk</option>';

        if (merks.length > 0) {
            for (var i = 0; i < merks.length; i++) {
                var m = merks[i].replace(/&/g,'&amp;').replace(/</g,'&lt;');
                sel.innerHTML += '<option value="' + m + '">' + m + '</option>';
            }
        } else if (kat && jenis) {
            sel.innerHTML += '<option value="Non-Merk">Non-Merk</option>';
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
                    { key: 'total', label: 'Total', value: s.total, icon: 'fa-solid fa-boxes-stacked', iconBg: 'bg-gradient-to-br from-blue-500/10 to-violet-500/10', iconColor: 'text-blue-600 dark:text-blue-400', textColor: 'text-gray-800 dark:text-white', barWidth: (s.total / maxVal) * 100, barGradient: 'bg-blue-600' },
                    { key: 'tersedia', label: 'Tersedia', value: s.tersedia, icon: 'fa-solid fa-check', iconBg: 'bg-gradient-to-br from-emerald-500/10 to-emerald-500/5', iconColor: 'text-emerald-500 dark:text-emerald-400', textColor: 'text-emerald-600 dark:text-emerald-400', barWidth: (s.tersedia / maxVal) * 100, barGradient: 'from-emerald-500 to-emerald-400' },
                    { key: 'terpakai', label: 'Terpakai', value: s.terpakai, icon: 'fa-solid fa-circle-xmark', iconBg: 'bg-gradient-to-br from-red-500/10 to-red-500/5', iconColor: 'text-red-500 dark:text-red-400', textColor: 'text-red-600 dark:text-red-400', barWidth: (s.terpakai / maxVal) * 100, barGradient: 'from-red-500 to-red-400' },
                    { key: 'rusak', label: 'Rusak', value: s.rusak, icon: 'fa-solid fa-triangle-exclamation', iconBg: 'bg-gradient-to-br from-amber-500/10 to-amber-500/5', iconColor: 'text-amber-500 dark:text-amber-400', textColor: 'text-amber-600 dark:text-amber-400', barWidth: (s.rusak / maxVal) * 100, barGradient: 'from-amber-500 to-amber-400' },
                    { key: 'perbaikan', label: 'Perbaikan', value: s.dalam_perbaikan, icon: 'fa-solid fa-wrench', iconBg: 'bg-gradient-to-br from-blue-500/10 to-blue-500/5', iconColor: 'text-blue-500 dark:text-blue-400', textColor: 'text-blue-600 dark:text-blue-400', barWidth: (s.dalam_perbaikan / maxVal) * 100, barGradient: 'from-blue-500 to-blue-400' },
                ];
            },

            init() {
                this.loadChart();
                this.startPolling();
                this.initParticles();
                this.initCounters();
            },

            initParticles() {
                var container = document.getElementById('dashboard-particles');
                if (container) createParticles(container, 15);
            },

            initCounters() {
                var self = this;
                setTimeout(function() {
                    document.querySelectorAll('.counter-value').forEach(function(el) {
                        var val = parseInt(el.textContent.replace(/\./g, '').replace(/,/g, ''));
                        if (val > 0) animateCounter(el, val, 1200);
                    });
                }, 300);
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
                            backgroundColor: ['#10b981', '#ef4444', '#f59e0b', '#00d4ff'],
                            borderWidth: 2,
                            borderColor: document.documentElement.classList.contains('dark') ? '#1a1a2e' : '#ffffff',
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
            background: isDark ? '#1a1a2e' : '#ffffff',
            color: isDark ? '#e2e8f0' : '#1e293b',
            confirmButtonColor: '#00d4ff',
        }, opt));
    }

    async function renderGroupModal(kategori, jenis, type, page, q) {
        window._lastGroupParams = { kategori: kategori, jenis: jenis, type: type, page: page || 1, q: q || '' };
        window.dispatchEvent(new CustomEvent('loading-start'));
        // type sudah string mentah, encode di sini
        const url = 'index.php?url=sparepart&action=list_by_group&kategori=' + encodeURIComponent(kategori) + '&jenis=' + encodeURIComponent(jenis) + '&type=' + encodeURIComponent(type) + '&page=' + (page || 1) + (q ? '&q=' + encodeURIComponent(q) : '');
        try {
            var res = await fetch(url);
            var data = await res.json();
        } catch(e) {
            window.dispatchEvent(new CustomEvent('loading-end'));
            darkSwal({ icon: 'error', title: 'Koneksi Gagal', text: 'Tidak bisa memuat data. Periksa koneksi internet.', confirmButtonColor: '#00d4ff' });
            return;
        }
        window.dispatchEvent(new CustomEvent('loading-end'));

        if (!data.success) {
            darkSwal({ icon: 'error', title: 'Error!', text: data.message || 'Gagal memuat data.', confirmButtonColor: '#00d4ff' });
            return;
        }

        const isAdmin = <?= isAdmin() ? 'true' : 'false' ?>;
        const s = data.stats || {};
        const curPage = data.page || 1;
        const searchQ = q || '';
        const APP_URL = '<?= rtrim(APP_URL, '/') ?>';

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
        let html = '<div class="flex flex-wrap gap-1.5 sm:gap-2 mb-3">';
        html += '<span class="inline-flex items-center gap-1 sm:gap-1.5 px-2 sm:px-3 py-1 sm:py-1.5 text-[10px] sm:text-xs font-bold rounded-lg sm:rounded-xl bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 shadow-sm"><i class="fa-solid fa-cubes text-[8px] sm:text-[10px]"></i>Total ' + (s.total || data.items.length) + '</span>';
        html += '<span class="inline-flex items-center gap-1 sm:gap-1.5 px-2 sm:px-3 py-1 sm:py-1.5 text-[10px] sm:text-xs font-bold rounded-lg sm:rounded-xl bg-emerald-50 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400 ring-1 ring-emerald-600/20"><i class="fa-solid fa-check-circle text-[8px] sm:text-[10px]"></i>' + (s.tersedia || 0) + ' Tersedia</span>';
        html += '<span class="inline-flex items-center gap-1 sm:gap-1.5 px-2 sm:px-3 py-1 sm:py-1.5 text-[10px] sm:text-xs font-bold rounded-lg sm:rounded-xl bg-red-50 text-red-700 dark:bg-red-900/40 dark:text-red-400 ring-1 ring-red-600/20"><i class="fa-solid fa-arrow-right-from-bracket text-[8px] sm:text-[10px]"></i>' + (s.terpakai || 0) + ' Terpakai</span>';
        if ((s.rusak || 0) > 0) html += '<span class="inline-flex items-center gap-1 sm:gap-1.5 px-2 sm:px-3 py-1 sm:py-1.5 text-[10px] sm:text-xs font-bold rounded-lg sm:rounded-xl bg-amber-50 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400 ring-1 ring-amber-600/20"><i class="fa-solid fa-triangle-exclamation text-[8px] sm:text-[10px]"></i>' + (s.rusak || 0) + ' Rusak</span>';
        if ((s.dalam_perbaikan || 0) > 0) html += '<span class="inline-flex items-center gap-1 sm:gap-1.5 px-2 sm:px-3 py-1 sm:py-1.5 text-[10px] sm:text-xs font-bold rounded-lg sm:rounded-xl bg-blue-50 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400 ring-1 ring-blue-600/20"><i class="fa-solid fa-wrench text-[8px] sm:text-[10px]"></i>' + (s.dalam_perbaikan || 0) + ' Perbaikan</span>';
        html += '</div>';

        // Search
        html += '<div class="flex gap-2 mb-3">';
        html += '<div class="relative flex-1"><i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>';
        html += '<input type="text" id="group-search-input" value="' + esc(searchQ) + '" placeholder="Cari SN, merk, PIC..." class="w-full pl-8 pr-3 py-2 border border-gray-200 dark:border-gray-600 dark:bg-gray-700/50 dark:text-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500/50 outline-none transition" onkeydown="if(event.key===\'Enter\'){var v=this.value;renderGroupModal(\'' + esc(kategori) + '\',\'' + esc(jenis) + '\',\'' + esc(type) + '\',1,v)}">';
        html += '</div>';
        html += '<button onclick="renderGroupModal(\'' + esc(kategori) + '\',\'' + esc(jenis) + '\',\'' + esc(type) + '\',1,document.getElementById(\'group-search-input\').value)" class="px-4 py-2 bg-blue-600 text-white rounded-xl text-sm hover:bg-blue-700 transition font-semibold shadow-sm magnetic-btn"><i class="fa-solid fa-search mr-1"></i>Cari</button>';
        html += '</div>';

        // Table
        html += '<div class="overflow-x-auto max-h-[28rem] overflow-y-auto border border-gray-200 dark:border-gray-700 rounded-xl">';
        html += '<table class="w-full text-sm">';
        html += '<thead class="sticky top-0 z-10"><tr class="bg-gradient-to-r from-blue-500/5 to-violet-500/5 dark:from-white/5 dark:to-white/3">';
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
            var snDisplay = (sp.serial_number && sp.kategori !== 'Non-Aset') ? '<span class="font-mono font-semibold text-gray-800 dark:text-gray-200">' + esc(sp.serial_number.replace(/^SN-/, '')) + '</span>' : '<span class="text-xs text-gray-400 italic font-medium bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded">qty: ' + sp.quantity + '</span>';
            var thumbHtml = '';
            var imgArr = imgList(sp.image);
            if (imgArr.length > 0) {
                thumbHtml = '<div class="relative inline-block">';
                thumbHtml += '<img src="' + APP_URL + '/' + esc(imgArr[0]) + '" class="w-9 h-9 rounded-lg object-cover border border-gray-200 dark:border-gray-600 shadow-sm cursor-zoom-in hover:scale-110 transition" loading="lazy" alt="" onclick="event.stopPropagation(); toggleImageZoom(\'' + APP_URL + '/' + esc(imgArr[0]) + '\', \'' + esc(sp.jenis_sparepart || '') + '\')">';
                if (imgArr.length > 1) thumbHtml += '<span class="absolute -bottom-1 -right-1 min-w-4 h-4 px-0.5 text-[8px] bg-indigo-600 text-white rounded-full flex items-center justify-center font-bold shadow">+' + (imgArr.length - 1) + '</span>';
                thumbHtml += '</div>';
            } else {
                thumbHtml = '<div class="w-9 h-9 rounded-lg bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600 flex items-center justify-center"><i class="fa-solid fa-box text-gray-400 text-xs"></i></div>';
            }
            var rowBg = idx % 2 === 0 ? 'bg-white dark:bg-gray-800' : 'bg-gray-50/50 dark:bg-gray-700/20';

            var actionBtns = '<div class="flex items-center justify-center gap-1">';
            actionBtns += '<button onclick="showDetail(' + sp.id + ')" class="p-1.5 text-xs bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-900/50 transition" title="Detail"><i class="fa-solid fa-circle-info"></i></button>';
            actionBtns += '<button onclick="showHistory(' + sp.id + ')" class="p-1.5 text-xs bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 rounded-lg hover:bg-blue-200 dark:hover:bg-blue-900/50 transition" title="Log Barang"><i class="fa-solid fa-clock-rotate-left"></i></button>';
            if (isAdmin) {
                actionBtns += '<button onclick="showEdit(' + sp.id + ')" class="hidden sm:inline-flex p-1.5 text-xs bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 rounded-lg hover:bg-amber-200 dark:hover:bg-amber-900/50 transition" title="Edit"><i class="fa-solid fa-pen"></i></button>';
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
                html += '<button onclick="renderGroupModal(\'' + esc(kategori) + '\',\'' + esc(jenis) + '\',\'' + esc(type) + '\',' + p + ',\'' + esc(searchQ) + '\')" class="w-7 h-7 rounded-lg text-xs font-bold transition ' + (p === curPage ? 'bg-blue-600 text-white shadow-md shadow-blue-500/30' : 'bg-gray-100 dark:bg-white/5 hover:bg-gray-200 dark:hover:bg-white/10 text-gray-700 dark:text-gray-300') + '">' + p + '</button>';
            }
            if (curPage < data.totalPages) {
                html += '<button onclick="renderGroupModal(\'' + esc(kategori) + '\',\'' + esc(jenis) + '\',\'' + esc(type) + '\',' + (curPage + 1) + ',\'' + esc(searchQ) + '\')" class="px-2.5 py-1 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition font-medium"><i class="fa-solid fa-chevron-right text-[10px]"></i></button>';
            }
            html += '</div></div>';
        }

        darkSwal({
            title: '<span class="flex items-center gap-2"><span class="w-8 h-8 rounded-lg bg-blue-500/10 dark:bg-blue-500/10 flex items-center justify-center"><i class="fa-solid fa-layer-group text-blue-600 dark:text-blue-400 text-sm"></i></span><span>' + esc(data.kategori) + ' — ' + esc(data.jenis) + (data.type && data.type !== '-' ? ' <span class="text-sm font-normal text-gray-400">(' + esc(data.type) + ')</span>' : '') + '</span></span>',
            html: html,
            confirmButtonColor: '#00d4ff',
            confirmButtonText: '<i class="fa-solid fa-xmark mr-1"></i>Tutup',
            width: '95vw',
            maxWidth: '900px',
            customClass: { popup: 'detail-group-modal', confirmButton: 'swal2-confirm-cyan' },
            didClose: function() { window._lastGroupParams = null; }
        });
    }

    async function showDetail(id) {
        window.dispatchEvent(new CustomEvent('loading-start'));
        try {
            var res = await fetch('index.php?url=sparepart&action=show&id=' + id);
            var data = await res.json();
        } catch(e) {
            window.dispatchEvent(new CustomEvent('loading-end'));
            darkSwal({ icon: 'error', title: 'Koneksi Gagal', text: 'Tidak bisa memuat data.', confirmButtonColor: '#00d4ff' });
            return;
        }
        window.dispatchEvent(new CustomEvent('loading-end'));
        if (!data.success) {
            darkSwal({ icon: 'error', title: 'Error!', text: data.message || 'Data tidak ditemukan.', confirmButtonColor: '#00d4ff' });
            return;
        }

        const isAdmin = <?= isAdmin() ? 'true' : 'false' ?>;
        const sp = data.data;
        const logs = data.logs || [];
        const APP_URL = '<?= rtrim(APP_URL, '/') ?>';
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
        var imgArr = imgList(sp.image);
        if (imgArr.length > 0) {
            photoHtml = '<div class="flex flex-wrap gap-2">';
            imgArr.forEach(function(p) {
                photoHtml += '<img src="' + APP_URL + '/' + esc(p) + '" class="w-24 h-24 sm:w-32 sm:h-32 rounded-2xl object-cover border border-gray-100 dark:border-gray-700 shadow-sm cursor-zoom-in" alt="" onclick="event.stopPropagation(); toggleImageZoom(\'' + APP_URL + '/' + esc(p) + '\', \'' + esc(sp.jenis_sparepart || '') + '\')">';
            });
            photoHtml += '</div>';
        } else {
            photoHtml = '<div class="w-24 h-24 sm:w-32 sm:h-32 rounded-2xl bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600 flex flex-col items-center justify-center text-gray-300 dark:text-gray-600"><i class="fa-solid fa-image text-2xl sm:text-4xl mb-2"></i><span class="text-[10px] sm:text-xs">Tidak ada foto</span></div>';
        }

        // Action buttons
        var actionsHtml = '';
        if (isAdmin) {
            actionsHtml += '<button onclick="showEdit(' + sp.id + ')" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-500/10 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400 rounded-xl text-xs font-semibold hover:bg-amber-500/20 dark:hover:bg-amber-500/20 transition"><i class="fa-solid fa-pen"></i>Edit</button>';
            actionsHtml += '<button onclick="showHapus(' + sp.id + ')" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-500/10 dark:bg-red-500/10 text-red-600 dark:text-red-400 rounded-xl text-xs font-semibold hover:bg-red-500/20 dark:hover:bg-red-500/20 transition"><i class="fa-solid fa-trash"></i>Hapus</button>';
        }

        // Info items
        var isNonAset = sp.kategori === 'Non-Aset';
        var infoItems = [
            { label: isNonAset ? 'QTY' : 'Serial Number', value: isNonAset ? (sp.quantity || 0) : (sp.serial_number || '-'), icon: isNonAset ? 'fa-hashtag' : 'fa-barcode', mono: !isNonAset },
            { label: 'Kategori', value: sp.kategori, icon: 'fa-tag' },
            { label: 'PIC', value: sp.pic || '-', icon: 'fa-user' },
            { label: 'Department', value: sp.department || '-', icon: 'fa-building' },
            { label: 'Tanggal Masuk', value: sp.tanggal || '-', icon: 'fa-calendar' },
            { label: 'Keterangan', value: sp.keterangan || '-', icon: 'fa-comment', full: true }
        ];

        var infoHtml = '<div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-2.5">';
        infoItems.forEach(function(item) {
            var colClass = item.full ? 'col-span-2' : '';
            var valClass = item.mono ? 'font-mono font-semibold text-gray-800 dark:text-white' : 'font-medium text-gray-700 dark:text-gray-200';
            infoHtml += '<div class="' + colClass + '"><p class="text-[10px] uppercase tracking-wider text-gray-400 dark:text-gray-500 font-semibold mb-0.5 flex items-center gap-1"><i class="fa-solid ' + item.icon + ' text-[8px]"></i>' + item.label + '</p><p class="text-sm ' + valClass + ' truncate" title="' + esc(item.value) + '">' + esc(item.value) + '</p></div>';
        });
        infoHtml += '</div>';

        // Build full modal
        var modalHtml = '<div class="text-left">';
        modalHtml += '<div class="flex flex-col sm:flex-row gap-4 mb-4">';
        modalHtml += photoHtml;
        modalHtml += '<div class="flex-1 min-w-0">';
        modalHtml += '<p class="text-lg font-extrabold text-gray-900 dark:text-white leading-tight mb-1">' + esc(sp.jenis_sparepart) + (sp.type_sparepart ? ' <span class="text-sm font-normal text-gray-400">(' + esc(sp.type_sparepart) + ')</span>' : '') + '</p>';
        modalHtml += '<p class="text-sm text-gray-500 dark:text-gray-400 mb-2">' + esc(sp.merk || '-') + '</p>';
        modalHtml += '<span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-xl bg-' + st.color + '-100 text-' + st.color + '-700 dark:bg-' + st.color + '-900/40 dark:text-' + st.color + '-400 ring-1 ring-' + st.color + '-600/20"><i class="fa-solid ' + st.icon + ' text-[10px]"></i>' + esc(sp.status) + '</span>';
        modalHtml += '<div class="flex flex-wrap items-center gap-2 mt-3">' + actionsHtml + '</div>';
        modalHtml += '</div></div>';
        // Info grid
        modalHtml += infoHtml;
        modalHtml += '</div>';

        darkSwal({
            title: false,
            html: modalHtml,
            confirmButtonColor: '#00d4ff',
            confirmButtonText: '<i class="fa-solid fa-xmark mr-1"></i>Tutup',
            width: '92vw',
            maxWidth: '600px',
            customClass: { popup: 'detail-item-modal', container: 'detail-item-container' },
            didClose: function() {
                if (window._lastGroupParams) {
                    var g = window._lastGroupParams;
                    renderGroupModal(g.kategori, g.jenis, g.type, g.page, g.q);
                }
            }
        });
    }

    async function showHistory(id) {
        Swal.close();
        window.dispatchEvent(new CustomEvent('loading-start'));
        var esc = function(t) { var d = document.createElement('div'); d.textContent = t; return d.innerHTML; };
        try {
            var res = await fetch('index.php?url=sparepart&action=show&id=' + id);
            var data = await res.json();
        } catch(e) {
            window.dispatchEvent(new CustomEvent('loading-end'));
            darkSwal({ icon: 'error', title: 'Koneksi Gagal', text: 'Tidak bisa memuat riwayat.', confirmButtonColor: '#00d4ff' });
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
                    html += '<div class="flex flex-wrap items-center gap-1.5 sm:gap-3 mb-4 text-xs">';
                    html += '<span class="font-bold text-gray-700 dark:text-gray-300">' + allLogs.length + ' transaksi</span>';
                    var tipeCounts = {};
                    allLogs.forEach(function(l) { tipeCounts[l.tipe_transaksi] = (tipeCounts[l.tipe_transaksi] || 0) + 1; });
                    Object.keys(tipeCounts).forEach(function(t) {
                        var badge = tipeBadge[t] || 'bg-gray-100 text-gray-600';
                        html += '<span class="px-1.5 py-0.5 rounded-full text-[10px] font-bold ' + badge + '">' + t + ': ' + tipeCounts[t] + '</span>';
                    });
                    html += '</div>';

                    // Timeline
                    html += '<div class="space-y-0 max-h-[60vh] sm:max-h-[32rem] overflow-y-auto pr-1">';
                    pageLogs.forEach(function(l, i) {
                        var badge = tipeBadge[l.tipe_transaksi] || 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300';
                        var icon = tipeIcon[l.tipe_transaksi] || 'fa-circle';
                        var isLast = i === pageLogs.length - 1 && currentPage === totalPages;
                        html += '<div class="flex gap-2.5 sm:gap-3 relative">';
                        html += '<div class="flex flex-col items-center"><div class="w-6 h-6 sm:w-7 sm:h-7 rounded-full bg-gradient-to-br from-blue-500/10 to-violet-500/10 flex items-center justify-center shrink-0 ring-2 ring-white dark:ring-gray-800"><i class="fa-solid ' + icon + ' text-blue-600 dark:text-blue-400 text-[9px] sm:text-[10px]"></i></div>' + (isLast ? '' : '<div class="w-px flex-1 bg-gray-200 dark:bg-gray-700 my-1"></div></div>');
                        html += '<div class="pb-3 sm:pb-4 flex-1 min-w-0">';
                        html += '<div class="flex items-center gap-1.5 sm:gap-2 flex-wrap mb-1"><span class="px-1.5 sm:px-2 py-0.5 text-[10px] sm:text-[11px] font-bold rounded-full ' + badge + '">' + esc(l.tipe_transaksi) + '</span>';
                        html += '<span class="text-[9px] sm:text-[10px] text-gray-400 dark:text-gray-500 font-mono ml-auto">' + (l.waktu || l.tanggal) + '</span></div>';
                        html += '<div class="flex items-center gap-1.5 sm:gap-2 text-[11px] sm:text-xs text-gray-600 dark:text-gray-400"><i class="fa-solid fa-user text-[8px] sm:text-[9px] text-gray-400"></i>' + esc(l.pic_penerima || l.user_name || '-');
                        if (l.department) html += ' <span class="text-gray-400 dark:text-gray-500">/</span> <i class="fa-solid fa-building text-[8px] sm:text-[9px] text-gray-400"></i> ' + esc(l.department);
                        html += '</div>';
                        if (l.keterangan_log) html += '<p class="text-[10px] sm:text-[11px] text-gray-400 dark:text-gray-500 mt-1 bg-gray-50 dark:bg-gray-700/30 rounded-lg px-2 sm:px-2.5 py-1 sm:py-1.5 leading-relaxed" title="' + esc(l.keterangan_log) + '">' + esc(l.keterangan_log.substring(0, 120)) + (l.keterangan_log.length > 120 ? '...' : '') + '</p>';
                        html += '</div></div>';
                    });
                    html += '</div>';

                    // Pagination
                    html += '<div class="flex items-center justify-between mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">';
                    html += '<span class="text-[11px] sm:text-xs text-gray-400 dark:text-gray-500 font-medium">Hal. ' + currentPage + '/' + totalPages + '</span>';
                    html += '<div class="flex items-center gap-1 sm:gap-1.5">';
                    if (currentPage > 1) {
                        html += '<button onclick="window._histPage=' + (currentPage - 1) + ';showHistoryRefresh(' + id + ')" class="w-7 h-7 flex items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition text-gray-600 dark:text-gray-300"><i class="fa-solid fa-chevron-left text-[10px]"></i></button>';
                    }
                    // Page input
                    html += '<input type="number" id="histPageInput" value="' + currentPage + '" min="1" max="' + totalPages + '" class="w-10 sm:w-12 h-7 text-center text-[11px] sm:text-xs border border-gray-200 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-gray-200 focus:ring-1 focus:ring-indigo-500 outline-none">';
                    html += '<button onclick="window._histPage=parseInt(document.getElementById(\'histPageInput\').value)||1;showHistoryRefresh(' + id + ')" class="h-7 px-2 sm:px-2.5 rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition text-[10px] sm:text-[11px] font-bold"><i class="fa-solid fa-arrow-right text-[9px] sm:text-[10px]"></i></button>';
                    if (currentPage < totalPages) {
                        html += '<button onclick="window._histPage=' + (currentPage + 1) + ';showHistoryRefresh(' + id + ')" class="w-7 h-7 flex items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition text-gray-600 dark:text-gray-300"><i class="fa-solid fa-chevron-right text-[10px]"></i></button>';
                    }
                    html += '</div></div>';
                }

                darkSwal({
                    title: '<span class="flex items-center gap-2"><span class="w-8 h-8 rounded-lg bg-blue-500/10 dark:bg-blue-500/10 flex items-center justify-center shrink-0"><i class="fa-solid fa-clock-rotate-left text-blue-500 dark:text-blue-400 text-sm"></i></span><span class="truncate">Riwayat Sparepart #' + id + '</span></span>',
                    html: html,
                    confirmButtonColor: '#00d4ff',
                    confirmButtonText: '<i class="fa-solid fa-xmark mr-1"></i>Tutup',
                    width: '92vw',
                    maxWidth: '550px',
                    customClass: { popup: 'history-modal' },
                    didOpen: function() {
                        var inp = document.getElementById('histPageInput');
                        if (inp) inp.addEventListener('keydown', function(e) { if (e.key === 'Enter') { window._histPage = parseInt(this.value) || 1; showHistoryRefresh(id); } });
                    },
                    didClose: function() {
                        if (window._lastGroupParams) {
                            var g = window._lastGroupParams;
                            renderGroupModal(g.kategori, g.jenis, g.type, g.page, g.q);
                        }
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
                confirmButtonColor: '#00d4ff',
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
                    darkSwal({ icon: 'success', title: 'Berhasil!', text: 'Data sparepart diupdate.', confirmButtonColor: '#00d4ff' }).then(() => location.reload());
                } else if (result.value) {
                    darkSwal({ icon: 'error', title: 'Error!', text: result.value.message || 'Gagal update.', confirmButtonColor: '#00d4ff' });
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
                            darkSwal({ icon: 'success', title: 'Berhasil!', text: 'Sparepart dihapus.', confirmButtonColor: '#00d4ff' }).then(() => location.reload());
                        } else {
                            darkSwal({ icon: 'error', title: 'Error!', text: data.message || 'Gagal hapus.', confirmButtonColor: '#00d4ff' });
                        }
                    });
            }
        });
    }

    /* ===== SN Scanner ===== */
    var snHtml5Qr = null;
    var snScannerRunning = false;

    function validateSnAfterScan(clean) {
        var snInput = document.getElementById('add-sn-input');
        var existing = snInput.value.trim();
        if (existing.indexOf(clean) !== -1) {
            darkSwal({ icon: 'warning', title: 'SN Duplikat', text: 'SN ' + clean + ' sudah ada di input.', confirmButtonColor: '#00d4ff' });
            return;
        }
        fetch('index.php?route=sparepart&action=showBySn&sn=' + encodeURIComponent(clean))
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success && data.data) {
                    var d = data.data;
                    darkSwal({
                        icon: 'warning',
                        title: 'SN Sudah Terdaftar',
                        html: '<div class="text-left text-sm"><p class="mb-2">SN <strong>' + clean + '</strong> sudah digunakan:</p>'
                            + '<div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3 space-y-1 text-left">'
                            + '<p><strong>Jenis:</strong> ' + (d.jenis_sparepart || '-') + '</p>'
                            + '<p><strong>Type:</strong> ' + (d.type_sparepart || '-') + '</p>'
                            + '<p><strong>Merk:</strong> ' + (d.merk || '-') + '</p>'
                            + '<p><strong>Status:</strong> ' + (d.status || '-') + '</p>'
                            + '</div></div>',
                        confirmButtonColor: '#00d4ff',
                        confirmButtonText: 'Tutup'
                    });
                } else {
                    if (existing !== '') {
                        snInput.value = existing + ', ' + clean;
                    } else {
                        snInput.value = clean;
                    }
                    snInput.focus();
                }
            })
            .catch(function() {
                if (existing !== '') {
                    snInput.value = existing + ', ' + clean;
                } else {
                    snInput.value = clean;
                }
                snInput.focus();
            });
    }

    function openSnScanner() {
        var overlay = document.getElementById('sn-scanner-overlay');
        overlay.style.display = '';
        if (!snHtml5Qr) {
            snHtml5Qr = new Html5Qrcode('sn-reader');
        }
        snHtml5Qr.start(
            { facingMode: 'environment' },
            { fps: 10, qrbox: { width: 250, height: 150 }, aspectRatio: 1.5 },
            function onScanSuccess(decodedText) {
                closeSnScanner();
                var clean = decodedText.replace(/^SN-/i, '').trim();
                validateSnAfterScan(clean);
            },
            function onScanFailure() {}
        ).then(function() {
            snScannerRunning = true;
        }).catch(function(err) {
            closeSnScanner();
            alert('Gagal akses kamera: ' + err);
        });
    }

    function closeSnScanner() {
        var overlay = document.getElementById('sn-scanner-overlay');
        overlay.style.display = 'none';
        if (snHtml5Qr && snScannerRunning) {
            snHtml5Qr.stop().then(function() {
                snScannerRunning = false;
            }).catch(function() {});
        }
    }

    function scanSnFromPhoto(input) {
        if (!input.files || !input.files[0]) return;
        var file = input.files[0];
        if (!snHtml5Qr) {
            snHtml5Qr = new Html5Qrcode('sn-reader');
        }
        snHtml5Qr.scanFileV2(file, true)
            .then(function(decodedText) {
                var clean = decodedText.replace(/^SN-/i, '').trim();
                validateSnAfterScan(clean);
            })
            .catch(function() {
                alert('Tidak ditemukan barcode/QR di foto. Pastikan foto jelas dan fokus.');
            });
        input.value = '';
    }
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.17.2"></script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>