-- ═══════════════════════════════════════════════════════════════════════════════
-- Base de Datos: helpdesk_db
-- Sistema de Mesa de Ayuda - Los Bélicos
-- Normalización: Tercera Forma Normal (3FN)
-- Codificación: UTF-8 (utf8mb4)
-- ═══════════════════════════════════════════════════════════════════════════════

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ─── Crear y seleccionar base de datos ────────────────────────────────────────
CREATE DATABASE IF NOT EXISTS `helpdesk`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `helpdesk`;

-- ─── Tabla: roles ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `roles` (
    `id`         TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nombre_rol` VARCHAR(50)      NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `roles` (`id`, `nombre_rol`) VALUES
(1, 'Coordinador'),
(2, 'Técnico'),
(3, 'Mesa de Ayuda');

-- ─── Tabla: departamentos ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `departamentos` (
    `id`                  SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nombre_departamento` VARCHAR(100)      NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `departamentos` (`nombre_departamento`) VALUES
('Dirección General'),
('Recursos Humanos'),
('Tecnologías de la Información'),
('Contabilidad y Finanzas'),
('Administración'),
('Servicios Escolares'),
('Biblioteca'),
('Mantenimiento');

-- ─── Tabla: estatus_tickets ───────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `estatus_tickets` (
    `id`             TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nombre_estatus` VARCHAR(50)      NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `estatus_tickets` (`id`, `nombre_estatus`) VALUES
(1, 'Abierto'),
(2, 'En Proceso'),
(3, 'Cerrado'),
(4, 'Pendiente de Información');

-- ─── Tabla: canales_contacto ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `canales_contacto` (
    `id`           TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nombre_canal` VARCHAR(60)      NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `canales_contacto` (`nombre_canal`) VALUES
('Teléfono'),
('WhatsApp'),
('Correo Electrónico'),
('Presencial'),
('Portal Web');

-- ─── Tabla: solicitantes ──────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `solicitantes` (
    `id`               INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `clave_reportante` VARCHAR(30)     NOT NULL UNIQUE,
    `nombre_completo`  VARCHAR(150)    NOT NULL,
    `correo`           VARCHAR(120)    NOT NULL,
    `id_departamento`  SMALLINT UNSIGNED NOT NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_clave_reportante` (`clave_reportante`),
    CONSTRAINT `fk_sol_departamento`
        FOREIGN KEY (`id_departamento`) REFERENCES `departamentos` (`id`)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Tabla: usuarios ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `usuarios` (
    `id`                    INT UNSIGNED   NOT NULL AUTO_INCREMENT,
    `clave_acceso`          VARCHAR(30)    NOT NULL UNIQUE,
    `nombre_completo`       VARCHAR(150)   NOT NULL,
    `correo_institucional`  VARCHAR(120)   NOT NULL UNIQUE,
    `password_hash`         VARCHAR(255)   NOT NULL,
    `id_rol`                TINYINT UNSIGNED NOT NULL,
    `estado`                ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo',
    PRIMARY KEY (`id`),
    INDEX `idx_clave_acceso` (`clave_acceso`),
    CONSTRAINT `fk_usr_rol`
        FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id`)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Tabla: tickets ───────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `tickets` (
    `id`              INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `folio`           VARCHAR(25)      NOT NULL UNIQUE,
    `id_solicitante`  INT UNSIGNED     NOT NULL,
    `id_canal`        TINYINT UNSIGNED NOT NULL,
    `descripcion`     TEXT             NOT NULL,
    `prioridad`       ENUM('alta','media','baja') NOT NULL DEFAULT 'media',
    `id_estatus`      TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `id_tecnico`      INT UNSIGNED     NULL,
    `fecha_creacion`  DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `fecha_cierre`    DATETIME         NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_folio`       (`folio`),
    INDEX `idx_id_estatus`  (`id_estatus`),
    INDEX `idx_id_tecnico`  (`id_tecnico`),
    CONSTRAINT `fk_tkt_solicitante`
        FOREIGN KEY (`id_solicitante`) REFERENCES `solicitantes` (`id`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_tkt_canal`
        FOREIGN KEY (`id_canal`) REFERENCES `canales_contacto` (`id`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_tkt_estatus`
        FOREIGN KEY (`id_estatus`) REFERENCES `estatus_tickets` (`id`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_tkt_tecnico`
        FOREIGN KEY (`id_tecnico`) REFERENCES `usuarios` (`id`)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Tabla: notas_internas ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `notas_internas` (
    `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_ticket`       INT UNSIGNED NOT NULL,
    `id_tecnico`      INT UNSIGNED NOT NULL,
    `nota`            TEXT         NOT NULL,
    `fecha_registro`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_nota_ticket` (`id_ticket`),
    CONSTRAINT `fk_nota_ticket`
        FOREIGN KEY (`id_ticket`) REFERENCES `tickets` (`id`)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_nota_tecnico`
        FOREIGN KEY (`id_tecnico`) REFERENCES `usuarios` (`id`)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ═══════════════════════════════════════════════════════════════════════════════
-- Datos de prueba: Usuarios del sistema
-- Contraseña para todos: Admin1234! (hash bcrypt)
-- ═══════════════════════════════════════════════════════════════════════════════
INSERT INTO `usuarios` (`clave_acceso`, `nombre_completo`, `correo_institucional`, `password_hash`, `id_rol`, `estado`) VALUES
(
    'coord01',
    'María González Torres',
    'mgonzalez@helpdesk.edu.mx',
    '$2y$12$tFm3.EoPmiUcnv9Kj.Oh6uXAAR7JEsi2PXC4/EhcL9fiEo3xEaBI2',  -- Admin1234!
    1,
    'activo'
),
(
    'tec01',
    'Carlos Ramírez López',
    'cramirez@helpdesk.edu.mx',
    '$2y$12$tFm3.EoPmiUcnv9Kj.Oh6uXAAR7JEsi2PXC4/EhcL9fiEo3xEaBI2',  -- Admin1234!
    2,
    'activo'
),
(
    'tec02',
    'Sofía Herrera Mendoza',
    'sherrera@helpdesk.edu.mx',
    '$2y$12$tFm3.EoPmiUcnv9Kj.Oh6uXAAR7JEsi2PXC4/EhcL9fiEo3xEaBI2',  -- Admin1234!
    2,
    'activo'
),
(
    'mesa01',
    'Javier Morales Ruiz',
    'jmorales@helpdesk.edu.mx',
    '$2y$12$tFm3.EoPmiUcnv9Kj.Oh6uXAAR7JEsi2PXC4/EhcL9fiEo3xEaBI2',  -- Admin1234!
    3,
    'activo'
);

-- ─── Nota sobre el hash ───────────────────────────────────────────────────────
-- El hash almacenado corresponde a 'Admin1234!' generado con:
-- password_hash('Admin1234!', PASSWORD_BCRYPT, ['cost' => 12])
-- Para generar un hash nuevo, ejecuta el script: /scripts/generar_hash.php

SET FOREIGN_KEY_CHECKS = 1;
