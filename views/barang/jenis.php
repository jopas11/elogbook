<?php

$page_title = 'Jenis & Type';
$require_admin = true;
require_once __DIR__ . '/../../helpers/auth.php';

$db = getDB();

$jenisList = $db->query("SELECT * FROM jenis_spareparts WHERE type IS NULL ORDER BY nama")->fetchAll();
$typeList = $db->query("SELECT * FROM jenis_spareparts WHERE type IS NOT NULL ORDER BY nama, type")->fetchAll();
$jenisOptions = $db->query("SELECT id, nama, kategori FROM jenis_spareparts WHERE type IS NULL ORDER BY nama")->fetchAll();
$merkList = $db->query("SELECT * FROM sparepart_merks ORDER BY kategori, jenis_sparepart, type_sparepart, merk")->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div x-data="jenis()" class="page-enter">
    <nav class="flex items-center gap-2 text-base text-gray-500 dark:text-gray-400 mb-4">
        <a href="<?= pageUrl('dashboard.php') ?>" class="hover:text-blue-600 transition">Home</a>
        <i class="fa-solid fa-chevron-right text-xs"></i>
        <span class="text-gray-700 dark:text-gray-200 font-medium">Jenis & Type</span>
    </nav>

    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-white gradient-text">Kelola Jenis & Type Sparepart</h2>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="glass-panel p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Daftar Jenis Sparepart</h3>
                <button @click="openModal('jenis', 'tambah')" class="px-3 py-1.5 bg-gradient-to-r bg-blue-600 text-white rounded-lg text-xs hover:bg-blue-700 magnetic-btn transition font-medium inline-flex items-center gap-1">
                    <i class="fa-solid fa-plus"></i> Tambah
                </button>
            </div>
            <div class="mb-3">
                <input type="text" x-model="jenisSearch" placeholder="Cari jenis..." class="w-full px-3 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 dark:text-gray-200 rounded-xl text-base focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500/50 outline-none transition-all duration-200">
            </div>
            <div class="space-y-2">
                <?php foreach ($jenisList as $j): ?>
                <div x-show="!jenisSearch || '<?= escape(strtolower($j['nama'] . ' ' . $j['kategori'])) ?>'.includes(jenisSearch.toLowerCase())"
                     class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <div class="min-w-0 flex-1 flex items-center gap-2">
                        <span class="text-sm text-gray-700 dark:text-gray-300"><?= escape($j['nama']) ?></span>
                        <?php if ($j['kategori']): ?>
                        <span class="text-xs px-1.5 py-0.5 rounded-full <?= $j['kategori'] === 'Aset' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' : 'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400' ?>"><?= escape($j['kategori']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="flex gap-1 shrink-0">
                        <button @click="openModal('jenis', 'edit', <?= $j['id'] ?>, '<?= escape($j['nama']) ?>', '', '<?= escape(isset($j['kategori']) ? $j['kategori'] : '') ?>')" title="Edit" class="p-1.5 text-xs bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400 rounded hover:bg-yellow-200 dark:hover:bg-yellow-900/50 transition">
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

        <div class="glass-panel p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Daftar Type Sparepart</h3>
                <button @click="openModal('type', 'tambah')" class="px-3 py-1.5 bg-gradient-to-r bg-blue-600 text-white rounded-lg text-xs hover:bg-blue-700 magnetic-btn transition font-medium inline-flex items-center gap-1">
                    <i class="fa-solid fa-plus"></i> Tambah
                </button>
            </div>
            <div class="mb-3">
                <input type="text" x-model="typeSearch" placeholder="Cari type..." class="w-full px-3 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 dark:text-gray-200 rounded-xl text-base focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500/50 outline-none transition-all duration-200">
            </div>
            <div class="space-y-2">
                <?php foreach ($typeList as $t): ?>
                <div x-show="!typeSearch || '<?= escape(strtolower($t['nama'] . ' ' . $t['type'])) ?>'.includes(typeSearch.toLowerCase())"
                     class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <div class="min-w-0 flex-1">
                        <span class="text-xs text-gray-400 dark:text-gray-500 font-medium"><?= escape($t['nama']) ?></span>
                        <span class="text-sm text-gray-700 dark:text-gray-300 ml-1"><?= escape($t['type']) ?></span>
                    </div>
                    <div class="flex gap-1 shrink-0">
                        <button @click="openModal('type', 'edit', <?= $t['id'] ?>, '<?= escape($t['nama']) ?>', '<?= escape($t['type']) ?>')" title="Edit" class="p-1.5 text-xs bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400 rounded hover:bg-yellow-200 dark:hover:bg-yellow-900/50 transition">
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

        <div class="glass-panel p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Daftar Merk</h3>
                <button @click="openModal('merk', 'tambah')" class="px-3 py-1.5 bg-gradient-to-r bg-blue-600 text-white rounded-lg text-xs hover:bg-blue-700 magnetic-btn transition font-medium inline-flex items-center gap-1">
                    <i class="fa-solid fa-plus"></i> Tambah
                </button>
            </div>
            <div class="mb-3">
                <input type="text" x-model="merkSearch" placeholder="Cari merk..." class="w-full px-3 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 dark:text-gray-200 rounded-xl text-base focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500/50 outline-none transition-all duration-200">
            </div>
            <div class="space-y-2">
                <?php foreach ($merkList as $m): ?>
                <div x-show="!merkSearch || '<?= escape(strtolower($m['kategori'] . ' ' . $m['jenis_sparepart'] . ' ' . ($m['type_sparepart'] ?: '') . ' ' . $m['merk'])) ?>'.includes(merkSearch.toLowerCase())"
                     class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-1.5 mb-0.5">
                            <span class="text-xs px-1.5 py-0.5 rounded-full <?= $m['kategori'] === 'Aset' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' : 'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400' ?>"><?= escape($m['kategori']) ?></span>
                            <span class="text-xs text-gray-400 dark:text-gray-500"><?= escape($m['jenis_sparepart']) ?></span>
                            <?php if ($m['type_sparepart']): ?>
                            <span class="text-xs text-gray-400 dark:text-gray-500">/ <?= escape($m['type_sparepart']) ?></span>
                            <?php endif; ?>
                        </div>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300"><?= escape($m['merk']) ?></span>
                    </div>
                    <div class="flex gap-1 shrink-0">
                        <button onclick="document.dispatchEvent(new CustomEvent('open-form-modal', {detail:{action:'update_merk',title:'Edit Merk',id:<?= $m['id'] ?>,nama:'<?= escape($m['jenis_sparepart']) ?>',type:'<?= escape($m['type_sparepart']) ?>',kategori:'<?= escape($m['kategori']) ?>',merk:'<?= escape($m['merk']) ?>'}}))" title="Edit" class="p-1.5 text-xs bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400 rounded hover:bg-yellow-200 dark:hover:bg-yellow-900/50 transition">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <form method="POST" class="inline" onsubmit="return confirm('Hapus merk ini?')">
                            <?= csrf() ?>
                            <input type="hidden" name="action" value="delete_merk">
                            <input type="hidden" name="id" value="<?= $m['id'] ?>">
                            <button type="submit" title="Hapus" class="p-1.5 text-xs bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded hover:bg-red-200 dark:hover:bg-red-900/50 transition">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($merkList)): ?>
                <p class="text-gray-400 dark:text-gray-500 text-sm text-center py-4">Belum ada merk sparepart.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div x-data="formModal()"
     x-show="open" x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center p-4"
     x-transition:enter="modal-enter-active" x-transition:enter-start="modal-enter"
     x-transition:leave="modal-leave-active" x-transition:leave-end="modal-leave">
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="open = false" x-transition:enter="backdrop-enter-active" x-transition:enter-start="backdrop-enter" x-transition:leave="backdrop-leave-active" x-transition:leave-end="backdrop-leave"></div>
    <div class="glass-panel rounded-2xl shadow-xl w-full max-w-md relative z-10">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-800 dark:text-white" x-text="title"></h3>
            <button @click="open = false" class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition">&times;</button>
        </div>
        <form method="POST" class="p-6 space-y-4">
            <?= csrf() ?>
            <input type="hidden" name="id" :value="id">
            <input type="hidden" name="action" :value="action">

            <!-- Form: Jenis (create/update) -->
            <template x-if="isJenis">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kategori <span class="text-red-500">*</span></label>
                        <select name="kategori" x-model="kategori" required
                                class="w-full px-3 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 dark:text-gray-200 rounded-xl text-base focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500/50 outline-none transition-all duration-200">
                            <option value="">Pilih Kategori</option>
                            <option value="Aset">Aset</option>
                            <option value="Non-Aset">Non-Aset</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nama <span class="text-red-500">*</span></label>
                        <input type="text" name="nama" x-model="nama" required
                               class="w-full px-3 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 dark:text-gray-200 rounded-xl text-base focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500/50 outline-none transition-all duration-200">
                    </div>
                </div>
            </template>

            <!-- Form: Type (create/update) -->
            <template x-if="isType">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Jenis <span class="text-red-500">*</span></label>
                        <select name="nama" x-model="nama" required
                                class="w-full px-3 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 dark:text-gray-200 rounded-xl text-base focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500/50 outline-none transition-all duration-200">
                            <option value="">Pilih Jenis</option>
                            <template x-for="j in jenisList" :key="j.id">
                                <option :value="j.nama" x-text="j.nama + ' (' + j.kategori + ')'"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type <span class="text-red-500">*</span></label>
                        <input type="text" name="type" x-model="type" required
                               class="w-full px-3 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 dark:text-gray-200 rounded-xl text-base focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500/50 outline-none transition-all duration-200">
                    </div>
                </div>
            </template>

            <!-- Form: Merk (create/update) -->
            <template x-if="isMerk">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kategori <span class="text-red-500">*</span></label>
                        <select name="kategori" x-model="kategori" required
                                @change="nama = ''; type = ''"
                                class="w-full px-3 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 dark:text-gray-200 rounded-xl text-base focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500/50 outline-none transition-all duration-200">
                            <option value="">Pilih Kategori</option>
                            <option value="Aset">Aset</option>
                            <option value="Non-Aset">Non-Aset</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Jenis <span class="text-red-500">*</span></label>
                        <select name="jenis_sparepart" x-model="nama" required
                                @change="type = ''"
                                class="w-full px-3 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 dark:text-gray-200 rounded-xl text-base focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500/50 outline-none transition-all duration-200">
                            <option value="">Pilih Jenis</option>
                            <template x-for="j in filteredJenises" :key="j.id">
                                <option :value="j.nama" x-text="j.nama"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type</label>
                        <select name="type_sparepart" x-model="type"
                                class="w-full px-3 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 dark:text-gray-200 rounded-xl text-base focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500/50 outline-none transition-all duration-200">
                            <option value="">Pilih Type</option>
                            <template x-for="t in filteredTypes" :key="t.id">
                                <option :value="t.type" x-text="t.type"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Merk <span class="text-red-500">*</span></label>
                        <input type="text" name="merk" x-model="merk" required
                               class="w-full px-3 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 dark:text-gray-200 rounded-xl text-base focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500/50 outline-none transition-all duration-200">
                    </div>
                </div>
            </template>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" @click="open = false" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition text-sm font-medium">Batal</button>
                <button type="submit" class="px-4 py-2 bg-gradient-to-r bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-medium inline-flex items-center gap-1.5 magnetic-btn">
                    <i class="fa-solid fa-save"></i> Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('jenis', () => ({
            jenisSearch: '',
            typeSearch: '',
            merkSearch: '',
            openModal(tipe, mode, id = 0, nama = '', type = '', kategori = '', merk = '') {
                let action = mode === 'tambah' ? `create_${tipe}` : `update_${tipe}`;
                let title = mode === 'tambah' ? `Tambah ${tipe.charAt(0).toUpperCase() + tipe.slice(1)}` : `Edit ${tipe.charAt(0).toUpperCase() + tipe.slice(1)}`;
                document.dispatchEvent(new CustomEvent('open-form-modal', {
                    detail: { action, title, id, nama, type, kategori, merk }
                }));
            }
        }));

        Alpine.data('formModal', () => ({
            open: false,
            action: '',
            title: '',
            id: 0,
            nama: '',
            type: '',
            kategori: '',
            merk: '',
            jenisList: <?= json_encode($jenisOptions) ?>,
            typeList: <?= json_encode($typeList) ?>,

            get filteredJenises() {
                var self = this;
                return self.jenisList.filter(function(j) { return j.kategori === self.kategori; });
            },
            get filteredTypes() {
                var self = this;
                return self.typeList.filter(function(t) { return t.nama === self.nama && t.kategori === self.kategori; });
            },
            get isJenis() {
                return !this.action.includes('type') && !this.action.includes('merk');
            },
            get isType() {
                return this.action.includes('type');
            },
            get isMerk() {
                return this.action.includes('merk');
            },

            init() {
                document.addEventListener('open-form-modal', (e) => {
                    const d = e.detail;
                    this.open = true;
                    this.action = d.action;
                    this.title = d.title;
                    this.id = d.id || 0;
                    this.nama = d.nama || '';
                    this.type = d.type || '';
                    this.kategori = d.kategori || '';
                    this.merk = d.merk || '';
                });
            }
        }));
    });
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
