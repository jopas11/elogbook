<?php

$page_title = 'Ubah Status';
$require_admin = false;
require_once __DIR__ . '/../includes/auth_check.php';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('ubah_status.php');
}

if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
    flash('error', 'Token CSRF tidak valid.');
    redirect('ubah_status.php');
}

$sparepartId = (int)($_POST['sparepart_id'] ?? 0);
$statusBaru = $_POST['status_baru'] ?? '';
$tanggal = $_POST['tanggal'] ?? date('Y-m-d');
$pic = trim($_POST['pic'] ?? '');
$department = trim($_POST['department'] ?? '');
$keterangan = trim($_POST['keterangan'] ?? '');

$validStatus = ['Tersedia', 'Terpakai', 'Rusak', 'Dalam Perbaikan'];
if (!in_array($statusBaru, $validStatus)) {
    flash('error', 'Status tidak valid.');
    redirect('ubah_status.php');
}

$stmt = $db->prepare("SELECT * FROM spareparts WHERE id = ? AND deleted_at IS NULL");
$stmt->execute([$sparepartId]);
$sparepart = $stmt->fetch();

if (!$sparepart) {
    flash('error', 'Sparepart tidak ditemukan.');
    redirect('ubah_status.php');
}

$statusLama = $sparepart['status'];

$db->prepare("UPDATE spareparts SET status = ?, pic = ?, department = ?, keterangan = ? WHERE id = ?")->execute([$statusBaru, $pic, $department, $keterangan, $sparepartId]);

$tipeTransaksi = $statusBaru === 'Dalam Perbaikan' ? 'Dalam Perbaikan' : 'Ubah Status';
$stmt = $db->prepare("INSERT INTO logbooks (sparepart_id, user_id, tipe_transaksi, pic_penerima, department, tanggal, keterangan_log) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->execute([$sparepartId, $user['id'], $tipeTransaksi, $pic, $department, $tanggal, "Status berubah dari $statusLama ke $statusBaru. $keterangan"]);

flash('success', 'Status sparepart berhasil diubah.');
redirect('ubah_status.php');
