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

$mode = _get($_POST, 'mode', 'nonaset_insert');

if ($mode === 'aset_update') {
    // === ASET: UPDATE existing sparepart by SN ===
    $sparepartId = (int)_get($_POST, 'sparepart_id', 0);
    $status_baru = _get($_POST, 'status_baru', '');
    $pic = trim(_get($_POST, 'pic', ''));
    $department = trim(_get($_POST, 'department', ''));
    $tanggal = _get($_POST, 'tanggal', date('Y-m-d'));
    $keterangan = trim(_get($_POST, 'keterangan', ''));

    $validStatus = array('Tersedia', 'Terpakai', 'Rusak', 'Dalam Perbaikan');
    if (!in_array($status_baru, $validStatus)) {
        flash('error', 'Status tidak valid.');
        redirect('sparepart_keluar.php');
    }

    $stmt = $db->prepare("SELECT * FROM spareparts WHERE id = ? AND deleted_at IS NULL");
    $stmt->execute(array($sparepartId));
    $sparepart = $stmt->fetch();

    if (!$sparepart) {
        flash('error', 'Sparepart tidak ditemukan.');
        redirect('sparepart_keluar.php');
    }

    // Non-admin can only update items for themselves
    if (!isAdmin() && $pic !== $user['name']) {
        flash('error', 'Anda hanya dapat mengubah barang untuk diri sendiri.');
        redirect('sparepart_keluar.php');
    }

    $status_lama = $sparepart['status'];

    $tipeTransaksi = 'Barang Masuk';
    if ($status_baru === 'Terpakai') {
        $tipeTransaksi = 'Barang Keluar';
    } elseif ($status_baru === 'Dalam Perbaikan') {
        $tipeTransaksi = 'Dalam Perbaikan';
    } elseif ($status_lama !== $status_baru) {
        $tipeTransaksi = 'Ubah Status';
    }

    $db->beginTransaction();
    try {
        $db->prepare("UPDATE spareparts SET status = ?, pic = ?, department = ?, keterangan = ? WHERE id = ?")
            ->execute(array($status_baru, $pic, $department, $keterangan, $sparepartId));

        $stmtLog = $db->prepare("INSERT INTO logbooks (sparepart_id, user_id, tipe_transaksi, status_lama, status_baru, pic_penerima, department, tanggal, keterangan_log) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmtLog->execute(array($sparepartId, $user['id'], $tipeTransaksi, $status_lama, $status_baru, $pic, $department, $tanggal, $keterangan));

        $db->commit();
        flash('success', 'Status sparepart berhasil diubah menjadi ' . $status_baru . '.');
    } catch (PDOException $e) {
        $db->rollBack();
        flash('error', 'Gagal mengubah status sparepart.');
    }

    redirect('sparepart_keluar.php');
}

// === NON-ASET: INSERT new sparepart ===
$kategori = 'Non-Aset';
$jenis_sparepart = trim(_get($_POST, 'jenis_sparepart', ''));
$type_sparepart = trim(_get($_POST, 'type_sparepart', ''));
$merk = trim(_get($_POST, 'merk', ''));
$status_lama = _get($_POST, 'status_lama', 'Tersedia');
$status_baru = _get($_POST, 'status_baru', '');
$pic = trim(_get($_POST, 'pic', ''));
$department = trim(_get($_POST, 'department', ''));
$tanggal = _get($_POST, 'tanggal', date('Y-m-d'));
$keterangan = trim(_get($_POST, 'keterangan', ''));

$validStatus = array('Tersedia', 'Terpakai', 'Rusak', 'Dalam Perbaikan');
if (!in_array($status_baru, $validStatus) || !in_array($status_lama, $validStatus)) {
    flash('error', 'Status tidak valid.');
    redirect('sparepart_keluar.php');
}

if (empty($jenis_sparepart) || empty($merk) || empty($pic) || empty($department)) {
    flash('error', 'Semua field wajib diisi.');
    redirect('sparepart_keluar.php');
}

$tipeTransaksi = 'Barang Masuk';
if ($status_baru === 'Terpakai') {
    $tipeTransaksi = 'Barang Keluar';
} elseif ($status_baru === 'Dalam Perbaikan') {
    $tipeTransaksi = 'Dalam Perbaikan';
} elseif ($status_lama !== $status_baru) {
    $tipeTransaksi = 'Ubah Status';
}

$quantity = max(1, (int)_get($_POST, 'quantity', 1));

$db->beginTransaction();
try {
    for ($i = 0; $i < $quantity; $i++) {
        $snNonAset = 'NON-SN-' . strtoupper(uniqid());
        $stmt = $db->prepare("INSERT INTO spareparts (user_id, kategori, jenis_sparepart, type_sparepart, serial_number, quantity, tanggal, merk, pic, department, status, keterangan) VALUES (?, ?, ?, ?, ?, 1, ?, ?, ?, ?, ?, ?)");
        $stmt->execute(array($user['id'], $kategori, $jenis_sparepart, $type_sparepart, $snNonAset, $tanggal, $merk, $pic, $department, $status_baru, $keterangan));
        $lastId = $db->lastInsertId();
    }

    $stmtLog = $db->prepare("INSERT INTO logbooks (sparepart_id, user_id, tipe_transaksi, status_lama, status_baru, pic_penerima, department, tanggal, keterangan_log) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmtLog->execute(array($lastId, $user['id'], $tipeTransaksi, $status_lama, $status_baru, $pic, $department, $tanggal, $keterangan));

    $db->commit();
    flash('success', $quantity . ' sparepart berhasil dicatat sebagai ' . $tipeTransaksi . '.');
} catch (PDOException $e) {
    $db->rollBack();
    flash('error', 'Gagal menyimpan data.');
}

redirect('sparepart_keluar.php');
