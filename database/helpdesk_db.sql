- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 31-05-2026 a las 04:01:53
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
-- Base de datos: `helpdesk_db`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `canales_contacto`
--

CREATE TABLE `canales_contacto` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre_canal` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `canales_contacto`
--

INSERT INTO `canales_contacto` (`id`, `nombre_canal`) VALUES
(3, 'Portal Web'),
(1, 'Teléfono'),
(2, 'WhatsApp');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `departamentos`
--

CREATE TABLE `departamentos` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre_departamento` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `departamentos`
--

INSERT INTO `departamentos` (`id`, `nombre_departamento`) VALUES
(4, 'Académico'),
(3, 'Finanzas'),
(2, 'Recursos Humanos'),
(1, 'Sistemas');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estatus_tickets`
--

CREATE TABLE `estatus_tickets` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre_estatus` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `estatus_tickets`
--

INSERT INTO `estatus_tickets` (`id`, `nombre_estatus`) VALUES
(5, 'Cerrado'),
(2, 'En Proceso'),
(1, 'Pendiente de Asignación'),
(4, 'Pendiente de Validación'),
(3, 'Terminado');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notas_internas`
--

CREATE TABLE `notas_internas` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_ticket` int(10) UNSIGNED NOT NULL,
  `id_usuario` int(10) UNSIGNED NOT NULL,
  `nota` text NOT NULL,
  `fecha_registro` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre_rol` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id`, `nombre_rol`) VALUES
(1, 'Coordinador'),
(2, 'Mesa de Ayuda'),
(3, 'Soporte Técnico');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `solicitantes`
--

CREATE TABLE `solicitantes` (
  `id` int(10) UNSIGNED NOT NULL,
  `clave_reportante` varchar(20) NOT NULL,
  `nombre_completo` varchar(150) NOT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `id_departamento` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `solicitantes`
--

INSERT INTO `solicitantes` (`id`, `clave_reportante`, `nombre_completo`, `correo`, `id_departamento`) VALUES
(1, 'ALUM1234', 'Carlos Ruiz', 'carlos.r@alumno.edu.mx', 4);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tickets`
--

CREATE TABLE `tickets` (
  `id` int(10) UNSIGNED NOT NULL,
  `folio` varchar(20) NOT NULL,
  `id_solicitante` int(10) UNSIGNED NOT NULL,
  `id_canal` int(10) UNSIGNED NOT NULL,
  `descripcion` text NOT NULL,
  `prioridad` enum('Baja','Media','Alta') NOT NULL DEFAULT 'Baja',
  `id_estatus` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `id_tecnico` int(10) UNSIGNED DEFAULT NULL,
  `id_mesa_asignada` int(10) UNSIGNED DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_cierre` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `tickets`
--

INSERT INTO `tickets` (`id`, `folio`, `id_solicitante`, `id_canal`, `descripcion`, `prioridad`, `id_estatus`, `id_tecnico`, `id_mesa_asignada`, `fecha_creacion`, `fecha_cierre`) VALUES
(1, 'TKT-2605-0001', 1, 2, 'No puedo acceder al sistema de calificaciones', 'Alta', 1, NULL, 2, '2026-05-30 19:56:35', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(10) UNSIGNED NOT NULL,
  `clave_acceso` varchar(20) NOT NULL,
  `nombre_completo` varchar(150) NOT NULL,
  `correo_institucional` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `id_rol` int(10) UNSIGNED NOT NULL,
  `estado` enum('Activo','Inactivo') NOT NULL DEFAULT 'Activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `clave_acceso`, `nombre_completo`, `correo_institucional`, `password_hash`, `id_rol`, `estado`) VALUES
(1, 'COORD01', 'Coordinador General', 'coord@instituto.edu.mx', '$2y$12$fZhtTBOi7mXqhihMwmF5T.JA.HX2.wO.afwFiA5I/mzvNbgJr/FtW', 1, 'Activo'),
(2, 'MESA01', 'Juan Pérez (Mesa)', 'mesa@instituto.edu.mx', '$2y$12$fZhtTBOi7mXqhihMwmF5T.JA.HX2.wO.afwFiA5I/mzvNbgJr/FtW', 2, 'Activo'),
(3, 'TEC01', 'Ana López (Técnico)', 'tecnico1@instituto.edu.mx', '$2y$12$fZhtTBOi7mXqhihMwmF5T.JA.HX2.wO.afwFiA5I/mzvNbgJr/FtW', 3, 'Activo');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `canales_contacto`
--
ALTER TABLE `canales_contacto`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre_canal` (`nombre_canal`);

--
-- Indices de la tabla `departamentos`
--
ALTER TABLE `departamentos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre_departamento` (`nombre_departamento`);

--
-- Indices de la tabla `estatus_tickets`
--
ALTER TABLE `estatus_tickets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre_estatus` (`nombre_estatus`);

--
-- Indices de la tabla `notas_internas`
--
ALTER TABLE `notas_internas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_ticket` (`id_ticket`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre_rol` (`nombre_rol`);

--
-- Indices de la tabla `solicitantes`
--
ALTER TABLE `solicitantes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `clave_reportante` (`clave_reportante`),
  ADD KEY `id_departamento` (`id_departamento`);

--
-- Indices de la tabla `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `folio` (`folio`),
  ADD KEY `id_solicitante` (`id_solicitante`),
  ADD KEY `id_canal` (`id_canal`),
  ADD KEY `idx_tickets_estatus` (`id_estatus`),
  ADD KEY `idx_tickets_tecnico` (`id_tecnico`),
  ADD KEY `idx_tickets_mesa` (`id_mesa_asignada`),
  ADD KEY `idx_tickets_fechas` (`fecha_creacion`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `clave_acceso` (`clave_acceso`),
  ADD UNIQUE KEY `correo_institucional` (`correo_institucional`),
  ADD KEY `id_rol` (`id_rol`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `canales_contacto`
--
ALTER TABLE `canales_contacto`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `departamentos`
--
ALTER TABLE `departamentos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `estatus_tickets`
--
ALTER TABLE `estatus_tickets`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `notas_internas`
--
ALTER TABLE `notas_internas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `solicitantes`
--
ALTER TABLE `solicitantes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `tickets`
--
ALTER TABLE `tickets`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `notas_internas`
--
ALTER TABLE `notas_internas`
  ADD CONSTRAINT `notas_internas_ibfk_1` FOREIGN KEY (`id_ticket`) REFERENCES `tickets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `notas_internas_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `solicitantes`
--
ALTER TABLE `solicitantes`
  ADD CONSTRAINT `solicitantes_ibfk_1` FOREIGN KEY (`id_departamento`) REFERENCES `departamentos` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `tickets_ibfk_1` FOREIGN KEY (`id_solicitante`) REFERENCES `solicitantes` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `tickets_ibfk_2` FOREIGN KEY (`id_canal`) REFERENCES `canales_contacto` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `tickets_ibfk_3` FOREIGN KEY (`id_estatus`) REFERENCES `estatus_tickets` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `tickets_ibfk_4` FOREIGN KEY (`id_tecnico`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `tickets_ibfk_5` FOREIGN KEY (`id_mesa_asignada`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
