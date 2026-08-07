<?php

$page_title = 'Master Barang';
$require_admin = true;
require_once __DIR__ . '/../../helpers/auth.php';

$db = getDB();

$search = _get($_GET, 'search', '');
$filterKategori = _get($_GET, 'kategori', '');
$filterStatus = _get($_GET, 'status', '');

$where = "WHERE s.deleted_at IS NULL";
$params = array();

if ($search) {
    $s = '%' . $search . '%';
    $where .= " AND (s.jenis_sparepart LIKE ? OR s.merk LIKE ? OR s.type_sparepart LIKE ? OR s.serial_number LIKE ? OR s.lokasi_penyimpanan LIKE ?)";
    array_push($params, $s, $s, $s, $s, $s);
}
if ($filterKategori) {
    $where .= " AND s.kategori = ?";
    $params[] = $filterKategori;
}
if ($filterStatus) {
    $where .= " AND s.status = ?";
    $params[] = $filterStatus;
}

$baseQuery = "SELECT s.*, u.name as created_by
              FROM spareparts s
              LEFT JOIN users u ON u.id = s.user_id
              $where
              ORDER BY s.created_at DESC";

list($items, $page, $totalPages) = paginate($db, $baseQuery, $params);

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div x-data="masterBarang()" class="page-enter">
    <nav class="flex items-center gap-2 text-base text-gray-500 dark:text-gray-400 mb-4">
        <a href="<?= pageUrl('dashboard.php') ?>" class="hover:text-blue-600 transition">Home</a>
        <i class="fa-solid fa-chevron-right text-xs"></i>
        <span class="text-gray-700 dark:text-gray-200 font-medium">Master Barang</span>
    </nav>

    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-6">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-white gradient-text">Master Barang Inventaris</h2>
        <div class="flex gap-2">
            <a href="index.php?route=dashboard" class="px-4 py-2 bg-gradient-to-r bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition inline-flex items-center gap-1.5 magnetic-btn">
                <i class="fa-solid fa-plus"></i> Tambah (Dashboard)
            </a>
        </div>
    </div>

    <!-- Filter -->
    <div class="glass-panel p-4 mb-6">
        <form method="GET" action="index.php" class="flex flex-wrap gap-3 items-end">
            <input type="hidden" name="route" value="master_barang">
            <div>
                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1 font-medium">Cari</label>
                <input type="text" name="search" value="<?= escape($search) ?>" placeholder="Nama, type, merk, SN, lokasi..." class="px-3 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 dark:text-gray-200 rounded-xl text-base focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500/50 outline-none transition-all duration-200 w-48">
            </div>
            <div>
                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1 font-medium">Kategori</label>
                <select name="kategori" class="px-3 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 dark:text-gray-200 rounded-xl text-base focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500/50 outline-none transition-all duration-200">
                    <option value="">Semua</option>
                    <option value="Aset" <?= $filterKategori === 'Aset' ? 'selected' : '' ?>>Aset</option>
                    <option value="Non-Aset" <?= $filterKategori === 'Non-Aset' ? 'selected' : '' ?>>Non-Aset</option>
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1 font-medium">Status</label>
                <select name="status" class="px-3 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 dark:text-gray-200 rounded-xl text-base focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500/50 outline-none transition-all duration-200">
                    <option value="">Semua</option>
                    <option value="Tersedia" <?= $filterStatus === 'Tersedia' ? 'selected' : '' ?>>Tersedia</option>
                    <option value="Terpakai" <?= $filterStatus === 'Terpakai' ? 'selected' : '' ?>>Terpakai</option>
                    <option value="Rusak" <?= $filterStatus === 'Rusak' ? 'selected' : '' ?>>Rusak</option>
                    <option value="Dalam Perbaikan" <?= $filterStatus === 'Dalam Perbaikan' ? 'selected' : '' ?>>Dalam Perbaikan</option>
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-gradient-to-r bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 transition font-medium inline-flex items-center gap-1.5 magnetic-btn">
                <i class="fa-solid fa-filter"></i> Filter
            </button>
            <?php if ($search || $filterKategori || $filterStatus): ?>
            <a href="index.php?route=master_barang" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg text-sm hover:bg-gray-300 dark:hover:bg-gray-600 transition font-medium">
                <i class="fa-solid fa-rotate"></i> Reset
            </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Table -->
    <div class="glass-panel overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/50 text-gray-600 dark:text-gray-300 text-xs uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold w-10">ID</th>
                        <th class="px-4 py-3 text-left font-semibold">SN</th>
                        <th class="px-4 py-3 text-left font-semibold">Nama Barang</th>
                        <th class="px-4 py-3 text-left font-semibold">Kategori</th>
                        <th class="px-4 py-3 text-left font-semibold">Jenis Penggunaan</th>
                        <th class="px-4 py-3 text-left font-semibold">Lokasi</th>
                        <th class="px-4 py-3 text-left font-semibold">Stok</th>
                        <th class="px-4 py-3 text-left font-semibold">Min</th>
                        <th class="px-4 py-3 text-left font-semibold">Status</th>
                        <th class="px-4 py-3 text-left font-semibold">PIC</th>
                        <th class="px-4 py-3 text-center font-semibold w-32">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="11" class="px-4 py-12 text-center text-gray-400 dark:text-gray-500">
                            <i class="fa-solid fa-box text-3xl mb-2 block"></i>
                            Tidak ada data barang.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($items as $item): ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400 font-mono text-xs">#<?= $item['id'] ?></td>
                        <td class="px-4 py-3 text-xs max-w-[120px] truncate" title="<?= $item['kategori'] === 'Non-Aset' ? $item['quantity'] : escape(preg_replace('/^SN-/', '', $item['serial_number'])) ?>"><?= $item['kategori'] === 'Non-Aset' ? '<span class="font-semibold">QTY: ' . $item['quantity'] . '</span>' : '<span class="font-mono">' . escape(preg_replace('/^SN-/', '', $item['serial_number']) ?: '-') . '</span>' ?></td>
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-800 dark:text-gray-200"><?= escape($item['jenis_sparepart']) ?></div>
                            <div class="text-xs text-gray-400 dark:text-gray-500"><?= escape($item['merk']) ?> <?= $item['type_sparepart'] ? '- ' . escape($item['type_sparepart']) : '' ?></div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-full <?= $item['kategori'] === 'Aset' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400' : 'bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-400' ?>"><?= escape($item['kategori']) ?></span>
                        </td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300"><?= escape(isset($item['jenis_penggunaan']) ? $item['jenis_penggunaan'] : '-') ?></td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400 text-xs"><?= escape(isset($item['lokasi_penyimpanan']) ? $item['lokasi_penyimpanan'] : '-') ?></td>
                        <td class="px-4 py-3 text-center font-semibold"><?= (int)$item['quantity'] ?></td>
                        <td class="px-4 py-3 text-center text-xs text-gray-500"><?= (int)$item['minimum_stok'] ?></td>
                        <td class="px-4 py-3"><?= getStatusBadge($item['status']) ?></td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300 text-xs"><?= escape(isset($item['pic']) ? $item['pic'] : '-') ?></td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-1">
                                <button onclick="showDetail(<?= $item['id'] ?>)" class="p-1.5 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition" title="Detail">
                                    <i class="fa-solid fa-eye text-xs"></i>
                                </button>
                                <button onclick="editItem(<?= $item['id'] ?>)" class="p-1.5 text-amber-600 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-900/30 rounded-lg transition" title="Edit">
                                    <i class="fa-solid fa-pen text-xs"></i>
                                </button>
                                <button onclick="deleteItem(<?= $item['id'] ?>)" class="p-1.5 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition" title="Hapus">
                                    <i class="fa-solid fa-trash text-xs"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?= renderPagination($page, $totalPages) ?>
    </div>
