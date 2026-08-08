<?php

function loadEnv($path) {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) continue;
        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) continue;
        $key = trim($parts[0]);
        $val = trim($parts[1]);
        $val = trim($val, '"\'');
        $_ENV[$key] = $val;
        putenv("$key=$val");
    }
}

loadEnv(__DIR__ . '/../.env');

define('DB_HOST', isset($_ENV['DB_HOST']) ? $_ENV['DB_HOST'] : 'localhost');
define('DB_PORT', isset($_ENV['DB_PORT']) ? $_ENV['DB_PORT'] : '3306');
define('DB_NAME', isset($_ENV['DB_NAME']) ? $_ENV['DB_NAME'] : '');
define('DB_USER', isset($_ENV['DB_USER']) ? $_ENV['DB_USER'] : 'root');
define('DB_PASS', isset($_ENV['DB_PASS']) ? $_ENV['DB_PASS'] : '');
define('APP_URL', isset($_ENV['APP_URL']) ? $_ENV['APP_URL'] : 'http://localhost');
$scriptPath = isset($_SERVER['SCRIPT_NAME']) ? str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])) : '';
if ($scriptPath === '/' || $scriptPath === '') {
    $requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    if (preg_match('#^/elogbook(/|$)#', $requestUri, $m)) {
        $scriptPath = '/elogbook';
    }
}
define('APP_BASE_PATH', ($scriptPath === '/' || $scriptPath === '') ? '' : $scriptPath);
define('APP_NAME', isset($_ENV['APP_NAME']) ? $_ENV['APP_NAME'] : 'App');

session_start();
date_default_timezone_set('Asia/Jakarta');
