<?php

$page_title = 'Sparepart';
$require_admin = false;
require_once __DIR__ . '/../includes/auth_check.php';

$db = getDB();

$action = _get($_GET, 'action', '');

// JSON endpoints
if ($action === 'show') {
    header('Content-Type: application/json');
    $id = (int)_get($_GET, 'id', 0);
    $stmt = $db->prepare("SELECT * FROM spareparts WHERE id = ? AND deleted_at IS NULL");
    $stmt->execute(array($id));
    $data = $stmt->fetch();
    $logs = array();
    if ($data) {
        $logWhere = "l.sparepart_id = ? AND l.deleted_at IS NULL";
        $logParams = array($id);
        if (!isAdmin()) {
            $logWhere .= " AND l.user_id = ?";
            $logParams[] = $user['id'];
        }
        $stmtLogs = $db->prepare("
            SELECT l.*, u.name AS user_name
            FROM logbooks l
            LEFT JOIN users u ON l.user_id = u.id
            WHERE $logWhere
            ORDER BY l.created_at DESC
            LIMIT 20
        ");
        $stmtLogs->execute($logParams);
        $logs = $stmtLogs->fetchAll();
    }
    if ($data) {
        echo json_encode(array('success' => true, 'data' => $data, 'logs' => $logs));
    } else {
        echo json_encode(array('success' => false, 'message' => 'Data tidak ditemukan.'));
    }
    exit;
}

if ($action === 'store') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isAdmin()) {
        flash('error', 'Akses ditolak.');
        redirect('dashboard.php');
    }
    if (!verifyCsrf(_get($_POST, 'csrf_token', ''))) {
        flash('error', 'Token CSRF tidak valid.');
        redirect('dashboard.php');
    }

    $kategori = _get($_POST, 'kategori', 'Aset');
    $jenis_sparepart = trim(_get($_POST, 'jenis_sparepart', ''));
    $type_sparepart = trim(_get($_POST, 'type_sparepart', ''));
    $quantity = (int)_get($_POST, 'quantity', 1);
    $tanggal = _get($_POST, 'tanggal', date('Y-m-d'));
    $merk = trim(_get($_POST, 'merk', ''));
    $pic = trim(_get($_POST, 'pic', ''));
    $department = trim(_get($_POST, 'department', ''));
    $status = _get($_POST, 'status', 'Tersedia');
    $keterangan = trim(_get($_POST, 'keterangan', ''));

    // Parse SNs: comma-separated numbers, auto-prefix "SN-"
    $raw = array_map('trim', explode(',', trim(_get($_POST, 'serial_number', ''))));
    $raw = array_filter($raw, function($v) { return $v !== ''; });
    $serialNumbers = array_map(function($v) { return 'SN-' . $v; }, $raw);

    if (count($serialNumbers) !== $quantity) {
        flash('error', 'Jumlah serial number (' . count($serialNumbers) . ') tidak sama dengan quantity (' . $quantity . ').');
        redirect('dashboard.php');
    }

    $inserted = 0;
    $db->beginTransaction();
    try {
        $stmt = $db->prepare("INSERT INTO spareparts (user_id, kategori, jenis_sparepart, type_sparepart, serial_number, quantity, tanggal, merk, pic, department, status, keterangan) VALUES (?, ?, ?, ?, ?, 1, ?, ?, ?, ?, ?, ?)");

        foreach ($serialNumbers as $sn) {
            $stmt->execute(array($user['id'], $kategori, $jenis_sparepart, $type_sparepart, $sn, $tanggal, $merk, $pic, $department, $status, $keterangan));
            $inserted++;
        }

        $db->commit();

        // Log
        if ($inserted > 0) {
            $stmt = $db->prepare("INSERT INTO logbooks (sparepart_id, user_id, tipe_transaksi, status_baru, pic_penerima, department, tanggal, keterangan_log) VALUES (?, ?, 'Barang Masuk', ?, ?, ?, ?, ?)");
            $stmt->execute(array($db->lastInsertId(), $user['id'], $status, $pic, $department, $tanggal, $keterangan));
        }

        flash('success', $inserted . ' sparepart berhasil ditambahkan.');
    } catch (PDOException $e) {
        $db->rollBack();
        if ($e->getCode() == 23000 && strpos($e->getMessage(), 'serial_number') !== false) {
            flash('error', 'Serial number "' . $sn . '" sudah terdaftar.');
        } else {
            flash('error', 'Gagal menambah sparepart.');
        }
    }
    redirect('dashboard.php');
}

if ($action === 'update') {
    header('Content-Type: application/json');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isAdmin()) {
        echo json_encode(array('success' => false, 'message' => 'Akses ditolak.'));
        exit;
    }
    if (!verifyCsrf(_get($_POST, 'csrf_token', ''))) {
        echo json_encode(array('success' => false, 'message' => 'Token CSRF tidak valid.'));
        exit;
    }
    $id = (int)_get($_GET, 'id', 0);
    try {
        $stmt = $db->prepare("UPDATE spareparts SET kategori=?, jenis_sparepart=?, type_sparepart=?, serial_number=?, quantity=?, merk=?, pic=?, department=?, status=?, keterangan=? WHERE id=? AND deleted_at IS NULL");
        $stmt->execute(array(
            _get($_POST, 'kategori', 'Aset'),
            trim(_get($_POST, 'jenis_sparepart', '')),
            trim(_get($_POST, 'type_sparepart', '')),
            trim(_get($_POST, 'serial_number', '')),
            (int)_get($_POST, 'quantity', 1),
            trim(_get($_POST, 'merk', '')),
            trim(_get($_POST, 'pic', '')),
            trim(_get($_POST, 'department', '')),
            _get($_POST, 'status', 'Tersedia'),
            trim(_get($_POST, 'keterangan', '')),
            $id
        ));
        echo json_encode(array('success' => true));
    } catch (PDOException $e) {
        echo json_encode(array('success' => false, 'message' => 'Gagal update: ' . $e->getMessage()));
    }
    exit;
}

if ($action === 'destroy') {
    header('Content-Type: application/json');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isAdmin()) {
        echo json_encode(array('success' => false, 'message' => 'Akses ditolak.'));
        exit;
    }
    if (!verifyCsrf(_get($_POST, 'csrf_token', ''))) {
        echo json_encode(array('success' => false, 'message' => 'Token CSRF tidak valid.'));
        exit;
    }
    $id = (int)_get($_GET, 'id', 0);
    try {
        $stmt = $db->prepare("UPDATE spareparts SET deleted_at = NOW() WHERE id = ?");
        $stmt->execute(array($id));
        echo json_encode(array('success' => true));
    } catch (PDOException $e) {
        echo json_encode(array('success' => false, 'message' => 'Gagal hapus.'));
    }
    exit;
}

redirect('dashboard.php');
