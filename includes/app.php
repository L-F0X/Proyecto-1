<?php
declare(strict_types=1);

$localConfig = dirname(__DIR__) . '/config.local.php';
if (is_file($localConfig)) {
    require_once $localConfig;
}

$isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== '' && $_SERVER['HTTPS'] !== 'off';
session_name('sena_personal');
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_set_cookie_params([
    'httponly' => true,
    'secure' => $isHttps,
    'samesite' => 'Lax',
]);
session_start();

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; style-src 'self'; script-src 'self'; img-src 'self' data:; form-action 'self'; base-uri 'self'; frame-ancestors 'none'");

function app_env(string $name): string|false
{
    $value = getenv($name);
    if ($value !== false) {
        return $value;
    }

    $value = $_SERVER[$name] ?? $_ENV[$name] ?? false;
    return is_string($value) ? $value : false;
}

function db(): PDO
{
    static $connection = null;
    if ($connection instanceof PDO) {
        return $connection;
    }

    $host = app_env('DB_HOST') ?: '127.0.0.1';
    $database = app_env('DB_NAME') ?: 'sena_personal';
    $username = app_env('DB_USER');
    $password = app_env('DB_PASS');

    if ($username === false || ($password === false && app_env('APP_ENV') !== 'development')) {
        throw new RuntimeException('Configura DB_USER y DB_PASS; en desarrollo, DB_PASS puede omitirse si la cuenta MySQL no tiene contraseña.');
    }
    $password = $password === false ? '' : $password;

    $connection = new PDO(
        "mysql:host={$host};dbname={$database};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    return $connection;
}

function e(string|int|float|null $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    $submitted = $_POST['csrf_token'] ?? '';
    if (!is_string($submitted) || !hash_equals(csrf_token(), $submitted)) {
        http_response_code(403);
        exit('Solicitud inválida. Recarga la página e inténtalo de nuevo.');
    }
}

function require_login(): void
{
    if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) {
        header('Location: login.php');
        exit;
    }
}

function require_admin(): void
{
    require_login();
    if (($_SESSION['user']['role'] ?? '') !== 'admin') {
        http_response_code(403);
        exit('No tienes permisos para realizar esta acción.');
    }
}

function require_manager(): void
{
    require_login();
    if (!in_array($_SESSION['user']['role'] ?? '', ['admin', 'talento_humano'], true)) {
        http_response_code(403);
        exit('No tienes permisos para realizar esta acción.');
    }
}

function set_flash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function take_flash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);

    return is_array($flash) ? $flash : null;
}
