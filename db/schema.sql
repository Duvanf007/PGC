-- ============================================================
-- SCHEMA.SQL — Base de Datos del Sistema de Gestión de Seguridad
-- Municipio de Guachetá
-- ============================================================

CREATE DATABASE IF NOT EXISTS seguridad_guacheta
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE seguridad_guacheta;

-- ============================================================
-- ESTRUCTURA DE TABLAS
-- ============================================================

CREATE TABLE IF NOT EXISTS usuarios (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  nombre      VARCHAR(100) NOT NULL,
  apellido    VARCHAR(100) NOT NULL,
  email       VARCHAR(180) NOT NULL UNIQUE,
  password    VARCHAR(255) NOT NULL,
  rol         ENUM('admin','institucion','ciudadano') NOT NULL DEFAULT 'ciudadano',
  institucion VARCHAR(150) DEFAULT 'Ciudadano',
  avatar      VARCHAR(10) DEFAULT '👤',
  activo      TINYINT(1) NOT NULL DEFAULT 1,
  fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_email (email),
  INDEX idx_rol (rol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS reportes (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  titulo        VARCHAR(255) NOT NULL,
  descripcion   TEXT NOT NULL,
  tipo          ENUM('derrumbe','incendio','robo','choque','vandalismo','violencia','emergencia','otro') NOT NULL DEFAULT 'otro',
  gravedad      ENUM('alta','media','baja') NOT NULL DEFAULT 'media',
  ubicacion     VARCHAR(255) DEFAULT '',
  imagen_path   VARCHAR(500) DEFAULT NULL,
  usuario_id    INT NOT NULL,
  estado        ENUM('activo','en proceso','resuelto') NOT NULL DEFAULT 'activo',
  publica       TINYINT(1) NOT NULL DEFAULT 1,
  fecha         DATETIME DEFAULT CURRENT_TIMESTAMP,
  fecha_actualizado DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  INDEX idx_usuario (usuario_id),
  INDEX idx_tipo (tipo),
  INDEX idx_gravedad (gravedad),
  INDEX idx_estado (estado),
  INDEX idx_fecha (fecha DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- CONFIGURACIÓN INICIAL (Cuentas del Sistema)
-- ============================================================

-- Insertar cuentas administrativas e institucionales
-- NOTA: Las contraseñas aquí están en texto plano (admin123, etc.). 
-- Deben ser encriptadas (ej. con password_hash de PHP) en tu código antes del login, 
-- o reemplazadas aquí por sus respectivos hashes BCrypt.

INSERT IGNORE INTO usuarios (id, nombre, apellido, email, password, rol, institucion, avatar) VALUES
(1,  'Administrador', 'Sistema',   'admin@guacheta.gov.co',    'admin123', 'admin',       'Sistema General',             '🔑'),
(2,  'Alcaldía',      'Guachetá',  'alcaldia@guacheta.gov.co', 'alcaldia123', 'institucion', 'Alcaldía de Guachetá',        '🏛️'),
(3,  'Policía',       'Guachetá',  'policia@guacheta.gov.co',  'policia123', 'institucion', 'Policía Nacional - Guachetá', '👮'),
(4,  'Hospital',      'Guachetá',  'hospital@guacheta.gov.co', 'hospital123', 'institucion', 'Hospital San Judas Tadeo',    '🏥');
