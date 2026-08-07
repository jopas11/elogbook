<?php
/**
 * Image Proxy — serve images from public/uploads/ via /public/uploads/ URL.
 * This allows the app to use APP_URL/public/uploads/... for all image requests.
 */

$path = isset($_GET['path']) ? $_GET['path'] : '';
$path = preg_replace('/[^a-zA-Z0-9_\-\.\/]/', '', $path);

if ($path === '' || strpos($path, '..') !== false) {
    http_response_code(400);
    exit;
}

$baseDir = __DIR__ . '/public/uploads/';
$file = $baseDir . $path;

if (!file_exists($file) || !is_file($file)) {
    http_response_code(404);
    exit;
}

$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
$mimeTypes = [
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
    'gif'  => 'image/gif',
    'webp' => 'image/webp',
    'svg'  => 'image/svg+xml',
    'ico'  => 'image/x-icon',
    'bmp'  => 'image/bmp',
];

if (!isset($mimeTypes[$ext])) {
    http_response_code(403);
    exit;
}

header('Content-Type: ' . $mimeTypes[$ext]);
header('Cache-Control: public, max-age=31536000, immutable');
header('Content-Length: ' . filesize($file));
readfile($file);
