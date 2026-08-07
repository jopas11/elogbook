<?php
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../config/database.php';

class SparepartKeluarController {
    private static function uploadImage() {
        if (empty($_FILES['images']) || !is_array($_FILES['images']['name'])) {
            return null;
        }

        $uploadResult = uploadMultipleImages($_FILES['images'], 5);
        if ($uploadResult === '__FLASH_SET__') {
            return '__FLASH_SET__';
        }
        return encodeImages($uploadResult);
    }

    private static function findNonAset($db, $jenis, $type, $merk, $status) {
        $stmt = $db->prepare("SELECT * FROM spareparts WHERE kategori = 'Non-Aset' AND jenis_sparepart = ? AND type_sparepart = ? AND merk = ? AND status = ? AND deleted_at IS NULL LIMIT 1");
        $stmt->execute(array($jenis, $type, $merk, $status));
        return $stmt->fetch();
    }

    private static function copyNonAsetRow($base, $status, $qty, $imagePath = null) {
        return array(
            $base['user_id'], $base['kategori'], $base['jenis_penggunaan'], $base['lokasi_penyimpanan'],
            $base['jenis_sparepart'], $base['type_sparepart'], $qty, $base['tanggal'], $base['merk'],
            $base['pic'], $base['department'], $status, $base['keterangan'], $imagePath ?: $base['image']
        );
    }

