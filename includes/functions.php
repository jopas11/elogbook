<?php

function redirect(string $url): void {
    // Auto-route: if the .php file doesn't exist at root but exists in pages/ or actions/
    if (!str_starts_with($url, 'http') && !str_starts_with($url, 'index.php') && !str_starts_with($url, '/')) {
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

function pageUrl(string $file): string {
    $page = str_replace('.php', '', $file);
    $rootFile = __DIR__ . '/../' . $file;
    $pageFile = __DIR__ . '/../pages/' . $page . '.php';
    $actionFile = __DIR__ . '/../actions/' . $page . '.php';
    if (!file_exists($rootFile) && (file_exists($pageFile) || file_exists($actionFile))) {
        return 'index.php?url=' . $page;
    }
    return $file;
}

function old(string $key, string $default = ''): string {
    return $_SESSION['old'][$key] ?? $default;
}

function flash(string $key, ?string $message = null): ?string {
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }
    $msg = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $msg;
}

function hasFlash(string $key): bool {
    return isset($_SESSION['flash'][$key]);
}

function csrf(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return '<input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token'] . '">';
}

function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(string $token): bool {
    if (empty($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        return false;
    }
    return true;
}

function isAdmin(): bool {
    return ($_SESSION['user']['role'] ?? '') === 'admin';
}

function user(): ?array {
    return $_SESSION['user'] ?? null;
}

function escape(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function formatTanggal(string $date): string {
    if (!$date || $date === '0000-00-00') return '-';
    $months = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $d = explode('-', $date);
    return (int)$d[2] . ' ' . $months[(int)$d[1]] . ' ' . $d[0];
}

function getStatusBadge(string $status): string {
    $colors = [
        'Tersedia' => 'bg-green-100 text-green-800',
        'Terpakai' => 'bg-red-100 text-red-800',
        'Rusak' => 'bg-yellow-100 text-yellow-800',
        'Dalam Perbaikan' => 'bg-blue-100 text-blue-800',
    ];
    $color = $colors[$status] ?? 'bg-gray-100 text-gray-800';
    return '<span class="px-2 py-1 text-xs font-medium rounded-full ' . $color . '">' . $status . '</span>';
}

function getRoleBadge(string $role): string {
    if ($role === 'admin') {
        return '<span class="px-2 py-1 text-xs font-medium rounded-full bg-purple-100 text-purple-800">Admin</span>';
    }
    return '<span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800">User</span>';
}

function renderPagination(int $currentPage, int $totalPages, array $queryParams = []): string {
    if ($totalPages <= 1) return '';

    $params = array_merge($_GET, $queryParams, ['page' => $currentPage]);
    $html = '<div class="flex flex-col sm:flex-row justify-between items-center gap-3 px-4 py-3 border-t border-gray-100 dark:border-gray-700">';
    $html .= '<p class="text-sm text-gray-500 dark:text-gray-400">Halaman ' . $currentPage . ' dari ' . $totalPages . '</p>';
    $html .= '<div class="flex gap-1 flex-wrap">';

    for ($i = 1; $i <= $totalPages; $i++) {
        $params['page'] = $i;
        $url = '?' . http_build_query($params);
        $active = $i === $currentPage ? 'bg-primary-800 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600';
        $html .= '<a href="' . $url . '" class="px-3 py-1 rounded-lg text-sm font-medium transition ' . $active . '">' . $i . '</a>';
    }

    $html .= '</div></div>';
    return $html;
}

function paginate(PDO $db, string $baseQuery, array $params, int $perPage = 15): array {
    $page = max(1, (int)($_GET['page'] ?? 1));
    $offset = ($page - 1) * $perPage;

    $countStmt = $db->prepare("SELECT COUNT(*) FROM ($baseQuery) _count");
    $countStmt->execute($params);
    $totalItems = (int)$countStmt->fetchColumn();
    $totalPages = (int)ceil($totalItems / $perPage);

    $dataStmt = $db->prepare("$baseQuery LIMIT $perPage OFFSET $offset");
    $dataStmt->execute($params);
    $data = $dataStmt->fetchAll();

    return [$data, $page, $totalPages];
}

/**
 * Build FULLTEXT search condition + params with Ngram support.
 * Falls back to LIKE for short terms (< 2 chars).
 */
function ftSearch(array $fulltextCols, string $searchTerm, ?string $idCol = null): array {
    $clean = trim(preg_replace('/[+\-><\(\)~*\"@\.]+/u', ' ', $searchTerm));

    if (mb_strlen($clean) >= 2) {
        $words = array_filter(explode(' ', $clean), fn($w) => trim($w) !== '');
        $terms = array_map(fn($w) => '+' . trim($w) . '*', $words);
        $cols = implode(', ', $fulltextCols);
        $condition = "MATCH($cols) AGAINST(? IN BOOLEAN MODE)";
        $params = [implode(' ', $terms)];
    } else {
        $likes = array_map(fn($c) => "$c LIKE ?", $fulltextCols);
        $condition = '(' . implode(' OR ', $likes) . ')';
        $params = array_fill(0, count($fulltextCols), '%' . $searchTerm . '%');
    }

    if ($idCol) {
        $condition .= " OR CAST($idCol AS CHAR) LIKE ?";
        $params[] = '%' . $searchTerm . '%';
    }

    return [$condition, $params];
}
