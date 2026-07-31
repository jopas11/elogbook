<?php
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../config/database.php';

class ApprovalController {
    public static function list() {
        $page_title = 'Approval Status';
        $require_admin = true;
        require_once __DIR__ . '/../helpers/auth.php';

        $db = getDB();
        $status = _get($_GET, 'status', 'pending');

        $where = "WHERE a.deleted_at IS NULL";
        $params = array();

        if ($status && in_array($status, array('pending', 'approved', 'rejected'))) {
            $where .= " AND a.status = ?";
            $params[] = $status;
        }

        $baseQuery = "SELECT a.*, s.jenis_sparepart, s.merk, s.serial_number, u.name as user_name, approver.name as approved_by_name
                      FROM status_approvals a
                      JOIN spareparts s ON s.id = a.sparepart_id
                      JOIN users u ON u.id = a.user_id
                      LEFT JOIN users approver ON approver.id = a.approved_by
                      $where
                      ORDER BY a.created_at DESC";

        list($items, $page, $totalPages) = paginate($db, $baseQuery, $params, 15);

        $counts = $db->query("SELECT status, COUNT(*) as cnt FROM status_approvals WHERE deleted_at IS NULL GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);

        require_once __DIR__ . '/../includes/header.php';
        require_once __DIR__ . '/../includes/sidebar.php';
        require __DIR__ . '/../views/transaksi/approval.php';
        require_once __DIR__ . '/../includes/footer.php';
    }

    public static function myApprovals() {
        $page_title = 'Approval Saya';
        $require_admin = false;
        require_once __DIR__ . '/../helpers/auth.php';

        $db = getDB();
        $user = $_SESSION['user'];
        $status = _get($_GET, 'status', 'pending');

        $where = "WHERE a.deleted_at IS NULL AND a.user_id = ?";
        $params = array($user['id']);

        if ($status && in_array($status, array('pending', 'approved', 'rejected'))) {
            $where .= " AND a.status = ?";
            $params[] = $status;
        }

        $baseQuery = "SELECT a.*, s.jenis_sparepart, s.merk, s.serial_number, approver.name as approved_by_name
                      FROM status_approvals a
                      JOIN spareparts s ON s.id = a.sparepart_id
                      LEFT JOIN users approver ON approver.id = a.approved_by
                      $where
                      ORDER BY a.created_at DESC";

        list($items, $page, $totalPages) = paginate($db, $baseQuery, $params, 15);

        $countStmt = $db->prepare("SELECT status, COUNT(*) as cnt FROM status_approvals WHERE deleted_at IS NULL AND user_id = ? GROUP BY status");
        $countStmt->execute(array($user['id']));
        $counts = $countStmt->fetchAll(PDO::FETCH_KEY_PAIR);

        require_once __DIR__ . '/../includes/header.php';
        require_once __DIR__ . '/../includes/sidebar.php';
        require __DIR__ . '/../views/transaksi/my_approvals.php';
        require_once __DIR__ . '/../includes/footer.php';
    }

    public static function approve() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isAdmin()) {
            echo json_encode(array('success' => false, 'message' => 'Akses ditolak.'));
            exit;
        }
        if (!verifyCsrf(_get($_POST, 'csrf_token', ''))) {
            echo json_encode(array('success' => false, 'message' => 'Token CSRF tidak valid.'));
            exit;
        }

        $id = (int)_get($_POST, 'id', 0);
        $db = getDB();
        $user = $_SESSION['user'];

        $stmt = $db->prepare("SELECT a.*, s.jenis_sparepart, s.merk, s.serial_number, s.kategori, s.type_sparepart FROM status_approvals a JOIN spareparts s ON s.id = a.sparepart_id WHERE a.id = ? AND a.status = 'pending' AND a.deleted_at IS NULL");
        $stmt->execute(array($id));
        $approval = $stmt->fetch();

        if (!$approval) {
            echo json_encode(array('success' => false, 'message' => 'Approval tidak ditemukan atau sudah diproses.'));
            exit;
        }

