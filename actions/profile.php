<?php

$page_title = 'Profile';
$require_admin = false;
require_once __DIR__ . '/../includes/auth_check.php';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('profile.php');
}

if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
    flash('error', 'Token CSRF tidak valid.');
    redirect('profile.php');
}

$action = $_POST['action'] ?? '';

if ($action === 'update_profile') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    if ($name && $email) {
        try {
            $stmt = $db->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
            $stmt->execute([$name, $email, $user['id']]);
            $_SESSION['user']['name'] = $name;
            $_SESSION['user']['email'] = $email;
            flash('success', 'Profile berhasil diupdate.');
        } catch (PDOException $e) {
            flash('error', 'Email sudah digunakan.');
        }
    }
} elseif ($action === 'update_password') {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['new_password_confirmation'] ?? '';

    $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$user['id']]);
    $data = $stmt->fetch();

    if (!password_verify($current, $data['password'])) {
        flash('error', 'Password saat ini salah.');
    } elseif (strlen($new) < 6) {
        flash('error', 'Password baru minimal 6 karakter.');
    } elseif ($new !== $confirm) {
        flash('error', 'Konfirmasi password tidak cocok.');
    } else {
        $hashed = password_hash($new, PASSWORD_DEFAULT);
        $db->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hashed, $user['id']]);
        flash('success', 'Password berhasil diubah.');
    }
} elseif ($action === 'delete_account') {
    $password = $_POST['password'] ?? '';
    $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$user['id']]);
    $data = $stmt->fetch();

    if (password_verify($password, $data['password'])) {
        $db->prepare("DELETE FROM users WHERE id = ?")->execute([$user['id']]);
        session_destroy();
        redirect('login.php');
    } else {
        flash('error', 'Password salah.');
    }
}

redirect('profile.php');
