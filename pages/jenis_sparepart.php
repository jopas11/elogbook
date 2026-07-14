<?php

$page_title = 'Jenis & Type';
$require_admin = true;
require_once __DIR__ . '/../includes/auth_check.php';

$db = getDB();

$jenisList = $db->query("SELECT * FROM jenis_spareparts WHERE type IS NULL ORDER BY nama")->fetchAll();
$typeList = $db->query("SELECT * FROM jenis_spareparts WHERE type IS NOT NULL ORDER BY nama")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div x-data="jenis()" class="page-enter">
    <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-4">
        <a href="<?= pageUrl('dashboard.php') ?>" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition">Home</a>
        <i class="fa-solid fa-chevron-right text-xs"></i>
        <span class="text-gray-700 dark:text-gray-200 font-medium">Jenis & Type</span>
    </nav>

    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Kelola Jenis & Type Sparepart</h2>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Daftar Jenis Sparepart</h3>
                <button @click="openModal('jenis', 'tambah')" class="px-3 py-1.5 bg-indigo-600 text-white rounded-lg text-xs hover:bg-indigo-700 transition font-medium inline-flex items-center gap-1">
                    <i class="fa-solid fa-plus"></i> Tambah
                </button>
            </div>
            <div class="space-y-2">
                <?php foreach ($jenisList as $j): ?>
                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <span class="text-sm text-gray-700 dark:text-gray-300"><?= escape($j['nama']) ?></span>
                    <div class="flex gap-1">
                        <button @click="openModal('jenis', 'edit', <?= $j['id'] ?>, '<?= escape($j['nama']) ?>')" title="Edit" class="p-1.5 text-xs bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400 rounded hover:bg-yellow-200 dark:hover:bg-yellow-900/50 transition">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <form method="POST" class="inline" onsubmit="return confirm('Hapus jenis ini?')">
                            <?= csrf() ?>
                            <input type="hidden" name="action" value="delete_jenis">
                            <input type="hidden" name="id" value="<?= $j['id'] ?>">
                            <button type="submit" title="Hapus" class="p-1.5 text-xs bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded hover:bg-red-200 dark:hover:bg-red-900/50 transition">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($jenisList)): ?>
                <p class="text-gray-400 dark:text-gray-500 text-sm text-center py-4">Belum ada jenis sparepart.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Daftar Type Sparepart</h3>
                <button @click="openModal('type', 'tambah')" class="px-3 py-1.5 bg-indigo-600 text-white rounded-lg text-xs hover:bg-indigo-700 transition font-medium inline-flex items-center gap-1">
                    <i class="fa-solid fa-plus"></i> Tambah
                </button>
            </div>
            <div class="space-y-2">
                <?php foreach ($typeList as $t): ?>
                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <div class="min-w-0 flex-1">
                        <span class="text-sm text-gray-700 dark:text-gray-300"><?= escape($t['nama']) ?></span>
                        <?php if ($t['type']): ?>
                        <span class="text-xs text-gray-400 dark:text-gray-500 ml-2">(<?= escape($t['type']) ?>)</span>
                        <?php endif; ?>
                    </div>
                    <div class="flex gap-1 shrink-0">
                        <button @click="openModal('type', 'edit', <?= $t['id'] ?>, '<?= escape($t['nama']) ?>', '<?= escape(isset($t['type']) ? $t['type'] : '') ?>')" title="Edit" class="p-1.5 text-xs bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400 rounded hover:bg-yellow-200 dark:hover:bg-yellow-900/50 transition">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <form method="POST" class="inline" onsubmit="return confirm('Hapus type ini?')">
                            <?= csrf() ?>
                            <input type="hidden" name="action" value="delete_type">
                            <input type="hidden" name="id" value="<?= $t['id'] ?>">
                            <button type="submit" title="Hapus" class="p-1.5 text-xs bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded hover:bg-red-200 dark:hover:bg-red-900/50 transition">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($typeList)): ?>
                <p class="text-gray-400 dark:text-gray-500 text-sm text-center py-4">Belum ada type sparepart.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div x-data="{ open: false, form: { action: '', title: '', id: 0, nama: '', type: '' } }"
     x-show="open" x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center p-4"
     @open-form-modal.window="open = true; form = $event.detail"
     x-transition:enter="modal-enter-active" x-transition:enter-start="modal-enter"
     x-transition:leave="modal-leave-active" x-transition:leave-end="modal-leave">
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="open = false" x-transition:enter="backdrop-enter-active" x-transition:enter-start="backdrop-enter" x-transition:leave="backdrop-leave-active" x-transition:leave-end="backdrop-leave"></div>
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-md relative z-10">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-800 dark:text-white" x-text="form.title"></h3>
            <button @click="open = false" class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition">&times;</button>
        </div>
        <form method="POST" class="p-6 space-y-4">
            <?= csrf() ?>
            <input type="hidden" name="id" :value="form.id">
            <input type="hidden" name="action" :value="form.action">
            <div x-show="!form.action.includes('type')">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nama <span class="text-red-500">*</span></label>
                <input type="text" name="nama" x-model="form.nama" :required="!form.action.includes('type')" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition">
            </div>
            <div x-show="form.action.includes('type')">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type <span class="text-red-500">*</span></label>
                <input type="text" name="type" x-model="form.type" :required="form.action.includes('type')" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition">
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
    document.addEventListener('alpine:init', () => {
        Alpine.data('jenis', () => ({
            openModal(tipe, mode, id = 0, nama = '', type = '') {
                let action = mode === 'tambah' ? `create_${tipe}` : `update_${tipe}`;
                let title = mode === 'tambah' ? `Tambah ${tipe.charAt(0).toUpperCase() + tipe.slice(1)}` : `Edit ${tipe.charAt(0).toUpperCase() + tipe.slice(1)}`;
                window.dispatchEvent(new CustomEvent('open-form-modal', {
                    detail: { action, title, id, nama, type }
                }));
            }
        }));
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