</div>

<script>
    const CSRF_TOKEN = '<?= csrfToken() ?>';

    async function showDetail(id) {
        window.dispatchEvent(new CustomEvent('loading-start'));
        const res = await fetch('index.php?route=sparepart&action=show&id=' + id);
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
                title: 'Detail Barang',
                html: `
                    <div class="text-left space-y-2 text-sm">
                        <p><strong>Jenis:</strong> ${escapeHtml(data.data.jenis_sparepart)}</p>
                        <p><strong>Type:</strong> ${escapeHtml(data.data.type_sparepart || '-')}</p>
                        <p><strong>Merk:</strong> ${escapeHtml(data.data.merk || '-')}</p>
                        <p><strong>${(data.data.kategori === 'Non-Aset') ? 'QTY' : 'Serial Number'}:</strong> ${(data.data.kategori === 'Non-Aset') ? (data.data.quantity || 0) : (data.data.serial_number || '-')}</p>
                        <p><strong>Kategori:</strong> ${escapeHtml(data.data.kategori)}</p>
                        <p><strong>Jenis Penggunaan:</strong> ${escapeHtml(data.data.jenis_penggunaan || '-')}</p>
                        <p><strong>Lokasi Penyimpanan:</strong> ${escapeHtml(data.data.lokasi_penyimpanan || '-')}</p>
                        <p><strong>Quantity:</strong> ${data.data.quantity}</p>
                        <p><strong>Minimum Stok:</strong> ${data.data.minimum_stok}</p>
                        <p><strong>Status:</strong> ${escapeHtml(data.data.status)}</p>
                        <p><strong>PIC:</strong> ${escapeHtml(data.data.pic || '-')}</p>
                        <p><strong>Department:</strong> ${escapeHtml(data.data.department || '-')}</p>
                        <p><strong>Tanggal:</strong> ${escapeHtml(data.data.tanggal)}</p>
                        <p><strong>Keterangan:</strong> ${escapeHtml(data.data.keterangan || '-')}</p>
                        ${(() => { var imgs = imgList(data.data.image); if (imgs.length) { return '<div class="mt-3"><strong>Foto:</strong><div class="flex flex-wrap gap-2 mt-1">' + imgs.map(function(p){ return '<img src="<?= rtrim(APP_URL, '/') ?>/' + escapeHtml(p) + '" class="w-24 h-24 rounded-lg border border-gray-200 dark:border-gray-600 object-cover cursor-pointer hover:opacity-80 transition" onclick="window.open(this.src)" loading="lazy">'; }).join('') + '</div></div>'; } return ''; })()}
                        ${logsHtml}
                    </div>
                `,
                confirmButtonColor: '#00d4ff',
                confirmButtonText: 'Tutup'
            });
        } else {
            darkSwal({ icon: 'error', title: 'Error', text: data.message || 'Gagal memuat data.' });
        }
    }

    async function editItem(id) {
        const res = await fetch('index.php?route=sparepart&action=show&id=' + id);
        const data = await res.json();
        if (!data.success) {
            darkSwal({ icon: 'error', title: 'Error', text: 'Gagal memuat data.' });
            return;
        }
        const d = data.data;
        darkSwal({
            title: 'Edit Sparepart #' + id,
            html: `
                <form id="editForm-${id}" class="text-left space-y-3">
                    <input type="hidden" name="csrf_token" value="${CSRF_TOKEN}">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Kategori</label>
                            <select name="kategori" class="w-full px-2 py-1.5 border rounded text-sm">
                                <option value="Aset" ${d.kategori === 'Aset' ? 'selected' : ''}>Aset</option>
                                <option value="Non-Aset" ${d.kategori === 'Non-Aset' ? 'selected' : ''}>Non-Aset</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Jenis Penggunaan</label>
                            <select name="jenis_penggunaan" class="w-full px-2 py-1.5 border rounded text-sm">
                                <option value="">Pilih</option>
                                <option value="Reusable" ${d.jenis_penggunaan === 'Reusable' ? 'selected' : ''}>Reusable</option>
                                <option value="Consumable" ${d.jenis_penggunaan === 'Consumable' ? 'selected' : ''}>Consumable</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Jenis Sparepart</label>
                            <input type="text" name="jenis_sparepart" value="${escapeHtml(d.jenis_sparepart)}" class="w-full px-2 py-1.5 border rounded text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Type</label>
                            <input type="text" name="type_sparepart" value="${escapeHtml(d.type_sparepart || '')}" class="w-full px-2 py-1.5 border rounded text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">SN</label>
                            <input type="text" name="serial_number" value="${escapeHtml(d.serial_number || '')}" class="w-full px-2 py-1.5 border rounded text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Quantity</label>
                            <input type="number" name="quantity" value="${d.quantity}" min="1" class="w-full px-2 py-1.5 border rounded text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Min. Stok</label>
                            <input type="number" name="minimum_stok" value="${d.minimum_stok || 1}" min="0" class="w-full px-2 py-1.5 border rounded text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Merk</label>
                            <input type="text" name="merk" value="${escapeHtml(d.merk || '')}" class="w-full px-2 py-1.5 border rounded text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Lokasi</label>
                            <input type="text" name="lokasi_penyimpanan" value="${escapeHtml(d.lokasi_penyimpanan || '')}" class="w-full px-2 py-1.5 border rounded text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
                            <select name="status" class="w-full px-2 py-1.5 border rounded text-sm">
                                <option value="Tersedia" ${d.status === 'Tersedia' ? 'selected' : ''}>Tersedia</option>
                                <option value="Terpakai" ${d.status === 'Terpakai' ? 'selected' : ''}>Terpakai</option>
                                <option value="Rusak" ${d.status === 'Rusak' ? 'selected' : ''}>Rusak</option>
                                <option value="Dalam Perbaikan" ${d.status === 'Dalam Perbaikan' ? 'selected' : ''}>Dalam Perbaikan</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">PIC</label>
                            <input type="text" name="pic" value="${escapeHtml(d.pic || '')}" class="w-full px-2 py-1.5 border rounded text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Department</label>
                            <input type="text" name="department" value="${escapeHtml(d.department || '')}" class="w-full px-2 py-1.5 border rounded text-sm">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Keterangan</label>
                            <textarea name="keterangan" rows="2" class="w-full px-2 py-1.5 border rounded text-sm">${escapeHtml(d.keterangan || '')}</textarea>
                        </div>
                    </div>
                </form>
            `,
            confirmButtonText: 'Simpan',
            confirmButtonColor: '#00d4ff',
            showCancelButton: true,
            cancelButtonText: 'Batal',
            preConfirm: async () => {
                const form = document.getElementById('editForm-' + id);
                const formData = new FormData(form);
                formData.append('csrf_token', CSRF_TOKEN);
                const res = await fetch('index.php?route=sparepart&action=update&id=' + id, { method: 'POST', body: formData });
                const result = await res.json();
                if (!result.success) {
                    darkSwal.showValidationMessage(result.message || 'Gagal menyimpan.');
                }
                return result;
            }
        }).then(function(result) {
            if (result.isConfirmed) {
                darkSwal({ icon: 'success', title: 'Berhasil', text: 'Data berhasil diperbarui.', timer: 1500, showConfirmButton: false });
                setTimeout(() => window.location.reload(), 1500);
            }
        });
    }

    function deleteItem(id) {
        darkSwal({
            title: 'Hapus Sparepart?',
            text: 'Data akan dihapus secara soft-delete.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal',
            preConfirm: async () => {
                const formData = new FormData();
                formData.append('csrf_token', CSRF_TOKEN);
                const res = await fetch('index.php?route=sparepart&action=destroy&id=' + id, { method: 'POST', body: formData });
                const result = await res.json();
                if (!result.success) {
                    darkSwal.showValidationMessage('Gagal menghapus.');
                }
                return result;
            }
        }).then(function(result) {
            if (result.isConfirmed) {
                darkSwal({ icon: 'success', title: 'Berhasil', text: 'Data berhasil dihapus.', timer: 1500, showConfirmButton: false });
                setTimeout(() => window.location.reload(), 1500);
            }
        });
    }
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.17.2"></script>
<script>
    function darkSwal(opt) {
        var isDark = document.documentElement.classList.contains('dark');
        return Swal.fire(Object.assign({
            background: isDark ? '#1a1a2e' : '#ffffff',
            color: isDark ? '#e2e8f0' : '#1e293b',
            confirmButtonColor: '#00d4ff',
        }, opt));
    }
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
