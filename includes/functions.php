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
    $token = bin2hex(random_bytes(32));
    $_SESSION['csrf_token'] = $token;
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

function verifyCsrf(string $token): bool {
    if (empty($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        return false;
    }
    unset($_SESSION['csrf_token']);
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
