<?php
$_GET = ['route' => 'dashboard'];
$_POST = [];
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['PHP_SELF'] = '/elogbook/index.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_USER_AGENT'] = 'test';

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/helpers/functions.php';

$route = 'dashboard';

$routeMap = [
    'dashboard'        => 'dashboard/index.php',
    'master_barang'    => 'barang/master.php',
    'jenis_sparepart'  => 'barang/jenis.php',
    'sparepart_keluar' => 'transaksi/keluar.php',
    'history'          => 'transaksi/history.php',
    'ubah_status'      => 'transaksi/ubah_status.php',
    'audit_logs'       => 'laporan/audit.php',
    'login'            => 'auth/login.php',
    'profile'          => 'auth/profile.php',
    'users'            => 'auth/users.php',
    'export_csv'       => 'laporan/export_csv.php',
    'export_pdf'       => 'laporan/export_pdf.php',
];

echo "Testing route: $route\n";

if (isset($routeMap[$route]) && $routeMap[$route] !== null) {
    $view = __DIR__ . '/views/' . $routeMap[$route];
    echo "View path: $view\n";
    echo "File exists: " . (file_exists($view) ? 'YES' : 'NO') . "\n";
}

echo "\nAll route map checks:\n";
foreach ($routeMap as $r => $v) {
    $path = __DIR__ . '/views/' . $v;
    echo "  $r -> views/$v : " . (file_exists($path) ? 'OK' : 'MISSING') . "\n";
}

echo "\nInclude path test:\n";
echo "  config/app.php: " . (file_exists(__DIR__ . '/config/app.php') ? 'OK' : 'MISSING') . "\n";
echo "  helpers/functions.php: " . (file_exists(__DIR__ . '/helpers/functions.php') ? 'OK' : 'MISSING') . "\n";
echo "  helpers/auth.php: " . (file_exists(__DIR__ . '/helpers/auth.php') ? 'OK' : 'MISSING') . "\n";
echo "  config/database.php: " . (file_exists(__DIR__ . '/config/database.php') ? 'OK' : 'MISSING') . "\n";
