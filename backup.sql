-- ============================================
-- BASE DE DATOS COMPLETA PARA PRÁCTICAS 1 Y 2
-- Basado en backup.sql + modificaciones requeridas
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
-- TABLA EJECUTIVO (igual que backup.sql)
-- ============================================

DROP TABLE IF EXISTS `ejecutivo`;
CREATE TABLE `ejecutivo` (
  `id_eje` int(10) UNSIGNED NOT NULL,
  `nom_eje` varchar(100) NOT NULL,
  `tel_eje` varchar(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `ejecutivo` (`id_eje`, `nom_eje`, `tel_eje`) VALUES
(1, 'Juan Carlos Pérez', '555-0123'),
(2, 'María Fernanda López', '555-0456'),
(3, 'Roberto González', '555-0789'),
(4, 'Francisco Pineda', '555-0789');

-- ============================================
-- TABLA CITA (modificada con orden correcto)
-- Orden final: id_cit, cit_cit, hor_cit, nom_cit, tel_cit
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

-- Insertar datos prueba
INSERT INTO `cita` (`id_cit`, `nom_cit`, `id_eje2`, `cit_cit`, `hor_cit`, `tel_cit`) VALUES
(1, 'Ana García Silva', 1, CURDATE(), '09:00:00', '555-1001'),
(2, 'Carlos Rodríguez', 2, CURDATE(), '10:30:00', '555-1002'),
(3, 'Laura Martínez', 1, CURDATE(), '14:15:00', '555-1003'),
(4, 'Pedro Sánchez', 3, CURDATE(), '16:45:00', '555-1004');

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
  MODIFY `id_cit` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

-- Constraints (Foreign Keys)
ALTER TABLE `cita`
  ADD CONSTRAINT `cita_ibfk_1` FOREIGN KEY (`id_eje2`) REFERENCES `ejecutivo` (`id_eje`);

-- Verificar estructuras creadas
SHOW TABLES;
DESC ejecutivo;
DESC cita;

-- ============================================
-- DATOS DE EJEMPLO ADICIONALES PARA TESTING
-- ============================================

-- Insertar más citas de ejemplo 
INSERT INTO `cita` (`nom_cit`, `tel_cit`, `hor_cit`, `cit_cit`, `id_eje2`) VALUES 
('Juan Pérez González', '555-1234', '09:30:00', CURDATE(), 1),
('María García Ruiz', '555-5678', '11:45:00', CURDATE(), 2),
('Carlos Ruiz Mendoza', '555-9012', '13:15:00', CURDATE(), 3),
('Ana López Silva', '555-3456', '15:30:00', CURDATE(), 1),
('Roberto Díaz Torres', '555-7890', '17:00:00', CURDATE(), 2),
('Elena Morales Castro', '555-2468', '08:15:00', DATE_ADD(CURDATE(), INTERVAL 1 DAY), 3),
('Fernando Vargas León', '555-1357', '10:00:00', DATE_ADD(CURDATE(), INTERVAL 1 DAY), 4),
('Patricia Herrera Vega', '555-9753', '12:30:00', DATE_ADD(CURDATE(), INTERVAL 1 DAY), 1);

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
