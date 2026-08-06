<?php

/**
 * Lite admin impersonation bridge for legacy dashboard (course preview, etc.).
 * Does not load IonCube-encoded Fatbit core — only sets the PHP session and redirects.
 * Signed links are issued by Laravel admin API (AdminUserService::createDashboardBridgeUrl).
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/conf/conf-common.php';
require_once dirname(__FILE__) . '/settings.php';

$userId = (int) ($_GET['user_id'] ?? 0);
$courseId = (int) ($_GET['course_id'] ?? 0);
$expires = (int) ($_GET['exp'] ?? 0);
$signature = (string) ($_GET['sig'] ?? '');

if ($userId < 1 || $courseId < 1 || $expires < 1 || $signature === '') {
    http_response_code(400);
    exit('Invalid request');
}

if ($expires < time()) {
    http_response_code(403);
    exit('Link expired');
}

$bridgeKey = defined('ENCRYPTION_KEY') ? ENCRYPTION_KEY : '';
$payload = $userId . ':' . $courseId . ':' . $expires;
$expected = hash_hmac('sha256', $payload, $bridgeKey);

if (!hash_equals($expected, $signature)) {
    http_response_code(403);
    exit('Invalid signature');
}

$dsn = 'mysql:host=' . CONF_DB_SERVER . ';dbname=' . CONF_DB_NAME . ';charset=utf8mb4';
try {
    $pdo = new PDO($dsn, CONF_DB_USER, CONF_DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException) {
    http_response_code(500);
    exit('Database connection failed');
}

$stmt = $pdo->prepare(
    'SELECT user_id, user_email, user_username, user_first_name, user_last_name, user_is_teacher, user_is_affiliate
     FROM tbl_users
     WHERE user_id = :user_id AND user_deleted IS NULL AND user_active = 1
     LIMIT 1'
);
$stmt->execute(['user_id' => $userId]);
$user = $stmt->fetch();

if (!$user) {
    http_response_code(404);
    exit('User not found');
}

$cookiePath = defined('CONF_WEBROOT_FRONTEND') ? CONF_WEBROOT_FRONTEND : '/';
$cookieParams = [
    'httponly' => true,
    'secure' => false,
    'path' => $cookiePath,
];
if (PHP_VERSION_ID >= 70300) {
    $cookieParams['samesite'] = 'Lax';
}
session_set_cookie_params($cookieParams);
session_start();

$_SESSION = [];
$_SESSION['APP_SESSION'] = [
    'user_ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
    'user_id' => (int) $user['user_id'],
    'user_email' => $user['user_email'],
    'user_username' => $user['user_username'] ?? '',
    'user_first_name' => $user['user_first_name'],
    'user_last_name' => $user['user_last_name'],
    'user_token' => '',
];
$_SESSION['ADMIN_SESSION_ELEMENT'] = ['LOGGED' => 1];

$userType = 1;
if ((int) ($user['user_is_teacher'] ?? 0) === 1) {
    $userType = 2;
} elseif ((int) ($user['user_is_affiliate'] ?? 0) === 1) {
    $userType = 5;
}
$_SESSION['SITE_USER_TYPE'] = $userType;

$dashRoot = defined('CONF_WEBROOT_DASHBOARD') ? CONF_WEBROOT_DASHBOARD : '/dashboard/';
$redirect = rtrim($dashRoot, '/') . '/course-preview/index/' . $courseId;

header('Location: ' . $redirect, true, 302);
exit;
