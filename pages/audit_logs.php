<?php

$page_title = 'Audit Log';
$require_admin = true;
require_once __DIR__ . '/../includes/auth_check.php';

$db = getDB();

$search = _get($_GET, 'search', '');
$page = max(1, (int)_get($_GET, 'page', 1));
$perPage = in_array((int)_get($_GET, 'perPage', 10), array(10,20,30,50,100)) ? (int)_get($_GET, 'perPage', 10) : 10;

$where = 'WHERE 1=1';
$params = array();

if ($search) {
    $where .= " AND (al.action LIKE ? OR al.description LIKE ? OR u.name LIKE ? OR u.email LIKE ? OR al.ip_address LIKE ?)";
    $s = '%' . $search . '%';
    $params = array_merge($params, [$s, $s, $s, $s, $s]);
}

$countStmt = $db->prepare("SELECT COUNT(*) FROM audit_logs al LEFT JOIN users u ON al.user_id = u.id $where");
$countStmt->execute($params);
$totalItems = (int)$countStmt->fetchColumn();
$totalPages = (int)ceil($totalItems / $perPage);
$offset = ($page - 1) * $perPage;

$stmt = $db->prepare("SELECT al.*, u.name AS user_name, u.email AS user_email FROM audit_logs al LEFT JOIN users u ON al.user_id = u.id $where ORDER BY al.created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$logs = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-enter">
    <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-4">
        <a href="<?= pageUrl('dashboard.php') ?>" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition">Home</a>
        <i class="fa-solid fa-chevron-right text-xs"></i>
        <span class="text-gray-700 dark:text-gray-200 font-medium">Audit Log</span>
    </nav>

    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-6">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Audit Log</h2>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-4 mb-6">
        <form method="GET" action="index.php" onsubmit="submitFilter(this);return false" class="flex flex-wrap gap-3 items-end">
            <input type="hidden" name="url" value="audit_logs">
            <input type="hidden" name="page" value="<?= $page ?>">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1 font-medium">Cari</label>
                <input type="text" name="search" value="<?= escape($search) ?>" placeholder="Action, deskripsi, user, IP..." class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition">
            </div>

            <div class="flex gap-2">
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700 transition font-medium inline-flex items-center gap-1.5">
                    <i class="fa-solid fa-filter"></i> Filter
                </button>
                <a href="<?= pageUrl('audit_logs.php') ?>" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg text-sm hover:bg-gray-300 dark:hover:bg-gray-600 transition font-medium inline-flex items-center gap-1.5">
                    <i class="fa-solid fa-rotate"></i> Reset
                </a>
            </div>
        </form>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-700/50 text-left">
                        <th class="px-4 py-3 font-semibold text-gray-600 dark:text-gray-300 text-xs uppercase tracking-wider">Waktu</th>
                        <th class="px-4 py-3 font-semibold text-gray-600 dark:text-gray-300 text-xs uppercase tracking-wider">User</th>
                        <th class="px-4 py-3 font-semibold text-gray-600 dark:text-gray-300 text-xs uppercase tracking-wider">Aksi</th>
                        <th class="px-4 py-3 font-semibold text-gray-600 dark:text-gray-300 text-xs uppercase tracking-wider">Deskripsi</th>
                        <th class="px-4 py-3 font-semibold text-gray-600 dark:text-gray-300 text-xs uppercase tracking-wider">IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-400">Belum ada data.</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                        <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap"><?= formatDateTime($log['created_at']) ?></td>
                        <td class="px-4 py-3 text-xs text-gray-700 dark:text-gray-300 whitespace-nowrap">
                            <?php if ($log['user_name']): ?>
                                <span class="font-medium"><?= escape($log['user_name']) ?></span>
                                <span class="text-gray-400">(<?= escape($log['user_email']) ?>)</span>
                            <?php else: ?>
                                <span class="text-gray-400">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <?php
                            $actionColors = [
                                'login_berhasil' => 'text-emerald-600 dark:text-emerald-400',
                                'login_gagal' => 'text-red-600 dark:text-red-400',
                                'login_diblokir' => 'text-red-600 dark:text-red-400',
                                'logout' => 'text-gray-600 dark:text-gray-400',
                                'registrasi' => 'text-blue-600 dark:text-blue-400',
                                'password_diubah' => 'text-amber-600 dark:text-amber-400',
                                'akun_dihapus' => 'text-red-600 dark:text-red-400',
                                'user_dibuat' => 'text-indigo-600 dark:text-indigo-400',
                                'user_diubah' => 'text-indigo-600 dark:text-indigo-400',
                                'user_dihapus' => 'text-red-600 dark:text-red-400',
                                'sparepart_ditambah' => 'text-emerald-600 dark:text-emerald-400',
                                'sparepart_diubah' => 'text-amber-600 dark:text-amber-400',
                                'sparepart_dihapus' => 'text-red-600 dark:text-red-400',
                                'sparepart_diambil' => 'text-orange-600 dark:text-orange-400',
                                'sparepart_dikembalikan' => 'text-teal-600 dark:text-teal-400',
                            ];
                            $color = isset($actionColors[$log['action']]) ? $actionColors[$log['action']] : 'text-gray-600 dark:text-gray-400';
                            ?>
                            <span class="text-xs font-semibold <?= $color ?>"><?= escape($log['action']) ?></span>
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-400 max-w-xs truncate"><?= escape($log['description']) ?></td>
                        <td class="px-4 py-3 text-xs text-gray-400 font-mono whitespace-nowrap"><?= escape($log['ip_address']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?= renderPagination($page, $totalPages) ?>
    </div>
</div>

<script>try{var p=sessionStorage.getItem('sp_'+location.pathname+location.search);if(p){sessionStorage.removeItem('sp_'+location.pathname+location.search);window.scrollTo(0,+p);}}catch(e){}</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