        $db->beginTransaction();
        try {
            if ($approval['kategori'] === 'Non-Aset' && $approval['status_lama'] === 'Tersedia' && $approval['status_baru'] === 'Terpakai') {
                $qty = (int)$approval['quantity'];

                $tersediaRow = null;
                $tstmt = $db->prepare("SELECT id, quantity, image FROM spareparts WHERE jenis_sparepart = ? AND COALESCE(type_sparepart, '') = COALESCE(?, '') AND COALESCE(merk, '') = COALESCE(?, '') AND status = 'Tersedia' AND deleted_at IS NULL");
                $tstmt->execute(array($approval['jenis_sparepart'], $approval['type_sparepart'], $approval['merk']));
                $tersediaRow = $tstmt->fetch();

                $terpakaiRow = null;
                $tstmt2 = $db->prepare("SELECT id, quantity FROM spareparts WHERE jenis_sparepart = ? AND COALESCE(type_sparepart, '') = COALESCE(?, '') AND COALESCE(merk, '') = COALESCE(?, '') AND status = 'Terpakai' AND deleted_at IS NULL");
                $tstmt2->execute(array($approval['jenis_sparepart'], $approval['type_sparepart'], $approval['merk']));
                $terpakaiRow = $tstmt2->fetch();

                if (!$tersediaRow || (int)$tersediaRow['quantity'] < $qty) {
                    throw new PDOException('Stok tidak mencukupi. Tersedia: ' . ($tersediaRow ? (int)$tersediaRow['quantity'] : 0) . '.');
                }

                $terpakaiImage = $approval['image'] ?: $tersediaRow['image'];

                $newTersedia = (int)$tersediaRow['quantity'] - $qty;
                if ($newTersedia > 0) {
                    $db->prepare("UPDATE spareparts SET quantity = ? WHERE id = ?")
                        ->execute(array($newTersedia, $tersediaRow['id']));
                } else {
                    $db->prepare("DELETE FROM spareparts WHERE id = ?")
                        ->execute(array($tersediaRow['id']));
                }

                if ($terpakaiRow) {
                    $db->prepare("UPDATE spareparts SET quantity = quantity + ?, pic = ?, department = ? WHERE id = ?")
                        ->execute(array($qty, $approval['pic'], $approval['department'], $terpakaiRow['id']));
                } else {
                    $db->prepare("INSERT INTO spareparts (kategori, jenis_sparepart, type_sparepart, serial_number, quantity, tanggal, merk, pic, department, status, keterangan, image) VALUES ('Non-Aset', ?, ?, '', ?, ?, ?, ?, ?, 'Terpakai', ?, ?)")
                        ->execute(array($approval['jenis_sparepart'], null, $qty, $approval['tanggal'], $approval['merk'], $approval['pic'], $approval['department'], $approval['keterangan'], $terpakaiImage));
                }

                $logKeterangan = '[Disetujui oleh ' . $user['name'] . '] ' . ($approval['keterangan'] ?: '');
                $db->prepare("INSERT INTO logbooks (sparepart_id, user_id, image, tipe_transaksi, status_lama, status_baru, pic_penerima, department, tanggal, keterangan_log) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")
                    ->execute(array($approval['sparepart_id'], $approval['user_id'], $terpakaiImage, $approval['tipe_transaksi'], $approval['status_lama'], $approval['status_baru'], $approval['pic'], $approval['department'], $approval['tanggal'], $logKeterangan));

            } elseif ($approval['kategori'] === 'Non-Aset' && $approval['status_lama'] === 'Terpakai' && $approval['status_baru'] === 'Tersedia') {
                $qty = (int)$approval['quantity'];

                $tersediaRow = null;
                $tstmt = $db->prepare("SELECT id, quantity FROM spareparts WHERE jenis_sparepart = ? AND COALESCE(type_sparepart, '') = COALESCE(?, '') AND COALESCE(merk, '') = COALESCE(?, '') AND status = 'Tersedia' AND deleted_at IS NULL");
                $tstmt->execute(array($approval['jenis_sparepart'], $approval['type_sparepart'], $approval['merk']));
                $tersediaRow = $tstmt->fetch();

                $terpakaiRow = null;
                $tstmt2 = $db->prepare("SELECT id, quantity, image FROM spareparts WHERE jenis_sparepart = ? AND COALESCE(type_sparepart, '') = COALESCE(?, '') AND COALESCE(merk, '') = COALESCE(?, '') AND status = 'Terpakai' AND deleted_at IS NULL");
                $tstmt2->execute(array($approval['jenis_sparepart'], $approval['type_sparepart'], $approval['merk']));
                $terpakaiRow = $tstmt2->fetch();

                $tersediaImage = $approval['image'] ?: (isset($terpakaiRow['image']) ? $terpakaiRow['image'] : null);

                if ($tersediaRow) {
                    $db->prepare("UPDATE spareparts SET quantity = quantity + ? WHERE id = ?")
                        ->execute(array($qty, $tersediaRow['id']));
                } else {
                    $db->prepare("INSERT INTO spareparts (kategori, jenis_sparepart, type_sparepart, serial_number, quantity, tanggal, merk, pic, department, status, keterangan, image) VALUES ('Non-Aset', ?, ?, '', ?, ?, ?, ?, ?, 'Tersedia', ?, ?)")
                        ->execute(array($approval['jenis_sparepart'], null, $qty, $approval['tanggal'], $approval['merk'], $approval['pic'], $approval['department'], $approval['keterangan'], $tersediaImage));
                }

                if ($terpakaiRow) {
                    $newQty = (int)$terpakaiRow['quantity'] - $qty;
                    if ($newQty > 0) {
                        $db->prepare("UPDATE spareparts SET quantity = ? WHERE id = ?")
                            ->execute(array($newQty, $terpakaiRow['id']));
                    } else {
                        $db->prepare("DELETE FROM spareparts WHERE id = ?")
                            ->execute(array($terpakaiRow['id']));
                    }
                }

                $logKeterangan = '[Disetujui oleh ' . $user['name'] . '] ' . ($approval['keterangan'] ?: '');
                $db->prepare("INSERT INTO logbooks (sparepart_id, user_id, image, tipe_transaksi, status_lama, status_baru, pic_penerima, department, tanggal, keterangan_log) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")
                    ->execute(array($approval['sparepart_id'], $approval['user_id'], $approval['image'], $approval['tipe_transaksi'], $approval['status_lama'], $approval['status_baru'], $approval['pic'], $approval['department'], $approval['tanggal'], $logKeterangan));

            } else {
                $db->prepare("UPDATE spareparts SET status = ?, pic = ?, department = ?, keterangan = ? WHERE id = ?")
                    ->execute(array($approval['status_baru'], $approval['pic'], $approval['department'], $approval['keterangan'], $approval['sparepart_id']));

                $logKeterangan = '[Disetujui oleh ' . $user['name'] . '] ' . ($approval['keterangan'] ?: '');
                $db->prepare("INSERT INTO logbooks (sparepart_id, user_id, image, tipe_transaksi, status_lama, status_baru, pic_penerima, department, tanggal, keterangan_log) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")
                    ->execute(array($approval['sparepart_id'], $approval['user_id'], $approval['image'], $approval['tipe_transaksi'], $approval['status_lama'], $approval['status_baru'], $approval['pic'], $approval['department'], $approval['tanggal'], $logKeterangan));
            }

            $db->prepare("UPDATE status_approvals SET status = 'approved', approved_by = ?, approved_at = NOW() WHERE id = ?")
                ->execute(array($user['id'], $id));

            $db->commit();
            echo json_encode(array('success' => true, 'message' => 'Approval berhasil disetujui.'));
        } catch (PDOException $e) {
            $db->rollBack();
            echo json_encode(array('success' => false, 'message' => 'Gagal memproses approval.'));
        }
        exit;
    }

    public static function reject() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isAdmin()) {
            echo json_encode(array('success' => false, 'message' => 'Akses ditolak.'));
            exit;
        }
        if (!verifyCsrf(_get($_POST, 'csrf_token', ''))) {
            echo json_encode(array('success' => false, 'message' => 'Token CSRF tidak valid.'));
            exit;
        }

        $id = (int)_get($_POST, 'id', 0);
        $db = getDB();
        $user = $_SESSION['user'];

        $stmt = $db->prepare("SELECT * FROM status_approvals WHERE id = ? AND status = 'pending' AND deleted_at IS NULL");
        $stmt->execute(array($id));
        $approval = $stmt->fetch();

        if (!$approval) {
            echo json_encode(array('success' => false, 'message' => 'Approval tidak ditemukan atau sudah diproses.'));
            exit;
        }

        $db->prepare("UPDATE status_approvals SET status = 'rejected', approved_by = ?, rejected_at = NOW() WHERE id = ?")
            ->execute(array($user['id'], $id));

        echo json_encode(array('success' => true, 'message' => 'Approval ditolak.'));
        exit;
    }
}
