<?php

$page_title = 'Sparepart Keluar';
$require_admin = false;
require_once __DIR__ . '/../../helpers/auth.php';

$db = getDB();

$jenisList = $db->query("SELECT nama, kategori FROM jenis_spareparts WHERE type IS NULL ORDER BY nama")->fetchAll();

$asetJenisListNama = array_column(
    array_filter($jenisList, function($j) { return $j['kategori'] === 'Aset'; }),
    'nama'
);

$nonasetJenisListNama = array_column(
    array_filter($jenisList, function($j) { return $j['kategori'] === 'Non-Aset'; }),
    'nama'
);

$typeRows = $db->query("SELECT nama, type, kategori FROM jenis_spareparts WHERE type IS NOT NULL ORDER BY nama, type")->fetchAll();
$typesByJenis = [];
$nonasetTypesByJenis = [];
foreach ($typeRows as $t) {
    $typesByJenis[$t['nama']][] = $t['type'];
    if ($t['kategori'] === 'Non-Aset') {
        $nonasetTypesByJenis[$t['nama']][] = $t['type'];
    }
}

$merkRows = $db->query("SELECT kategori, jenis_sparepart, type_sparepart, merk FROM sparepart_merks ORDER BY kategori, jenis_sparepart, type_sparepart, merk")->fetchAll();
$merksByJenisType = [];
$nonasetMerksByJenisType = [];
foreach ($merkRows as $m) {
    $key = $m['jenis_sparepart'] . '||' . ($m['type_sparepart'] ?: '');
    $merksByJenisType[$key][] = $m['merk'];
    if ($m['kategori'] === 'Non-Aset') {
        $nonasetMerksByJenisType[$key][] = $m['merk'];
    }
}

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div x-data="sparepartKeluarForm()" class="page-enter max-w-3xl mx-auto">
    <nav class="flex items-center gap-2 text-base text-gray-500 dark:text-gray-400 mb-4">
        <a href="<?= pageUrl('dashboard.php') ?>" class="hover:text-blue-600 dark:hover:text-blue-400 transition">Home</a>
        <i class="fa-solid fa-chevron-right text-xs"></i>
        <span class="text-gray-700 dark:text-gray-200 font-medium">Sparepart Keluar</span>
    </nav>

    <!-- Tabs -->
    <div class="flex gap-1 mb-6 bg-gray-100 dark:bg-gray-800 p-1 rounded-xl w-fit border border-gray-200 dark:border-gray-700">
        <button @click="tab = 'aset'; resetForm()"
                :class="tab === 'aset' ? 'bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                class="px-5 py-2 rounded-lg text-sm font-semibold transition-all duration-200 inline-flex items-center gap-2">
            <i class="fa-solid fa-microchip"></i> Aset
        </button>
        <button @click="tab = 'nonaset'; resetForm()"
                :class="tab === 'nonaset' ? 'bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                class="px-5 py-2 rounded-lg text-sm font-semibold transition-all duration-200 inline-flex items-center gap-2">
            <i class="fa-solid fa-box"></i> Non-Aset
        </button>
    </div>

    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-white gradient-text">
            Catat Sparepart Keluar <span class="text-blue-600 dark:text-blue-400">—</span>
            <span x-text="tab === 'aset' ? 'Aset' : 'Non-Aset'" class="text-blue-600 dark:text-blue-400"></span>
        </h2>
    </div>

    <div class="glass-panel p-6 card-hover">
        <form method="POST" action="index.php?url=sparepart_keluar" class="space-y-5" enctype="multipart/form-data">
            <?= csrf() ?>
            <input type="hidden" name="mode" :value="tab === 'aset' ? 'aset_update' : 'nonaset_insert'">
            <input type="hidden" name="sparepart_id" x-model="sparepartId">

            <!-- ========== ASET TAB ========== -->
            <template x-if="tab === 'aset'">
                <div class="space-y-5">
                    <!-- SN Lookup - FIRST FIELD -->
                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-100 dark:border-blue-800/30">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Cari Sparepart <span class="text-red-500">*</span>
                        </label>
                        <div class="flex gap-2 relative">
                            <div class="flex flex-1 min-w-0">
                                <span class="inline-flex items-center px-3 py-2.5 border border-r-0 border-gray-200 dark:border-gray-600 bg-gray-100 dark:bg-gray-600 text-gray-600 dark:text-gray-300 rounded-l-xl text-base font-mono select-none">SN-</span>
                                <input type="text" x-model="snAset" @input="cariBySn" @focus="showSuggestions = false; setTimeout(() => _fetchSuggestions(), 100)"
                                       id="keluar-sn-input"
                                       class="flex-1 min-w-0 px-3 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 dark:text-gray-200 rounded-r-xl text-base focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500/50 outline-none transition-all duration-200 font-mono"
                                       placeholder="Masukkan serial number" autocomplete="off">
                            </div>
                            <button type="button" @click="cariBySn" :disabled="!snAset.trim()"
                                    class="px-4 py-2 bg-gradient-to-r bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition disabled:opacity-50 inline-flex items-center gap-1.5 magnetic-btn shrink-0">
                                <i class="fa-solid fa-search"></i> Cari
                            </button>
                            <div class="relative shrink-0" @click.outside="openScanDrop = false">
                                <button type="button" @click="openScanDrop = !openScanDrop"
                                        class="px-3 py-2 bg-emerald-500 text-white rounded-lg text-sm font-semibold hover:bg-emerald-600 transition inline-flex items-center gap-1.5" title="Scan barcode/QR">
                                    <i class="fa-solid fa-barcode"></i> Scan <i class="fa-solid fa-caret-down text-[10px] ml-0.5"></i>
                                </button>
                                <div x-show="openScanDrop" x-cloak x-transition
                                     class="absolute right-0 top-full mt-1 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl shadow-xl z-50 min-w-[180px] overflow-hidden">
                                    <button type="button" @click="openScanDrop = false; openKeluarScanner()" class="w-full text-left px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 flex items-center gap-2.5 transition">
                                        <i class="fa-solid fa-camera text-emerald-500"></i> Kamera
                                    </button>
                                    <button type="button" @click="openScanDrop = false; document.getElementById('keluar-sn-photo').click()" class="w-full text-left px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 flex items-center gap-2.5 transition">
                                        <i class="fa-solid fa-image text-blue-500"></i> Upload Foto
                                    </button>
                                </div>
                                <input type="file" id="keluar-sn-photo" accept="image/*" class="hidden" onchange="scanKeluarFromPhoto(this)">
                            </div>

                            <!-- Suggestions dropdown -->
                            <div x-show="showSuggestions" x-cloak
                                 class="absolute top-full left-0 right-0 mt-1 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg z-50 max-h-48 overflow-y-auto">
                                <template x-for="(item, idx) in suggestions" :key="idx">
                                    <button type="button" @click="selectSuggestion(item)"
                                            class="w-full text-left px-3 py-2 text-sm hover:bg-blue-50 dark:hover:bg-blue-900/30 border-b border-gray-100 dark:border-gray-600 last:border-b-0 transition flex items-center gap-2">
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
                                   class="w-full px-3 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-600 cursor-not-allowed opacity-70">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type Sparepart</label>
                            <input type="text" name="type_sparepart" x-model="selectedType" readonly
                                   class="w-full px-3 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-600 cursor-not-allowed opacity-70">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Merk</label>
                            <input type="text" name="merk" x-model="asetMerk" readonly
                                   class="w-full px-3 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-600 cursor-not-allowed opacity-70">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tanggal</label>
                            <input type="date" name="tanggal" x-model="asetTanggal"
                                   class="w-full px-3 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 dark:text-gray-200 rounded-xl text-base focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500/50 outline-none transition-all duration-200">
                        </div>
                    </div>

                    <!-- Status Aset -->
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
                            <i class="fa-solid fa-arrows-rotate text-blue-600"></i>
                            Perubahan Status
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status Sekarang</label>
                                <input type="text" x-model="status_lama" readonly
                                       class="w-full px-3 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-600 cursor-not-allowed opacity-70">
                                <input type="hidden" name="status_lama" x-model="status_lama">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status Baru <span class="text-red-500">*</span></label>
                                <select name="status_baru" x-model="status_baru" required
                                        class="w-full px-3 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 dark:text-gray-200 rounded-xl text-base focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500/50 outline-none transition-all duration-200">
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
                                   class="w-full px-3 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 dark:text-gray-200 rounded-xl text-base focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500/50 outline-none transition-all duration-200"
                                   placeholder="Nama penerima">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Department <span class="text-red-500">*</span></label>
                            <input type="text" name="department" x-model="asetDepartment" required
                                   class="w-full px-3 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 dark:text-gray-200 rounded-xl text-base focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500/50 outline-none transition-all duration-200"
                                   placeholder="Nama department">
                        </div>
                    </div>

                    <!-- Foto Aset -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Foto Barang <span class="text-gray-400 font-normal">(max 5)</span></label>
                        <div class="flex items-start gap-4">
                            <div class="flex-1">
                                <input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple
                                       @change="handleFoto($event, 'aset')"
                                       class="w-full text-sm text-gray-500 dark:text-gray-400 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-blue-900/30 dark:file:text-blue-400 dark:hover:file:dark:bg-blue-900/50 transition">
                            </div>
                        </div>
                        <div x-show="fotoPreviewAset.length > 0" x-cloak class="flex flex-wrap gap-2 mt-2">
                            <template x-for="(foto, idx) in fotoPreviewAset" :key="idx">
                                <div class="relative shrink-0">
                                    <img :src="foto.src" class="w-20 h-20 rounded-lg object-cover border border-gray-200 dark:border-gray-600 shadow-sm cursor-pointer hover:opacity-80 transition" @click="toggleImageZoom(foto.src, foto.name)">
                                    <button type="button" @click.stop="hapusFoto('aset', idx)" title="Hapus foto"
                                            class="absolute -top-1.5 -right-1.5 w-5 h-5 bg-red-500 text-white rounded-full flex items-center justify-center text-xs hover:bg-red-600 transition shadow-md">
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                </div>
                            </template>
                        </div>
                        <p class="text-xs text-gray-400 mt-1">Format: JPG, PNG, WebP. Maksimal 2MB per foto, max 5 foto.</p>
                        <p x-show="fotoError && tab === 'aset'" x-cloak class="text-xs text-red-500 dark:text-red-400 mt-1.5 flex items-center gap-1"><i class="fa-solid fa-circle-exclamation"></i><span x-text="fotoError"></span></p>
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
                            <select name="jenis_sparepart" x-model="selectedJenis" @change="selectedType = ''; selectedMerk = ''; customMerk = ''" required
                                    class="w-full px-3 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 dark:text-gray-200 rounded-xl text-base focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500/50 outline-none transition-all duration-200">
                                <option value="">Pilih Jenis</option>
                                <?php foreach ($nonasetJenisListNama as $j): ?>
                                <option value="<?= escape($j) ?>"><?= escape($j) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type Sparepart</label>
                            <select name="type_sparepart" x-model="selectedType"
                                    class="w-full px-3 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 dark:text-gray-200 rounded-xl text-base focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500/50 outline-none transition-all duration-200">
                                <option value="">Pilih Type</option>
                                <template x-for="t in filteredTypes" :key="t">
                                    <option :value="t" x-text="t"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Merk <span class="text-red-500">*</span></label>
                            <template x-if="filteredMerks.length > 0">
                                <div>
                                    <select name="merk_select" x-model="selectedMerk"
                                            class="w-full px-3 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 dark:text-gray-200 rounded-xl text-base focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500/50 outline-none transition-all duration-200">
                                        <option value="">Pilih Merk</option>
                                        <template x-for="m in filteredMerks" :key="m">
                                            <option :value="m" x-text="m"></option>
                                        </template>
                                        <option value="__custom__">Lainnya...</option>
                                    </select>
                                    <input type="text" name="merk" x-show="selectedMerk === '__custom__'" x-cloak
                                           x-model="customMerk" required
                                           placeholder="Ketik merk manual"
                                           class="w-full px-3 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 dark:text-gray-200 rounded-xl text-base focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500/50 outline-none transition-all duration-200 mt-1.5">
                                    <input type="hidden" name="merk" x-show="selectedMerk !== '__custom__'" :value="selectedMerk">
                                </div>
                            </template>
                            <template x-if="filteredMerks.length === 0">
                                <input type="text" name="merk" required
                                       class="w-full px-3 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 dark:text-gray-200 rounded-xl text-base focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500/50 outline-none transition-all duration-200"
                                       placeholder="Ketik merk">
                            </template>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Jumlah <span class="text-red-500">*</span></label>
                            <input type="number" name="quantity" value="1" min="1"
                                   class="w-full px-3 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 dark:text-gray-200 rounded-xl text-base focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500/50 outline-none transition-all duration-200">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tanggal <span class="text-red-500">*</span></label>
                            <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required
                                   class="w-full px-3 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 dark:text-gray-200 rounded-xl text-base focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500/50 outline-none transition-all duration-200">
                        </div>
                    </div>

                    <!-- Status Non-Aset -->
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
                            <i class="fa-solid fa-arrows-rotate text-blue-600"></i>
                            Perubahan Status
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status Sekarang <span class="text-red-500">*</span></label>
                                <select name="status_lama" x-model="status_lama" required
                                        class="w-full px-3 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 dark:text-gray-200 rounded-xl text-base focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500/50 outline-none transition-all duration-200">
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
                                        class="w-full px-3 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 dark:text-gray-200 rounded-xl text-base focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500/50 outline-none transition-all duration-200">
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
                                   class="w-full px-3 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 dark:text-gray-200 rounded-xl text-base focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500/50 outline-none transition-all duration-200"
                                   placeholder="Nama penerima">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Department <span class="text-red-500">*</span></label>
                            <input type="text" name="department" x-model="asetDepartment" required
                                   class="w-full px-3 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 dark:text-gray-200 rounded-xl text-base focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500/50 outline-none transition-all duration-200"
                                   placeholder="Nama department">
                        </div>
                    </div>

                    <!-- Foto Non-Aset -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Foto Barang <span class="text-red-500">*</span> <span class="text-gray-400 font-normal">(max 5)</span></label>
                        <div class="flex items-start gap-4">
                            <div class="flex-1">
                                <input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple
                                       @change="handleFoto($event, 'nonaset')"
                                       class="w-full text-sm text-gray-500 dark:text-gray-400 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-blue-900/30 dark:file:text-blue-400 dark:hover:file:dark:bg-blue-900/50 transition">
                            </div>
                        </div>
                        <div x-show="fotoPreviewNonaset.length > 0" x-cloak class="flex flex-wrap gap-2 mt-2">
                            <template x-for="(foto, idx) in fotoPreviewNonaset" :key="idx">
                                <div class="relative shrink-0">
                                    <img :src="foto.src" class="w-20 h-20 rounded-lg object-cover border border-gray-200 dark:border-gray-600 shadow-sm cursor-pointer hover:opacity-80 transition" @click="toggleImageZoom(foto.src, foto.name)">
                                    <button type="button" @click.stop="hapusFoto('nonaset', idx)" title="Hapus foto"
                                            class="absolute -top-1.5 -right-1.5 w-5 h-5 bg-red-500 text-white rounded-full flex items-center justify-center text-xs hover:bg-red-600 transition shadow-md">
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                </div>
                            </template>
                        </div>
                        <p class="text-xs text-gray-400 mt-1">Format: JPG, PNG, WebP. Maksimal 2MB per foto, max 5 foto. Wajib untuk semua transaksi.</p>
                        <p x-show="fotoError && tab === 'nonaset'" x-cloak class="text-xs text-red-500 dark:text-red-400 mt-1.5 flex items-center gap-1"><i class="fa-solid fa-circle-exclamation"></i><span x-text="fotoError"></span></p>
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
                        :disabled="(tab === 'aset' && (!snFound || !sparepartId || fotoPreviewAset.length === 0)) || (tab === 'nonaset' && fotoPreviewNonaset.length === 0) || !!fotoError"
                        class="px-5 py-2.5 bg-gradient-to-r bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-all inline-flex items-center gap-2 shadow-md shadow-blue-500/20 disabled:opacity-50 disabled:cursor-not-allowed magnetic-btn">
                    <i class="fa-solid fa-save"></i> Simpan
                </button>
            </div>
        </form>
    </div>

