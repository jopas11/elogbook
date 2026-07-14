<?php

$require_admin = false;
require_once __DIR__ . '/../includes/auth_check.php';

$db = getDB();

$where = "WHERE deleted_at IS NULL";
$params = array();

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

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=spareparts-' . date('Y-m-d') . '.csv');

$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8
fputcsv($output, ['ID', 'Kategori', 'Jenis', 'Type', 'Serial Number', 'Quantity', 'Tanggal', 'Merk', 'PIC', 'Department', 'Status', 'Keterangan']);

foreach ($spareparts as $sp) {
    fputcsv($output, [
        $sp['id'],
        $sp['kategori'],
        $sp['jenis_sparepart'],
        $sp['type_sparepart'],
        $sp['serial_number'],
        $sp['quantity'],
        $sp['tanggal'],
        $sp['merk'],
        $sp['pic'],
        $sp['department'],
        $sp['status'],
        $sp['keterangan'],
    ]);
}

fclose($output);
exit;
