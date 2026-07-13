<?php

$page_title = 'Sparepart Keluar';
$require_admin = false;
require_once __DIR__ . '/../includes/auth_check.php';

$db = getDB();

$search = $_GET['search'] ?? '';
$where = "WHERE status = 'Tersedia' AND deleted_at IS NULL";
$params = [];
if ($search) {
    [$ftWhere, $params] = ftSearch(
        ['jenis_sparepart', 'merk', 'type_sparepart', 'serial_number'],
        $search,
        'id'
    );
    $where .= " AND ($ftWhere)";
}
$baseQuery = "SELECT * FROM spareparts $where ORDER BY created_at DESC";
[$spareparts, $page, $totalPages] = paginate($db, $baseQuery, $params);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div x-data="sparepartKeluar()" class="page-enter">
    <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-4">
        <a href="<?= pageUrl('dashboard.php') ?>" class="hover:text-primary-800 dark:hover:text-primary-400 transition">Home</a>
        <i class="fa-solid fa-chevron-right text-xs"></i>
        <span class="text-gray-700 dark:text-gray-200 font-medium">Sparepart Keluar</span>
    </nav>

    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Sparepart Keluar</h2>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-4 mb-6">
        <form method="GET" action="index.php" class="flex gap-3 items-end">
            <input type="hidden" name="url" value="sparepart_keluar">
            <div class="flex-1">
                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1 font-medium">Cari</label>
                <input type="text" name="search" value="<?= escape($search) ?>" placeholder="ID, jenis, merk..." class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 outline-none transition">
            </div>
            <button type="submit" class="px-4 py-2 bg-primary-800 text-white rounded-lg text-sm hover:bg-primary-900 transition font-medium inline-flex items-center gap-1.5">
                <i class="fa-solid fa-search"></i> Cari
            </button>
            <?php if ($search): ?>
            <a href="<?= pageUrl('sparepart_keluar.php') ?>" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg text-sm hover:bg-gray-300 dark:hover:bg-gray-600 transition font-medium inline-flex items-center gap-1.5">
                <i class="fa-solid fa-rotate"></i> Reset
            </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Desktop -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden hidden md:block">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/50 text-gray-600 dark:text-gray-300">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold">ID</th>
                        <th class="px-4 py-3 text-left font-semibold">Jenis</th>
                        <th class="px-4 py-3 text-left font-semibold">Type</th>
                        <th class="px-4 py-3 text-left font-semibold">Merk</th>
                        <th class="px-4 py-3 text-left font-semibold hidden lg:table-cell">SN</th>
                        <th class="px-4 py-3 text-left font-semibold">Qty</th>
                        <th class="px-4 py-3 text-center font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    <?php foreach ($spareparts as $sp): ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition bg-white dark:bg-gray-800">
                        <td class="px-4 py-3 font-medium text-gray-800 dark:text-gray-200">#<?= $sp['id'] ?></td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300"><?= escape($sp['jenis_sparepart']) ?></td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400"><?= escape($sp['type_sparepart'] ?? '-') ?></td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400"><?= escape($sp['merk'] ?? '-') ?></td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400 hidden lg:table-cell"><?= escape($sp['serial_number'] ?? '-') ?></td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300"><?= $sp['quantity'] ?></td>
                        <td class="px-4 py-3 text-center">
                            <button @click="openAmbil(<?= $sp['id'] ?>, '<?= escape($sp['jenis_sparepart']) ?>', '<?= escape($sp['merk'] ?? '') ?>', <?= $sp['quantity'] ?>)" class="px-3 py-1.5 bg-green-600 text-white rounded-lg text-xs hover:bg-green-700 transition font-medium inline-flex items-center gap-1">
                                <i class="fa-solid fa-right-from-bracket"></i> Ambil
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($spareparts)): ?>
                    <tr>
                        <td colspan="7" class="px-4 py-12 text-center">
                            <div class="flex flex-col items-center gap-2 text-gray-400 dark:text-gray-500">
                                <i class="fa-solid fa-box-open text-4xl"></i>
                                <p class="text-sm">Tidak ada sparepart tersedia.</p>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
            </tbody>
                </table>
            </div>
            <?= renderPagination($page, $totalPages) ?>
        </div>

        <!-- Mobile -->
        <div class="md:hidden space-y-3">
        <?php if (empty($spareparts)): ?>
        <div class="flex flex-col items-center gap-2 py-12 text-gray-400 dark:text-gray-500">
            <i class="fa-solid fa-box-open text-4xl"></i>
            <p class="text-sm">Tidak ada sparepart tersedia.</p>
        </div>
        <?php else: ?>
            <?php foreach ($spareparts as $sp): ?>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-4 transition hover:shadow-md">
                <div class="flex justify-between items-start mb-2">
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold text-gray-800 dark:text-gray-200 text-sm">#<?= $sp['id'] ?> — <?= escape($sp['jenis_sparepart']) ?></p>
                        <p class="text-xs text-gray-500 dark:text-gray-400"><?= escape($sp['merk'] ?? '-') ?> | <?= escape($sp['type_sparepart'] ?? '-') ?></p>
                    </div>
                    <span class="text-xs text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded-full font-medium">Qty: <?= $sp['quantity'] ?></span>
                </div>
                <button @click="openAmbil(<?= $sp['id'] ?>, '<?= escape($sp['jenis_sparepart']) ?>', '<?= escape($sp['merk'] ?? '') ?>', <?= $sp['quantity'] ?>)" class="w-full mt-2 px-3 py-2 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700 transition font-medium inline-flex items-center justify-center gap-1.5">
                    <i class="fa-solid fa-right-from-bracket"></i> Ambil
                </button>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
        <?= renderPagination($page, $totalPages) ?>
    </div>
</div>

<!-- Ambil Modal -->
<div x-data="{ open: false, sp: {} }"
     x-show="open" x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center p-4"
     @open-ambil.window="open = true; sp = $event.detail"
     x-transition:enter="modal-enter-active" x-transition:enter-start="modal-enter"
     x-transition:leave="modal-leave-active" x-transition:leave-end="modal-leave">
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="open = false" x-transition:enter="backdrop-enter-active" x-transition:enter-start="backdrop-enter" x-transition:leave="backdrop-leave-active" x-transition:leave-end="backdrop-leave"></div>
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-lg relative z-10">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-800 dark:text-white">Ambil Sparepart</h3>
            <button @click="open = false" class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition">&times;</button>
        </div>
        <form method="POST" class="p-6 space-y-4">
            <?= csrf() ?>
            <input type="hidden" name="sparepart_id" :value="sp.id">
            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3 mb-2">
                <p class="text-sm text-gray-700 dark:text-gray-300"><span class="font-medium">Sparepart:</span> <span x-text="sp.jenis"></span></p>
                <p class="text-sm text-gray-700 dark:text-gray-300"><span class="font-medium">Merk:</span> <span x-text="sp.merk || '-'"></span></p>
                <p class="text-sm text-gray-700 dark:text-gray-300"><span class="font-medium">Tersedia:</span> <span x-text="sp.qty"></span></p>
            </div>
            <div x-show="sp.qty > 1">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Jumlah Ambil</label>
                <input type="number" name="quantity" value="1" min="1" :max="sp.qty" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 outline-none transition">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">PIC Penerima <span class="text-red-500">*</span></label>
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
                    <i class="fa-solid fa-check"></i> Konfirmasi Ambil
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('sparepartKeluar', () => ({
            openAmbil(id, jenis, merk, qty) {
                window.dispatchEvent(new CustomEvent('open-ambil', {
                    detail: { id, jenis, merk, qty }
                }));
            }
        }));
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>