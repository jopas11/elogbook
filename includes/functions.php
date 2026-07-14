<?php

function &_get(&$arr, $key, $default = null) {
    if (is_array($arr) && isset($arr[$key])) {
        return $arr[$key];
    }
    return $default;
}

function redirect($url) {
    if (strpos($url, 'http') !== 0 && strpos($url, 'index.php') !== 0 && strpos($url, '/') !== 0) {
        $page = str_replace('.php', '', $url);
        $rootFile = __DIR__ . '/../' . $url;
        $pageFile = __DIR__ . '/../pages/' . $page . '.php';
        $actionFile = __DIR__ . '/../actions/' . $page . '.php';
        if (!file_exists($rootFile) && (file_exists($pageFile) || file_exists($actionFile))) {
            $url = 'index.php?url=' . $page;
        }
    }
    header('Location: ' . $url);
    exit;
}

function pageUrl($file) {
    $page = str_replace('.php', '', $file);
    $rootFile = __DIR__ . '/../' . $file;
    $pageFile = __DIR__ . '/../pages/' . $page . '.php';
    $actionFile = __DIR__ . '/../actions/' . $page . '.php';
    if (!file_exists($rootFile) && (file_exists($pageFile) || file_exists($actionFile))) {
        return 'index.php?url=' . $page;
    }
    return $file;
}

function old($key, $default = '') {
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
        'Tersedia' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        'Terpakai' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        'Rusak' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        'Dalam Perbaikan' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    );

    if (!$lama) {
        $c = isset($colors[$baru]) ? $colors[$baru] : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300';
        return '<span class="inline-flex items-center px-2 py-1 text-xs font-semibold rounded-full ' . $c . '">' . $baru . '</span>';
    }

    $cLama = isset($colors[$lama]) ? $colors[$lama] : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300';
    $cBaru = isset($colors[$baru]) ? $colors[$baru] : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300';

    return '<span class="inline-flex items-center gap-1 text-xs font-semibold"><span class="px-2 py-1 rounded-full ' . $cLama . '">' . $lama . '</span><span class="text-gray-400">&rarr;</span><span class="px-2 py-1 rounded-full ' . $cBaru . '">' . $baru . '</span></span>';
}

function renderPagination($currentPage, $totalPages, $queryParams = array()) {
    if ($totalPages <= 1) return '';

    $params = array_merge($_GET, $queryParams, array('page' => $currentPage));
    $html = '<div class="flex flex-col sm:flex-row justify-between items-center gap-3 px-4 py-3 border-t border-gray-100 dark:border-gray-700">';
    $html .= '<p class="text-sm text-gray-500 dark:text-gray-400">Halaman ' . $currentPage . ' dari ' . $totalPages . '</p>';
    $html .= '<div class="flex gap-1 flex-wrap">';

    for ($i = 1; $i <= $totalPages; $i++) {
        $params['page'] = $i;
        $url = '?' . http_build_query($params);
        $active = $i === $currentPage ? 'bg-indigo-600 text-white shadow-md shadow-indigo-500/20' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600';
        $html .= '<a href="' . $url . '" class="px-3 py-1 rounded-lg text-sm font-medium transition ' . $active . '">' . $i . '</a>';
    }

    $html .= '</div></div>';
    return $html;
}

function paginate($db, $baseQuery, $params, $perPage = 15) {
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

function ftSearch($fulltextCols, $searchTerm, $idCol = null) {
    $clean = trim(preg_replace('/[+\-><\(\)~*\"@\.]+/u', ' ', $searchTerm));

    if (mb_strlen($clean) >= 2) {
        $words = array_filter(explode(' ', $clean), function($w) { return trim($w) !== ''; });
        $terms = array_map(function($w) { return '+' . trim($w) . '*'; }, $words);
        $cols = implode(', ', $fulltextCols);
        $condition = "MATCH($cols) AGAINST(? IN BOOLEAN MODE)";
        $params = array(implode(' ', $terms));
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
