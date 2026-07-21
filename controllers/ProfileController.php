<?php
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../config/database.php';

class ProfileController {
    public static function updatePassword() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('profile.php');
        }

        if (!verifyCsrf(_get($_POST, 'csrf_token', ''))) {
            flash('error', 'Token CSRF tidak valid.');
            redirect('profile.php');
        }

        $current = _get($_POST, 'current_password', '');
        $new = _get($_POST, 'new_password', '');
        $confirm = _get($_POST, 'new_password_confirmation', '');

        $db = getDB();
        $user = $_SESSION['user'];
        $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute(array($user['id']));
        $data = $stmt->fetch();

        if (!verifyPassword($current, $data['password'])) {
            flash('error', 'Password saat ini salah.');
        } elseif (strlen($new) < 6) {
            flash('error', 'Password baru minimal 6 karakter.');
        } elseif ($new !== $confirm) {
            flash('error', 'Konfirmasi password tidak cocok.');
        } else {
            $hashed = hashPassword($new);
            $db->prepare("UPDATE users SET password = ? WHERE id = ?")->execute(array($hashed, $user['id']));
            flash('success', 'Password berhasil diubah.');
        }

        redirect('profile.php');
    }

    public static function deleteAccount() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('profile.php');
        }

        if (!verifyCsrf(_get($_POST, 'csrf_token', ''))) {
            flash('error', 'Token CSRF tidak valid.');
            redirect('profile.php');
        }

        $password = _get($_POST, 'password', '');
        $db = getDB();
        $user = $_SESSION['user'];
        $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute(array($user['id']));
        $data = $stmt->fetch();

        if (verifyPassword($password, $data['password'])) {
            $db->prepare("DELETE FROM users WHERE id = ?")->execute(array($user['id']));
            session_destroy();
            redirect('login.php');
        } else {
            flash('error', 'Password salah.');
        }

        redirect('profile.php');
    }
}
