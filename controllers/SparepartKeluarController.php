<?php
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../config/database.php';

class SparepartKeluarController {
    private static function uploadImage() {
        if (empty($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $file = $_FILES['image'];
        $allowed = array('jpg', 'jpeg', 'png', 'webp');
        $maxSize = 2 * 1024 * 1024;

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            flash('error', 'Format foto tidak didukung. Gunakan JPG, PNG, atau WebP.');
            return '__FLASH_SET__';
        }

        if ($file['size'] > $maxSize) {
            flash('error', 'Ukuran foto maksimal 2MB.');
            return '__FLASH_SET__';
        }

        $uploadDir = __DIR__ . '/../public/uploads/spareparts/' . date('Y') . '/' . date('m') . '/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filename = time() . '_' . bin2hex(openssl_random_pseudo_bytes(4)) . '.' . $ext;
        $destPath = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            flash('error', 'Gagal menyimpan foto.');
            return '__FLASH_SET__';
        }

        return 'public/uploads/spareparts/' . date('Y') . '/' . date('m') . '/' . $filename;
    }

    public static function nonasetInsert() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('sparepart_keluar.php');
        }

        if (!verifyCsrf(_get($_POST, 'csrf_token', ''))) {
            flash('error', 'Token CSRF tidak valid.');
            redirect('sparepart_keluar.php');
        }

        $user = $_SESSION['user'];
        $imagePath = self::uploadImage();

        if (!$imagePath || $imagePath === '__FLASH_SET__') {
            if ($imagePath !== '__FLASH_SET__') {
                flash('error', 'Foto barang wajib diupload (format JPG/PNG/WebP, maks 2MB).');
            }
            redirect('sparepart_keluar.php');
        }

        $kategori = 'Non-Aset';
        $jenis_penggunaan = _get($_POST, 'jenis_penggunaan', '');
        $lokasi_penyimpanan = trim(_get($_POST, 'lokasi_penyimpanan', ''));
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

        $db = getDB();
        $db->beginTransaction();
        try {
            for ($i = 0; $i < $quantity; $i++) {
                $snNonAset = 'NON-SN-' . strtoupper(uniqid());
                $stmt = $db->prepare("INSERT INTO spareparts (user_id, kategori, jenis_penggunaan, lokasi_penyimpanan, minimum_stok, jenis_sparepart, type_sparepart, serial_number, quantity, tanggal, merk, pic, department, status, keterangan, image) VALUES (?, ?, ?, ?, 1, ?, ?, ?, 1, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute(array($user['id'], $kategori, $jenis_penggunaan, $lokasi_penyimpanan, $jenis_sparepart, $type_sparepart, $snNonAset, $tanggal, $merk, $pic, $department, $status_baru, $keterangan, $imagePath));
                $lastId = $db->lastInsertId();
            }

            $stmtLog = $db->prepare("INSERT INTO logbooks (sparepart_id, user_id, image, tipe_transaksi, status_lama, status_baru, pic_penerima, department, tanggal, keterangan_log) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmtLog->execute(array($lastId, $user['id'], $imagePath, $tipeTransaksi, $status_lama, $status_baru, $pic, $department, $tanggal, $keterangan));

            $db->commit();
            flash('success', $quantity . ' sparepart berhasil dicatat sebagai ' . $tipeTransaksi . '.');
        } catch (PDOException $e) {
            $db->rollBack();
            flash('error', 'Gagal menyimpan data. Silakan coba lagi.');
        }

        redirect('sparepart_keluar.php');
    }

    public static function asetUpdate() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('sparepart_keluar.php');
        }

        if (!verifyCsrf(_get($_POST, 'csrf_token', ''))) {
            flash('error', 'Token CSRF tidak valid.');
            redirect('sparepart_keluar.php');
        }

        $user = $_SESSION['user'];
        $imagePath = self::uploadImage();

        if (!$imagePath || $imagePath === '__FLASH_SET__') {
            if ($imagePath !== '__FLASH_SET__') {
                flash('error', 'Foto barang wajib diupload (format JPG/PNG/WebP, maks 2MB).');
            }
            redirect('sparepart_keluar.php');
        }

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

        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM spareparts WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute(array($sparepartId));
        $sparepart = $stmt->fetch();

        if (!$sparepart) {
            flash('error', 'Sparepart tidak ditemukan.');
            redirect('sparepart_keluar.php');
        }

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
            $db->prepare("UPDATE spareparts SET status = ?, pic = ?, department = ?, keterangan = ?, image = ? WHERE id = ?")
                ->execute(array($status_baru, $pic, $department, $keterangan, $imagePath, $sparepartId));

            $stmtLog = $db->prepare("INSERT INTO logbooks (sparepart_id, user_id, image, tipe_transaksi, status_lama, status_baru, pic_penerima, department, tanggal, keterangan_log) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmtLog->execute(array($sparepartId, $user['id'], $imagePath, $tipeTransaksi, $status_lama, $status_baru, $pic, $department, $tanggal, $keterangan));

            $db->commit();
            flash('success', 'Status sparepart berhasil diubah menjadi ' . $status_baru . '.');
        } catch (PDOException $e) {
            $db->rollBack();
            flash('error', 'Gagal mengubah status sparepart. Silakan coba lagi.');
        }

        redirect('sparepart_keluar.php');
    }

    public static function pinjam() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('sparepart_keluar.php');
        }

        if (!verifyCsrf(_get($_POST, 'csrf_token', ''))) {
            flash('error', 'Token CSRF tidak valid.');
            redirect('sparepart_keluar.php');
        }

        $sparepartId = (int)_get($_POST, 'sparepart_id', 0);
        $peminjam = trim(_get($_POST, 'peminjam', ''));
        $department = trim(_get($_POST, 'department', ''));
        $tanggalPinjam = _get($_POST, 'tanggal_pinjam', date('Y-m-d'));
        $tanggalRencanaKembali = _get($_POST, 'tanggal_rencana_kembali', '');
        $kondisiPinjam = trim(_get($_POST, 'kondisi_pinjam', ''));
        $keterangan = trim(_get($_POST, 'keterangan', ''));

        if (empty($sparepartId) || empty($peminjam)) {
            flash('error', 'Sparepart dan peminjam wajib diisi.');
            redirect('sparepart_keluar.php');
        }

        $db = getDB();
        $user = $_SESSION['user'];
        $stmt = $db->prepare("SELECT * FROM spareparts WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute(array($sparepartId));
        $sparepart = $stmt->fetch();

        if (!$sparepart) {
            flash('error', 'Sparepart tidak ditemukan.');
            redirect('sparepart_keluar.php');
        }
        if ($sparepart['jenis_penggunaan'] !== 'Reusable') {
            flash('error', 'Hanya sparepart Reusable yang dapat dipinjam.');
            redirect('sparepart_keluar.php');
        }
        if ($sparepart['status'] !== 'Tersedia') {
            flash('error', 'Sparepart tidak tersedia untuk dipinjam (status: ' . $sparepart['status'] . ').');
            redirect('sparepart_keluar.php');
        }

        $statusLama = $sparepart['status'];
        $db->beginTransaction();
        try {
            $db->prepare("UPDATE spareparts SET status = 'Terpakai', pic = ?, department = ? WHERE id = ?")
                ->execute(array($peminjam, $department, $sparepartId));
            $stmtPinjam = $db->prepare("INSERT INTO peminjaman (sparepart_id, user_id, peminjam, department, tanggal_pinjam, tanggal_rencana_kembali, kondisi_pinjam, keterangan, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Dipinjam')");
            $stmtPinjam->execute(array($sparepartId, $user['id'], $peminjam, $department, $tanggalPinjam, $tanggalRencanaKembali ?: null, $kondisiPinjam, $keterangan));
            $stmtLog = $db->prepare("INSERT INTO logbooks (sparepart_id, user_id, image, tipe_transaksi, status_lama, status_baru, pic_penerima, department, tanggal, keterangan_log) VALUES (?, ?, NULL, 'Dipinjam', ?, 'Terpakai', ?, ?, ?, ?)");
            $stmtLog->execute(array($sparepartId, $user['id'], $statusLama, $peminjam, $department, $tanggalPinjam, $keterangan));
            $db->commit();
            flash('success', 'Peminjaman berhasil dicatat.');
        } catch (PDOException $e) {
            $db->rollBack();
            flash('error', 'Gagal mencatat peminjaman. Silakan coba lagi.');
        }
        redirect('sparepart_keluar.php');
    }

    public static function kembali() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('sparepart_keluar.php');
        }

        if (!verifyCsrf(_get($_POST, 'csrf_token', ''))) {
            flash('error', 'Token CSRF tidak valid.');
            redirect('sparepart_keluar.php');
        }

        $peminjamanId = (int)_get($_POST, 'peminjaman_id', 0);
        $kondisiKembali = trim(_get($_POST, 'kondisi_kembali', ''));
        $keterangan = trim(_get($_POST, 'keterangan', ''));
        $tanggal = date('Y-m-d');

        if (!$peminjamanId || !$kondisiKembali) {
            flash('error', 'Data tidak lengkap.');
            redirect('sparepart_keluar.php');
        }

        $validKondisi = array('Baik', 'Rusak Ringan', 'Rusak Berat', 'Hilang');
        if (!in_array($kondisiKembali, $validKondisi)) {
            flash('error', 'Kondisi kembali tidak valid.');
            redirect('sparepart_keluar.php');
        }

        $db = getDB();
        $user = $_SESSION['user'];
        $stmt = $db->prepare("SELECT p.*, s.jenis_sparepart, s.merk, s.serial_number FROM peminjaman p JOIN spareparts s ON s.id = p.sparepart_id WHERE p.id = ? AND p.deleted_at IS NULL");
        $stmt->execute(array($peminjamanId));
        $peminjaman = $stmt->fetch();

        if (!$peminjaman) {
            flash('error', 'Peminjaman tidak ditemukan.');
            redirect('sparepart_keluar.php');
        }
        if (!in_array($peminjaman['status'], array('Dipinjam', 'Telat'))) {
            flash('error', 'Peminjaman ini sudah dikembalikan.');
            redirect('sparepart_keluar.php');
        }
        if (!isAdmin() && $peminjaman['user_id'] != $user['id']) {
            flash('error', 'Anda hanya dapat memproses pengembalian milik sendiri.');
            redirect('sparepart_keluar.php');
        }

        $isLate = $peminjaman['tanggal_rencana_kembali'] && $tanggal > $peminjaman['tanggal_rencana_kembali'];
        $newStatus = $isLate ? 'Telat' : 'Dikembalikan';

        $db->beginTransaction();
        try {
            $db->prepare("UPDATE peminjaman SET tanggal_kembali = ?, kondisi_kembali = ?, status = ?, keterangan = COALESCE(NULLIF(?, ''), keterangan) WHERE id = ?")
                ->execute(array($tanggal, $kondisiKembali, $newStatus, $keterangan, $peminjamanId));
            $db->prepare("UPDATE spareparts SET status = 'Tersedia' WHERE id = ?")
                ->execute(array($peminjaman['sparepart_id']));
            $stmtLog = $db->prepare("INSERT INTO logbooks (sparepart_id, user_id, image, tipe_transaksi, status_lama, status_baru, pic_penerima, department, tanggal, keterangan_log) VALUES (?, ?, NULL, 'Dikembalikan', 'Terpakai', 'Tersedia', ?, ?, ?, ?)");
            $stmtLog->execute(array($peminjaman['sparepart_id'], $user['id'], $peminjaman['peminjam'], $peminjaman['department'], $tanggal, $keterangan));
            $db->commit();
            $msg = 'Pengembalian berhasil dicatat.';
            if ($isLate) $msg .= ' Peminjaman ini terlambat dikembalikan.';
            flash('success', $msg);
        } catch (PDOException $e) {
            $db->rollBack();
            flash('error', 'Gagal memproses pengembalian. Silakan coba lagi.');
        }
        redirect('sparepart_keluar.php');
    }
}
