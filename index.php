<?php

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/helpers/functions.php';

$route = _get($_GET, 'route', '');
if (empty($route) && isset($_GET['url'])) {
    $route = rtrim($_GET['url'], '/');
}
$route = rtrim($route, '/');
$route = filter_var($route, FILTER_SANITIZE_URL);
$route = preg_replace('/\.php$/i', '', $route);

if (empty($route) || $route === 'index') {
    if (isset($_SESSION['user'])) {
        redirect('dashboard.php');
    }
    redirect('login.php');
}

$routeMap = [
    'dashboard'        => 'dashboard/index.php',
    'master_barang'    => 'barang/master.php',
    'jenis_sparepart'  => 'barang/jenis.php',
    'sparepart_keluar' => 'transaksi/keluar.php',
    'history'          => 'transaksi/history.php',
    'ubah_status'      => 'transaksi/ubah_status.php',
    'audit_logs'       => 'laporan/audit.php',
    'login'            => 'auth/login.php',
    'register'         => 'auth/register.php',
    'profile'          => 'auth/profile.php',
    'users'            => 'auth/users.php',
];

// Handle logout
if ($route === 'logout') {
    require_once __DIR__ . '/controllers/AuthController.php';
    AuthController::logout();
    exit;
}

// Handle export routes (they output files directly, not HTML views)
if ($route === 'export_csv' || $route === 'export_pdf') {
    require_once __DIR__ . '/controllers/ExportController.php';
    if ($route === 'export_csv') {
        ExportController::csv();
    } else {
        ExportController::pdf();
    }
    exit;
}

// Determine action from GET or POST
$action = _get($_GET, 'action', '');
if (empty($action)) {
    $action = _get($_POST, 'action', '');
}
if (empty($action)) {
    $action = _get($_POST, 'mode', '');
}

// Convert snake_case to camelCase
if ($action && strpos($action, '_') !== false) {
    $parts = explode('_', $action);
    $camel = array_shift($parts);
    if (!empty($parts)) {
        $camel .= implode('', array_map('ucfirst', $parts));
    }
    $action = $camel;
}

// Handle controller actions (JSON endpoints or POST handlers)
$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
$hasAction = !empty($action);

$controllerMap = [
    'sparepart'        => 'SparepartController',
    'sparepart_keluar' => 'SparepartKeluarController',
    'jenis_sparepart'  => 'JenisSparepartController',
    'ubah_status'      => 'UbahStatusController',
    'users'            => 'UsersController',
    'profile'          => 'ProfileController',
];

if (isset($controllerMap[$route]) && ($isPost || $hasAction)) {
    $class = $controllerMap[$route];
    $file = __DIR__ . '/controllers/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
        if ($hasAction && method_exists($class, $action)) {
            $class::$action();
            exit;
        }
        if ($isPost) {
            $defaults = [
                'SparepartKeluarController' => 'nonasetInsert',
                'JenisSparepartController'  => 'createJenis',
                'UbahStatusController'      => 'updateStatus',
                'UsersController'           => 'create',
                'ProfileController'         => 'updatePassword',
            ];
            if (isset($defaults[$class]) && method_exists($class, $defaults[$class])) {
                $class::{$defaults[$class]}();
                exit;
            }
        }
        // If GET with action but method doesn't exist, fall through to view
        if (!$isPost) {
            // Allow fall-through to view
        } else {
            exit;
        }
    }
}

// Serve views
if (isset($routeMap[$route]) && $routeMap[$route] !== null) {
    $view = __DIR__ . '/views/' . $routeMap[$route];
    if (file_exists($view)) {
        require $view;
        return;
    }
}

http_response_code(404);
require __DIR__ . '/views/auth/404.php';
