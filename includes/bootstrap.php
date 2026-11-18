<?php
declare(strict_types=1);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'samesite' => 'Lax'
    ]);
    session_start();
}
require_once __DIR__ . '/../config/database.php';
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));

function json_response(array $data, int $status = 200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_SLASHES);
    exit;
}
function require_role(string $role): void {
    if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== $role) {
        json_response(['success' => false, 'message' => 'You are not authorised to perform this action.'], 401);
    }
}
function check_csrf(): void {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf'] ?? '');
    if (!$token || !hash_equals($_SESSION['csrf'] ?? '', $token)) {
        json_response(['success' => false, 'message' => 'Security token expired. Refresh the page and try again.'], 419);
    }
}
function clean_string(mixed $value, int $max = 255): string {
    return mb_substr(trim((string)$value), 0, $max);
}
