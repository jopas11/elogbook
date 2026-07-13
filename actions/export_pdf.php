<?php

$require_admin = false;
require_once __DIR__ . '/../includes/auth_check.php';

$db = getDB();

$where = "WHERE deleted_at IS NULL";
$params = [];

if (!empty($_GET['kategori'])) {
    $where .= " AND kategori = ?";
    $params[] = $_GET['kategori'];
}
if (!empty($_GET['status'])) {
    $where .= " AND status = ?";
    $params[] = $_GET['status'];
}

$stmt = $db->prepare("SELECT * FROM spareparts $where ORDER BY created_at DESC");
$stmt->execute($params);
$spareparts = $stmt->fetchAll();

$stats = $db->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'Tersedia' THEN 1 ELSE 0 END) as tersedia,
        SUM(CASE WHEN status = 'Terpakai' THEN 1 ELSE 0 END) as terpakai,
        SUM(CASE WHEN status = 'Rusak' THEN 1 ELSE 0 END) as rusak,
        SUM(CASE WHEN status = 'Dalam Perbaikan' THEN 1 ELSE 0 END) as dalam_perbaikan
    FROM spareparts WHERE deleted_at IS NULL
")->fetch();

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Sparepart</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; }
        h1 { text-align: center; color: #1e40af; margin-bottom: 5px; }
        .date { text-align: center; color: #666; font-size: 10px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #1e40af; color: white; padding: 6px 8px; text-align: left; font-size: 10px; }
        td { padding: 5px 8px; border-bottom: 1px solid #ddd; font-size: 10px; }
        tr:nth-child(even) { background: #f9f9f9; }
        .stats { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .stat-box { background: #f0f0f0; padding: 8px 15px; border-radius: 5px; text-align: center; flex: 1; margin: 0 5px; }
        .stat-box .num { font-size: 16px; font-weight: bold; }
        .stat-box .label { font-size: 9px; color: #666; }
        .footer { text-align: center; color: #999; font-size: 9px; margin-top: 20px; }
    </style>
</head>
<body>
    <h1>Laporan Inventaris Sparepart</h1>
    <p class="date">Tanggal: <?= date('d F Y') ?></p>

    <div class="stats">
        <div class="stat-box"><div class="num"><?= $stats['total'] ?></div><div class="label">Total</div></div>
        <div class="stat-box"><div class="num" style="color:#16a34a"><?= $stats['tersedia'] ?></div><div class="label">Tersedia</div></div>
        <div class="stat-box"><div class="num" style="color:#dc2626"><?= $stats['terpakai'] ?></div><div class="label">Terpakai</div></div>
        <div class="stat-box"><div class="num" style="color:#ca8a04"><?= $stats['rusak'] ?></div><div class="label">Rusak</div></div>
        <div class="stat-box"><div class="num" style="color:#2563eb"><?= $stats['dalam_perbaikan'] ?></div><div class="label">Perbaikan</div></div>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Jenis</th>
                <th>Type</th>
                <th>Merk</th>
                <th>SN</th>
                <th>Kategori</th>
                <th>Status</th>
                <th>PIC</th>
                <th>Tanggal</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($spareparts as $sp): ?>
            <tr>
                <td>#<?= $sp['id'] ?></td>
                <td><?= htmlspecialchars($sp['jenis_sparepart']) ?></td>
                <td><?= htmlspecialchars($sp['type_sparepart'] ?? '-') ?></td>
                <td><?= htmlspecialchars($sp['merk'] ?? '-') ?></td>
                <td><?= htmlspecialchars($sp['serial_number'] ?? '-') ?></td>
                <td><?= $sp['kategori'] ?></td>
                <td><?= $sp['status'] ?></td>
                <td><?= htmlspecialchars($sp['pic'] ?? '-') ?></td>
                <td><?= date('d/m/Y', strtotime($sp['tanggal'])) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <p class="footer">Dicetak pada <?= date('d F Y H:i') ?> | <?= APP_NAME ?></p>
</body>
</html>
<?php exit; ?>
