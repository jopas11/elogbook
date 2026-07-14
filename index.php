<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';

$url = _get($_GET, 'url', '');
$url = rtrim($url, '/');
$url = filter_var($url, FILTER_SANITIZE_URL);
$url = preg_replace('/\.php$/i', '', $url); // Strip .php extension if present

if (empty($url) || $url === 'index.php') {
    if (isset($_SESSION['user'])) {
        redirect('dashboard.php');
    }
    redirect('login.php');
}

$page = $url . '.php';

// POST requests → handle via actions/ first
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $actionPath = __DIR__ . '/actions/' . $page;
    if (file_exists($actionPath)) {
        require $actionPath;
        return;
    }
}

// GET requests (or POST without action file) → load from pages/
$pagePath = __DIR__ . '/pages/' . $page;
if (file_exists($pagePath)) {
    require $pagePath;
    return;
}

// Fallback to actions/ if not in pages/
$actionPath = __DIR__ . '/actions/' . $page;
if (file_exists($actionPath)) {
    require $actionPath;
    return;
}

http_response_code(404);
require __DIR__ . '/pages/404.php';
