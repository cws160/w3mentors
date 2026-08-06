<?php

/**
 * Router for `php -S localhost:8090 -t public public/router.php` (local legacy PHP without Apache).
 * Mirrors the rewrite rules in public/.htaccess.
 */
declare(strict_types=1);

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/');

if ($uri !== '/' && is_file(__DIR__ . $uri)) {
    return false;
}

if ($uri === '/admin' || str_starts_with($uri, '/admin/')) {
    $_GET['url'] = ltrim(substr($uri, strlen('/admin')), '/');
    require __DIR__ . '/admin.php';

    return true;
}

if ($uri === '/dashboard' || str_starts_with($uri, '/dashboard/')) {
    $_GET['url'] = ltrim(substr($uri, strlen('/dashboard')), '/');
    require __DIR__ . '/dashboard.php';

    return true;
}

if (preg_match('#^/([a-zA-Z-]+)/dashboard(?:/(.*))?$#', $uri, $matches)) {
    $suffix = $matches[2] ?? '';
    $_GET['url'] = $matches[1] . ($suffix !== '' ? '/' . $suffix : '');
    require __DIR__ . '/dashboard.php';

    return true;
}

$_GET['url'] = ltrim($uri, '/');
require __DIR__ . '/index.php';

return true;
