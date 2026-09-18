-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 18-09-2026 a las 20:49:21
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `seguridad_guacheta`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reportes`
--

CREATE TABLE `reportes` (
  `id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `descripcion` text NOT NULL,
  `tipo` enum('derrumbe','incendio','robo','choque','vandalismo','violencia','emergencia','otro') NOT NULL DEFAULT 'otro',
  `gravedad` enum('alta','media','baja') NOT NULL DEFAULT 'media',
  `ubicacion` varchar(255) DEFAULT '',
  `imagen_path` varchar(500) DEFAULT NULL,
  `usuario_id` int(11) NOT NULL,
  `estado` enum('activo','en proceso','resuelto') NOT NULL DEFAULT 'activo',
  `publica` tinyint(1) NOT NULL DEFAULT 1,
  `fecha` datetime DEFAULT current_timestamp(),
  `fecha_actualizado` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `reportes`
--

INSERT INTO `reportes` (`id`, `titulo`, `descripcion`, `tipo`, `gravedad`, `ubicacion`, `imagen_path`, `usuario_id`, `estado`, `publica`, `fecha`, `fecha_actualizado`) VALUES
(1, 'derrumbe', 'se callo la peña jajaja', 'derrumbe', 'media', 'alcaldia', NULL, 1, 'activo', 1, '2026-09-18 11:51:33', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `email` varchar(180) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` enum('admin','institucion','ciudadano') NOT NULL DEFAULT 'ciudadano',
  `institucion` varchar(150) DEFAULT 'Ciudadano',
  `avatar` varchar(10) DEFAULT '?',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_registro` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `apellido`, `email`, `password`, `rol`, `institucion`, `avatar`, `activo`, `fecha_registro`) VALUES
(1, 'Administrador', 'Sistema', 'admin@guacheta.gov.co', '$2y$10$9K5sF4E/KFOO8IXGoCpIiusVRpaFhyXHEwTPeLYnzzHaGYjFsPHkq', 'admin', 'Sistema General', '🔑', 1, '2026-09-18 11:48:03'),
(2, 'Alcaldía', 'Guachetá', 'alcaldia@guacheta.gov.co', 'alcaldia123', 'institucion', 'Alcaldía de Guachetá', '🏛️', 1, '2026-09-18 11:48:03'),
(3, 'Policía', 'Guachetá', 'policia@guacheta.gov.co', 'policia123', 'institucion', 'Policía Nacional - Guachetá', '👮', 1, '2026-09-18 11:48:03'),
(4, 'Hospital', 'Guachetá', 'hospital@guacheta.gov.co', 'hospital123', 'institucion', 'Hospital San Judas Tadeo', '🏥', 1, '2026-09-18 11:48:03');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `reportes`
--
ALTER TABLE `reportes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_usuario` (`usuario_id`),
  ADD KEY `idx_tipo` (`tipo`),
  ADD KEY `idx_gravedad` (`gravedad`),
  ADD KEY `idx_estado` (`estado`),
  ADD KEY `idx_fecha` (`fecha`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_rol` (`rol`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `reportes`
--
ALTER TABLE `reportes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `reportes`
--
ALTER TABLE `reportes`
  ADD CONSTRAINT `reportes_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
