-- ============================================
-- PRÁCTICA 2: CITAS CON HORARIOS FIJOS
-- Base de datos completa para intervalos de horario
-- ============================================

-- Configuración inicial
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- Crear/usar base de datos
CREATE DATABASE IF NOT EXISTS `ahj_ende_pinedah` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `ahj_ende_pinedah`;

-- ============================================
-- TABLA EJECUTIVO
-- ============================================

DROP TABLE IF EXISTS `ejecutivo`;
CREATE TABLE `ejecutivo` (
  `id_eje` int(10) UNSIGNED NOT NULL,
  `nom_eje` varchar(100) NOT NULL,
  `tel_eje` varchar(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Datos de ejecutivos
INSERT INTO `ejecutivo` (`id_eje`, `nom_eje`, `tel_eje`) VALUES
(1, 'Juan Carlos Pérez', '555-0123'),
(2, 'María Fernanda López', '555-0456'),
(3, 'Roberto González', '555-0789'),
(4, 'Francisco Pineda', '555-0789');

-- ============================================
-- TABLA CITA - PRÁCTICA 2
-- Con columna fija de horarios 8AM-8PM
-- ============================================

DROP TABLE IF EXISTS `cita`;
CREATE TABLE `cita` (
  `id_cit` int(11) NOT NULL,
  `cit_cit` date NOT NULL DEFAULT (curdate()),
  `hor_cit` time NOT NULL DEFAULT '08:00:00',
  `nom_cit` varchar(100) NOT NULL,
  `tel_cit` varchar(15) NOT NULL DEFAULT '',
  `id_eje2` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Datos distribuidos en diferentes intervalos (8AM-8PM)
INSERT INTO `cita` (`id_cit`, `cit_cit`, `hor_cit`, `nom_cit`, `tel_cit`, `id_eje2`) VALUES
-- Intervalos de mañana
(1, CURDATE(), '08:30:00', 'Ana García Silva', '555-1001', 1),
(2, CURDATE(), '09:15:00', 'Carlos Rodríguez', '555-1002', 2),
(3, CURDATE(), '10:45:00', 'Laura Martínez', '555-1003', 1),
(4, CURDATE(), '11:20:00', 'Pedro Sánchez', '555-1004', 3),
-- Intervalos de tarde
(5, CURDATE(), '13:30:00', 'Juan Pérez González', '555-1234', 1),
(6, CURDATE(), '14:45:00', 'María García Ruiz', '555-5678', 2),
(7, CURDATE(), '15:15:00', 'Carlos Ruiz Mendoza', '555-9012', 3),
(8, CURDATE(), '16:30:00', 'Ana López Silva', '555-3456', 1),
(9, CURDATE(), '17:00:00', 'Roberto Díaz Torres', '555-7890', 2),
(10, CURDATE(), '18:30:00', 'Patricia Herrera Vega', '555-9753', 4),
-- Día siguiente con diferentes horarios
(11, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '08:15:00', 'Elena Morales Castro', '555-2468', 3),
(12, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '12:30:00', 'Fernando Vargas León', '555-1357', 4),
(13, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '19:45:00', 'Sofía Mendoza Ruiz', '555-8642', 2);

-- ============================================
-- ÍNDICES Y CONSTRAINTS
-- ============================================

-- Índices para tabla ejecutivo
ALTER TABLE `ejecutivo`
  ADD PRIMARY KEY (`id_eje`);

-- Índices para tabla cita
ALTER TABLE `cita`
  ADD PRIMARY KEY (`id_cit`),
  ADD KEY `id_eje2` (`id_eje2`);

-- AUTO_INCREMENT
ALTER TABLE `ejecutivo`
  MODIFY `id_eje` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

ALTER TABLE `cita`
  MODIFY `id_cit` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

-- Constraints (Foreign Keys)
ALTER TABLE `cita`
  ADD CONSTRAINT `cita_ibfk_1` FOREIGN KEY (`id_eje2`) REFERENCES `ejecutivo` (`id_eje`);

-- ============================================
-- TABLA PARA COLUMNAS DINÁMICAS (OPCIONAL)
-- ============================================

CREATE TABLE IF NOT EXISTS `columnas_dinamicas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_columna` varchar(100) NOT NULL,
  `posicion` int(11) DEFAULT 999,
  `tipo` varchar(50) DEFAULT 'text',
  `fecha_creacion` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre_columna` (`nombre_columna`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- ============================================
-- SCRIPT COMPLETADO - PRÁCTICA 2
-- ============================================
-- Base de datos: ahj_ende_pinedah
-- Tablas: ejecutivo, cita
-- Columna fija de horarios 8AM-8PM en intervalos de 1 hora
-- Encaje automático de citas en intervalos correspondientes
-- ============================================
