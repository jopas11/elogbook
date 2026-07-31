<?php
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../config/database.php';

class JenisSparepartController {
    public static function createJenis() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('jenis_sparepart.php');
        }

        if (!isAdmin()) {
            flash('error', 'Akses ditolak. Hanya admin yang dapat mengelola jenis sparepart.');
            redirect('jenis_sparepart.php');
        }

        if (!verifyCsrf(_get($_POST, 'csrf_token', ''))) {
            flash('error', 'Token CSRF tidak valid.');
            redirect('jenis_sparepart.php');
        }

        $nama = trim(_get($_POST, 'nama', ''));
        $kategori = _get($_POST, 'kategori', '');

        if ($nama && $kategori) {
            $db = getDB();
            $stmt = $db->prepare("INSERT INTO jenis_spareparts (nama, kategori) VALUES (?, ?)");
            $stmt->execute([$nama, $kategori]);
            flash('success', 'Jenis sparepart ditambahkan.');
        }

        redirect('jenis_sparepart.php');
    }

    public static function updateJenis() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('jenis_sparepart.php');
        }

        if (!isAdmin()) {
            flash('error', 'Akses ditolak. Hanya admin yang dapat mengelola jenis sparepart.');
            redirect('jenis_sparepart.php');
        }

        if (!verifyCsrf(_get($_POST, 'csrf_token', ''))) {
            flash('error', 'Token CSRF tidak valid.');
            redirect('jenis_sparepart.php');
        }

        $id = (int)_get($_POST, 'id', 0);
        $nama = trim(_get($_POST, 'nama', ''));
        $kategori = _get($_POST, 'kategori', '');

        if ($id && $nama) {
            $db = getDB();
            $stmt = $db->prepare("UPDATE jenis_spareparts SET nama = ?, kategori = ? WHERE id = ? AND type IS NULL");
            $stmt->execute([$nama, $kategori, $id]);
            flash('success', 'Jenis sparepart diupdate.');
        }

        redirect('jenis_sparepart.php');
    }

    public static function deleteJenis() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('jenis_sparepart.php');
        }

        if (!isAdmin()) {
            flash('error', 'Akses ditolak. Hanya admin yang dapat mengelola jenis sparepart.');
            redirect('jenis_sparepart.php');
        }

        if (!verifyCsrf(_get($_POST, 'csrf_token', ''))) {
            flash('error', 'Token CSRF tidak valid.');
            redirect('jenis_sparepart.php');
        }

        $id = (int)_get($_POST, 'id', 0);

        if ($id) {
            $db = getDB();
            $stmt = $db->prepare("DELETE FROM jenis_spareparts WHERE id = ? AND type IS NULL");
            $stmt->execute([$id]);
            flash('success', 'Jenis sparepart dihapus.');
        }

        redirect('jenis_sparepart.php');
    }

    public static function createType() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('jenis_sparepart.php');
        }

        if (!isAdmin()) {
            flash('error', 'Akses ditolak. Hanya admin yang dapat mengelola jenis sparepart.');
            redirect('jenis_sparepart.php');
        }

        if (!verifyCsrf(_get($_POST, 'csrf_token', ''))) {
            flash('error', 'Token CSRF tidak valid.');
            redirect('jenis_sparepart.php');
        }

        $nama = trim(_get($_POST, 'nama', ''));
        $type = trim(_get($_POST, 'type', ''));

        if ($nama && $type) {
            $db = getDB();
            $stmtK = $db->prepare("SELECT kategori FROM jenis_spareparts WHERE nama = ? AND type IS NULL LIMIT 1");
            $stmtK->execute([$nama]);
            $jenisRow = $stmtK->fetch();
            $kategori = $jenisRow ? $jenisRow['kategori'] : 'Aset';
            $stmt = $db->prepare("INSERT INTO jenis_spareparts (nama, kategori, type) VALUES (?, ?, ?)");
            $stmt->execute([$nama, $kategori, $type]);
            flash('success', 'Type sparepart ditambahkan.');
        }

        redirect('jenis_sparepart.php');
    }

    public static function updateType() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('jenis_sparepart.php');
        }

        if (!isAdmin()) {
            flash('error', 'Akses ditolak. Hanya admin yang dapat mengelola jenis sparepart.');
            redirect('jenis_sparepart.php');
        }

        if (!verifyCsrf(_get($_POST, 'csrf_token', ''))) {
            flash('error', 'Token CSRF tidak valid.');
            redirect('jenis_sparepart.php');
        }

        $id = (int)_get($_POST, 'id', 0);
        $nama = trim(_get($_POST, 'nama', ''));
        $type = trim(_get($_POST, 'type', ''));

        if ($id && $nama && $type) {
            $db = getDB();
            $stmtK = $db->prepare("SELECT kategori FROM jenis_spareparts WHERE nama = ? AND type IS NULL LIMIT 1");
            $stmtK->execute([$nama]);
            $jenisRow = $stmtK->fetch();
            $kategori = $jenisRow ? $jenisRow['kategori'] : 'Aset';
            $stmt = $db->prepare("UPDATE jenis_spareparts SET nama = ?, type = ?, kategori = ? WHERE id = ? AND type IS NOT NULL");
            $stmt->execute([$nama, $type, $kategori, $id]);
            flash('success', 'Type sparepart diupdate.');
        }

        redirect('jenis_sparepart.php');
    }

    public static function deleteType() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('jenis_sparepart.php');
        }

        if (!isAdmin()) {
            flash('error', 'Akses ditolak. Hanya admin yang dapat mengelola jenis sparepart.');
            redirect('jenis_sparepart.php');
        }

        if (!verifyCsrf(_get($_POST, 'csrf_token', ''))) {
            flash('error', 'Token CSRF tidak valid.');
            redirect('jenis_sparepart.php');
        }

        $id = (int)_get($_POST, 'id', 0);

        if ($id) {
            $db = getDB();
            $stmt = $db->prepare("DELETE FROM jenis_spareparts WHERE id = ? AND type IS NOT NULL");
            $stmt->execute([$id]);
            flash('success', 'Type sparepart dihapus.');
        }

        redirect('jenis_sparepart.php');
    }

    public static function createMerk() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('jenis_sparepart.php');
        }

        if (!isAdmin()) {
            flash('error', 'Akses ditolak. Hanya admin yang dapat mengelola merk.');
            redirect('jenis_sparepart.php');
        }

        if (!verifyCsrf(_get($_POST, 'csrf_token', ''))) {
            flash('error', 'Token CSRF tidak valid.');
            redirect('jenis_sparepart.php');
        }

        $kategori = _get($_POST, 'kategori', '');
        $jenis = trim(_get($_POST, 'jenis_sparepart', ''));
        $type = trim(_get($_POST, 'type_sparepart', '')) ?: null;
        $merk = trim(_get($_POST, 'merk', ''));

        if ($kategori && $jenis && $merk) {
            $db = getDB();
            $stmt = $db->prepare("INSERT INTO sparepart_merks (kategori, jenis_sparepart, type_sparepart, merk) VALUES (?, ?, ?, ?)");
            $stmt->execute([$kategori, $jenis, $type, $merk]);
            flash('success', 'Merk sparepart ditambahkan.');
        }

        redirect('jenis_sparepart.php');
    }

    public static function updateMerk() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('jenis_sparepart.php');
        }

        if (!isAdmin()) {
            flash('error', 'Akses ditolak. Hanya admin yang dapat mengelola merk.');
            redirect('jenis_sparepart.php');
        }

        if (!verifyCsrf(_get($_POST, 'csrf_token', ''))) {
            flash('error', 'Token CSRF tidak valid.');
            redirect('jenis_sparepart.php');
        }

        $id = (int)_get($_POST, 'id', 0);
        $merk = trim(_get($_POST, 'merk', ''));

        if ($id && $merk) {
            $db = getDB();
            $stmt = $db->prepare("UPDATE sparepart_merks SET merk = ? WHERE id = ?");
            $stmt->execute([$merk, $id]);
            flash('success', 'Merk sparepart diupdate.');
        }

        redirect('jenis_sparepart.php');
    }

    public static function deleteMerk() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('jenis_sparepart.php');
        }

        if (!isAdmin()) {
            flash('error', 'Akses ditolak. Hanya admin yang dapat mengelola merk.');
            redirect('jenis_sparepart.php');
        }

        if (!verifyCsrf(_get($_POST, 'csrf_token', ''))) {
            flash('error', 'Token CSRF tidak valid.');
            redirect('jenis_sparepart.php');
        }

        $id = (int)_get($_POST, 'id', 0);

        if ($id) {
            $db = getDB();
            $stmt = $db->prepare("DELETE FROM sparepart_merks WHERE id = ?");
            $stmt->execute([$id]);
            flash('success', 'Merk sparepart dihapus.');
        }

        redirect('jenis_sparepart.php');
    }
}
