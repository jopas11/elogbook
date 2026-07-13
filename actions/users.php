<?php

$page_title = 'Kelola User';
$require_admin = true;
require_once __DIR__ . '/../includes/auth_check.php';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('users.php');
}

if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
    flash('error', 'Token CSRF tidak valid.');
    redirect('users.php');
}

$action = $_POST['action'] ?? '';

if ($action === 'create') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'user';

    if ($name && $email && $password) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        try {
            $stmt = $db->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $hashed, $role]);
            flash('success', 'User berhasil ditambahkan.');
        } catch (PDOException $e) {
            flash('error', 'Email sudah terdaftar.');
        }
    }
} elseif ($action === 'update') {
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'user';

    $stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $target = $stmt->fetch();

    if (!$target) {
        flash('error', 'User tidak ditemukan.');
        redirect('users.php');
    }

    if ($password && $target['role'] === 'admin' && $id != $user['id']) {
        flash('error', 'Tidak bisa mengubah password admin lain.');
        redirect('users.php');
    }

    if ($name && $email) {
        try {
            if ($password) {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users SET name = ?, email = ?, role = ?, password = ? WHERE id = ?");
                $stmt->execute([$name, $email, $role, $hashed, $id]);
            } else {
                $stmt = $db->prepare("UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?");
                $stmt->execute([$name, $email, $role, $id]);
            }
            flash('success', 'User berhasil diupdate.');
        } catch (PDOException $e) {
            flash('error', 'Email sudah terdaftar.');
        }
    }
} elseif ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id == $user['id']) {
        flash('error', 'Tidak bisa menghapus akun sendiri.');
    } else {
        $stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $target = $stmt->fetch();
        if ($target && $target['role'] === 'admin') {
            flash('error', 'Tidak bisa menghapus admin lain.');
        } else {
            $db->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
            flash('success', 'User berhasil dihapus.');
        }
    }
}

redirect('users.php');
