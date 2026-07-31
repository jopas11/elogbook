<?php
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../config/database.php';

class UbahStatusController {
    public static function updateStatus() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('ubah_status.php');
        }

        if (!verifyCsrf(_get($_POST, 'csrf_token', ''))) {
            flash('error', 'Token CSRF tidak valid.');
            redirect('ubah_status.php');
        }

        $sparepartId = (int)_get($_POST, 'sparepart_id', 0);
        $statusBaru = _get($_POST, 'status_baru', '');
        $tanggal = _get($_POST, 'tanggal', date('Y-m-d'));
        $pic = trim(_get($_POST, 'pic', ''));
        $department = trim(_get($_POST, 'department', ''));
        $keterangan = trim(_get($_POST, 'keterangan', ''));

        $validStatus = array('Tersedia', 'Terpakai', 'Rusak', 'Dalam Perbaikan');
        if (!in_array($statusBaru, $validStatus)) {
            flash('error', 'Status tidak valid.');
            redirect('ubah_status.php');
        }

        $db = getDB();
        $user = $_SESSION['user'];
        $stmt = $db->prepare("SELECT * FROM spareparts WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute(array($sparepartId));
        $sparepart = $stmt->fetch();

        if (!$sparepart) {
            flash('error', 'Sparepart tidak ditemukan.');
            redirect('ubah_status.php');
        }

        $statusLama = $sparepart['status'];

        if ($statusLama === $statusBaru) {
            flash('error', 'Status baru sama dengan status lama.');
            redirect('ubah_status.php');
        }

        $checkPending = $db->prepare("SELECT id FROM status_approvals WHERE sparepart_id = ? AND status = 'pending' AND deleted_at IS NULL LIMIT 1");
        $checkPending->execute(array($sparepartId));
        if ($checkPending->fetch()) {
            flash('error', 'Barang ini sedang dalam proses approval. Tidak dapat mengubah status sampai disetujui/ditolak.');
            redirect('ubah_status.php');
        }

        $db->prepare("UPDATE spareparts SET status = ?, pic = ?, department = ?, keterangan = ? WHERE id = ?")->execute(array($statusBaru, $pic, $department, $keterangan, $sparepartId));

        $tipeTransaksi = $statusBaru === 'Dalam Perbaikan' ? 'Dalam Perbaikan' : 'Ubah Status';
        $stmt = $db->prepare("INSERT INTO logbooks (sparepart_id, user_id, image, tipe_transaksi, status_lama, status_baru, pic_penerima, department, tanggal, keterangan_log) VALUES (?, ?, NULL, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute(array($sparepartId, $user['id'], $tipeTransaksi, $statusLama, $statusBaru, $pic, $department, $tanggal, $keterangan));

        flash('success', 'Status sparepart berhasil diubah.');
        redirect('ubah_status.php');
    }
}
