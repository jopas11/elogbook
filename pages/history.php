<?php

$page_title = 'History';
$require_admin = false;
require_once __DIR__ . '/../includes/auth_check.php';

$db = getDB();

$search = _get($_GET, 'search', '');
$where = "WHERE l.deleted_at IS NULL";
$params = array();

if ($search) {
    list($ftWhere, $ftParams) = ftSearch(
        array('s.jenis_sparepart', 's.merk', 's.type_sparepart', 's.serial_number'),
        $search
    );
    // Also search logbook columns — combine with OR
    list($ftWhere2, $ftParams2) = ftSearch(
        array('l.pic_penerima'),
        $search
    );
    $where .= " AND (($ftWhere) OR ($ftWhere2) OR l.tipe_transaksi LIKE ?)";
    $params = array_merge($params, $ftParams, $ftParams2, array('%' . $search . '%'));
}
if (!empty($_GET['date_from'])) {
    $where .= " AND l.tanggal >= ?";
    $params[] = $_GET['date_from'];
}
if (!empty($_GET['date_to'])) {
    $where .= " AND l.tanggal <= ?";
    $params[] = $_GET['date_to'];
}

if (!isAdmin()) {
    $where .= " AND l.user_id = ?";
    $params[] = $user['id'];
}

list($logbooks, $page, $totalPages) = paginate($db, "SELECT l.*, s.jenis_sparepart, s.merk, u.name as user_name FROM logbooks l JOIN spareparts s ON s.id = l.sparepart_id JOIN users u ON u.id = l.user_id $where ORDER BY l.created_at DESC", $params);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div x-data="history()" class="page-enter">
    <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-4">
        <a href="<?= pageUrl('dashboard.php') ?>" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition">Home</a>
        <i class="fa-solid fa-chevron-right text-xs"></i>
        <span class="text-gray-700 dark:text-gray-200 font-medium">History</span>
    </nav>

    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-white">History Logbook</h2>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-4 mb-6">
        <form method="GET" action="index.php" class="flex flex-wrap gap-3 items-end">
            <input type="hidden" name="url" value="history">
            <div>
                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1 font-medium">Cari</label>
                <input type="text" name="search" value="<?= escape($search) ?>" placeholder="Jenis, PIC, transaksi..." class="px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition">
            </div>
            <div>
                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1 font-medium">Dari Tanggal</label>
                <input type="date" name="date_from" value="<?= escape(_get($_GET, 'date_from', '')) ?>" class="px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition">
            </div>
            <div>
                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1 font-medium">Sampai Tanggal</label>
                <input type="date" name="date_to" value="<?= escape(_get($_GET, 'date_to', '')) ?>" class="px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700 transition font-medium inline-flex items-center gap-1.5">
                    <i class="fa-solid fa-filter"></i> Filter
                </button>
                <a href="<?= pageUrl('history.php') ?>" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg text-sm hover:bg-gray-300 dark:hover:bg-gray-600 transition font-medium inline-flex items-center gap-1.5">
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
                        <th class="px-4 py-3 text-left font-semibold">Tanggal</th>
                        <th class="px-4 py-3 text-left font-semibold">Sparepart</th>
                        <th class="px-4 py-3 text-left font-semibold">Transaksi</th>
                        <th class="px-4 py-3 text-left font-semibold">Status</th>
                        <th class="px-4 py-3 text-left font-semibold">PIC</th>
                        <th class="px-4 py-3 text-left font-semibold">User</th>
                        <th class="px-4 py-3 text-left font-semibold hidden lg:table-cell">Keterangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    <?php foreach ($logbooks as $i => $log): ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition <?= $i % 2 === 0 ? 'bg-white dark:bg-gray-800' : 'bg-gray-50/50 dark:bg-gray-800/50' ?>">
                        <td class="px-4 py-3 font-medium text-gray-800 dark:text-gray-200"><?= $log['id'] ?></td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400"><?= formatTanggal($log['tanggal']) ?></td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300"><?= escape($log['jenis_sparepart']) ?></td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 text-xs font-medium rounded-full
                                <?= $log['tipe_transaksi'] === 'Barang Masuk' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : '' ?>
                                <?= $log['tipe_transaksi'] === 'Barang Keluar' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' : '' ?>
                                <?= $log['tipe_transaksi'] === 'Ubah Status' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' : '' ?>
                                <?= $log['tipe_transaksi'] === 'Dalam Perbaikan' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400' : '' ?>
                                <?= $log['tipe_transaksi'] === 'Permintaan' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400' : '' ?>
                            "><?= $log['tipe_transaksi'] ?></span>
                        </td>
                        <td class="px-4 py-3"><?= renderStatusTransition($log) ?></td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400"><?= escape(isset($log['pic_penerima']) ? $log['pic_penerima'] : '-') ?></td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400"><?= escape($log['user_name']) ?></td>
                        <td class="px-4 py-3 max-w-xs truncate text-gray-600 dark:text-gray-400 hidden lg:table-cell"><?= escape($log['keterangan_log']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($logbooks)): ?>
                    <tr>
                        <td colspan="8" class="px-4 py-12 text-center">
                            <div class="flex flex-col items-center gap-2 text-gray-400 dark:text-gray-500">
                                <i class="fa-solid fa-clock-rotate-left text-4xl"></i>
                                <p class="text-sm">Belum ada history.</p>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?= renderPagination($page, $totalPages) ?>
    </div>

    <!-- Mobile Cards -->
    <div class="md:hidden space-y-3">
        <?php if (empty($logbooks)): ?>
        <div class="flex flex-col items-center gap-2 py-12 text-gray-400 dark:text-gray-500">
            <i class="fa-solid fa-clock-rotate-left text-4xl"></i>
            <p class="text-sm">Belum ada history.</p>
        </div>
        <?php else: ?>
            <?php foreach ($logbooks as $log): ?>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-4 transition hover:shadow-md">
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 text-sm
                        <?= $log['tipe_transaksi'] === 'Barang Masuk' ? 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400' : '' ?>
                        <?= $log['tipe_transaksi'] === 'Barang Keluar' ? 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400' : '' ?>
                        <?= $log['tipe_transaksi'] === 'Ubah Status' ? 'bg-yellow-100 text-yellow-600 dark:bg-yellow-900/30 dark:text-yellow-400' : '' ?>
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
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-200"><?= escape($log['jenis_sparepart']) ?></p>
                        <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5 text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            <span class="px-1.5 py-0.5 rounded text-xs font-medium
                                <?= $log['tipe_transaksi'] === 'Barang Masuk' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : '' ?>
                                <?= $log['tipe_transaksi'] === 'Barang Keluar' ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' : '' ?>
                                <?= $log['tipe_transaksi'] === 'Ubah Status' ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400' : '' ?>
                                <?= $log['tipe_transaksi'] === 'Dalam Perbaikan' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' : '' ?>
                                <?= $log['tipe_transaksi'] === 'Permintaan' ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400' : '' ?>
                            "><?= $log['tipe_transaksi'] ?></span>
                            <?= renderStatusTransition($log) ?>
                            <span><?= escape($log['user_name']) ?></span>
                            <span><?= formatTanggal($log['tanggal']) ?></span>
                        </div>
                        <?php if ($log['keterangan_log']): ?>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1 line-clamp-2"><?= escape($log['keterangan_log']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
        <?= renderPagination($page, $totalPages) ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.17.2"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
