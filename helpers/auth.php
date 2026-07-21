<?php

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user'])) {
    flash('error', 'Silakan login terlebih dahulu.');
    redirect('login.php');
}

$user = $_SESSION['user'];

if (isset($require_admin) && $require_admin && !isAdmin()) {
    flash('error', 'Akses ditolak. Hanya untuk admin.');
    redirect('dashboard.php');
}
