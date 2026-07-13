<?php

$page_title = 'Sparepart';
$require_admin = false;
require_once __DIR__ . '/../includes/auth_check.php';

$db = getDB();

$action = $_GET['action'] ?? '';

// JSON endpoints
if ($action === 'show') {
    header('Content-Type: application/json');
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $db->prepare("SELECT * FROM spareparts WHERE id = ? AND deleted_at IS NULL");
    $stmt->execute([$id]);
    $data = $stmt->fetch();
    $logs = [];
    if ($data) {
        $stmtLogs = $db->prepare("
            SELECT l.*, u.name AS user_name
            FROM logbooks l
            LEFT JOIN users u ON l.user_id = u.id
            WHERE l.sparepart_id = ? AND l.deleted_at IS NULL
            ORDER BY l.created_at DESC
            LIMIT 20
        ");
        $stmtLogs->execute([$id]);
        $logs = $stmtLogs->fetchAll();
    }
    if ($data) {
        echo json_encode(['success' => true, 'data' => $data, 'logs' => $logs]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Data tidak ditemukan.']);
    }
    exit;
}

if ($action === 'store') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isAdmin()) {
        flash('error', 'Akses ditolak.');
        redirect('dashboard.php');
    }
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        flash('error', 'Token CSRF tidak valid.');
        redirect('dashboard.php');
    }

    $kategori = $_POST['kategori'] ?? 'Aset';
    $jenis_sparepart = trim($_POST['jenis_sparepart'] ?? '');
    $type_sparepart = trim($_POST['type_sparepart'] ?? '');
    $quantity = (int)($_POST['quantity'] ?? 1);
    $tanggal = $_POST['tanggal'] ?? date('Y-m-d');
    $merk = trim($_POST['merk'] ?? '');
    $pic = trim($_POST['pic'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $status = $_POST['status'] ?? 'Tersedia';
    $keterangan = trim($_POST['keterangan'] ?? '');

    // Parse SNs: comma-separated numbers, auto-prefix "SN-"
    $raw = array_map('trim', explode(',', trim($_POST['serial_number'] ?? '')));
    $raw = array_filter($raw, fn($v) => $v !== '');
    $serialNumbers = array_map(fn($v) => 'SN-' . $v, $raw);

    if (count($serialNumbers) !== $quantity) {
        flash('error', 'Jumlah serial number (' . count($serialNumbers) . ') tidak sama dengan quantity (' . $quantity . ').');
        redirect('dashboard.php');
    }

    $inserted = 0;
    $db->beginTransaction();
    try {
        $stmt = $db->prepare("INSERT INTO spareparts (kategori, jenis_sparepart, type_sparepart, serial_number, quantity, tanggal, merk, pic, department, status, keterangan) VALUES (?, ?, ?, ?, 1, ?, ?, ?, ?, ?, ?)");

        foreach ($serialNumbers as $sn) {
            $stmt->execute([$kategori, $jenis_sparepart, $type_sparepart, $sn, $tanggal, $merk, $pic, $department, $status, $keterangan]);
            $inserted++;
        }

        $db->commit();

        // Log
        if ($inserted > 0) {
            $stmt = $db->prepare("INSERT INTO logbooks (sparepart_id, user_id, tipe_transaksi, pic_penerima, department, tanggal, keterangan_log) VALUES (?, ?, 'Barang Masuk', ?, ?, ?, ?)");
            $stmt->execute([$db->lastInsertId(), $user['id'], $pic, $department, $tanggal, 'Barang masuk: ' . $jenis_sparepart . ' (' . $merk . ') x' . $inserted]);
        }

        flash('success', $inserted . ' sparepart berhasil ditambahkan.');
    } catch (PDOException $e) {
        $db->rollBack();
        if ($e->getCode() == 23000 && str_contains($e->getMessage(), 'serial_number')) {
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
        echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
        exit;
    }
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'message' => 'Token CSRF tidak valid.']);
        exit;
    }
    $id = (int)($_GET['id'] ?? 0);
    try {
        $stmt = $db->prepare("UPDATE spareparts SET kategori=?, jenis_sparepart=?, type_sparepart=?, serial_number=?, quantity=?, merk=?, pic=?, department=?, status=?, keterangan=? WHERE id=? AND deleted_at IS NULL");
        $stmt->execute([
            $_POST['kategori'] ?? 'Aset',
            trim($_POST['jenis_sparepart'] ?? ''),
            trim($_POST['type_sparepart'] ?? ''),
            trim($_POST['serial_number'] ?? ''),
            (int)($_POST['quantity'] ?? 1),
            trim($_POST['merk'] ?? ''),
            trim($_POST['pic'] ?? ''),
            trim($_POST['department'] ?? ''),
            $_POST['status'] ?? 'Tersedia',
            trim($_POST['keterangan'] ?? ''),
            $id
        ]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Gagal update: ' . $e->getMessage()]);
    }
    exit;
}

if ($action === 'destroy') {
    header('Content-Type: application/json');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isAdmin()) {
        echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
        exit;
    }
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'message' => 'Token CSRF tidak valid.']);
        exit;
    }
    $id = (int)($_GET['id'] ?? 0);
    try {
        $stmt = $db->prepare("UPDATE spareparts SET deleted_at = NOW() WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Gagal hapus.']);
    }
    exit;
}

redirect('dashboard.php');
