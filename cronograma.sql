-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 01-06-2025 a las 20:52:39
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
-- Base de datos: `cronograma`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `diasnohabiles`
--

CREATE TABLE `diasnohabiles` (
  `id_dia` int(11) NOT NULL,
  `id_semestre` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `descripcion` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `diasnohabiles`
--

INSERT INTO `diasnohabiles` (`id_dia`, `id_semestre`, `fecha`, `descripcion`) VALUES
(1, 1, '2025-02-03', 'Dia de la Constitución '),
(2, 1, '2025-03-17', 'Natalicio de Benito Juarez'),
(3, 1, '2025-05-01', 'Dia del trabajo'),
(4, 1, '2025-05-05', 'Batalla de Puebla'),
(5, 1, '2025-05-15', 'Dia del maestro'),
(6, 2, '2025-09-16', 'Dia de la Independencia'),
(7, 2, '2025-11-20', 'Revolución Mexicana'),
(8, 2, '2025-11-03', 'Dia de muertos');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `disponibilidad`
--

CREATE TABLE `disponibilidad` (
  `id_disponibilidad` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  `id_tema` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `disponibilidad`
--

INSERT INTO `disponibilidad` (`id_disponibilidad`, `id_usuario`, `fecha`, `hora_inicio`, `hora_fin`, `id_tema`) VALUES
(1, 1, '2025-01-13', '10:00:00', '11:00:00', 1),
(2, 1, '2025-01-14', '10:00:00', '11:00:00', 1),
(3, 1, '2025-01-15', '10:00:00', '11:00:00', 1),
(4, 1, '2025-01-16', '10:00:00', '11:00:00', 2),
(5, 1, '2025-01-17', '10:00:00', '11:00:00', 2),
(6, 1, '2025-01-20', '10:00:00', '11:00:00', 2),
(7, 1, '2025-01-21', '10:00:00', '11:00:00', 3),
(8, 1, '2025-01-22', '10:00:00', '11:00:00', 3),
(9, 1, '2025-01-23', '10:00:00', '11:00:00', 4),
(10, 1, '2025-01-24', '10:00:00', '11:00:00', 4),
(11, 1, '2025-01-27', '10:00:00', '11:00:00', 5),
(12, 1, '2025-01-28', '10:00:00', '11:00:00', 5),
(13, 1, '2025-01-29', '10:00:00', '11:00:00', 5),
(14, 1, '2025-01-30', '10:00:00', '11:00:00', 6),
(15, 1, '2025-01-31', '10:00:00', '11:00:00', 6),
(16, 1, '2025-02-03', '10:00:00', '11:00:00', 6),
(17, 1, '2025-02-04', '10:00:00', '11:00:00', 7),
(18, 1, '2025-02-05', '10:00:00', '11:00:00', 7),
(19, 1, '2025-02-06', '10:00:00', '11:00:00', 8),
(20, 1, '2025-02-07', '10:00:00', '11:00:00', 8),
(21, 1, '2025-02-10', '10:00:00', '11:00:00', 8),
(22, 1, '2025-02-11', '10:00:00', '11:00:00', 9),
(23, 1, '2025-02-12', '10:00:00', '11:00:00', 9),
(24, 1, '2025-02-13', '10:00:00', '11:00:00', 10),
(25, 1, '2025-02-14', '10:00:00', '11:00:00', 10),
(26, 1, '2025-02-17', '10:00:00', '11:00:00', 10),
(27, 1, '2025-02-18', '10:00:00', '11:00:00', 11),
(28, 1, '2025-02-19', '10:00:00', '11:00:00', 11),
(29, 1, '2025-02-20', '10:00:00', '11:00:00', 11),
(30, 1, '2025-02-21', '10:00:00', '11:00:00', 12),
(31, 1, '2025-02-24', '10:00:00', '11:00:00', 12),
(32, 1, '2025-02-25', '10:00:00', '11:00:00', 12),
(33, 1, '2025-02-26', '10:00:00', '11:00:00', 13),
(36, 1, '2025-02-27', '10:00:00', '11:00:00', 13),
(37, 1, '2025-02-28', '10:00:00', '11:00:00', 14),
(38, 1, '2025-03-03', '10:00:00', '11:00:00', 14),
(39, 1, '2025-03-04', '10:00:00', '11:00:00', 14),
(40, 1, '2025-03-05', '10:00:00', '11:00:00', 15),
(41, 1, '2025-03-06', '10:00:00', '11:00:00', 15),
(42, 1, '2025-03-07', '10:00:00', '11:00:00', 15),
(43, 1, '2025-03-10', '10:00:00', '11:00:00', 16),
(44, 1, '2025-03-11', '10:00:00', '11:00:00', 16),
(45, 1, '2025-03-12', '10:00:00', '11:00:00', 16),
(46, 1, '2025-03-13', '10:00:00', '11:00:00', 17),
(47, 1, '2025-03-14', '10:00:00', '11:00:00', 17),
(49, 1, '2025-03-17', '10:00:00', '11:00:00', 17),
(50, 1, '2025-03-18', '10:00:00', '11:00:00', 18),
(51, 1, '2025-03-19', '10:00:00', '11:00:00', 18),
(52, 1, '2025-03-20', '10:00:00', '11:00:00', 19),
(53, 1, '2025-03-21', '10:00:00', '11:00:00', 19),
(54, 1, '2025-03-24', '10:00:00', '11:00:00', 19),
(55, 1, '2025-03-25', '10:00:00', '11:00:00', 20),
(56, 1, '2025-03-26', '10:00:00', '11:00:00', 20),
(57, 1, '2025-03-27', '10:00:00', '11:00:00', 21),
(58, 1, '2025-03-28', '10:00:00', '11:00:00', 21),
(59, 1, '2025-04-01', '10:00:00', '11:00:00', 22),
(60, 1, '2025-04-02', '10:00:00', '11:00:00', 22),
(61, 1, '2025-04-03', '10:00:00', '11:00:00', 22),
(62, 1, '2025-04-04', '10:00:00', '11:00:00', 23),
(63, 1, '2025-04-07', '10:00:00', '11:00:00', 23),
(64, 1, '2025-05-05', '10:00:00', '11:00:00', 24),
(65, 1, '2025-05-06', '10:00:00', '11:00:00', 24),
(66, 1, '2025-05-07', '10:00:00', '11:00:00', 25),
(67, 1, '2025-05-08', '10:00:00', '11:00:00', 25);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `dosificacion`
--

CREATE TABLE `dosificacion` (
  `id_dosificacion` int(11) NOT NULL,
  `id_tema` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `horas_planeadas` decimal(4,2) DEFAULT NULL,
  `horas_asignadas` decimal(4,2) DEFAULT NULL,
  `motivo_reduccion` enum('dia_no_habil','evaluacion','vacaciones') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `dosificacion`
--

INSERT INTO `dosificacion` (`id_dosificacion`, `id_tema`, `fecha`, `horas_planeadas`, `horas_asignadas`, `motivo_reduccion`) VALUES
(279, 1, '2025-01-13', 1.00, 1.00, NULL),
(280, 1, '2025-01-14', 1.00, 1.00, NULL),
(281, 1, '2025-01-15', 1.00, 1.00, NULL),
(282, 2, '2025-01-16', 1.00, 1.00, NULL),
(283, 2, '2025-01-17', 1.00, 1.00, NULL),
(284, 2, '2025-01-20', 1.00, 1.00, NULL),
(285, 3, '2025-01-21', 1.00, 1.00, NULL),
(286, 3, '2025-01-22', 1.00, 1.00, NULL),
(287, 4, '2025-01-23', 1.00, 1.00, NULL),
(288, 4, '2025-01-24', 1.00, 1.00, NULL),
(289, 5, '2025-01-27', 1.00, 1.00, NULL),
(290, 5, '2025-01-28', 1.00, 1.00, NULL),
(291, 5, '2025-01-29', 1.00, 0.00, 'evaluacion'),
(292, 6, '2025-01-30', 1.00, 1.00, NULL),
(293, 6, '2025-01-31', 1.00, 1.00, NULL),
(294, 6, '2025-02-03', 1.00, 0.00, 'dia_no_habil'),
(295, 7, '2025-02-04', 1.00, 1.00, NULL),
(296, 7, '2025-02-05', 1.00, 1.00, NULL),
(297, 8, '2025-02-06', 1.00, 1.00, NULL),
(298, 8, '2025-02-07', 1.00, 1.00, NULL),
(299, 8, '2025-02-10', 1.00, 1.00, NULL),
(300, 9, '2025-02-11', 1.00, 1.00, NULL),
(301, 9, '2025-02-12', 1.00, 1.00, NULL),
(302, 10, '2025-02-13', 1.00, 1.00, NULL),
(303, 10, '2025-02-14', 1.00, 1.00, NULL),
(304, 10, '2025-02-17', 1.00, 1.00, NULL),
(305, 11, '2025-02-18', 1.00, 1.00, NULL),
(306, 11, '2025-02-19', 1.00, 1.00, NULL),
(307, 11, '2025-02-20', 1.00, 1.00, NULL),
(308, 12, '2025-02-21', 1.00, 1.00, NULL),
(309, 12, '2025-02-24', 1.00, 1.00, NULL),
(310, 12, '2025-02-25', 1.00, 1.00, NULL),
(311, 13, '2025-02-26', 1.00, 0.00, 'evaluacion'),
(312, 13, '2025-02-27', 1.00, 1.00, NULL),
(313, 14, '2025-02-28', 1.00, 1.00, NULL),
(314, 14, '2025-03-03', 1.00, 1.00, NULL),
(315, 14, '2025-03-04', 1.00, 1.00, NULL),
(316, 15, '2025-03-05', 1.00, 1.00, NULL),
(317, 15, '2025-03-06', 1.00, 1.00, NULL),
(318, 15, '2025-03-07', 1.00, 1.00, NULL),
(319, 16, '2025-03-10', 1.00, 1.00, NULL),
(320, 16, '2025-03-11', 1.00, 1.00, NULL),
(321, 16, '2025-03-12', 1.00, 1.00, NULL),
(322, 17, '2025-03-13', 1.00, 1.00, NULL),
(323, 17, '2025-03-14', 1.00, 1.00, NULL),
(324, 17, '2025-03-17', 1.00, 0.00, 'dia_no_habil'),
(325, 18, '2025-03-18', 1.00, 1.00, NULL),
(326, 18, '2025-03-19', 1.00, 1.00, NULL),
(327, 19, '2025-03-20', 1.00, 1.00, NULL),
(328, 19, '2025-03-21', 1.00, 1.00, NULL),
(329, 19, '2025-03-24', 1.00, 1.00, NULL),
(330, 20, '2025-03-25', 1.00, 1.00, NULL),
(331, 20, '2025-03-26', 1.00, 1.00, NULL),
(332, 21, '2025-03-27', 1.00, 0.00, 'evaluacion'),
(333, 21, '2025-03-28', 1.00, 1.00, NULL),
(334, 22, '2025-04-01', 1.00, 1.00, NULL),
(335, 22, '2025-04-02', 1.00, 1.00, NULL),
(336, 22, '2025-04-03', 1.00, 1.00, NULL),
(337, 23, '2025-04-04', 1.00, 1.00, NULL),
(338, 23, '2025-04-07', 1.00, 1.00, NULL),
(339, 24, '2025-05-05', 1.00, 0.00, 'dia_no_habil'),
(340, 24, '2025-05-06', 1.00, 1.00, NULL),
(341, 25, '2025-05-07', 1.00, 1.00, NULL),
(342, 25, '2025-05-08', 1.00, 1.00, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `evaluaciones`
--

CREATE TABLE `evaluaciones` (
  `id_evaluacion` int(11) NOT NULL,
  `id_unidad` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `fecha_evaluacion` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `evaluaciones`
--

INSERT INTO `evaluaciones` (`id_evaluacion`, `id_unidad`, `id_usuario`, `fecha_evaluacion`) VALUES
(1, 1, 1, '2025-01-29'),
(2, 2, 1, '2025-02-26'),
(3, 3, 1, '2025-02-26'),
(4, 4, 1, '2025-03-27'),
(5, 5, 1, '2025-03-27'),
(6, 6, 1, '2025-05-15');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `planificacionusuario`
--

CREATE TABLE `planificacionusuario` (
  `id_planificacion` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_tema` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `horas_planeadas` decimal(4,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `planificacionusuario`
--

INSERT INTO `planificacionusuario` (`id_planificacion`, `id_usuario`, `id_tema`, `fecha`, `horas_planeadas`) VALUES
(1, 1, 1, '2025-05-28', 3.00),
(2, 1, 2, '2025-05-28', 3.00),
(3, 1, 3, '2025-05-28', 2.00),
(4, 1, 4, '2025-05-28', 2.00),
(5, 1, 5, '2025-05-28', 3.00),
(6, 1, 6, '2025-05-28', 3.00),
(7, 1, 7, '2025-05-28', 2.00),
(8, 1, 8, '2025-05-28', 3.00),
(9, 1, 9, '2025-05-28', 2.00),
(10, 1, 10, '2025-05-28', 3.00),
(11, 1, 11, '2025-05-28', 3.00),
(12, 1, 12, '2025-05-28', 3.00),
(13, 1, 13, '2025-05-28', 2.00),
(14, 1, 14, '2025-05-28', 3.00),
(15, 1, 15, '2025-05-28', 3.00),
(16, 1, 16, '2025-05-28', 3.00),
(17, 1, 17, '2025-05-28', 3.00),
(18, 1, 18, '2025-05-28', 2.00),
(19, 1, 19, '2025-05-28', 3.00),
(20, 1, 20, '2025-05-28', 2.00),
(21, 1, 21, '2025-05-28', 2.00),
(22, 1, 22, '2025-05-28', 3.00),
(23, 1, 23, '2025-05-28', 2.00),
(24, 1, 24, '2025-05-28', 2.00),
(25, 1, 25, '2025-05-28', 2.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `programas`
--

CREATE TABLE `programas` (
  `id_programa` int(11) NOT NULL,
  `id_semestre` int(11) NOT NULL,
  `nombre_materia` varchar(100) NOT NULL,
  `horas_teoricas` int(11) DEFAULT NULL,
  `horas_practicas` int(11) DEFAULT NULL,
  `id_admin` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `programas`
--

INSERT INTO `programas` (`id_programa`, `id_semestre`, `nombre_materia`, `horas_teoricas`, `horas_practicas`, `id_admin`) VALUES
(1, 1, 'Sistemas Programables', 32, 32, 2);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `semestres`
--

CREATE TABLE `semestres` (
  `id_semestre` int(11) NOT NULL,
  `nombre` varchar(50) DEFAULT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `id_admin` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `semestres`
--

INSERT INTO `semestres` (`id_semestre`, `nombre`, `fecha_inicio`, `fecha_fin`, `id_admin`) VALUES
(1, 'Enero/Junio', '2025-01-13', '2025-05-30', 2),
(2, 'Agosto/Diciembre', '2025-08-25', '2025-12-08', 2);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `temas`
--

CREATE TABLE `temas` (
  `id_tema` int(11) NOT NULL,
  `id_unidad` int(11) NOT NULL,
  `nombre_tema` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `temas`
--

INSERT INTO `temas` (`id_tema`, `id_unidad`, `nombre_tema`) VALUES
(1, 1, '1.1 Ópticos'),
(2, 1, '1.2 Temperatura'),
(3, 1, '1.3 Presión'),
(4, 1, '1.4 Proximidad'),
(5, 2, '2.1 Eléctricos'),
(6, 2, '2.2 Mecánicos'),
(7, 2, '2.3 Hidráulicos'),
(8, 3, '3.1 Características generales'),
(9, 3, '3.2 Circuitería alternativa para entrada/salida'),
(10, 4, '4.1 Modelo de programación'),
(11, 4, '4.2 Estructura de los registros del CPU'),
(12, 4, '4.3 Modos de direccionamiento'),
(13, 4, '4.4 Conjunto de instrucciones'),
(14, 4, '4.5 Lenguajes ensambladores'),
(15, 4, '4.6 Codificación'),
(16, 5, '5.1 Tipos de puertos'),
(17, 5, '5.2 Programación de puertos'),
(18, 5, '5.3 Aplicaciones de puertos'),
(19, 5, '5.4 Estándares de buses'),
(20, 5, '5.5 Manejo del bus'),
(21, 5, '5.6 Aplicaciones de buses'),
(22, 5, '5.7 Comunicación'),
(23, 6, '6.1 Conceptos básicos y clasificación'),
(24, 6, '6.2 Módulos de adquisición de datos'),
(25, 6, '6.3 Diseño y aplicación de interfaces');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `unidades`
--

CREATE TABLE `unidades` (
  `id_unidad` int(11) NOT NULL,
  `id_programa` int(11) NOT NULL,
  `nombre_unidad` varchar(100) DEFAULT NULL,
  `numero_unidad` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `unidades`
--

INSERT INTO `unidades` (`id_unidad`, `id_programa`, `nombre_unidad`, `numero_unidad`) VALUES
(1, 1, 'Unidad 1', 1),
(2, 1, 'Unidad 2', 2),
(3, 1, 'Unidad 3', 3),
(4, 1, 'Unidad 4', 4),
(5, 1, 'Unidad 5', 5),
(6, 1, 'Unidad 6', 6);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `contraseña` varchar(255) NOT NULL,
  `rol` enum('admin','alumno') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `nombre`, `contraseña`, `rol`) VALUES
(1, 'eric', '$2y$10$yRgG/yUhLbRw0Vdd5aw0desf0PUfWqaxGLKh.1j6MdOcAW.P9z3ie', 'alumno'),
(2, 'jazmin', '$2y$10$0dJY9SvIepZrYxWdZrMyGe3C3UH69uJG.rn9deFNOGgM7pfOdKUUK', 'admin');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vacaciones`
--

CREATE TABLE `vacaciones` (
  `id_lapso` int(11) NOT NULL,
  `id_semestre` int(11) NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `descripcion` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `vacaciones`
--

INSERT INTO `vacaciones` (`id_lapso`, `id_semestre`, `fecha_inicio`, `fecha_fin`, `descripcion`) VALUES
(1, 1, '2025-04-14', '2025-04-25', 'Vacaciones de Semena Santa');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `diasnohabiles`
--
ALTER TABLE `diasnohabiles`
  ADD PRIMARY KEY (`id_dia`),
  ADD KEY `id_semestre` (`id_semestre`);

--
-- Indices de la tabla `disponibilidad`
--
ALTER TABLE `disponibilidad`
  ADD PRIMARY KEY (`id_disponibilidad`),
  ADD KEY `id_tema` (`id_tema`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `dosificacion`
--
ALTER TABLE `dosificacion`
  ADD PRIMARY KEY (`id_dosificacion`),
  ADD KEY `id_tema` (`id_tema`);

--
-- Indices de la tabla `evaluaciones`
--
ALTER TABLE `evaluaciones`
  ADD PRIMARY KEY (`id_evaluacion`),
  ADD KEY `id_unidad` (`id_unidad`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `planificacionusuario`
--
ALTER TABLE `planificacionusuario`
  ADD PRIMARY KEY (`id_planificacion`),
  ADD KEY `id_usuario` (`id_usuario`),
  ADD KEY `id_tema` (`id_tema`);

--
-- Indices de la tabla `programas`
--
ALTER TABLE `programas`
  ADD PRIMARY KEY (`id_programa`),
  ADD KEY `id_semestre` (`id_semestre`),
  ADD KEY `id_admin` (`id_admin`);

--
-- Indices de la tabla `semestres`
--
ALTER TABLE `semestres`
  ADD PRIMARY KEY (`id_semestre`),
  ADD KEY `id_admin` (`id_admin`);

--
-- Indices de la tabla `temas`
--
ALTER TABLE `temas`
  ADD PRIMARY KEY (`id_tema`),
  ADD KEY `id_unidad` (`id_unidad`);

--
-- Indices de la tabla `unidades`
--
ALTER TABLE `unidades`
  ADD PRIMARY KEY (`id_unidad`),
  ADD KEY `id_programa` (`id_programa`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`);

--
-- Indices de la tabla `vacaciones`
--
ALTER TABLE `vacaciones`
  ADD PRIMARY KEY (`id_lapso`),
  ADD KEY `id_semestre` (`id_semestre`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `diasnohabiles`
--
ALTER TABLE `diasnohabiles`
  MODIFY `id_dia` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `disponibilidad`
--
ALTER TABLE `disponibilidad`
  MODIFY `id_disponibilidad` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;

--
-- AUTO_INCREMENT de la tabla `dosificacion`
--
ALTER TABLE `dosificacion`
  MODIFY `id_dosificacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=343;

--
-- AUTO_INCREMENT de la tabla `evaluaciones`
--
ALTER TABLE `evaluaciones`
  MODIFY `id_evaluacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `planificacionusuario`
--
ALTER TABLE `planificacionusuario`
  MODIFY `id_planificacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT de la tabla `programas`
--
ALTER TABLE `programas`
  MODIFY `id_programa` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `semestres`
--
ALTER TABLE `semestres`
  MODIFY `id_semestre` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `temas`
--
ALTER TABLE `temas`
  MODIFY `id_tema` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT de la tabla `unidades`
--
ALTER TABLE `unidades`
  MODIFY `id_unidad` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `vacaciones`
--
ALTER TABLE `vacaciones`
  MODIFY `id_lapso` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `diasnohabiles`
--
ALTER TABLE `diasnohabiles`
  ADD CONSTRAINT `diasnohabiles_ibfk_1` FOREIGN KEY (`id_semestre`) REFERENCES `semestres` (`id_semestre`);

--
-- Filtros para la tabla `disponibilidad`
--
ALTER TABLE `disponibilidad`
  ADD CONSTRAINT `disponibilidad_ibfk_1` FOREIGN KEY (`id_tema`) REFERENCES `temas` (`id_tema`),
  ADD CONSTRAINT `disponibilidad_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `dosificacion`
--
ALTER TABLE `dosificacion`
  ADD CONSTRAINT `dosificacion_ibfk_1` FOREIGN KEY (`id_tema`) REFERENCES `temas` (`id_tema`);

--
-- Filtros para la tabla `evaluaciones`
--
ALTER TABLE `evaluaciones`
  ADD CONSTRAINT `evaluaciones_ibfk_1` FOREIGN KEY (`id_unidad`) REFERENCES `unidades` (`id_unidad`),
  ADD CONSTRAINT `evaluaciones_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `planificacionusuario`
--
ALTER TABLE `planificacionusuario`
  ADD CONSTRAINT `planificacionusuario_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`),
  ADD CONSTRAINT `planificacionusuario_ibfk_2` FOREIGN KEY (`id_tema`) REFERENCES `temas` (`id_tema`);

--
-- Filtros para la tabla `programas`
--
ALTER TABLE `programas`
  ADD CONSTRAINT `programas_ibfk_1` FOREIGN KEY (`id_semestre`) REFERENCES `semestres` (`id_semestre`),
  ADD CONSTRAINT `programas_ibfk_2` FOREIGN KEY (`id_admin`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `semestres`
--
ALTER TABLE `semestres`
  ADD CONSTRAINT `semestres_ibfk_1` FOREIGN KEY (`id_admin`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `temas`
--
ALTER TABLE `temas`
  ADD CONSTRAINT `temas_ibfk_1` FOREIGN KEY (`id_unidad`) REFERENCES `unidades` (`id_unidad`);

--
-- Filtros para la tabla `unidades`
--
ALTER TABLE `unidades`
  ADD CONSTRAINT `unidades_ibfk_1` FOREIGN KEY (`id_programa`) REFERENCES `programas` (`id_programa`);

--
-- Filtros para la tabla `vacaciones`
--
ALTER TABLE `vacaciones`
  ADD CONSTRAINT `vacaciones_ibfk_1` FOREIGN KEY (`id_semestre`) REFERENCES `semestres` (`id_semestre`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
