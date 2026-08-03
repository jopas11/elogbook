<div class="page-enter">
    <nav class="flex items-center gap-2 text-base text-gray-500 dark:text-gray-400 mb-4">
        <a href="<?= pageUrl('dashboard.php') ?>" class="hover:text-blue-600 transition">Home</a>
        <i class="fa-solid fa-chevron-right text-xs"></i>
        <span class="text-gray-700 dark:text-gray-200 font-medium">Approval Saya</span>
    </nav>

    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-6">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-white gradient-text">Approval Saya</h2>
    </div>

    <!-- Status Tabs -->
    <div class="flex gap-1 mb-6 bg-gray-100 dark:bg-gray-800 p-1 rounded-xl w-fit border border-gray-200 dark:border-gray-700">
        <a href="?route=my_approvals&status=pending"
           class="px-4 py-2 rounded-lg text-sm font-semibold transition-all duration-200 inline-flex items-center gap-2 <?= $status === 'pending' ? 'bg-white dark:bg-gray-700 text-amber-600 dark:text-amber-400 shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300' ?>">
            <i class="fa-solid fa-clock"></i> Pending
            <span class="ml-1 px-1.5 py-0.5 text-[10px] font-bold rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400"><?= isset($counts['pending']) ? $counts['pending'] : 0 ?></span>
        </a>
        <a href="?route=my_approvals&status=approved"
           class="px-4 py-2 rounded-lg text-sm font-semibold transition-all duration-200 inline-flex items-center gap-2 <?= $status === 'approved' ? 'bg-white dark:bg-gray-700 text-emerald-600 dark:text-emerald-400 shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300' ?>">
            <i class="fa-solid fa-check"></i> Disetujui
            <span class="ml-1 px-1.5 py-0.5 text-[10px] font-bold rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400"><?= isset($counts['approved']) ? $counts['approved'] : 0 ?></span>
        </a>
        <a href="?route=my_approvals&status=rejected"
           class="px-4 py-2 rounded-lg text-sm font-semibold transition-all duration-200 inline-flex items-center gap-2 <?= $status === 'rejected' ? 'bg-white dark:bg-gray-700 text-red-600 dark:text-red-400 shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300' ?>">
            <i class="fa-solid fa-xmark"></i> Ditolak
            <span class="ml-1 px-1.5 py-0.5 text-[10px] font-bold rounded-full bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400"><?= isset($counts['rejected']) ? $counts['rejected'] : 0 ?></span>
        </a>
    </div>

    <!-- Search -->
    <form method="GET" class="mb-4">
        <input type="hidden" name="route" value="my_approvals">
        <input type="hidden" name="status" value="<?= escape($status) ?>">
        <div class="flex gap-2">
            <input type="text" name="search" value="<?= escape($search ?? '') ?>" placeholder="Cari jenis, merk, SN..."
                   class="flex-1 max-w-sm px-4 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 dark:text-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500/50 outline-none transition-all duration-200">
            <?php if (!empty($search)): ?>
            <a href="?route=my_approvals&status=<?= escape($status) ?>" class="px-4 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded-xl text-sm font-medium hover:bg-gray-300 dark:hover:bg-gray-600 transition inline-flex items-center gap-1">
                <i class="fa-solid fa-xmark"></i> Reset
            </a>
            <?php endif; ?>
        </div>
    </form>

    <!-- Table -->
    <div class="glass-panel overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/50 text-gray-600 dark:text-gray-300 text-xs uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold">#</th>
                        <th class="px-4 py-3 text-left font-semibold">Barang</th>
                        <th class="px-4 py-3 text-left font-semibold">Transisi Status</th>
                        <th class="px-4 py-3 text-left font-semibold">PIC</th>
                        <th class="px-4 py-3 text-left font-semibold">Status</th>
                        <th class="px-4 py-3 text-left font-semibold">Tanggal Submit</th>
                        <th class="px-4 py-3 text-left font-semibold">Diproses</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="7" class="px-4 py-12 text-center text-gray-400 dark:text-gray-500">
                            <i class="fa-solid fa-inbox text-3xl mb-2 block"></i>
                            Tidak ada data approval <?= $status === 'pending' ? 'yang menunggu' : '' ?>.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($items as $item): ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400 font-mono text-xs">#<?= $item['id'] ?></td>
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-800 dark:text-gray-200"><?= escape($item['jenis_sparepart']) ?></div>
                            <div class="text-xs text-gray-400 dark:text-gray-500">
                                <?php
                                    $snLabel = $item['kategori'] === 'Non-Aset' ? 'QTY' : 'SN';
                                    if ($item['kategori'] === 'Non-Aset') {
                                        $snValue = isset($item['quantity']) ? $item['quantity'] : '-';
                                    } else {
                                        $rawSn = isset($item['serial_number']) ? $item['serial_number'] : '';
                                        $rawSn = str_ireplace('SN-', '', $rawSn);
                                        $rawSn = ltrim($rawSn);
                                        $snValue = $rawSn !== '' ? $rawSn : '-';
                                    }
                                ?>
                                <?= $snLabel ?>: <?= escape($snValue) ?>
                                <?= $item['merk'] ? ' • ' . escape($item['merk']) : '' ?>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <?= renderStatusTransition($item) ?>
                        </td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300 text-xs"><?= escape($item['pic'] ?: '-') ?></td>
                        <td class="px-4 py-3">
                            <?php if ($item['status'] === 'pending'): ?>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-semibold rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400">
                                <i class="fa-solid fa-clock"></i> Menunggu
                            </span>
                            <?php elseif ($item['status'] === 'approved'): ?>
                            <div class="flex flex-col gap-0.5">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-semibold rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400">
                                    <i class="fa-solid fa-check"></i> Disetujui
                                </span>
                                <?php if (!empty($item['approved_by_name'])): ?>
                                <span class="text-[10px] text-gray-400 dark:text-gray-500">oleh <?= escape($item['approved_by_name']) ?></span>
                                <?php endif; ?>
                            </div>
                            <?php else: ?>
                            <div class="flex flex-col gap-0.5">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-semibold rounded-full bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400">
                                    <i class="fa-solid fa-xmark"></i> Ditolak
                                </span>
                                <?php if (!empty($item['approved_by_name'])): ?>
                                <span class="text-[10px] text-gray-400 dark:text-gray-500">oleh <?= escape($item['approved_by_name']) ?></span>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-gray-500 dark:text-gray-500 text-xs font-mono"><?= formatDateTime($item['created_at']) ?></td>
                        <td class="px-4 py-3 text-gray-500 dark:text-gray-500 text-xs font-mono">
                            <?php if ($item['status'] === 'approved' && $item['approved_at']): ?>
                                <?= formatDateTime($item['approved_at']) ?>
                            <?php elseif ($item['status'] === 'rejected' && isset($item['rejected_at']) && $item['rejected_at']): ?>
                                <?= formatDateTime($item['rejected_at']) ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
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
