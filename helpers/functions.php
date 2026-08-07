<?php

// Polyfill array_column() untuk PHP < 5.5
if (!function_exists('array_column')) {
    function array_column(array $input, $columnKey, $indexKey = null) {
        $result = array();
        foreach ($input as $row) {
            if (!isset($row[$columnKey])) continue;
            $key = ($indexKey !== null && isset($row[$indexKey])) ? $row[$indexKey] : count($result);
            $result[$key] = $row[$columnKey];
        }
        return $result;
    }
}

function &_get(&$arr, $key, $default = null) {
    if (is_array($arr) && isset($arr[$key])) {
        return $arr[$key];
    }
    return $default;
}

function redirect($url) {
    if (strpos($url, 'http') !== 0 && strpos($url, 'index.php') !== 0 && strpos($url, '/') !== 0) {
        $page = str_replace('.php', '', $url);
        $url = 'index.php?route=' . $page;
    }
    header('Location: ' . $url);
    exit;
}

function pageUrl($file) {
    $page = str_replace('.php', '', $file);
    return 'index.php?route=' . $page;
}

function imageUrl($path) {
    $path = ltrim(trim($path), '/');
    if ($path === '') {
        return '';
    }
    return rtrim(APP_URL, '/') . '/' . $path;
}

function old($key, $default = '') {
    if (!isset($_SESSION['old']) || !is_array($_SESSION['old'])) {
        return $default;
    }
    return _get($_SESSION['old'], $key, $default);
}

function flash($key, $message = null) {
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }
    $msg = _get($_SESSION['flash'], $key);
    unset($_SESSION['flash'][$key]);
    return $msg;
}

function hasFlash($key) {
    return isset($_SESSION['flash'][$key]);
}

function makeToken() {
    if (function_exists('random_bytes')) {
        return bin2hex(random_bytes(32));
    }
    return bin2hex(openssl_random_pseudo_bytes(32));
}

function csrf() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = makeToken();
    }
    return '<input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token'] . '">';
}

function csrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = makeToken();
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf($token) {
    if (empty($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        return false;
    }
    return true;
}

function isAdmin() {
    return _get($_SESSION['user'], 'role', '') === 'admin';
}

function user() {
    return _get($_SESSION, 'user');
}

function escape($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function hashPassword($password) {
    return md5($password);
}

function verifyPassword($password, $hash) {
    return $hash === md5($password);
}

function escapeJS($value) {
    return json_encode($value, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
}

function logAudit($action, $description) {
    $db = getDB();
    $userId = !empty($_SESSION['user']['id']) ? $_SESSION['user']['id'] : null;
    $ip = _get($_SERVER, 'REMOTE_ADDR', '');
    $ua = _get($_SERVER, 'HTTP_USER_AGENT', '');
    $stmt = $db->prepare("INSERT INTO audit_logs (user_id, action, description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute(array($userId, $action, $description, $ip, $ua));
}

function formatDateTime($datetime) {
    if (!$datetime || $datetime === '0000-00-00 00:00:00') return '-';
    $months = array('', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember');
    $parts = explode(' ', $datetime);
    $date = explode('-', $parts[0]);
    $time = isset($parts[1]) ? $parts[1] : '00:00:00';
    return (int)$date[2] . ' ' . $months[(int)$date[1]] . ' ' . $date[0] . ' ' . $time;
}

function formatTanggal($date) {
    if (!$date || $date === '0000-00-00') return '-';
    $months = array('', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember');
    $d = explode('-', $date);
    return (int)$d[2] . ' ' . $months[(int)$d[1]] . ' ' . $d[0];
}

function getStatusBadge($status) {
    $colors = array(
        'Tersedia' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400 ring-1 ring-emerald-600/10 dark:ring-emerald-400/20',
        'Terpakai' => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400 ring-1 ring-red-600/10 dark:ring-red-400/20',
        'Rusak' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400 ring-1 ring-amber-600/10 dark:ring-amber-400/20',
        'Dalam Perbaikan' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400 ring-1 ring-blue-600/10 dark:ring-blue-400/20',
    );
    $color = isset($colors[$status]) ? $colors[$status] : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 ring-1 ring-gray-600/10 dark:ring-gray-400/20';
    return '<span class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold rounded-full ' . $color . '">' . $status . '</span>';
}

function getRoleBadge($role) {
    if ($role === 'admin') {
        return '<span class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold rounded-full bg-gradient-to-r from-purple-100 to-indigo-100 text-purple-700 dark:from-purple-900/50 dark:to-indigo-900/50 dark:text-purple-300 ring-1 ring-purple-600/10 dark:ring-purple-400/20">Admin</span>';
    }
    return '<span class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300 ring-1 ring-gray-600/10 dark:ring-gray-400/20">User</span>';
}

function renderStatusTransition($log) {
    $lama = isset($log['status_lama']) ? $log['status_lama'] : null;
    $baru = isset($log['status_baru']) ? $log['status_baru'] : null;

    if (!$lama && !$baru) return '<span class="text-gray-400">-</span>';

    $colors = array(
        'Tersedia' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400',
        'Terpakai' => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400',
        'Rusak' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400',
        'Dalam Perbaikan' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400',
    );

    if (!$lama) {
        $c = isset($colors[$baru]) ? $colors[$baru] : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300';
        return '<span class="inline-flex items-center px-2 py-1 text-xs font-semibold rounded-full ' . $c . '">' . $baru . '</span>';
    }

    $cLama = isset($colors[$lama]) ? $colors[$lama] : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300';
    $cBaru = isset($colors[$baru]) ? $colors[$baru] : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300';

    return '<span class="inline-flex items-center gap-1 text-xs font-semibold"><span class="px-2 py-1 rounded-full ' . $cLama . '">' . $lama . '</span><span class="text-gray-400">&rarr;</span><span class="px-2 py-1 rounded-full ' . $cBaru . '">' . $baru . '</span></span>';
}

function renderPagination($currentPage, $totalPages, $queryParams = array(), $currentPerPage = 15) {
    if ($totalPages <= 1 && empty($_GET['perPage'])) return '';

    $perPage = in_array((int)_get($_GET, 'perPage', $currentPerPage), array(10,20,30,50,100)) ? (int)_get($_GET, 'perPage', $currentPerPage) : $currentPerPage;

    $params = array_merge($_GET, $queryParams, array('page' => $currentPage));
    unset($params['perPage']);
    $buildUrl = function($p) use ($params) {
        $params['page'] = $p;
        return '?' . http_build_query($params);
    };

    $prevDisabled = $currentPage <= 1 ? ' opacity-50 pointer-events-none' : '';
    $nextDisabled = $currentPage >= $totalPages ? ' opacity-50 pointer-events-none' : '';

    $html = '<div class="flex flex-col sm:flex-row justify-between items-center gap-3 px-4 py-3 border-t border-gray-100 dark:border-gray-700">';
    $html .= '<div class="flex items-center gap-2 text-base text-gray-500 dark:text-gray-400">';
    $html .= '<span>Tampilkan</span>';
    $html .= '<select onchange="var u=window.location.pathname+window.location.search.replace(/[?&]perPage=\d+/g,\'\').replace(/^\?&/,\'?\');var sep=u.includes(\'?\')?\'&\':\'?\';window.location.href=u+sep+\'perPage=\'+this.value" class="border rounded px-2 py-1 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">';
    foreach (array(10,20,30,50,100) as $v) {
        $sel = $v === $perPage ? ' selected' : '';
        $html .= '<option value="' . $v . '"' . $sel . '>' . $v . '</option>';
    }
    $html .= '</select>';
    $html .= '<span>baris</span>';
    $html .= '</div>';

    if ($totalPages > 1) {
        $html .= '<div class="flex items-center justify-center gap-3">';
        $html .= '<a href="' . $buildUrl($currentPage - 1) . '" class="px-4 py-2 rounded-lg text-base bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-300 dark:hover:bg-gray-500 transition font-semibold' . $prevDisabled . '">« Prev</a>';
        $html .= '<span class="text-gray-500 dark:text-gray-400 text-base">Halaman</span>';
        $html .= '<input type="number" min="1" max="' . $totalPages . '" id="pageJump" value="' . $currentPage . '" class="w-16 text-center border rounded-lg px-2 py-1 text-base dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">';
        $html .= '<button onclick="jumpPage(document.getElementById(\'pageJump\').value,' . $totalPages . ')" class="px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 transition text-base font-semibold">Run</button>';
        $html .= '<a href="' . $buildUrl($currentPage + 1) . '" class="px-4 py-2 rounded-lg text-base bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-300 dark:hover:bg-gray-500 transition font-semibold' . $nextDisabled . '">Next »</a>';
        $html .= '<span class="text-sm text-gray-400">' . $currentPage . ' / ' . $totalPages . '</span>';
        $html .= '</div>';
    }

    $html .= '<script>function jumpPage(p,m){p=parseInt(p);if(p>=1&&p<=m){var u=window.location.pathname+window.location.search.replace(/[?&]page=\d+/g,\'\').replace(/^\?&/,\'?\');var sep=u.includes(\'?\')?\'&\':\'?\';window.location.href=u+sep+\'page=\'+p}}</script>';
    $html .= '</div>';
    return $html;
}

function paginate($db, $baseQuery, $params, $perPage = 15) {
    if (in_array((int)_get($_GET, 'perPage', $perPage), array(10,20,30,50,100))) {
        $perPage = (int)_get($_GET, 'perPage', $perPage);
    }
    $page = max(1, (int)_get($_GET, 'page', 1));
    $offset = ($page - 1) * $perPage;

    $countStmt = $db->prepare("SELECT COUNT(*) FROM ($baseQuery) _count");
    $countStmt->execute($params);
    $totalItems = (int)$countStmt->fetchColumn();
    $totalPages = (int)ceil($totalItems / $perPage);

    $dataStmt = $db->prepare("$baseQuery LIMIT $perPage OFFSET $offset");
    $dataStmt->execute($params);
    $data = $dataStmt->fetchAll();

    return array($data, $page, $totalPages);
}

function convertToWebp($tmpPath, $uploadDir, $filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $webpName = pathinfo($filename, PATHINFO_FILENAME) . '.webp';
    $destPath = $uploadDir . $webpName;

    if ($ext === 'webp') {
        if (move_uploaded_file($tmpPath, $destPath)) {
            return $destPath;
        }
        return false;
    }

    $src = null;
    switch ($ext) {
        case 'jpg':
        case 'jpeg':
            $src = @imagecreatefromjpeg($tmpPath);
            break;
        case 'png':
            $src = @imagecreatefrompng($tmpPath);
            if ($src) {
                imagealphablending($src, false);
                imagesavealpha($src, true);
            }
            break;
    }

    if (!$src) {
        return false;
    }

    $result = imagewebp($src, $destPath, 80);
    imagedestroy($src);

    if (!$result) {
        return false;
    }

    return $destPath;
}

function parseImages($val) {
    if (empty($val)) {
        return array();
    }
    $decoded = json_decode($val, true);
    if (is_array($decoded)) {
        $paths = array();
        foreach ($decoded as $p) {
            if (is_string($p) && trim($p) !== '') {
                $paths[] = $p;
            }
        }
        return $paths;
    }
    if (is_string($val) && trim($val) !== '') {
        return array($val);
    }
    return array();
}

function uploadMultipleImages($files, $maxFiles = 5, $maxSize = 2097152) {
    $paths = array();
    if (empty($files) || !is_array($files['name'])) {
        return $paths;
    }
    $allowed = array('jpg', 'jpeg', 'png', 'webp');
    $count = min(count($files['name']), $maxFiles);
    for ($i = 0; $i < $count; $i++) {
        if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            flash('error', 'Gagal upload foto (file ke-' . ($i + 1) . ').');
            return '__FLASH_SET__';
        }
        $ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            flash('error', 'Format foto tidak didukung. Gunakan JPG, PNG, atau WebP.');
            return '__FLASH_SET__';
        }
        if ($files['size'][$i] > $maxSize) {
            flash('error', 'Ukuran foto maksimal 2MB per file.');
            return '__FLASH_SET__';
        }
        $uploadDir = __DIR__ . '/../public/uploads/spareparts/' . date('Y') . '/' . date('m') . '/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $filename = time() . '_' . bin2hex(openssl_random_pseudo_bytes(4)) . '.' . $ext;
        $convertedPath = convertToWebp($files['tmp_name'][$i], $uploadDir, $filename);
        if (!$convertedPath) {
            flash('error', 'Gagal upload foto. Periksa izin folder uploads.');
            return '__FLASH_SET__';
        }
        $webpName = pathinfo($filename, PATHINFO_FILENAME) . '.webp';
        $paths[] = 'public/uploads/spareparts/' . date('Y') . '/' . date('m') . '/' . $webpName;
    }
    return $paths;
}

function encodeImages($paths) {
    if (empty($paths)) {
        return null;
    }
    return json_encode(array_values($paths));
}

function ftSearch($fulltextCols, $searchTerm, $idCol = null) {
    $searchTerm = trim($searchTerm);
    $cols = implode(', ', $fulltextCols);

    if (mb_strlen($searchTerm) >= 2) {
        $condition = "MATCH($cols) AGAINST(? IN NATURAL LANGUAGE MODE)";
        $params = array($searchTerm);
    } else {
        $likes = array_map(function($c) { return "$c LIKE ?"; }, $fulltextCols);
        $condition = '(' . implode(' OR ', $likes) . ')';
        $params = array_fill(0, count($fulltextCols), '%' . $searchTerm . '%');
    }

    if ($idCol) {
        $condition .= " OR CAST($idCol AS CHAR) LIKE ?";
        $params[] = '%' . $searchTerm . '%';
    }

    return array($condition, $params);
}
