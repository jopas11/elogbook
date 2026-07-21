<?php
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../config/database.php';

class SparepartController {
    public static function show() {
        $db = getDB();
        $user = $_SESSION['user'];
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

    public static function searchSn() {
        $db = getDB();
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

    public static function listByGroup() {
        $db = getDB();
        header('Content-Type: application/json');

        $user = $_SESSION['user'];
        $isAdmin = isAdmin();

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

        // Non-admin: hanya lihat item sendiri (PIC atau punya riwayat)
        if (!$isAdmin) {
            $where .= " AND (pic = ? OR id IN (SELECT sparepart_id FROM logbooks WHERE user_id = ? AND deleted_at IS NULL))";
            $params[] = $user['name'];
            $params[] = $user['id'];
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

        $sumStmt = $db->prepare("SELECT
            SUM(quantity) as total,
            SUM(CASE WHEN status = 'Tersedia' THEN quantity ELSE 0 END) as tersedia,
            SUM(CASE WHEN status = 'Terpakai' THEN quantity ELSE 0 END) as terpakai,
            SUM(CASE WHEN status = 'Rusak' THEN quantity ELSE 0 END) as rusak,
            SUM(CASE WHEN status = 'Dalam Perbaikan' THEN quantity ELSE 0 END) as dalam_perbaikan
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

    public static function showBySn() {
        $db = getDB();
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

    public static function cekPeminjaman() {
        $db = getDB();
        $user = $_SESSION['user'];
        header('Content-Type: application/json');
        $sparepartId = (int)_get($_GET, 'sparepart_id', 0);
        if (!$sparepartId) {
            echo json_encode(array('success' => false, 'message' => 'ID sparepart diperlukan.'));
            exit;
        }
        $stmt = $db->prepare("SELECT p.*, s.jenis_sparepart, s.merk, s.serial_number FROM peminjaman p JOIN spareparts s ON s.id = p.sparepart_id WHERE p.sparepart_id = ? AND p.deleted_at IS NULL AND p.status IN ('Dipinjam','Telat') ORDER BY p.created_at DESC LIMIT 1");
        $stmt->execute(array($sparepartId));
        $data = $stmt->fetch();
        if ($data) {
            if (!isAdmin() && $data['user_id'] != $user['id']) {
                echo json_encode(array('success' => false, 'message' => 'Peminjaman ini bukan milik Anda.'));
                exit;
            }
            echo json_encode(array('success' => true, 'data' => $data));
        } else {
            echo json_encode(array('success' => false, 'message' => 'Tidak ada peminjaman aktif untuk sparepart ini.'));
        }
        exit;
    }

    public static function realtimeDashboard() {
        $db = getDB();
        $user = $_SESSION['user'];
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
                    SELECT id, jenis_sparepart, type_sparepart, merk, serial_number, kategori, status, pic, department, tanggal, keterangan, image, updated_at
                    FROM spareparts WHERE deleted_at IS NULL AND status = 'Terpakai' AND updated_at >= NOW() - INTERVAL 24 HOUR
                    ORDER BY updated_at DESC LIMIT 20
                ")->fetchAll();
            } else {
                $stmt = $db->prepare("
                    SELECT id, jenis_sparepart, type_sparepart, merk, serial_number, kategori, status, pic, department, tanggal, keterangan, image, updated_at
                    FROM spareparts WHERE deleted_at IS NULL AND status = 'Terpakai' AND pic = ? AND updated_at >= NOW() - INTERVAL 24 HOUR
                    ORDER BY updated_at DESC LIMIT 20
                ");
                $stmt->execute(array($user['name']));
                $dipakai = $stmt->fetchAll();
            }

            $lowStock = $db->query("
                SELECT id, jenis_sparepart, merk, serial_number, quantity, minimum_stok
                FROM spareparts WHERE deleted_at IS NULL AND status = 'Tersedia' AND quantity <= minimum_stok
                ORDER BY quantity ASC LIMIT 10
            ")->fetchAll();

            echo json_encode(array(
                'success' => true,
                'stats' => $stats,
                'dipakai' => $dipakai,
                'low_stock' => $lowStock,
                'server_time' => date('Y-m-d H:i:s')
            ));
        } catch (PDOException $e) {
            echo json_encode(array('success' => false, 'message' => 'Terjadi kesalahan sistem.'));
        }
        exit;
    }

    public static function store() {
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

        // Optional image upload
        $imagePath = null;
        if (!empty($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['image'];
            $allowed = array('jpg', 'jpeg', 'png', 'webp');
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed)) {
                flash('error', 'Format foto tidak didukung. Gunakan JPG, PNG, atau WebP.');
                redirect('dashboard.php');
            }
            if ($file['size'] > 2 * 1024 * 1024) {
                flash('error', 'Ukuran foto maksimal 2MB.');
                redirect('dashboard.php');
            }
            $uploadDir = __DIR__ . '/../public/uploads/spareparts/' . date('Y') . '/' . date('m') . '/';
            if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); }
            $filename = time() . '_' . bin2hex(openssl_random_pseudo_bytes(4)) . '.' . $ext;
            if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                $imagePath = 'public/uploads/spareparts/' . date('Y') . '/' . date('m') . '/' . $filename;
            }
        }

        if ($kategori === 'Aset') {
            $raw = array_map('trim', explode(',', trim(_get($_POST, 'serial_number', ''))));
            $raw = array_filter($raw, function($v) { return $v !== ''; });
            $serialNumbers = array_map(function($v) { return 'SN-' . $v; }, $raw);
            $quantity = count($serialNumbers);
        }

        $db = getDB();
        $user = $_SESSION['user'];
        $inserted = 0;
        $lastId = null;
        $db->beginTransaction();
        try {
            if ($kategori === 'Aset') {
                $stmt = $db->prepare("INSERT INTO spareparts (user_id, kategori, jenis_sparepart, type_sparepart, serial_number, quantity, tanggal, merk, pic, department, status, keterangan, image) VALUES (?, ?, ?, ?, ?, 1, ?, ?, ?, ?, ?, ?, ?)");
                foreach ($serialNumbers as $sn) {
                    $stmt->execute(array($user['id'], $kategori, $jenis_sparepart, $type_sparepart, $sn, $tanggal, $merk, $pic, $department, $status, $keterangan, $imagePath));
                    $inserted++;
                }
                $lastId = $db->lastInsertId();
            } else {
                $stmt = $db->prepare("INSERT INTO spareparts (user_id, kategori, jenis_sparepart, type_sparepart, serial_number, quantity, tanggal, merk, pic, department, status, keterangan, image) VALUES (?, ?, ?, ?, NULL, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute(array($user['id'], $kategori, $jenis_sparepart, $type_sparepart, $quantity, $tanggal, $merk, $pic, $department, $status, $keterangan, $imagePath));
                $inserted = $quantity;
                $lastId = $db->lastInsertId();
            }

            if ($lastId) {
                $stmt = $db->prepare("INSERT INTO logbooks (sparepart_id, user_id, image, tipe_transaksi, status_baru, pic_penerima, department, tanggal, keterangan_log) VALUES (?, ?, ?, 'Barang Masuk', ?, ?, ?, ?, ?)");
                $stmt->execute(array($lastId, $user['id'], $imagePath, $status, $pic, $department, $tanggal, $keterangan));
            }

            $db->commit();
            flash('success', $inserted . ' sparepart berhasil ditambahkan.');
        } catch (PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            flash('error', 'Gagal menambah sparepart. Silakan coba lagi.');
        }
        redirect('dashboard.php');
    }

    public static function update() {
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
            $db = getDB();
            $stmt = $db->prepare("UPDATE spareparts SET kategori=?, jenis_penggunaan=?, lokasi_penyimpanan=?, jenis_sparepart=?, type_sparepart=?, serial_number=?, quantity=?, minimum_stok=?, merk=?, pic=?, department=?, status=?, keterangan=? WHERE id=? AND deleted_at IS NULL");
            $stmt->execute(array(
                _get($_POST, 'kategori', 'Aset'),
                _get($_POST, 'jenis_penggunaan', ''),
                trim(_get($_POST, 'lokasi_penyimpanan', '')),
                trim(_get($_POST, 'jenis_sparepart', '')),
                trim(_get($_POST, 'type_sparepart', '')),
                trim(_get($_POST, 'serial_number', '')),
                (int)_get($_POST, 'quantity', 1),
                (int)_get($_POST, 'minimum_stok', 1),
                trim(_get($_POST, 'merk', '')),
                trim(_get($_POST, 'pic', '')),
                trim(_get($_POST, 'department', '')),
                _get($_POST, 'status', 'Tersedia'),
                trim(_get($_POST, 'keterangan', '')),
                $id
            ));
            echo json_encode(array('success' => true));
        } catch (PDOException $e) {
            echo json_encode(array('success' => false, 'message' => 'Gagal update data.'));
        }
        exit;
    }

    public static function destroy() {
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
            $db = getDB();
            $stmt = $db->prepare("UPDATE spareparts SET deleted_at = NOW() WHERE id = ?");
            $stmt->execute(array($id));
            echo json_encode(array('success' => true));
        } catch (PDOException $e) {
            echo json_encode(array('success' => false, 'message' => 'Gagal hapus.'));
        }
        exit;
    }
}
