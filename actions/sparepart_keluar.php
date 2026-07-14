<?php

$page_title = 'Sparepart Keluar';
$require_admin = false;
require_once __DIR__ . '/../includes/auth_check.php';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('sparepart_keluar.php');
}

if (!verifyCsrf(_get($_POST, 'csrf_token', ''))) {
    flash('error', 'Token CSRF tidak valid.');
    redirect('sparepart_keluar.php');
}

$sparepartId = (int)_get($_POST, 'sparepart_id', 0);
$qtyAmbil = max(1, (int)_get($_POST, 'quantity', 1));
$pic = trim(_get($_POST, 'pic', ''));
$department = trim(_get($_POST, 'department', ''));
$tanggal = _get($_POST, 'tanggal', date('Y-m-d'));
$keterangan = trim(_get($_POST, 'keterangan', ''));

$stmt = $db->prepare("SELECT * FROM spareparts WHERE id = ? AND status = 'Tersedia' AND deleted_at IS NULL");
$stmt->execute(array($sparepartId));
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

$statusLama = 'Tersedia';
$statusBaru = 'Tersedia';
if ($qtyAmbil < $currentQty) {
    // Partial: decrement quantity, keep status Tersedia
    $newQty = $currentQty - $qtyAmbil;
    $db->prepare("UPDATE spareparts SET quantity = ?, pic = ?, department = ? WHERE id = ?")->execute(array($newQty, $pic, $department, $sparepartId));
} else {
    // All taken: set status to Terpakai
    $statusBaru = 'Terpakai';
    $db->prepare("UPDATE spareparts SET status = 'Terpakai', pic = ?, department = ? WHERE id = ?")->execute(array($pic, $department, $sparepartId));
}

$stmt = $db->prepare("INSERT INTO logbooks (sparepart_id, user_id, tipe_transaksi, status_lama, status_baru, pic_penerima, department, tanggal, keterangan_log) VALUES (?, ?, 'Barang Keluar', ?, ?, ?, ?, ?, ?)");
$stmt->execute(array($sparepartId, $user['id'], $statusLama, $statusBaru, $pic, $department, $tanggal, $keterangan));

$msg = $qtyAmbil > 1 ? $qtyAmbil . ' sparepart berhasil diambil.' : 'Sparepart berhasil diambil.';
flash('success', $msg);
redirect('sparepart_keluar.php');
