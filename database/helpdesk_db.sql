-- ============================================================
-- phpMyAdmin SQL Dump
-- Sistema Mesa de Ayuda — Los Bélicos
-- Versión: Corregida (Paso 3)
-- Servidor: 127.0.0.1 — MariaDB 10.4.32
-- ============================================================
--
-- CORRECCIONES APLICADAS:
--   · Roles: id=2 ahora es "Soporte Técnico" / id=3 ahora es "Mesa de Ayuda"
--   · Usuarios: MESA01 → id_rol=3 | TEC01 → id_rol=2
--   · Se añade TEC02 para poblar la gráfica RF_15
--   · 10 tickets en todos los estatus para probar RF_14 y RF_15
--   · Notas internas con columna correcta (id_usuario)
--
-- CREDENCIALES DE ACCESO (todos los usuarios):
--   Contraseña: Admin1234!
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- ============================================================
-- ESTRUCTURA
-- ============================================================

CREATE TABLE `canales_contacto` (
  `id`           int(10) UNSIGNED NOT NULL,
  `nombre_canal` varchar(50)      NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `departamentos` (
  `id`                   int(10) UNSIGNED NOT NULL,
  `nombre_departamento`  varchar(100)     NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Estatus: 1=Pendiente de Asignación | 2=En Proceso | 3=Terminado
--          4=Pendiente de Validación | 5=Cerrado
CREATE TABLE `estatus_tickets` (
  `id`             int(10) UNSIGNED NOT NULL,
  `nombre_estatus` varchar(50)      NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `notas_internas` (
  `id`             int(10) UNSIGNED NOT NULL,
  `id_ticket`      int(10) UNSIGNED NOT NULL,
  `id_usuario`     int(10) UNSIGNED NOT NULL,
  `nota`           text             NOT NULL,
  `fecha_registro` datetime         NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Roles: 1=Coordinador | 2=Soporte Técnico | 3=Mesa de Ayuda
CREATE TABLE `roles` (
  `id`         int(10) UNSIGNED NOT NULL,
  `nombre_rol` varchar(50)      NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `solicitantes` (
  `id`               int(10) UNSIGNED NOT NULL,
  `clave_reportante` varchar(20)      NOT NULL,
  `nombre_completo`  varchar(150)     NOT NULL,
  `correo`           varchar(100)     DEFAULT NULL,
  `id_departamento`  int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `tickets` (
  `id`               int(10) UNSIGNED                   NOT NULL,
  `folio`            varchar(20)                        NOT NULL,
  `id_solicitante`   int(10) UNSIGNED                   NOT NULL,
  `id_canal`         int(10) UNSIGNED                   NOT NULL,
  `descripcion`      text                               NOT NULL,
  `prioridad`        enum('Baja','Media','Alta')         NOT NULL DEFAULT 'Baja',
  `id_estatus`       int(10) UNSIGNED                   NOT NULL DEFAULT 1,
  `id_tecnico`       int(10) UNSIGNED                   DEFAULT NULL,
  `id_mesa_asignada` int(10) UNSIGNED                   DEFAULT NULL,
  `fecha_creacion`   datetime                           NOT NULL DEFAULT current_timestamp(),
  `fecha_cierre`     datetime                           DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `usuarios` (
  `id`                    int(10) UNSIGNED                NOT NULL,
  `clave_acceso`          varchar(20)                    NOT NULL,
  `nombre_completo`       varchar(150)                   NOT NULL,
  `correo_institucional`  varchar(100)                   NOT NULL,
  `password_hash`         varchar(255)                   NOT NULL,
  `id_rol`                int(10) UNSIGNED               NOT NULL,
  `estado`                enum('Activo','Inactivo')       NOT NULL DEFAULT 'Activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DATOS DE CATÁLOGOS
-- ============================================================

INSERT INTO `canales_contacto` (`id`, `nombre_canal`) VALUES
(1, 'Teléfono'),
(2, 'WhatsApp'),
(3, 'Portal Web');

INSERT INTO `departamentos` (`id`, `nombre_departamento`) VALUES
(1, 'Sistemas'),
(2, 'Recursos Humanos'),
(3, 'Finanzas'),
(4, 'Académico');

INSERT INTO `estatus_tickets` (`id`, `nombre_estatus`) VALUES
(1, 'Pendiente de Asignación'),
(2, 'En Proceso'),
(3, 'Terminado'),
(4, 'Pendiente de Validación'),
(5, 'Cerrado');

-- ============================================================
-- ROLES  (*** CORRECCIÓN PRINCIPAL ***)
--   ANTES: id=2 → Mesa de Ayuda  | id=3 → Soporte Técnico
--   AHORA: id=2 → Soporte Técnico | id=3 → Mesa de Ayuda
-- ============================================================

INSERT INTO `roles` (`id`, `nombre_rol`) VALUES
(1, 'Coordinador'),
(2, 'Soporte Técnico'),
(3, 'Mesa de Ayuda');

-- ============================================================
-- USUARIOS (*** id_rol corregido + TEC02 añadido ***)
--   id=1 COORD01 — Coordinador          (id_rol=1) ✔ sin cambios
--   id=2 MESA01  — Juan Pérez Mesa      (id_rol=3) ← era 2
--   id=3 TEC01   — Ana López            (id_rol=2) ← era 3
--   id=4 TEC02   — Carlos Mendoza       (id_rol=2) ← NUEVO
-- ============================================================

INSERT INTO `usuarios`
  (`id`, `clave_acceso`, `nombre_completo`, `correo_institucional`, `password_hash`, `id_rol`, `estado`)
VALUES
(1, 'COORD01', 'Coordinador General', 'coord@instituto.edu.mx',
 '$2y$12$fZhtTBOi7mXqhihMwmF5T.JA.HX2.wO.afwFiA5I/mzvNbgJr/FtW', 1, 'Activo'),

(2, 'MESA01', 'Juan Pérez', 'mesa@instituto.edu.mx',
 '$2y$12$fZhtTBOi7mXqhihMwmF5T.JA.HX2.wO.afwFiA5I/mzvNbgJr/FtW', 3, 'Activo'),

(3, 'TEC01', 'Ana López', 'tecnico1@instituto.edu.mx',
 '$2y$12$fZhtTBOi7mXqhihMwmF5T.JA.HX2.wO.afwFiA5I/mzvNbgJr/FtW', 2, 'Activo'),

(4, 'TEC02', 'Carlos Mendoza', 'tecnico2@instituto.edu.mx',
 '$2y$12$fZhtTBOi7mXqhihMwmF5T.JA.HX2.wO.afwFiA5I/mzvNbgJr/FtW', 2, 'Activo');

-- ============================================================
-- SOLICITANTES
-- ============================================================

INSERT INTO `solicitantes`
  (`id`, `clave_reportante`, `nombre_completo`, `correo`, `id_departamento`)
VALUES
(1, 'ALUM1234', 'Carlos Ruiz',      'carlos.r@alumno.edu.mx',      4),
(2, 'EMP2001',  'María García',     'maria.g@instituto.edu.mx',    2),
(3, 'DOC3001',  'Roberto Silva',    'roberto.s@instituto.edu.mx',  1),
(4, 'ADM4001',  'Patricia Torres',  'patricia.t@instituto.edu.mx', 3),
(5, 'ALUM5678', 'Luis Hernández',   'luis.h@alumno.edu.mx',        4);

-- ============================================================
-- TICKETS — datos de prueba para RF_14 y RF_15
--
--   id_tecnico:        3 = TEC01 (Ana López)
--                      4 = TEC02 (Carlos Mendoza)
--   id_mesa_asignada:  2 = MESA01 (Juan Pérez)
--
--   Distribución de estatus:
--     Pendiente de Asignación (1) → 2 tickets
--     En Proceso              (2) → 3 tickets
--     Terminado               (3) → 1 ticket
--     Pendiente de Validación (4) → 1 ticket
--     Cerrado                 (5) → 3 tickets
-- ============================================================

INSERT INTO `tickets`
  (`id`, `folio`, `id_solicitante`, `id_canal`, `descripcion`,
   `prioridad`, `id_estatus`, `id_tecnico`, `id_mesa_asignada`,
   `fecha_creacion`, `fecha_cierre`)
VALUES
-- Pendiente de Asignación (id_estatus = 1)
(1,  'HD-20260530-0001', 1, 2,
 'No puedo acceder al sistema de calificaciones desde el portal',
 'Alta', 1, NULL, 2, '2026-05-30 08:15:00', NULL),

(2,  'HD-20260530-0002', 4, 1,
 'Falla en proyector del aula 204, no detecta la señal HDMI',
 'Baja', 1, NULL, 2, '2026-05-30 09:45:00', NULL),

-- En Proceso (id_estatus = 2)
(3,  'HD-20260529-0001', 2, 1,
 'Impresora de red del área de RRHH no imprime desde ninguna PC',
 'Media', 2, 3, 2, '2026-05-29 10:30:00', NULL),

(4,  'HD-20260529-0002', 3, 3,
 'Error al generar reportes de nómina, el sistema arroja excepción SQL',
 'Alta', 2, 4, 2, '2026-05-29 11:00:00', NULL),

(5,  'HD-20260530-0003', 1, 3,
 'Correo institucional no adjunta archivos mayores a 5 MB',
 'Media', 2, 4, 2, '2026-05-30 10:00:00', NULL),

-- Terminado (id_estatus = 3) — técnico terminó, esperando enviar a validación
(6,  'HD-20260528-0001', 4, 2,
 'Solicitud de acceso a plataforma Microsoft Teams para nuevo colaborador',
 'Baja', 3, 3, 2, '2026-05-28 09:00:00', NULL),

-- Pendiente de Validación (id_estatus = 4) — en cola de Mesa de Ayuda
(7,  'HD-20260528-0002', 1, 1,
 'Pantalla no enciende en PC del laboratorio L3, posible falla de fuente',
 'Alta', 4, 3, 2, '2026-05-28 14:00:00', NULL),

-- Cerrados (id_estatus = 5)
(8,  'HD-20260527-0001', 2, 3,
 'Actualización de antivirus pendiente en equipo de secretaría',
 'Media', 5, 4, 2, '2026-05-27 09:00:00', '2026-05-28 16:00:00'),

(9,  'HD-20260527-0002', 3, 2,
 'Contraseña bloqueada en sistema académico SAES después de 5 intentos fallidos',
 'Media', 5, 3, 2, '2026-05-27 10:00:00', '2026-05-28 15:30:00'),

(10, 'HD-20260525-0001', 5, 1,
 'Solicitud de nueva extensión telefónica para el área de Recursos Humanos',
 'Baja', 5, 3, 2, '2026-05-25 08:00:00', '2026-05-26 12:00:00');

-- ============================================================
-- NOTAS INTERNAS
--   id_usuario: 2=MESA01, 3=TEC01, 4=TEC02
-- ============================================================

INSERT INTO `notas_internas` (`id_ticket`, `id_usuario`, `nota`, `fecha_registro`) VALUES
(3, 3, 'Se revisó la cola de impresión. El problema es el driver de red. Se procederá a reinstalar en todos los equipos.', '2026-05-29 11:30:00'),
(4, 4, 'Se identificó campo nulo en BD de nómina. Coordinando solución con el área de DBA. ETA: 24 hrs.', '2026-05-29 12:00:00'),
(7, 3, 'Fuente de poder reemplazada. PC enciende correctamente. Se envía a validación de Mesa.', '2026-05-28 17:45:00'),
(8, 2, 'Ticket validado. Antivirus actualizado y verificado en el equipo. Usuario notificado.', '2026-05-28 16:00:00'),
(9, 2, 'Validado. Contraseña restablecida y acceso confirmado directamente con el docente.', '2026-05-28 15:30:00'),
(10, 2, 'Extensión configurada y probada. Se confirma funcionamiento correcto con el área solicitante.', '2026-05-26 12:00:00');

-- ============================================================
-- ÍNDICES Y CLAVES PRIMARIAS
-- ============================================================

ALTER TABLE `canales_contacto`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_nombre_canal` (`nombre_canal`);

ALTER TABLE `departamentos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_nombre_departamento` (`nombre_departamento`);

ALTER TABLE `estatus_tickets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_nombre_estatus` (`nombre_estatus`);

ALTER TABLE `notas_internas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ni_ticket`  (`id_ticket`),
  ADD KEY `idx_ni_usuario` (`id_usuario`);

ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_nombre_rol` (`nombre_rol`);

ALTER TABLE `solicitantes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_clave_reportante` (`clave_reportante`),
  ADD KEY `idx_sol_departamento` (`id_departamento`);

ALTER TABLE `tickets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_folio`            (`folio`),
  ADD KEY `idx_tkt_solicitante`        (`id_solicitante`),
  ADD KEY `idx_tkt_canal`              (`id_canal`),
  ADD KEY `idx_tkt_estatus`            (`id_estatus`),
  ADD KEY `idx_tkt_tecnico`            (`id_tecnico`),
  ADD KEY `idx_tkt_mesa`               (`id_mesa_asignada`),
  ADD KEY `idx_tkt_fecha_creacion`     (`fecha_creacion`);

ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_clave_acceso`         (`clave_acceso`),
  ADD UNIQUE KEY `uk_correo_institucional` (`correo_institucional`),
  ADD KEY `idx_usr_rol` (`id_rol`);

-- ============================================================
-- AUTO_INCREMENT
-- ============================================================

ALTER TABLE `canales_contacto` MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
ALTER TABLE `departamentos`    MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
ALTER TABLE `estatus_tickets`  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
ALTER TABLE `notas_internas`   MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
ALTER TABLE `roles`            MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
ALTER TABLE `solicitantes`     MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
ALTER TABLE `tickets`          MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;
ALTER TABLE `usuarios`         MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

-- ============================================================
-- FOREIGN KEYS
-- ============================================================

ALTER TABLE `notas_internas`
  ADD CONSTRAINT `ni_fk_ticket`   FOREIGN KEY (`id_ticket`)  REFERENCES `tickets`  (`id`) ON DELETE CASCADE  ON UPDATE CASCADE,
  ADD CONSTRAINT `ni_fk_usuario`  FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE;

ALTER TABLE `solicitantes`
  ADD CONSTRAINT `sol_fk_depto` FOREIGN KEY (`id_departamento`) REFERENCES `departamentos` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `tickets`
  ADD CONSTRAINT `tkt_fk_solicitante`   FOREIGN KEY (`id_solicitante`)  REFERENCES `solicitantes`    (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `tkt_fk_canal`         FOREIGN KEY (`id_canal`)         REFERENCES `canales_contacto` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `tkt_fk_estatus`       FOREIGN KEY (`id_estatus`)       REFERENCES `estatus_tickets`  (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `tkt_fk_tecnico`       FOREIGN KEY (`id_tecnico`)       REFERENCES `usuarios`         (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `tkt_fk_mesa_asignada` FOREIGN KEY (`id_mesa_asignada`) REFERENCES `usuarios`         (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `usuarios`
  ADD CONSTRAINT `usr_fk_rol` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id`) ON UPDATE CASCADE;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
