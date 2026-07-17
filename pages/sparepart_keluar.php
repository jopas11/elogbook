<?php

$page_title = 'Sparepart Keluar';
$require_admin = false;
require_once __DIR__ . '/../includes/auth_check.php';

$db = getDB();

$jenisList = $db->query("SELECT nama FROM jenis_spareparts WHERE type IS NULL ORDER BY nama")->fetchAll();
$jenisListNama = array_column($jenisList, 'nama');

$typeRows = $db->query("SELECT nama, type FROM jenis_spareparts WHERE type IS NOT NULL ORDER BY nama, type")->fetchAll();
$typesByJenis = [];
foreach ($typeRows as $t) {
    $typesByJenis[$t['nama']][] = $t['type'];
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div x-data="sparepartKeluarForm()" class="page-enter max-w-3xl mx-auto">
    <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-4">
        <a href="<?= pageUrl('dashboard.php') ?>" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition">Home</a>
        <i class="fa-solid fa-chevron-right text-xs"></i>
        <span class="text-gray-700 dark:text-gray-200 font-medium">Sparepart Keluar</span>
    </nav>

    <!-- Tabs -->
    <div class="flex gap-1 mb-6 bg-gray-100 dark:bg-gray-800 p-1 rounded-xl w-fit border border-gray-200 dark:border-gray-700">
        <button @click="tab = 'aset'; resetForm()"
                :class="tab === 'aset' ? 'bg-white dark:bg-gray-700 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                class="px-5 py-2 rounded-lg text-sm font-semibold transition-all duration-200 inline-flex items-center gap-2">
            <i class="fa-solid fa-microchip"></i> Aset
        </button>
        <button @click="tab = 'nonaset'; resetForm()"
                :class="tab === 'nonaset' ? 'bg-white dark:bg-gray-700 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                class="px-5 py-2 rounded-lg text-sm font-semibold transition-all duration-200 inline-flex items-center gap-2">
            <i class="fa-solid fa-box"></i> Non-Aset
        </button>
    </div>

    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-white">
            Catat Sparepart Keluar <span class="text-indigo-600 dark:text-indigo-400">—</span>
            <span x-text="tab === 'aset' ? 'Aset' : 'Non-Aset'" class="text-indigo-600 dark:text-indigo-400"></span>
        </h2>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 card-hover">
        <form method="POST" action="index.php?url=sparepart_keluar" class="space-y-5">
            <?= csrf() ?>
            <input type="hidden" name="mode" :value="tab === 'aset' ? 'aset_update' : 'nonaset_insert'">
            <input type="hidden" name="sparepart_id" x-model="sparepartId">

            <!-- ========== ASET TAB ========== -->
            <template x-if="tab === 'aset'">
                <div class="space-y-5">
                    <!-- SN Lookup - FIRST FIELD -->
                    <div class="bg-indigo-50 dark:bg-indigo-900/20 rounded-lg p-4 border border-indigo-100 dark:border-indigo-800/30">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Cari Sparepart <span class="text-red-500">*</span>
                        </label>
                        <div class="flex gap-2 relative">
                            <div class="flex flex-1">
                                <span class="inline-flex items-center px-3 py-2 border border-r-0 border-gray-300 dark:border-gray-600 dark:bg-gray-600 bg-gray-100 text-gray-600 dark:text-gray-300 rounded-l-lg text-sm font-mono select-none">SN-</span>
                                <input type="text" x-model="snAset" @input="cariBySn" @focus="showSuggestions = false; setTimeout(() => _fetchSuggestions(), 100)"
                                       class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-r-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition font-mono"
                                       placeholder="Masukkan serial number" autocomplete="off">
                            </div>
                            <button type="button" @click="cariBySn" :disabled="!snAset.trim()"
                                    class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition disabled:opacity-50 inline-flex items-center gap-1.5">
                                <i class="fa-solid fa-search"></i> Cari
                            </button>

                            <!-- Suggestions dropdown -->
                            <div x-show="showSuggestions" x-cloak
                                 class="absolute top-full left-0 right-0 mt-1 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg z-50 max-h-48 overflow-y-auto">
                                <template x-for="(item, idx) in suggestions" :key="idx">
                                    <button type="button" @click="selectSuggestion(item)"
                                            class="w-full text-left px-3 py-2 text-sm hover:bg-indigo-50 dark:hover:bg-indigo-900/30 border-b border-gray-100 dark:border-gray-600 last:border-b-0 transition flex items-center gap-2">
                                        <span class="font-mono text-gray-800 dark:text-gray-200" x-text="item.serial_number.replace(/^SN-/, '')"></span>
                                        <span class="text-xs text-gray-400 dark:text-gray-500" x-text="(item.jenis_sparepart || '') + (item.merk ? ' (' + item.merk + ')' : '')"></span>
                                        <span class="ml-auto inline-flex items-center px-1.5 py-0.5 text-xs font-semibold rounded-full"
                                              :class="statusBadgeClass(item.status)" x-text="item.status"></span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <!-- Loading / Found / Not Found -->
                        <div class="mt-2 text-sm" x-show="snPesan" x-cloak>
                            <template x-if="snFound">
                                <div>
                                    <div class="flex items-center gap-1.5 text-emerald-600 dark:text-emerald-400">
                                        <i class="fa-solid fa-circle-check"></i>
                                        <span x-text="snPesan"></span>
                                    </div>
                                    <div class="mt-1.5 flex items-center gap-2" x-show="status_lama">
                                        <span class="text-xs text-gray-500 dark:text-gray-400">Status:</span>
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-semibold rounded-full"
                                              :class="statusBadgeClass(status_lama)" x-text="status_lama"></span>
                                    </div>
                                    <div class="mt-1.5 flex items-center gap-1.5 text-xs font-medium px-2.5 py-1.5 rounded-lg"
                                         x-show="status_lama === 'Terpakai'"
                                         :class="'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'">
                                        <i class="fa-solid fa-triangle-exclamation"></i>
                                        <span>Sparepart ini sudah <strong>Terpakai</strong>! Pastikan akan dipakai lagi.</span>
                                    </div>
                                    <div class="mt-1.5 flex items-center gap-1.5 text-xs font-medium px-2.5 py-1.5 rounded-lg"
                                         x-show="status_lama === 'Rusak'"
                                         :class="'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'">
                                        <i class="fa-solid fa-triangle-exclamation"></i>
                                        <span>Sparepart ini berstatus <strong>Rusak</strong>.</span>
                                    </div>
                                    <div class="mt-1.5 flex items-center gap-1.5 text-xs font-medium px-2.5 py-1.5 rounded-lg"
                                         x-show="status_lama === 'Tersedia'"
                                         :class="'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'">
                                        <i class="fa-solid fa-circle-check"></i>
                                        <span>Sparepart <strong>Tersedia</strong>, siap dipakai.</span>
                                    </div>
                                </div>
                            </template>
                            <template x-if="!snFound && snPesan">
                                <div class="flex items-center gap-1.5 text-red-600 dark:text-red-400">
                                    <i class="fa-solid fa-circle-exclamation"></i>
                                    <span x-text="snPesan"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Data fields (auto-filled from SN lookup) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Jenis Sparepart</label>
                            <input type="text" name="jenis_sparepart" x-model="selectedJenis" readonly
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg text-sm bg-gray-50 dark:bg-gray-600 cursor-not-allowed opacity-70">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type Sparepart</label>
                            <input type="text" name="type_sparepart" x-model="selectedType" readonly
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg text-sm bg-gray-50 dark:bg-gray-600 cursor-not-allowed opacity-70">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Merk</label>
                            <input type="text" name="merk" x-model="asetMerk" readonly
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg text-sm bg-gray-50 dark:bg-gray-600 cursor-not-allowed opacity-70">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tanggal</label>
                            <input type="date" name="tanggal" x-model="asetTanggal"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition">
                        </div>
                    </div>

                    <!-- Status Aset -->
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
                            <i class="fa-solid fa-arrows-rotate text-indigo-500"></i>
                            Perubahan Status
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status Sekarang</label>
                                <input type="text" x-model="status_lama" readonly
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg text-sm bg-gray-50 dark:bg-gray-600 cursor-not-allowed opacity-70">
                                <input type="hidden" name="status_lama" x-model="status_lama">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status Baru <span class="text-red-500">*</span></label>
                                <select name="status_baru" x-model="status_baru" required
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition">
                                    <option value="">— Pilih Status —</option>
                                    <template x-for="s in filteredStatusBaru" :key="s">
                                        <option :value="s" x-text="s"></option>
                                    </template>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- PIC & Department -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">PIC <span class="text-red-500">*</span></label>
                            <input type="text" name="pic" x-model="asetPic" required
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition"
                                   placeholder="Nama penerima">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Department <span class="text-red-500">*</span></label>
                            <input type="text" name="department" x-model="asetDepartment" required
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition"
                                   placeholder="Nama department">
                        </div>
                    </div>

                    <!-- SN list for hidden input -->
                    <template x-for="(sn, idx) in snList" :key="idx">
                        <input type="hidden" name="sn_list[]" :value="sn">
                    </template>
                </div>
            </template>

            <!-- ========== NON-ASET TAB ========== -->
            <template x-if="tab === 'nonaset'">
                <div class="space-y-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Jenis Sparepart <span class="text-red-500">*</span></label>
                            <select name="jenis_sparepart" x-model="selectedJenis" @change="selectedType = ''" required
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition">
                                <option value="">Pilih Jenis</option>
                                <?php foreach ($jenisListNama as $j): ?>
                                <option value="<?= escape($j) ?>"><?= escape($j) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type Sparepart</label>
                            <select name="type_sparepart" x-model="selectedType"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition">
                                <option value="">Pilih Type</option>
                                <template x-for="t in filteredTypes" :key="t">
                                    <option :value="t" x-text="t"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Merk <span class="text-red-500">*</span></label>
                            <input type="text" name="merk" required
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Jumlah <span class="text-red-500">*</span></label>
                            <input type="number" name="quantity" value="1" min="1"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tanggal <span class="text-red-500">*</span></label>
                            <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition">
                        </div>
                    </div>

                    <!-- Status Non-Aset -->
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
                            <i class="fa-solid fa-arrows-rotate text-indigo-500"></i>
                            Perubahan Status
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status Sekarang <span class="text-red-500">*</span></label>
                                <select name="status_lama" x-model="status_lama" required
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition">
                                    <option value="">— Pilih —</option>
                                    <option value="Tersedia">Tersedia</option>
                                    <option value="Terpakai">Terpakai</option>
                                    <option value="Rusak">Rusak</option>
                                    <option value="Dalam Perbaikan">Dalam Perbaikan</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status Baru <span class="text-red-500">*</span></label>
                                <select name="status_baru" x-model="status_baru" required
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition">
                                    <option value="">— Pilih Status —</option>
                                    <template x-for="s in filteredStatusBaru" :key="s">
                                        <option :value="s" x-text="s"></option>
                                    </template>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- PIC & Department -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">PIC <span class="text-red-500">*</span></label>
                            <input type="text" name="pic" x-model="asetPic" required
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition"
                                   placeholder="Nama penerima">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Department <span class="text-red-500">*</span></label>
                            <input type="text" name="department" x-model="asetDepartment" required
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition"
                                   placeholder="Nama department">
                        </div>
                    </div>

                </div>
            </template>

            <!-- Actions -->
            <div class="flex justify-end gap-3 pt-2 border-t border-gray-200 dark:border-gray-700">
                <a href="<?= pageUrl('dashboard.php') ?>"
                   class="px-5 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg text-sm font-medium hover:bg-gray-300 dark:hover:bg-gray-600 transition inline-flex items-center gap-2">
                    <i class="fa-solid fa-arrow-left"></i> Batal
                </a>
                <button type="submit"
                        :disabled="tab === 'aset' && (!snFound || !sparepartId)"
                        class="px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-lg text-sm font-medium hover:from-indigo-700 hover:to-purple-700 transition-all inline-flex items-center gap-2 shadow-md shadow-indigo-500/20 disabled:opacity-50 disabled:cursor-not-allowed">
                    <i class="fa-solid fa-save"></i> Simpan
                </button>
            </div>
        </form>
    </div>

</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('sparepartKeluarForm', () => ({
            tab: 'aset',
            // Aset fields
            snAset: '',
            snFound: false,
            snPesan: '',
            snList: [],
            sparepartId: 0,
            asetMerk: '',
            asetTanggal: '<?= date('Y-m-d') ?>',
            asetPic: '',
            asetDepartment: '',

            // Non-Aset fields (shared)
            selectedJenis: '',
            selectedType: '',
            status_lama: '-',
            status_baru: '',
            // Data
            typesByJenis: <?= json_encode($typesByJenis) ?>,
            snTimer: null,
            suggestions: [],
            showSuggestions: false,
            suggestionTimer: null,
            userPic: '<?= escape($user['name']) ?>',
            init() {
                document.addEventListener('click', (e) => {
                    if (!this.$el.contains(e.target)) this.showSuggestions = false;
                });
            },
            get filteredTypes() {
                return this.typesByJenis[this.selectedJenis] || [];
            },
            get filteredStatusBaru() {
                const allStatus = ['Tersedia', 'Terpakai', 'Rusak', 'Dalam Perbaikan'];
                if (!this.status_lama || this.status_lama === '-') return allStatus;
                return allStatus.filter(s => s !== this.status_lama);
            },
            cariBySn() {
                clearTimeout(this.snTimer);
                this.snTimer = setTimeout(() => {
                    this._cariBySn();
                }, 400);
                this._fetchSuggestions();
            },
            async _cariBySn() {
                const sn = this.snAset.trim();
                if (!sn) {
                    this.snFound = false;
                    this.snPesan = '';
                    this.sparepartId = 0;
                    this.selectedJenis = '';
                    this.selectedType = '';
                    this.asetMerk = '';
                    this.asetPic = '';
                    this.asetDepartment = '';
                    this.status_lama = '-';
                    this.snList = [];
                    return;
                }
                try {
                    const res = await fetch('index.php?url=sparepart&action=show_by_sn&sn=' + encodeURIComponent(sn));
                    const data = await res.json();
                    if (data.success) {
                        this.snFound = true;
                        this.snPesan = 'Ditemukan: ' + data.data.jenis_sparepart + ' (' + data.data.merk + ')';
                        this.sparepartId = data.data.id;
                        this.selectedJenis = data.data.jenis_sparepart;
                        this.selectedType = data.data.type_sparepart || '';
                        this.asetMerk = data.data.merk || '';
                        this.asetTanggal = data.data.tanggal || '<?= date('Y-m-d') ?>';
                        this.status_lama = data.data.status;
                        this.snList = [data.data.serial_number];
                    } else {
                        this.snFound = false;
                        this.snPesan = data.message;
                        this.sparepartId = 0;
                        this.selectedJenis = '';
                        this.selectedType = '';
                        this.asetMerk = '';
                        this.asetPic = '';
                        this.asetDepartment = '';
                        this.status_lama = '-';
                        this.snList = [];
                    }
                } catch (e) {
                    this.snFound = false;
                    this.snPesan = 'Gagal mencari data.';
                    this.sparepartId = 0;
                }
            },
            _fetchSuggestions() {
                clearTimeout(this.suggestionTimer);
                const q = this.snAset.trim();
                if (q.length < 1) {
                    this.suggestions = [];
                    this.showSuggestions = false;
                    return;
                }
                this.suggestionTimer = setTimeout(async () => {
                    try {
                        const res = await fetch('index.php?url=sparepart&action=search_sn&q=' + encodeURIComponent(q));
                        const data = await res.json();
                        if (data.success) {
                            this.suggestions = data.data;
                            this.showSuggestions = data.data.length > 0;
                        }
                    } catch (e) {
                        this.suggestions = [];
                        this.showSuggestions = false;
                    }
                }, 300);
            },
            selectSuggestion(item) {
                this.snAset = item.serial_number.replace(/^SN-/, '');
                this.showSuggestions = false;
                this.suggestions = [];
                this._cariBySn();
            },
            resetForm() {
                this.snAset = '';
                this.snFound = false;
                this.snPesan = '';
                this.snList = [];
                this.sparepartId = 0;
                this.selectedJenis = '';
                this.selectedType = '';
                this.asetMerk = '';
                this.asetTanggal = '<?= date('Y-m-d') ?>';
                this.asetPic = '';
                this.asetDepartment = '';
                this.status_lama = '-';
                this.status_baru = '';
            },
            statusBadgeClass(status) {
                const map = {
                    'Tersedia': 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400',
                    'Terpakai': 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400',
                    'Rusak': 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400',
                    'Dalam Perbaikan': 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400',
                };
                return map[status] || 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300';
            }
        }));
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
