<?php

$page_title = 'Sparepart Keluar';
$require_admin = false;
require_once __DIR__ . '/../includes/auth_check.php';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('sparepart_keluar.php');
}

if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
    flash('error', 'Token CSRF tidak valid.');
    redirect('sparepart_keluar.php');
}

$sparepartId = (int)($_POST['sparepart_id'] ?? 0);
$qtyAmbil = max(1, (int)($_POST['quantity'] ?? 1));
$pic = trim($_POST['pic'] ?? '');
$department = trim($_POST['department'] ?? '');
$tanggal = $_POST['tanggal'] ?? date('Y-m-d');
$keterangan = trim($_POST['keterangan'] ?? '');

$stmt = $db->prepare("SELECT * FROM spareparts WHERE id = ? AND status = 'Tersedia' AND deleted_at IS NULL");
$stmt->execute([$sparepartId]);
$sparepart = $stmt->fetch();

if (!$sparepart) {
    flash('error', 'Sparepart tidak tersedia.');
    redirect('sparepart_keluar.php');
}

$currentQty = (int)$sparepart['quantity'];

if ($qtyAmbil > $currentQty) {
    flash('error', 'Stok tidak mencukupi. Tersedia: ' . $currentQty);
    redirect('sparepart_keluar.php');
}

if ($qtyAmbil < $currentQty) {
    // Partial: decrement quantity, keep status Tersedia
    $newQty = $currentQty - $qtyAmbil;
    $db->prepare("UPDATE spareparts SET quantity = ?, pic = ?, department = ? WHERE id = ?")->execute([$newQty, $pic, $department, $sparepartId]);
} else {
    // All taken: set status to Terpakai
    $db->prepare("UPDATE spareparts SET status = 'Terpakai', pic = ?, department = ? WHERE id = ?")->execute([$pic, $department, $sparepartId]);
}

$logKeterangan = $keterangan ?: 'Barang keluar: ' . $sparepart['jenis_sparepart'];
if ($qtyAmbil > 1) $logKeterangan .= ' (x' . $qtyAmbil . ')';

$stmt = $db->prepare("INSERT INTO logbooks (sparepart_id, user_id, tipe_transaksi, pic_penerima, department, tanggal, keterangan_log) VALUES (?, ?, 'Barang Keluar', ?, ?, ?, ?)");
$stmt->execute([$sparepartId, $user['id'], $pic, $department, $tanggal, $logKeterangan]);

$msg = $qtyAmbil > 1 ? $qtyAmbil . ' sparepart berhasil diambil.' : 'Sparepart berhasil diambil.';
flash('success', $msg);
redirect('sparepart_keluar.php');
