<?php
// ============================================================
// api/config.php — Configuración y Conexión a MySQL
// ============================================================

// Configuración de la sesión
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 86400 * 7, // 7 días
        'path'     => '/',
        'secure'   => false,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// Cabeceras CORS y JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ============================================================
// CONFIGURACIÓN DE BASE DE DATOS
// ============================================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'seguridad_guacheta');
define('DB_USER', 'root');
define('DB_PASS', '');      // Contraseña de MySQL (vacía por defecto en XAMPP)
define('DB_PORT', '3306');

define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_URL', '../uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// ============================================================
// Conexión PDO
// ============================================================
function db(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;
    try {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        json_error('Error de conexión a la base de datos: ' . $e->getMessage(), 500);
    }
    return $pdo;
}

// ============================================================
// Helpers de respuesta
// ============================================================
function json_ok(array $data = [], int $code = 200): void {
    http_response_code($code);
    echo json_encode(['ok' => true] + $data, JSON_UNESCAPED_UNICODE);
    exit;
}

function json_error(string $error, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $error], JSON_UNESCAPED_UNICODE);
    exit;
}

// Obtener el usuario en sesión actual
function session_user(): ?array {
    return $_SESSION['user'] ?? null;
}

// Requerir sesión activa
function require_auth(): array {
    $u = session_user();
    if (!$u) json_error('No autenticado. Por favor inicia sesión.', 401);
    return $u;
}

// Requerir rol de admin
function require_admin(): array {
    $u = require_auth();
    if ($u['rol'] !== 'admin') json_error('Acceso denegado. Se requieren permisos de administrador.', 403);
    return $u;
}

// Obtener body JSON del request
function json_body(): array {
    $raw = file_get_contents('php://input');
    return $raw ? (json_decode($raw, true) ?? []) : [];
}