</div>

<!-- Scanner Overlay -->
<div id="keluar-scanner-overlay" class="fixed inset-0 z-[9999] bg-black/80 backdrop-blur-sm flex items-center justify-center p-4" style="display:none;">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
            <h4 class="font-bold text-gray-800 dark:text-white text-sm"><i class="fa-solid fa-barcode text-emerald-500 mr-1.5"></i>Scan Barcode / QR</h4>
            <button onclick="closeKeluarScanner()" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="p-3">
            <div id="keluar-reader" class="rounded-xl overflow-hidden"></div>
        </div>
        <div class="px-4 pb-4">
            <p class="text-xs text-gray-400 dark:text-gray-500 text-center">Arahkan kamera ke barcode/QR pada barang</p>
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('sparepartKeluarForm', () => ({
            tab: 'aset',
            openScanDrop: false,
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
            selectedMerk: '',
            customMerk: '',
            status_lama: '-',
            status_baru: '',
            // Data
            typesByJenis: <?= json_encode($typesByJenis) ?>,
            nonasetTypesByJenis: <?= json_encode($nonasetTypesByJenis) ?>,
            nonasetMerksByJenisType: <?= json_encode($nonasetMerksByJenisType) ?>,
            snTimer: null,
            suggestions: [],
            showSuggestions: false,
            suggestionTimer: null,
            userPic: '<?= escape($user['name']) ?>',
            // Foto preview
            fotoPreviewAset: [],
            fotoPreviewNonaset: [],
            fotoError: '',
            MAX_FILE_SIZE: 2 * 1024 * 1024,
            MAX_FILES: 5,
            validateFile(file) {
                if (file.size > this.MAX_FILE_SIZE) {
                    var ukuran = (file.size / (1024 * 1024)).toFixed(2);
                    this.fotoError = 'Ukuran foto "' + file.name + '" (' + ukuran + 'MB) melebihi batas 2MB.';
                    return false;
                }
                this.fotoError = '';
                return true;
            },
            handleFoto(e, target) {
                var files = e.target.files;
                if (!files || files.length === 0) return;
                var arr = target === 'aset' ? this.fotoPreviewAset : this.fotoPreviewNonaset;
                var remaining = this.MAX_FILES - arr.length;
                if (remaining <= 0) {
                    this.fotoError = 'Maksimal ' + this.MAX_FILES + ' foto.';
                    e.target.value = '';
                    return;
                }
                var count = Math.min(files.length, remaining);
                for (var i = 0; i < count; i++) {
                    var file = files[i];
                    if (!this.validateFile(file)) continue;
                    var self = this;
                    var reader = new FileReader();
                    reader.onload = (function(f, tgt) {
                        return function(ev) {
                            if (tgt === 'aset') self.fotoPreviewAset.push({ name: f.name, src: ev.target.result });
                            else self.fotoPreviewNonaset.push({ name: f.name, src: ev.target.result });
                        };
                    })(file, target);
                    reader.readAsDataURL(file);
                }
                e.target.value = '';
            },
            hapusFoto(target, idx) {
                if (target === 'aset') this.fotoPreviewAset.splice(idx, 1);
                else this.fotoPreviewNonaset.splice(idx, 1);
                this.fotoError = '';
            },
            init() {
                document.addEventListener('click', (e) => {
                    if (!this.$el.contains(e.target)) this.showSuggestions = false;
                });
            },
            get filteredTypes() {
                const source = this.tab === 'nonaset' ? this.nonasetTypesByJenis : this.typesByJenis;
                return source[this.selectedJenis] || [];
            },
            get filteredMerks() {
                const key = this.selectedJenis + '||' + this.selectedType;
                return this.nonasetMerksByJenisType[key] || [];
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
                this.fotoPreviewAset = '';
                this.fotoPreviewNonaset = '';
                this.fotoError = '';
                this.$el.querySelectorAll('input[type="file"]').forEach(el => el.value = '');
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

    /* ===== Keluar Scanner ===== */
    var keluarHtml5Qr = null;
    var keluarScannerRunning = false;

    function setKeluarSnAndSearch(clean) {
        var input = document.getElementById('keluar-sn-input');
        input.value = clean;
        try {
            var component = Alpine.$data(input);
            if (component && component.snAset !== undefined) {
                component.snAset = clean;
                component._cariBySn();
                return;
            }
        } catch (e) {}
        input.dispatchEvent(new Event('input', { bubbles: true }));
    }

    function openKeluarScanner() {
        var overlay = document.getElementById('keluar-scanner-overlay');
        overlay.style.display = '';
        if (!keluarHtml5Qr) {
            keluarHtml5Qr = new Html5Qrcode('keluar-reader');
        }
        keluarHtml5Qr.start(
            { facingMode: 'environment' },
            { fps: 10, qrbox: { width: 250, height: 150 }, aspectRatio: 1.5 },
            function onScanSuccess(decodedText) {
                closeKeluarScanner();
                var clean = decodedText.replace(/^SN-/i, '').trim();
                setKeluarSnAndSearch(clean);
            },
            function onScanFailure() {}
        ).then(function() {
            keluarScannerRunning = true;
        }).catch(function(err) {
            closeKeluarScanner();
            alert('Gagal akses kamera: ' + err);
        });
    }

    function closeKeluarScanner() {
        var overlay = document.getElementById('keluar-scanner-overlay');
        overlay.style.display = 'none';
        if (keluarHtml5Qr && keluarScannerRunning) {
            keluarHtml5Qr.stop().then(function() {
                keluarScannerRunning = false;
            }).catch(function() {});
        }
    }

    function scanKeluarFromPhoto(input) {
        if (!input.files || !input.files[0]) return;
        var file = input.files[0];
        if (!keluarHtml5Qr) {
            keluarHtml5Qr = new Html5Qrcode('keluar-reader');
        }
        keluarHtml5Qr.scanFileV2(file, true)
            .then(function(decodedText) {
                var clean = decodedText.replace(/^SN-/i, '').trim();
                setKeluarSnAndSearch(clean);
            })
            .catch(function() {
                alert('Tidak ditemukan barcode/QR di foto. Pastikan foto jelas dan fokus.');
            });
        input.value = '';
    }
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
