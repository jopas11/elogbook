<?php
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../config/database.php';

class UsersController {
    public static function create() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('users.php');
        }

        if (!isAdmin()) {
            flash('error', 'Akses ditolak. Hanya admin yang dapat mengelola user.');
            redirect('users.php');
        }

        if (!verifyCsrf(_get($_POST, 'csrf_token', ''))) {
            flash('error', 'Token CSRF tidak valid.');
            redirect('users.php');
        }

        $name = trim(_get($_POST, 'name', ''));
        $email = trim(_get($_POST, 'email', ''));
        $password = _get($_POST, 'password', '');
        $role = _get($_POST, 'role', 'user');

        $validRoles = array('admin', 'user');
        if (!in_array($role, $validRoles)) {
            $role = 'user';
        }

        if ($name && $email && $password) {
            $hashed = hashPassword($password);
            try {
                $db = getDB();
                $stmt = $db->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
                $stmt->execute(array($name, $email, $hashed, $role));
                flash('success', 'User berhasil ditambahkan.');
            } catch (PDOException $e) {
                flash('error', 'Email sudah terdaftar.');
            }
        }

        redirect('users.php');
    }

    public static function update() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('users.php');
        }

        if (!isAdmin()) {
            flash('error', 'Akses ditolak. Hanya admin yang dapat mengelola user.');
            redirect('users.php');
        }

        if (!verifyCsrf(_get($_POST, 'csrf_token', ''))) {
            flash('error', 'Token CSRF tidak valid.');
            redirect('users.php');
        }

        $id = (int)_get($_POST, 'id', 0);
        $name = trim(_get($_POST, 'name', ''));
        $email = trim(_get($_POST, 'email', ''));
        $password = _get($_POST, 'password', '');
        $role = _get($_POST, 'role', 'user');

        $validRoles = array('admin', 'user');
        if (!in_array($role, $validRoles)) {
            $role = 'user';
        }

        $db = getDB();
        $user = $_SESSION['user'];

        $stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute(array($id));
        $target = $stmt->fetch();

        if (!$target) {
            flash('error', 'User tidak ditemukan.');
            redirect('users.php');
        }

        if ($target['role'] === 'admin' && $id !== (int)$user['id']) {
            flash('error', 'Tidak bisa mengubah data admin lain.');
            redirect('users.php');
        }

        if ($name && $email) {
            try {
                if ($password) {
                    $hashed = hashPassword($password);
                    $stmt = $db->prepare("UPDATE users SET name = ?, email = ?, role = ?, password = ? WHERE id = ?");
                    $stmt->execute(array($name, $email, $role, $hashed, $id));
                } else {
                    $stmt = $db->prepare("UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?");
                    $stmt->execute(array($name, $email, $role, $id));
                }
                flash('success', 'User berhasil diupdate.');
            } catch (PDOException $e) {
                flash('error', 'Email sudah terdaftar.');
            }
        }

        redirect('users.php');
    }

    public static function delete() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('users.php');
        }

        if (!isAdmin()) {
            flash('error', 'Akses ditolak. Hanya admin yang dapat mengelola user.');
            redirect('users.php');
        }

        if (!verifyCsrf(_get($_POST, 'csrf_token', ''))) {
            flash('error', 'Token CSRF tidak valid.');
            redirect('users.php');
        }

        $id = (int)_get($_POST, 'id', 0);
        $db = getDB();
        $user = $_SESSION['user'];

        if ($id === (int)$user['id']) {
            flash('error', 'Tidak bisa menghapus akun sendiri.');
        } else {
            $stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
            $stmt->execute(array($id));
            $target = $stmt->fetch();
            if ($target && $target['role'] === 'admin') {
                flash('error', 'Tidak bisa menghapus admin lain.');
            } else {
                $db->prepare("DELETE FROM users WHERE id = ?")->execute(array($id));
                flash('success', 'User berhasil dihapus.');
            }
        }

        redirect('users.php');
    }
}
