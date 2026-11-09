-- ==============================================================================
-- DEVLEVEL v2 — SCRIPT DDL Y CARGA INICIAL DE DATOS
-- Módulo: Desarrollo Web en Entorno Servidor (DWS) | 2º DAW
-- Unidad Didáctica 3: Persistencia Relacional y Seguridad con PHP PDO
-- Práctica Evaluable: P3_1 (DevLevel v2 — Capa de Persistencia Relacional)
-- Motor: MariaDB 10.4+ / MySQL 8.0+ | Codificación: UTF-8 (utf8mb4)
-- ==============================================================================

SET FOREIGN_KEY_CHECKS = 0;
DROP DATABASE IF EXISTS `devlevel_db`;
CREATE DATABASE `devlevel_db` 
    CHARACTER SET utf8mb4 
    COLLATE utf8mb4_unicode_ci;
USE `devlevel_db`;
SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------------------------
-- 1. TABLA: usuarios
-- Almacena los perfiles técnicos de desarrolladores y managers del sistema.
-- ------------------------------------------------------------------------------
CREATE TABLE `usuarios` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `nombre` VARCHAR(100) NOT NULL,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(120) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `rol` ENUM('PROJECT_MANAGER', 'DEVELOPER_SENIOR', 'DEVELOPER_JUNIOR') NOT NULL DEFAULT 'DEVELOPER_JUNIOR',
    `puntos` INT UNSIGNED NOT NULL DEFAULT 0,
    `nivel` TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_usuarios_rol` (`rol`),
    INDEX `idx_usuarios_nivel` (`nivel`),
    INDEX `idx_usuarios_puntos` (`puntos`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 2. TABLA: skills
-- Catálogo de habilidades y áreas de especialización técnica.
-- ------------------------------------------------------------------------------
CREATE TABLE `skills` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `nombre` VARCHAR(60) NOT NULL UNIQUE,
    `categoria` ENUM('backend', 'frontend', 'herramientas', 'seguridad', 'devops') NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_skills_categoria` (`categoria`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 3. TABLA: tareas
-- Catálogo de misiones/tareas disponibles para los desarrolladores.
-- ------------------------------------------------------------------------------
CREATE TABLE `tareas` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `titulo` VARCHAR(150) NOT NULL,
    `descripcion` TEXT NOT NULL,
    `puntos` INT UNSIGNED NOT NULL,
    `nivel_minimo` TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `dificultad` ENUM('facil', 'media', 'dificil') NOT NULL DEFAULT 'media',
    `activa` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_tareas_nivel_minimo` (`nivel_minimo`),
    INDEX `idx_tareas_dificultad` (`dificultad`),
    INDEX `idx_tareas_activa` (`activa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 4. TABLA: tarea_skill (Relación N:M entre tareas y habilidades)
-- Asocia cada tarea con una o más competencias requeridas.
-- ------------------------------------------------------------------------------
CREATE TABLE `tarea_skill` (
    `tarea_id` INT UNSIGNED NOT NULL,
    `skill_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`tarea_id`, `skill_id`),
    CONSTRAINT `fk_ts_tarea` FOREIGN KEY (`tarea_id`) 
        REFERENCES `tareas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_ts_skill` FOREIGN KEY (`skill_id`) 
        REFERENCES `skills` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 5. TABLA: progreso_tareas (Historial de tareas completadas)
-- Registra cada canje/resolución de tarea garantizando integridad referencial.
-- La restricción UNIQUE(usuario_id, tarea_id) asegura que un desarrollador no 
-- cobre dos veces la misma recompensa.
-- ------------------------------------------------------------------------------
CREATE TABLE `progreso_tareas` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `usuario_id` INT UNSIGNED NOT NULL,
    `tarea_id` INT UNSIGNED NOT NULL,
    `puntos_obtenidos` INT UNSIGNED NOT NULL,
    `completada_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_usuario_tarea` (`usuario_id`, `tarea_id`),
    INDEX `idx_progreso_usuario` (`usuario_id`),
    INDEX `idx_progreso_tarea` (`tarea_id`),
    CONSTRAINT `fk_pt_usuario` FOREIGN KEY (`usuario_id`) 
        REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_pt_tarea` FOREIGN KEY (`tarea_id`) 
        REFERENCES `tareas` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================================
-- CARGA DE DATOS INICIALES (SEMILLAS / SEEDING)
-- ==============================================================================

-- 1. Inserción de habilidades técnicas (Skills)
INSERT INTO `skills` (`id`, `nombre`, `categoria`) VALUES
(1, 'PHP Backend', 'backend'),
(2, 'HTML5 Semántico', 'frontend'),
(3, 'CSS Moderno & Flex/Grid', 'frontend'),
(4, 'Control de Versiones Git', 'herramientas'),
(5, 'Seguridad Web & OWASP', 'seguridad'),
(6, 'Bases de Datos MySQL/MariaDB', 'backend'),
(7, 'Docker & Contenedores', 'devops');

-- 2. Inserción de usuarios base
-- Credenciales verificadas con password_verify() (algoritmo BCRYPT nativo PHP 8.2+):
-- * manager -> Manager2026!
-- * senior  -> Senior2026!
-- * junior  -> Junior2026!
INSERT INTO `usuarios` (`id`, `nombre`, `username`, `email`, `password_hash`, `rol`, `puntos`, `nivel`) VALUES
(1, 'Laura Manager', 'manager', 'laura.manager@devlevel.internal', '$2y$10$ZZ.SDsXNIjSOO8ihyxqsAeW1qD82rtiqwfEGvlDj8MiOIzLSwM25W', 'PROJECT_MANAGER', 0, 5),
(2, 'Carlos Senior', 'senior', 'carlos.senior@devlevel.internal', '$2y$10$Bxc2zMjNp/Xmc6TuK9aVqu97KdteCVTfPgmNDAAX71NDDd8/iws9C', 'DEVELOPER_SENIOR', 500, 4),
(3, 'Ana Junior', 'junior', 'ana.junior@devlevel.internal', '$2y$10$0iY0kCkl9OzATK/hEMEcSuLOeL6L1NhOaYhBzVv/i7pTAXu8MBG6q', 'DEVELOPER_JUNIOR', 0, 1),
(4, 'Marcos Trainee', 'trainee', 'marcos.trainee@devlevel.internal', '$2y$10$0iY0kCkl9OzATK/hEMEcSuLOeL6L1NhOaYhBzVv/i7pTAXu8MBG6q', 'DEVELOPER_JUNIOR', 50, 1);

-- 3. Inserción de tareas en el catálogo (12 tareas de diversa complejidad para pruebas de paginación)
INSERT INTO `tareas` (`id`, `titulo`, `descripcion`, `puntos`, `nivel_minimo`, `dificultad`, `activa`) VALUES
(101, 'Procesar formulario de contacto con sanitización', 'Implementar captura segura con filter_input y escape XSS con htmlspecialchars en PHP puro.', 100, 1, 'facil', 1),
(102, 'Estructurar maquetación CSS con CSS Grid responsivo', 'Desarrollar un layout adaptable con áreas de grid y diseño mobile-first sin librerías externas.', 80, 1, 'facil', 1),
(103, 'Control de versiones: Flujo Gitflow y resolución de merge conflicts', 'Crear ramas de feature, simular un conflicto de fusión en código PHP y resolverlo con merge commits.', 120, 1, 'media', 1),
(104, 'Implementar autenticación y hash seguro de contraseñas', 'Construir el sistema de login con password_verify y regeneración del session ID tras éxito.', 250, 2, 'media', 1),
(105, 'Diseño de capa de persistencia relacional con PDO y Singleton', 'Crear una clase de conexión segura con DSN, atributos obligatorios y captura de PDOException.', 220, 2, 'media', 1),
(106, 'Paginación de datos eficiente mediante LIMIT y OFFSET en MariaDB', 'Optimizar consultas preparadas para catalogar registros con conteo total de tuplas.', 180, 2, 'media', 1),
(107, 'Auditoría y mitigación de vulnerabilidades SQL Injection', 'Refactorizar consultas concatenadas a sentencias preparadas con parámetros nombrados en PDO.', 300, 3, 'media', 1),
(108, 'Arquitectura de control de acceso basada en roles (RBAC)', 'Desarrollar guards estrictos en servidor para diferenciar accesos de Junior, Senior y Manager.', 350, 3, 'dificil', 1),
(109, 'Gestión de transacciones ACID en operaciones concurrentes', 'Implementar transacciones atómicas con beginTransaction, commit y rollBack ante excepciones.', 400, 4, 'dificil', 1),
(110, 'Contenerización de pila LAMP con Docker Compose', 'Crear un entorno reproducible con contenedores aislados de PHP 8.2-FPM, Nginx y MariaDB.', 450, 4, 'dificil', 1),
(111, 'Desarrollo de API RESTful con tokens de autenticación Bearer', 'Construir endpoints CRUD desacoplados con respuestas JSON tipadas y validación de cabeceras.', 500, 5, 'dificil', 1),
(112, 'Optimización de consultas y resolución del problema N+1', 'Auditar consultas SQL lentas, añadir índices relacionales y reestructurar JOINs optimizados.', 420, 5, 'dificil', 1);

-- 4. Asignación de habilidades a tareas (tarea_skill)
INSERT INTO `tarea_skill` (`tarea_id`, `skill_id`) VALUES
(101, 1), (101, 2), -- 101: PHP, HTML5
(102, 3),          -- 102: CSS
(103, 4),          -- 103: Git
(104, 1), (104, 5), -- 104: PHP, OWASP
(105, 1), (105, 6), -- 105: PHP, MySQL
(106, 1), (106, 6), -- 106: PHP, MySQL
(107, 1), (107, 5), (107, 6), -- 107: PHP, OWASP, MySQL
(108, 1), (108, 5), -- 108: PHP, OWASP
(109, 1), (109, 6), -- 109: PHP, MySQL
(110, 4), (110, 7), -- 110: Git, Docker
(111, 1), (111, 5), -- 111: PHP, OWASP
(112, 1), (112, 6); -- 112: PHP, MySQL

-- 5. Historial inicial de progreso (Carlos Senior ha completado tareas que justifican sus 500 XP)
INSERT INTO `progreso_tareas` (`usuario_id`, `tarea_id`, `puntos_obtenidos`, `completada_at`) VALUES
(2, 101, 100, DATE_SUB(NOW(), INTERVAL 5 DAY)),
(2, 104, 250, DATE_SUB(NOW(), INTERVAL 3 DAY)),
(2, 106, 150, DATE_SUB(NOW(), INTERVAL 1 DAY));