    private static function insertNonAsetRow($db, $data) {
        $cols = 'user_id, kategori, jenis_penggunaan, lokasi_penyimpanan, minimum_stok, jenis_sparepart, type_sparepart, serial_number, quantity, tanggal, merk, pic, department, status, keterangan, image';
        $ins = $db->prepare("INSERT INTO spareparts ($cols) VALUES (?, ?, ?, ?, 1, ?, ?, NULL, ?, ?, ?, ?, ?, ?, ?, ?)");
        $ins->execute($data);
        return $db->lastInsertId();
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

        $kategori = 'Non-Aset';
        $jenis_penggunaan = _get($_POST, 'jenis_penggunaan', '') ?: null;
        $lokasi_penyimpanan = trim(_get($_POST, 'lokasi_penyimpanan', '')) ?: null;
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

        $quantity = max(1, (int)_get($_POST, 'quantity', 1));

        $tipeTransaksi = 'Barang Masuk';
        if ($status_baru === 'Terpakai') {
            $tipeTransaksi = 'Barang Keluar';
        } elseif ($status_baru === 'Dalam Perbaikan') {
            $tipeTransaksi = 'Dalam Perbaikan';
        } elseif ($status_lama !== $status_baru) {
            $tipeTransaksi = 'Ubah Status';
        }

        $db = getDB();

        $tersediaRow = self::findNonAset($db, $jenis_sparepart, $type_sparepart, $merk, 'Tersedia');
        $terpakaiRow = self::findNonAset($db, $jenis_sparepart, $type_sparepart, $merk, 'Terpakai');
        $hasExisting = $tersediaRow || $terpakaiRow;

        $imageVal = null;
        if (!empty($_FILES['images']) && is_array($_FILES['images']['name'])) {
            $uploadResult = uploadMultipleImages($_FILES['images'], 5);
            if ($uploadResult === '__FLASH_SET__') {
                redirect('sparepart_keluar.php');
            }
            $imageVal = encodeImages($uploadResult);
        }

        if (!$hasExisting && !$imageVal) {
            flash('error', 'Foto barang wajib diupload untuk item baru (format JPG/PNG/WebP, maks 2MB per foto).');
            redirect('sparepart_keluar.php');
        }

        if (!isAdmin() && $status_lama !== $status_baru && !$imageVal) {
            flash('error', 'Foto barang wajib diupload untuk perubahan status (kondisi barang saat ini).');
            redirect('sparepart_keluar.php');
        }

        if (!isAdmin() && $status_lama !== $status_baru) {
            $approvalSparepartId = null;
            if ($status_lama === 'Tersedia' && $tersediaRow) {
                $approvalSparepartId = $tersediaRow['id'];
            } elseif ($status_lama === 'Terpakai' && $terpakaiRow) {
                $approvalSparepartId = $terpakaiRow['id'];
            } else {
                $statusLamaRow = self::findNonAset($db, $jenis_sparepart, $type_sparepart, $merk, $status_lama);
                $approvalSparepartId = $statusLamaRow ? $statusLamaRow['id'] : null;
            }

            if (!$approvalSparepartId) {
                flash('error', 'Barang dengan status "' . $status_lama . '" tidak ditemukan.');
                redirect('sparepart_keluar.php');
            }

            $checkPending = $db->prepare("SELECT id FROM status_approvals WHERE sparepart_id = ? AND status = 'pending' AND deleted_at IS NULL LIMIT 1");
            $checkPending->execute(array($approvalSparepartId));
            if ($checkPending->fetch()) {
                flash('error', 'Barang ini sedang dalam proses approval. Tidak dapat mengubah status sampai disetujui/ditolak.');
                redirect('sparepart_keluar.php');
            }

            $db->prepare("INSERT INTO status_approvals (sparepart_id, user_id, tipe_transaksi, kategori, type_sparepart, status_lama, status_baru, quantity, pic, department, tanggal, keterangan, image, status) VALUES (?, ?, ?, 'Non-Aset', ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')")
                ->execute(array($approvalSparepartId, $user['id'], $tipeTransaksi, $type_sparepart, $status_lama, $status_baru, $quantity, $pic, $department, $tanggal, $keterangan, $imageVal));
            flash('success', 'Permintaan perubahan status telah dikirim. Menunggu persetujuan admin.');
            redirect('sparepart_keluar.php');
        }

        $db->beginTransaction();
        try {
            $sparepartId = null;

            if ($hasExisting) {
                $baseRow = $tersediaRow ?: $terpakaiRow;
                $tersediaQty = $tersediaRow ? (int)$tersediaRow['quantity'] : 0;
                $terpakaiQty = $terpakaiRow ? (int)$terpakaiRow['quantity'] : 0;

                if ($tipeTransaksi === 'Barang Keluar') {
                    if ($tersediaQty < $quantity) {
                        throw new PDOException('Stok tidak mencukupi. Tersedia: ' . $tersediaQty . '.');
                    }
                    $db->prepare("UPDATE spareparts SET quantity = ? WHERE id = ?")
                        ->execute(array($tersediaQty - $quantity, $tersediaRow['id']));
                    $sparepartId = $tersediaRow['id'];

                    if ($terpakaiRow) {
                        $db->prepare("UPDATE spareparts SET quantity = ? WHERE id = ?")
                            ->execute(array($terpakaiQty + $quantity, $terpakaiRow['id']));
                    } else {
                        $sparepartId = self::insertNonAsetRow($db, self::copyNonAsetRow($baseRow, 'Terpakai', $quantity, $imageVal));
                    }

                } elseif ($status_lama === 'Terpakai' && $status_baru === 'Tersedia') {
                    if ($terpakaiQty < $quantity) {
                        throw new PDOException('Jumlah kembali melebihi stok terpakai. Terpakai: ' . $terpakaiQty . '.');
                    }
                    if ($tersediaRow) {
                        $db->prepare("UPDATE spareparts SET quantity = ? WHERE id = ?")
                            ->execute(array($tersediaQty + $quantity, $tersediaRow['id']));
                        $sparepartId = $tersediaRow['id'];
                    } else {
                        $sparepartId = self::insertNonAsetRow($db, self::copyNonAsetRow($baseRow, 'Tersedia', $quantity, $imageVal));
                    }

                    $newTerpakai = $terpakaiQty - $quantity;
                    if ($newTerpakai > 0) {
                        $db->prepare("UPDATE spareparts SET quantity = ? WHERE id = ?")
                            ->execute(array($newTerpakai, $terpakaiRow['id']));
                    } else {
                        $db->prepare("DELETE FROM spareparts WHERE id = ?")
                            ->execute(array($terpakaiRow['id']));
                    }

                } else {
                    if ($tersediaRow) {
                        $db->prepare("UPDATE spareparts SET quantity = ?, pic = ?, department = ?, keterangan = ?, tanggal = ? WHERE id = ?")
                            ->execute(array($tersediaQty + $quantity, $pic, $department, $keterangan, $tanggal, $tersediaRow['id']));
                        $sparepartId = $tersediaRow['id'];
                    } else {
                        $sparepartId = self::insertNonAsetRow($db, self::copyNonAsetRow($baseRow, 'Tersedia', $quantity, $imageVal));
                    }
                }

            } else {
                $sparepartId = self::insertNonAsetRow($db, array($user['id'], $kategori, $jenis_penggunaan, $lokasi_penyimpanan, $jenis_sparepart, $type_sparepart, $quantity, $tanggal, $merk, $pic, $department, $status_baru, $keterangan, $imageVal));
            }

            $deltaLabel = ($tipeTransaksi === 'Barang Keluar' ? '-' : '+') . $quantity;
            $logKeterangan = trim($keterangan . ' [' . $deltaLabel . ']');

            $stmtLog = $db->prepare("INSERT INTO logbooks (sparepart_id, user_id, image, tipe_transaksi, status_lama, status_baru, pic_penerima, department, tanggal, keterangan_log) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmtLog->execute(array($sparepartId, $user['id'], $imageVal, $tipeTransaksi, $status_lama, $status_baru, $pic, $department, $tanggal, $logKeterangan));

            $db->commit();
            flash('success', $quantity . ' sparepart berhasil dicatat sebagai ' . $tipeTransaksi . '.');
        } catch (PDOException $e) {
            $db->rollBack();
            flash('error', $e->getMessage() ?: 'Gagal menyimpan data. Silakan coba lagi.');
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
        $imageVal = self::uploadImage();

        if (!$imageVal || $imageVal === '__FLASH_SET__') {
            if ($imageVal !== '__FLASH_SET__') {
                flash('error', 'Foto barang wajib diupload (format JPG/PNG/WebP, maks 2MB per foto).');
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

        $status_lama = $sparepart['status'];

        $tipeTransaksi = 'Barang Masuk';
        if ($status_baru === 'Terpakai') {
            $tipeTransaksi = 'Barang Keluar';
        } elseif ($status_baru === 'Dalam Perbaikan') {
            $tipeTransaksi = 'Dalam Perbaikan';
        } elseif ($status_lama !== $status_baru) {
            $tipeTransaksi = 'Ubah Status';
        }

        if (!isAdmin() && $status_lama !== $status_baru) {
            $checkPending = $db->prepare("SELECT id FROM status_approvals WHERE sparepart_id = ? AND status = 'pending' AND deleted_at IS NULL LIMIT 1");
            $checkPending->execute(array($sparepartId));
            if ($checkPending->fetch()) {
                flash('error', 'Barang ini sedang dalam proses approval. Tidak dapat mengubah status sampai disetujui/ditolak.');
                redirect('sparepart_keluar.php');
            }

            $db->prepare("INSERT INTO status_approvals (sparepart_id, user_id, tipe_transaksi, kategori, type_sparepart, status_lama, status_baru, quantity, pic, department, tanggal, keterangan, image, status) VALUES (?, ?, ?, 'Aset', ?, ?, ?, 1, ?, ?, ?, ?, ?, 'pending')")
                ->execute(array($sparepartId, $user['id'], $tipeTransaksi, $sparepart['type_sparepart'], $status_lama, $status_baru, $pic, $department, $tanggal, $keterangan, $imageVal));
            flash('success', 'Permintaan perubahan status telah dikirim. Menunggu persetujuan admin.');
            redirect('sparepart_keluar.php');
        }

        $db->beginTransaction();
        try {
            $db->prepare("UPDATE spareparts SET status = ?, pic = ?, department = ?, keterangan = ? WHERE id = ?")
                ->execute(array($status_baru, $pic, $department, $keterangan, $sparepartId));

            $stmtLog = $db->prepare("INSERT INTO logbooks (sparepart_id, user_id, image, tipe_transaksi, status_lama, status_baru, pic_penerima, department, tanggal, keterangan_log) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmtLog->execute(array($sparepartId, $user['id'], $imageVal, $tipeTransaksi, $status_lama, $status_baru, $pic, $department, $tanggal, $keterangan));

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
