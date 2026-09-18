<?php
// ============================================================
// api/usuarios.php — Gestión de Usuarios (solo admin)
// ============================================================
require_once __DIR__ . '/config.php';

$action = $_GET['action'] ?? '';

switch ($action) {

    // ── GET listar todos los usuarios ─────────────────────────
    case 'listar':
        require_admin();
        $stmt = db()->query(
            'SELECT id, nombre, apellido, email, rol, institucion, avatar, activo, fecha_registro
             FROM usuarios
             ORDER BY fecha_registro ASC'
        );
        $usuarios = $stmt->fetchAll();
        foreach ($usuarios as &$u) { $u['id'] = (int)$u['id']; $u['activo'] = (bool)$u['activo']; }
        json_ok(['usuarios' => $usuarios]);
        break;

    // ── POST activar / desactivar usuario ─────────────────────
    case 'toggle_activo':
        require_admin();
        $body = json_body();
        $id   = (int)($body['id'] ?? 0);
        if (!$id) json_error('ID inválido.');

        $stmt = db()->prepare('SELECT id, activo FROM usuarios WHERE id = ?');
        $stmt->execute([$id]);
        $u = $stmt->fetch();
        if (!$u) json_error('Usuario no encontrado.', 404);

        $nuevo = $u['activo'] ? 0 : 1;
        db()->prepare('UPDATE usuarios SET activo = ? WHERE id = ?')->execute([$nuevo, $id]);
        json_ok(['activo' => (bool)$nuevo]);
        break;

    default:
        json_error('Acción no válida.', 404);
}
