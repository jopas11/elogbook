<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';

$url = $_GET['url'] ?? '';
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
$paths = [
    __DIR__ . '/pages/' . $page,
    __DIR__ . '/actions/' . $page,
];

foreach ($paths as $pagePath) {
    if (file_exists($pagePath)) {
        require $pagePath;
        return;
    }
}

http_response_code(404);
require __DIR__ . '/pages/404.php';
