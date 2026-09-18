<?php
// ============================================================
// api/auth.php — Autenticación (login, logout, registro, sesión)
// ============================================================
require_once __DIR__ . '/config.php';

$action = $_GET['action'] ?? '';

switch ($action) {

    // ── GET sesión actual ─────────────────────────────────────
    case 'session':
        $u = session_user();
        if ($u) json_ok(['user' => $u]);
        json_error('Sin sesión activa', 401);
        break;

    // ── POST login ────────────────────────────────────────────
    case 'login':
        $body = json_body();
        $email    = trim($body['email'] ?? '');
        $password = $body['password'] ?? '';

        if (!$email || !$password) json_error('Correo y contraseña son requeridos.');

        $stmt = db()->prepare('SELECT * FROM usuarios WHERE email = ? AND activo = 1 LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Verificar contraseña (soportar texto plano para seeds, hash para usuarios nuevos)
        $valid = false;
        if ($user) {
            if (password_verify($password, $user['password'])) {
                $valid = true;
            } else if ($user['password'] === $password) {
                // Contraseña en texto plano (datos seed) → migrar a hash
                $hash = password_hash($password, PASSWORD_BCRYPT);
                db()->prepare('UPDATE usuarios SET password = ? WHERE id = ?')->execute([$hash, $user['id']]);
                $valid = true;
            }
        }

        if (!$valid) json_error('Correo o contraseña incorrectos.');

        // Guardar en sesión (sin password)
        unset($user['password']);
        $_SESSION['user'] = $user;

        json_ok(['user' => $user]);
        break;

    // ── POST logout ───────────────────────────────────────────
    case 'logout':
        session_destroy();
        json_ok(['message' => 'Sesión cerrada']);
        break;

    // ── POST registro nuevo usuario ciudadano ─────────────────
    case 'register':
        $body     = json_body();
        $nombre   = trim($body['nombre'] ?? '');
        $apellido = trim($body['apellido'] ?? '');
        $email    = trim($body['email'] ?? '');
        $password = $body['password'] ?? '';

        if (!$nombre || !$apellido || !$email || !$password) {
            json_error('Todos los campos son requeridos.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            json_error('El correo electrónico no es válido.');
        }
        if (strlen($password) < 6) {
            json_error('La contraseña debe tener al menos 6 caracteres.');
        }

        // Verificar si ya existe
        $check = db()->prepare('SELECT id FROM usuarios WHERE email = ? LIMIT 1');
        $check->execute([$email]);
        if ($check->fetch()) json_error('Ya existe una cuenta con ese correo electrónico.');

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $ins  = db()->prepare(
            'INSERT INTO usuarios (nombre, apellido, email, password, rol, institucion, avatar)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $ins->execute([$nombre, $apellido, $email, $hash, 'ciudadano', 'Ciudadano', '👤']);
        $id = db()->lastInsertId();

        $user = [
            'id'           => (int)$id,
            'nombre'       => $nombre,
            'apellido'     => $apellido,
            'email'        => $email,
            'rol'          => 'ciudadano',
            'institucion'  => 'Ciudadano',
            'avatar'       => '👤',
        ];
        $_SESSION['user'] = $user;
        json_ok(['user' => $user], 201);
        break;

    default:
        json_error('Acción no válida.', 404);
}
