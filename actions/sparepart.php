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
                SELECT l.*, u.name AS user_name, DATE_FORMAT(l.created_at, '%d/%m/%Y %H:%i') AS waktu
                FROM logbooks l
                LEFT JOIN users u ON l.user_id = u.id
                WHERE $logWhere
                ORDER BY l.created_at DESC
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

if ($action === 'search_sn') {
    header('Content-Type: application/json');
    $q = trim(_get($_GET, 'q', ''));
    if ($q === '') {
        echo json_encode(array('success' => true, 'data' => array()));
        exit;
    }
    $search = 'SN-' . $q . '%';
    $stmt = $db->prepare("SELECT id, serial_number, jenis_sparepart, type_sparepart, merk, status FROM spareparts WHERE serial_number LIKE ? AND deleted_at IS NULL ORDER BY serial_number LIMIT 10");
    $stmt->execute(array($search));
    $results = $stmt->fetchAll();
    echo json_encode(array('success' => true, 'data' => $results));
    exit;
}

if ($action === 'list_by_group') {
    header('Content-Type: application/json');
    $kategori = _get($_GET, 'kategori', '');
    $jenis = _get($_GET, 'jenis', '');
    $type = _get($_GET, 'type', '');

    if (empty($kategori) || empty($jenis)) {
        echo json_encode(array('success' => false, 'message' => 'Parameter tidak lengkap.'));
        exit;
    }

    $search = _get($_GET, 'q', '');

    if ($type === '' || $type === '-') {
        $where = "kategori = ? AND jenis_sparepart = ? AND (type_sparepart IS NULL OR type_sparepart = '') AND deleted_at IS NULL";
        $params = array($kategori, $jenis);
    } else {
        $where = "kategori = ? AND jenis_sparepart = ? AND type_sparepart = ? AND deleted_at IS NULL";
        $params = array($kategori, $jenis, $type);
    }

    if ($search) {
        $where .= " AND (serial_number LIKE ? OR merk LIKE ?)";
        $s = '%' . $search . '%';
        $params[] = $s;
        $params[] = $s;
    }

    $page = max(1, (int)_get($_GET, 'page', 1));
    $perPage = 20;
    $offset = ($page - 1) * $perPage;

    $countStmt = $db->prepare("SELECT COUNT(*) FROM spareparts WHERE $where");
    $countStmt->execute($params);
    $totalItems = (int)$countStmt->fetchColumn();
    $totalPages = max(1, (int)ceil($totalItems / $perPage));

    $stmt = $db->prepare("SELECT * FROM spareparts WHERE $where ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
    $stmt->execute($params);
    $items = $stmt->fetchAll();

    // Summary stats
    $sumStmt = $db->prepare("SELECT
        COUNT(*) as total,
        SUM(CASE WHEN status = 'Tersedia' THEN 1 ELSE 0 END) as tersedia,
        SUM(CASE WHEN status = 'Terpakai' THEN 1 ELSE 0 END) as terpakai,
        SUM(CASE WHEN status = 'Rusak' THEN 1 ELSE 0 END) as rusak,
        SUM(CASE WHEN status = 'Dalam Perbaikan' THEN 1 ELSE 0 END) as dalam_perbaikan
    FROM spareparts WHERE $where");
    $sumStmt->execute($params);
    $stats = $sumStmt->fetch();

    echo json_encode(array(
        'success' => true,
        'items' => $items,
        'kategori' => $kategori,
        'jenis' => $jenis,
        'type' => $type,
        'stats' => $stats,
        'page' => $page,
        'totalPages' => $totalPages,
        'totalItems' => $totalItems,
        'search' => $search
    ));
    exit;
}

if ($action === 'show_by_sn') {
    header('Content-Type: application/json');
    $sn = trim(_get($_GET, 'sn', ''));
    if ($sn === '') {
        echo json_encode(array('success' => false, 'message' => 'Serial number diperlukan.'));
        exit;
    }
    if (strpos($sn, 'SN-') !== 0) {
        $sn = 'SN-' . $sn;
    }
    $stmt = $db->prepare("SELECT * FROM spareparts WHERE serial_number = ? AND deleted_at IS NULL LIMIT 1");
    $stmt->execute(array($sn));
    $data = $stmt->fetch();
    if ($data) {
        echo json_encode(array('success' => true, 'data' => $data));
    } else {
        echo json_encode(array('success' => false, 'message' => 'Serial number tidak ditemukan.'));
    }
    exit;
}

if ($action === 'realtime_dashboard') {
    header('Content-Type: application/json');
    try {
        $stats = $db->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'Tersedia' THEN 1 ELSE 0 END) as tersedia,
                SUM(CASE WHEN status = 'Terpakai' THEN 1 ELSE 0 END) as terpakai,
                SUM(CASE WHEN status = 'Rusak' THEN 1 ELSE 0 END) as rusak,
                SUM(CASE WHEN status = 'Dalam Perbaikan' THEN 1 ELSE 0 END) as dalam_perbaikan
            FROM spareparts WHERE deleted_at IS NULL
        ")->fetch();

        if (isAdmin()) {
            $dipakai = $db->query("
                SELECT id, jenis_sparepart, type_sparepart, merk, serial_number, kategori, status, pic, department, tanggal, keterangan, updated_at
                FROM spareparts WHERE deleted_at IS NULL AND status = 'Terpakai'
                ORDER BY updated_at DESC LIMIT 20
            ")->fetchAll();
        } else {
            $stmt = $db->prepare("
                SELECT id, jenis_sparepart, type_sparepart, merk, serial_number, kategori, status, pic, department, tanggal, keterangan, updated_at
                FROM spareparts WHERE deleted_at IS NULL AND status = 'Terpakai' AND pic = ?
                ORDER BY updated_at DESC LIMIT 20
            ");
            $stmt->execute(array($user['name']));
            $dipakai = $stmt->fetchAll();
        }

        echo json_encode(array(
            'success' => true,
            'stats' => $stats,
            'dipakai' => $dipakai,
            'server_time' => date('Y-m-d H:i:s')
        ));
    } catch (PDOException $e) {
        echo json_encode(array('success' => false, 'message' => $e->getMessage()));
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
    $quantity = $kategori === 'Aset' ? 0 : (int)_get($_POST, 'quantity', 1);
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

    if ($kategori === 'Aset') {
        $quantity = count($serialNumbers);
    } elseif (count($serialNumbers) !== $quantity) {
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
        if ($db->inTransaction()) {
            $db->rollBack();
        }
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
