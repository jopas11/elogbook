<?php

$page_title = 'Jenis & Type';
$require_admin = true;
require_once __DIR__ . '/../includes/auth_check.php';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('jenis_sparepart.php');
}

if (!verifyCsrf(_get($_POST, 'csrf_token', ''))) {
    flash('error', 'Token CSRF tidak valid.');
    redirect('jenis_sparepart.php');
}

$action = _get($_POST, 'action', '');
$id = (int)_get($_POST, 'id', 0);
$nama = trim(_get($_POST, 'nama', ''));
$type = trim(_get($_POST, 'type', ''));

if ($action === 'create_jenis' && $nama) {
    $stmt = $db->prepare("INSERT INTO jenis_spareparts (nama) VALUES (?)");
    $stmt->execute([$nama]);
    flash('success', 'Jenis sparepart ditambahkan.');
} elseif ($action === 'update_jenis' && $id && $nama) {
    $stmt = $db->prepare("UPDATE jenis_spareparts SET nama = ? WHERE id = ? AND type IS NULL");
    $stmt->execute([$nama, $id]);
    flash('success', 'Jenis sparepart diupdate.');
} elseif ($action === 'delete_jenis' && $id) {
    $stmt = $db->prepare("DELETE FROM jenis_spareparts WHERE id = ? AND type IS NULL");
    $stmt->execute([$id]);
    flash('success', 'Jenis sparepart dihapus.');
} elseif ($action === 'create_type' && $type) {
    $stmt = $db->prepare("INSERT INTO jenis_spareparts (nama, type) VALUES (?, ?)");
    $stmt->execute([$type, $type]);
    flash('success', 'Type sparepart ditambahkan.');
} elseif ($action === 'update_type' && $id && $type) {
    $stmt = $db->prepare("UPDATE jenis_spareparts SET nama = ?, type = ? WHERE id = ? AND type IS NOT NULL");
    $stmt->execute([$type, $type, $id]);
    flash('success', 'Type sparepart diupdate.');
} elseif ($action === 'delete_type' && $id) {
    $stmt = $db->prepare("DELETE FROM jenis_spareparts WHERE id = ? AND type IS NOT NULL");
    $stmt->execute([$id]);
    flash('success', 'Type sparepart dihapus.');
}

redirect('jenis_sparepart.php');
