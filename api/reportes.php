<?php
// ============================================================
// api/reportes.php — CRUD de Reportes
// ============================================================
require_once __DIR__ . '/config.php';

$action = $_GET['action'] ?? '';

switch ($action) {

    // ── GET listar todos los reportes públicos ────────────────
    case 'listar':
        $tipo    = $_GET['tipo']    ?? '';
        $gravedad= $_GET['gravedad']?? '';
        $buscar  = $_GET['buscar']  ?? '';

        $sql    = 'SELECT r.*, u.nombre, u.apellido, u.institucion, u.rol AS usuario_rol, u.avatar
                   FROM reportes r
                   JOIN usuarios u ON u.id = r.usuario_id
                   WHERE r.publica = 1';
        $params = [];

        if ($tipo)    { $sql .= ' AND r.tipo = ?';     $params[] = $tipo; }
        if ($gravedad){ $sql .= ' AND r.gravedad = ?'; $params[] = $gravedad; }
        if ($buscar) {
            $sql .= ' AND (r.titulo LIKE ? OR r.descripcion LIKE ? OR r.ubicacion LIKE ?)';
            $like = "%$buscar%";
            $params = array_merge($params, [$like, $like, $like]);
        }
        $sql .= ' ORDER BY r.fecha DESC';

        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        $reportes = $stmt->fetchAll();
        $reportes = array_map('formatReporte', $reportes);

        json_ok(['reportes' => $reportes]);
        break;

    // ── GET mis reportes (usuario autenticado) ────────────────
    case 'mis_reportes':
        $user = require_auth();
        $stmt = db()->prepare(
            'SELECT r.*, u.nombre, u.apellido, u.institucion, u.rol AS usuario_rol, u.avatar
             FROM reportes r
             JOIN usuarios u ON u.id = r.usuario_id
             WHERE r.usuario_id = ?
             ORDER BY r.fecha DESC'
        );
        $stmt->execute([$user['id']]);
        $reportes = array_map('formatReporte', $stmt->fetchAll());
        json_ok(['reportes' => $reportes]);
        break;

    // ── GET todos los reportes (solo admin) ───────────────────
    case 'todos':
        $user = require_admin();
        $stmt = db()->prepare(
            'SELECT r.*, u.nombre, u.apellido, u.institucion, u.rol AS usuario_rol, u.avatar
             FROM reportes r
             JOIN usuarios u ON u.id = r.usuario_id
             ORDER BY r.fecha DESC'
        );
        $stmt->execute();
        json_ok(['reportes' => array_map('formatReporte', $stmt->fetchAll())]);
        break;

    // ── GET estadísticas (admin) ──────────────────────────────
    case 'stats':
        require_admin();
        $pdo = db();
        $total     = $pdo->query('SELECT COUNT(*) FROM reportes')->fetchColumn();
        $activos   = $pdo->query("SELECT COUNT(*) FROM reportes WHERE estado='activo'")->fetchColumn();
        $enProceso = $pdo->query("SELECT COUNT(*) FROM reportes WHERE estado='en proceso'")->fetchColumn();
        $resueltos = $pdo->query("SELECT COUNT(*) FROM reportes WHERE estado='resuelto'")->fetchColumn();
        $alta      = $pdo->query("SELECT COUNT(*) FROM reportes WHERE gravedad='alta'")->fetchColumn();
        $media     = $pdo->query("SELECT COUNT(*) FROM reportes WHERE gravedad='media'")->fetchColumn();
        $baja      = $pdo->query("SELECT COUNT(*) FROM reportes WHERE gravedad='baja'")->fetchColumn();

        // Por tipo
        $tipos = $pdo->query("SELECT tipo, COUNT(*) as cnt FROM reportes GROUP BY tipo")->fetchAll();

        json_ok([
            'stats' => compact('total','activos','enProceso','resueltos','alta','media','baja'),
            'tipos' => $tipos
        ]);
        break;

    // ── POST crear reporte (multipart/form-data) ──────────────
    case 'crear':
        $user = require_auth();
        $titulo      = trim($_POST['titulo'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $tipo        = $_POST['tipo']     ?? 'otro';
        $gravedad    = $_POST['gravedad'] ?? 'media';
        $ubicacion   = trim($_POST['ubicacion'] ?? '');

        if (!$titulo || !$descripcion) json_error('Título y descripción son requeridos.');

        $imagen_path = null;
        if (!empty($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $imagen_path = subirImagen($_FILES['imagen']);
        }

        $stmt = db()->prepare(
            'INSERT INTO reportes (titulo, descripcion, tipo, gravedad, ubicacion, imagen_path, usuario_id, publica)
             VALUES (?, ?, ?, ?, ?, ?, ?, 1)'
        );
        $stmt->execute([$titulo, $descripcion, $tipo, $gravedad, $ubicacion, $imagen_path, $user['id']]);
        $id = db()->lastInsertId();

        $stmt2 = db()->prepare('SELECT r.*, u.nombre, u.apellido, u.institucion, u.rol AS usuario_rol, u.avatar FROM reportes r JOIN usuarios u ON u.id = r.usuario_id WHERE r.id = ?');
        $stmt2->execute([$id]);
        json_ok(['reporte' => formatReporte($stmt2->fetch())], 201);
        break;

    // ── POST actualizar reporte ───────────────────────────────
    case 'actualizar':
        $user = require_auth();
        $body = json_body();
        $id   = (int)($body['id'] ?? 0);
        if (!$id) json_error('ID no válido.');

        // Verificar propiedad o admin
        $rep = db()->prepare('SELECT * FROM reportes WHERE id = ?');
        $rep->execute([$id]);
        $reporte = $rep->fetch();
        if (!$reporte) json_error('Reporte no encontrado.', 404);
        if ($user['rol'] !== 'admin' && (int)$reporte['usuario_id'] !== (int)$user['id']) {
            json_error('Sin permisos para editar este reporte.', 403);
        }

        $campos = ['titulo','descripcion','tipo','gravedad','ubicacion','estado','publica'];
        $sets   = [];
        $params = [];
        foreach ($campos as $c) {
            if (isset($body[$c])) {
                $sets[]   = "$c = ?";
                $params[] = $body[$c];
            }
        }
        if (empty($sets)) json_error('Sin campos para actualizar.');
        $params[] = $id;
        db()->prepare('UPDATE reportes SET ' . implode(', ', $sets) . ' WHERE id = ?')->execute($params);

        $stmt = db()->prepare('SELECT r.*, u.nombre, u.apellido, u.institucion, u.rol AS usuario_rol, u.avatar FROM reportes r JOIN usuarios u ON u.id = r.usuario_id WHERE r.id = ?');
        $stmt->execute([$id]);
        json_ok(['reporte' => formatReporte($stmt->fetch())]);
        break;

    // ── POST eliminar reporte ─────────────────────────────────
    case 'eliminar':
        $user = require_auth();
        $body = json_body();
        $id   = (int)($body['id'] ?? 0);
        if (!$id) json_error('ID no válido.');

        $rep = db()->prepare('SELECT * FROM reportes WHERE id = ?');
        $rep->execute([$id]);
        $reporte = $rep->fetch();
        if (!$reporte) json_error('Reporte no encontrado.', 404);
        if ($user['rol'] !== 'admin' && (int)$reporte['usuario_id'] !== (int)$user['id']) {
            json_error('Sin permisos para eliminar este reporte.', 403);
        }

        // Eliminar imagen si existe
        if ($reporte['imagen_path'] && file_exists(UPLOAD_DIR . basename($reporte['imagen_path']))) {
            @unlink(UPLOAD_DIR . basename($reporte['imagen_path']));
        }
        db()->prepare('DELETE FROM reportes WHERE id = ?')->execute([$id]);
        json_ok(['message' => 'Reporte eliminado correctamente.']);
        break;

    default:
        json_error('Acción no válida.', 404);
}

// ============================================================
// HELPERS LOCALES
// ============================================================

/**
 * Formatear fila de reporte para la respuesta JSON
 */
function formatReporte(array $r): array {
    $r['id']         = (int)$r['id'];
    $r['usuario_id'] = (int)$r['usuario_id'];
    $r['publica']    = (bool)$r['publica'];
    $r['usuario_nombre'] = ($r['nombre'] ?? '') . ' ' . ($r['apellido'] ?? '');
    // Construir URL completa de imagen si existe
    if ($r['imagen_path']) {
        // Construir URL relativa que el frontend pueda usar
        $r['imagen_url'] = 'uploads/' . basename($r['imagen_path']);
    } else {
        $r['imagen_url'] = null;
    }
    return $r;
}

/**
 * Subir imagen al servidor y retornar el nombre del archivo
 */
function subirImagen(array $file): string {
    $tipos_permitidos = ['image/jpeg','image/jpg','image/png','image/gif','image/webp'];
    if (!in_array($file['type'], $tipos_permitidos, true)) {
        json_error('Tipo de imagen no permitido. Usa JPG, PNG, GIF o WebP.');
    }
    if ($file['size'] > MAX_FILE_SIZE) {
        json_error('La imagen supera el tamaño máximo de 5MB.');
    }

    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);

    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
    $nombre   = uniqid('img_', true) . '.' . strtolower($ext);
    $destino  = UPLOAD_DIR . $nombre;

    if (!move_uploaded_file($file['tmp_name'], $destino)) {
        json_error('Error al guardar la imagen en el servidor.');
    }
    return $nombre;
}
