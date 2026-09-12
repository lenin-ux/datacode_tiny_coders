-- =====================================================================
-- DATACODE -- Script de ejemplo TODO EN UNO para probar el sistema
-- =====================================================================
-- Este archivo junta, en el orden correcto, los tres pasos que normalmente
-- se corren por separado:
--   1) la estructura original de la base de datos (tablas vacias, con
--      algunos catalogos base: roles, unidad regional, aulas, etc.)
--   2) la migracion que agrega el rol Docente y las columnas/tabla nuevas
--   3) datos de ejemplo (seed) que llenan el sistema para poder probarlo
--      de inmediato: usuarios de cada rol, talleres, torneos, hackathones,
--      eventos, propuestas en distintos estados del foro, comentarios,
--      votos e inscripciones.
--
-- COMO USARLO:
--   Crea una base de datos vacia (por ejemplo "datacode.db") en phpMyAdmin
--   o con tu cliente de MySQL preferido, y despues importa este archivo
--   completo de una sola vez. No hace falta correr nada mas.
--
--   Si ya tienes datos que quieres conservar, NO uses este archivo: en su
--   lugar corre database/migracion_v2_mvp.sql y luego
--   database/seed_datos_prueba.sql por separado, como se explica en el
--   README.
--
-- Cuentas de prueba una vez importado (ver tambien el README):
--   Coordinador ......... matricula 23060000  / contrasena Lcas0078
--   Alumno normal ........ matricula 26060002  / contrasena 2606AAAE
--   Docente .............. matricula 00060001  / contrasena 2706AADD
--   Depto. de Ingenieria . matricula 00060002  / contrasena 2706AADE
--   Jefatura de Academia . matricula 00060003  / contrasena 2706AADF
-- =====================================================================



-- =====================================================================
-- PASO 1: ESTRUCTURA ORIGINAL
-- =====================================================================

-- phpMyAdmin SQL Dump
-- version 4.9.1
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost
-- Tiempo de generación: 12-09-2026 a las 18:21:37
-- Versión del servidor: 8.0.17
-- Versión de PHP: 7.3.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `datacode.db`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `anuncioweb`
--

CREATE TABLE `anuncioweb` (
  `id_anuncioweb` int(11) NOT NULL,
  `rutaimagen_anuncionweb` varchar(255) NOT NULL,
  `eventos_id_evento` int(11) DEFAULT NULL,
  `torneos_id_torneo` int(11) DEFAULT NULL,
  `hackathones_id_hackaton` int(11) DEFAULT NULL,
  `talleres_id_taller` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asistenciahackatons`
--

CREATE TABLE `asistenciahackatons` (
  `id_asistenciahackaton` int(11) NOT NULL,
  `estado_asistenciataller` enum('Asistio','Falto','Justificado') NOT NULL,
  `asistenciashackathons_id_asistenciashackaton` int(11) NOT NULL,
  `num_asistenciahackaton` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asistenciashackathons`
--

CREATE TABLE `asistenciashackathons` (
  `id_asistenciashackaton` int(11) NOT NULL,
  `cartaalumno` char(1) NOT NULL,
  `cartatutor_hackaton` char(1) NOT NULL,
  `inscripcioneshackatons_id_inscripcioneshackaton` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asistenciatalleres`
--

CREATE TABLE `asistenciatalleres` (
  `id_asistenciataller` int(11) NOT NULL,
  `estado_asistenciataller` enum('Asistio','Falto','Justificado') NOT NULL,
  `inscripcionestalleres_id_inscripcionestaller` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `aulas`
--

CREATE TABLE `aulas` (
  `id_aula` int(11) NOT NULL,
  `nombre_aula` varchar(35) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `aulas`
--

INSERT INTO `aulas` (`id_aula`, `nombre_aula`) VALUES
(1, '51');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `comentarios`
--

CREATE TABLE `comentarios` (
  `id_comentario` int(11) NOT NULL,
  `texto_comentario` mediumtext NOT NULL,
  `propuestas_id_propuesta` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `disponibilidades`
--

CREATE TABLE `disponibilidades` (
  `id_disponiblidad` int(11) NOT NULL,
  `hora_disponibilidad` time NOT NULL,
  `duracion_disponibilidad` int(11) NOT NULL,
  `fecha_disponibilidad` date NOT NULL,
  `aulas_id_aula` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `disponibilidades`
--

INSERT INTO `disponibilidades` (`id_disponiblidad`, `hora_disponibilidad`, `duracion_disponibilidad`, `fecha_disponibilidad`, `aulas_id_aula`) VALUES
(1, '09:00:00', 120, '2027-09-11', 1),
(2, '09:00:00', 120, '2027-09-11', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `entregables`
--

CREATE TABLE `entregables` (
  `id_entregable` int(11) NOT NULL,
  `descripcion_entregable` mediumtext NOT NULL,
  `hackathones_id_hackaton` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `eventos`
--

CREATE TABLE `eventos` (
  `id_evento` int(11) NOT NULL,
  `nombre_evento` varchar(60) NOT NULL,
  `fecha_evento` date NOT NULL,
  `hora_evento` time NOT NULL,
  `duracion_evento` int(11) NOT NULL,
  `jornadas_id_jornada` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `grupos`
--

CREATE TABLE `grupos` (
  `id_grupo` int(11) NOT NULL,
  `turno_grupo` enum('Matutino','Vespertino') NOT NULL,
  `numero_grupo` int(11) NOT NULL,
  `semestre_grupo` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `grupos`
--

INSERT INTO `grupos` (`id_grupo`, `turno_grupo`, `numero_grupo`, `semestre_grupo`) VALUES
(1, 'Matutino', 1, 7),
(2, 'Matutino', 5, 1),
(3, 'Matutino', 5, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `hackathones`
--

CREATE TABLE `hackathones` (
  `id_hackaton` int(11) NOT NULL,
  `fechainicio_hackaton` date NOT NULL,
  `fechafinal_hackaton` date NOT NULL,
  `cuota_hackaton` decimal(5,2) DEFAULT NULL,
  `disponibilidades_id_disponiblidad` int(11) NOT NULL,
  `jornadas_id_jornada` int(11) NOT NULL,
  `numasistencia_hackathon` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inscripcioneshackatons`
--

CREATE TABLE `inscripcioneshackatons` (
  `id_inscripcioneshackaton` int(11) NOT NULL,
  `hackathones_id_hackaton` int(11) NOT NULL,
  `usuarios_id_usuario` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inscripcionestalleres`
--

CREATE TABLE `inscripcionestalleres` (
  `id_inscripcionestaller` int(11) NOT NULL,
  `talleres_id_taller` int(11) NOT NULL,
  `usuarios_id_usuario` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `inscripcionestalleres`
--

INSERT INTO `inscripcionestalleres` (`id_inscripcionestaller`, `talleres_id_taller`, `usuarios_id_usuario`) VALUES
(1, 1, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inscripcionestorneos`
--

CREATE TABLE `inscripcionestorneos` (
  `idinscripcionestorneo` int(11) NOT NULL,
  `torneos_id_torneo` int(11) NOT NULL,
  `usuarios_id_usuario` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `jornadas`
--

CREATE TABLE `jornadas` (
  `id_jornada` int(11) NOT NULL,
  `nombre_jornada` varchar(45) NOT NULL,
  `fecha_jornada` date NOT NULL,
  `unidadesregionales_id_ur` varchar(45) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `jornadas`
--

INSERT INTO `jornadas` (`id_jornada`, `nombre_jornada`, `fecha_jornada`, `unidadesregionales_id_ur`) VALUES
(1, '2027', '2027-09-11', '1');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `propuestas`
--

CREATE TABLE `propuestas` (
  `id_propuesta` int(11) NOT NULL,
  `titulo_propuesta` varchar(48) NOT NULL,
  `descripcion_propuesta` longtext NOT NULL,
  `aprobacion_propuesta` enum('sin visualizar','aprobado','no aprobado','a consideracion') NOT NULL DEFAULT 'sin visualizar',
  `tipo_propuesta` enum('Talleres','Torneos','Comite','Hackaton','General') NOT NULL,
  `usuarios_id_usuario` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `rol` varchar(32) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id`, `rol`) VALUES
(1, 'Alumno'),
(2, 'Comite'),
(3, 'Coordinación'),
(4, 'Departamento de Ingeniería'),
(5, 'Jefatura de Academia');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rubricas`
--

CREATE TABLE `rubricas` (
  `id_rubrica` int(11) NOT NULL,
  `criterio_rubrica` varchar(48) NOT NULL,
  `descripcion_rubrica` mediumtext NOT NULL,
  `puntaje_rubrica` int(2) NOT NULL,
  `hackathones_id_hackaton` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `talleres`
--

CREATE TABLE `talleres` (
  `id_taller` int(11) NOT NULL,
  `nombre_taller` varchar(45) NOT NULL,
  `materiales_taller` varchar(45) NOT NULL,
  `descripcion_taller` varchar(45) NOT NULL,
  `fecha_taller` date NOT NULL,
  `horainicio_taller` time NOT NULL,
  `horatermino_taller` time NOT NULL,
  `disponibilidades_id_disponiblidad` int(11) NOT NULL,
  `jornadas_id_jornada` int(11) NOT NULL,
  `usuarios_id_usuario` int(11) NOT NULL,
  `responsable_usuario_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `talleres`
--

INSERT INTO `talleres` (`id_taller`, `nombre_taller`, `materiales_taller`, `descripcion_taller`, `fecha_taller`, `horainicio_taller`, `horatermino_taller`, `disponibilidades_id_disponiblidad`, `jornadas_id_jornada`, `usuarios_id_usuario`, `responsable_usuario_id`) VALUES
(1, 'Instalación de Camaras de Video Vigilancia', 'Pinzas de corte, lentes', 'Instalación', '2027-09-11', '08:00:00', '12:00:00', 1, 1, 1, 1),
(2, 'El de Olga', 'pinturas', 'pintar', '2027-09-11', '06:00:00', '12:00:00', 1, 1, 1, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `torneos`
--

CREATE TABLE `torneos` (
  `id_torneo` int(11) NOT NULL,
  `formato_torneo` mediumtext NOT NULL,
  `reglas_torneo` mediumtext NOT NULL,
  `horarioinicio_torneo` time NOT NULL,
  `fechatorneo` date NOT NULL,
  `disponibilidades_id_disponiblidad` int(11) NOT NULL,
  `jornadas_id_jornada` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `unidadesregionales`
--

CREATE TABLE `unidadesregionales` (
  `id_ur` varchar(45) NOT NULL,
  `nombre_ur` varchar(24) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `unidadesregionales`
--

INSERT INTO `unidadesregionales` (`id_ur`, `nombre_ur`) VALUES
('1', 'Guamúchil');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL,
  `nombres_usuario` varchar(80) NOT NULL,
  `apellidos_usuario` varchar(80) NOT NULL,
  `matricula_usuario` char(8) NOT NULL,
  `habilitado_usuario` char(1) NOT NULL,
  `unidadesregionales_id_ur` varchar(45) NOT NULL,
  `roles_id` int(11) NOT NULL,
  `grupos_id_grupo` int(11) NOT NULL,
  `password_usuario` char(8) NOT NULL,
  `rutaimagen_usuario` varchar(255) NOT NULL,
  `correo_usuario` varchar(120) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `nombres_usuario`, `apellidos_usuario`, `matricula_usuario`, `habilitado_usuario`, `unidadesregionales_id_ur`, `roles_id`, `grupos_id_grupo`, `password_usuario`, `rutaimagen_usuario`, `correo_usuario`) VALUES
(1, 'Lenin', 'Castro', '23060078', '1', '1', 1, 1, 'Lcas0078', '', NULL),
(2, 'Juan', 'Hernandez', '23060000', '1', '1', 3, 1, 'Lcas0078', '', NULL),
(3, 'Juan Pablo', 'Castro Perez', '26060001', '1', '1', 1, 1, 'Jper', '', NULL),
(4, 'María', 'Hernández', '26060002', '1', '1', 1, 2, '2606AAAE', '', 'maria.hernandez2@alumnos.datacode.mx'),
(5, 'Carlos', 'López', '26060003', '1', '1', 1, 2, '2606AAAF', '', 'carlos.lopez3@alumnos.datacode.mx'),
(6, 'Ana', 'Martínez', '26060004', '1', '1', 1, 2, '2606AAAG', '', 'ana.martinez4@alumnos.datacode.mx'),
(7, 'Luis', 'Rodríguez', '26060005', '1', '1', 1, 2, '2606AAAH', '', 'luis.rodriguez5@alumnos.datacode.mx'),
(8, 'Sofía', 'Pérez', '26060006', '1', '1', 1, 2, '2606AAAI', '', 'sofia.perez6@alumnos.datacode.mx'),
(9, 'Diego', 'Sánchez', '26060007', '1', '1', 1, 2, '2606AAAJ', '', 'diego.sanchez7@alumnos.datacode.mx'),
(10, 'Valeria', 'Ramírez', '26060008', '1', '1', 1, 2, '2606AABA', '', 'valeria.ramirez8@alumnos.datacode.mx'),
(11, 'Jorge', 'Torres', '26060009', '1', '1', 1, 2, '2606AABB', '', 'jorge.torres9@alumnos.datacode.mx'),
(12, 'Camila', 'Flores', '26060010', '1', '1', 1, 2, '2606AABC', '', 'camila.flores10@alumnos.datacode.mx'),
(13, 'Fernando', 'Rivera', '26060011', '1', '1', 1, 2, '2606AABD', '', 'fernando.rivera11@alumnos.datacode.mx'),
(14, 'Paola', 'Gómez', '26060012', '1', '1', 1, 2, '2606AABE', '', 'paola.gomez12@alumnos.datacode.mx'),
(15, 'Ricardo', 'Díaz', '26060013', '1', '1', 1, 2, '2606AABF', '', 'ricardo.diaz13@alumnos.datacode.mx'),
(16, 'Daniela', 'Cruz', '26060014', '1', '1', 1, 2, '2606AABG', '', 'daniela.cruz14@alumnos.datacode.mx'),
(17, 'Miguel', 'Morales', '26060015', '1', '1', 1, 2, '2606AABH', '', 'miguel.morales15@alumnos.datacode.mx'),
(18, 'Andrea', 'Reyes', '26060016', '1', '1', 1, 2, '2606AABI', '', 'andrea.reyes16@alumnos.datacode.mx'),
(19, 'Alejandro', 'Jiménez', '26060017', '1', '1', 1, 2, '2606AABJ', '', 'alejandro.jimenez17@alumnos.datacode.mx'),
(20, 'Fernanda', 'Ortiz', '26060018', '1', '1', 1, 2, '2606AACA', '', 'fernanda.ortiz18@alumnos.datacode.mx'),
(21, 'Emilio', 'Gutiérrez', '26060019', '1', '1', 1, 2, '2606AACB', '', 'emilio.gutierrez19@alumnos.datacode.mx'),
(22, 'Regina', 'Vázquez', '26060020', '1', '1', 1, 2, '2606AACC', '', 'regina.vazquez20@alumnos.datacode.mx'),
(23, 'Iván', 'Castillo', '26060021', '1', '1', 1, 2, '2606AACD', '', 'ivan.castillo21@alumnos.datacode.mx'),
(24, 'Ximena', 'Romero', '26060022', '1', '1', 1, 2, '2606AACE', '', 'ximena.romero22@alumnos.datacode.mx'),
(25, 'Sergio', 'Ruiz', '26060023', '1', '1', 1, 2, '2606AACF', '', 'sergio.ruiz23@alumnos.datacode.mx'),
(26, 'Mariana', 'Alvarez', '26060024', '1', '1', 1, 2, '2606AACG', '', 'mariana.alvarez24@alumnos.datacode.mx'),
(27, 'Raúl', 'Mendoza', '26060025', '1', '1', 1, 2, '2606AACH', '', 'raul.mendoza25@alumnos.datacode.mx'),
(28, 'Karla', 'Vargas', '26060026', '1', '1', 1, 2, '2606AACI', '', 'karla.vargas26@alumnos.datacode.mx'),
(29, 'Oscar', 'Castro', '26060027', '1', '1', 1, 2, '2606AACJ', '', 'oscar.castro27@alumnos.datacode.mx'),
(30, 'Lucía', 'Ortega', '26060028', '1', '1', 1, 2, '2606AADA', '', 'lucia.ortega28@alumnos.datacode.mx'),
(31, 'Adrián', 'Delgado', '26060029', '1', '1', 1, 2, '2606AADB', '', 'adrian.delgado29@alumnos.datacode.mx'),
(32, 'Isabel', 'Guerrero', '26060030', '1', '1', 1, 2, '2606AADC', '', 'isabel.guerrero30@alumnos.datacode.mx');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `votos`
--

CREATE TABLE `votos` (
  `id_voto` int(11) NOT NULL,
  `propuestas_id_propuesta` int(11) NOT NULL,
  `usuarios_id_usuario` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `anuncioweb`
--
ALTER TABLE `anuncioweb`
  ADD PRIMARY KEY (`id_anuncioweb`),
  ADD KEY `fk_anuncioweb_eventos1` (`eventos_id_evento`),
  ADD KEY `fk_anuncioweb_torneos1` (`torneos_id_torneo`),
  ADD KEY `fk_anuncioweb_hackathones1` (`hackathones_id_hackaton`),
  ADD KEY `fk_anuncioweb_talleres1` (`talleres_id_taller`);

--
-- Indices de la tabla `asistenciahackatons`
--
ALTER TABLE `asistenciahackatons`
  ADD PRIMARY KEY (`id_asistenciahackaton`,`asistenciashackathons_id_asistenciashackaton`),
  ADD UNIQUE KEY `idasistencia_UNIQUE` (`id_asistenciahackaton`),
  ADD KEY `fk_asistenciahackatons_asistenciashackathons1_idx` (`asistenciashackathons_id_asistenciashackaton`);

--
-- Indices de la tabla `asistenciashackathons`
--
ALTER TABLE `asistenciashackathons`
  ADD PRIMARY KEY (`id_asistenciashackaton`,`inscripcioneshackatons_id_inscripcioneshackaton`),
  ADD UNIQUE KEY `id_asistencia_UNIQUE` (`id_asistenciashackaton`),
  ADD KEY `fk_asistenciashackathons_inscripcioneshackatons1_idx` (`inscripcioneshackatons_id_inscripcioneshackaton`);

--
-- Indices de la tabla `asistenciatalleres`
--
ALTER TABLE `asistenciatalleres`
  ADD PRIMARY KEY (`id_asistenciataller`,`inscripcionestalleres_id_inscripcionestaller`),
  ADD UNIQUE KEY `id_asistencia_UNIQUE` (`id_asistenciataller`),
  ADD KEY `fk_asistenciatalleres_inscripcionestalleres1_idx` (`inscripcionestalleres_id_inscripcionestaller`);

--
-- Indices de la tabla `aulas`
--
ALTER TABLE `aulas`
  ADD PRIMARY KEY (`id_aula`),
  ADD UNIQUE KEY `id_aula_UNIQUE` (`id_aula`);

--
-- Indices de la tabla `comentarios`
--
ALTER TABLE `comentarios`
  ADD PRIMARY KEY (`id_comentario`,`propuestas_id_propuesta`),
  ADD UNIQUE KEY `id_comentarios_UNIQUE` (`id_comentario`),
  ADD KEY `fk_comentarios_propuestas1_idx` (`propuestas_id_propuesta`);

--
-- Indices de la tabla `disponibilidades`
--
ALTER TABLE `disponibilidades`
  ADD PRIMARY KEY (`id_disponiblidad`,`aulas_id_aula`),
  ADD KEY `fk_disponibilidades_aulas1_idx` (`aulas_id_aula`);

--
-- Indices de la tabla `entregables`
--
ALTER TABLE `entregables`
  ADD PRIMARY KEY (`id_entregable`,`hackathones_id_hackaton`),
  ADD UNIQUE KEY `id_entregable_UNIQUE` (`id_entregable`),
  ADD KEY `fk_Entregables_hackathones1_idx` (`hackathones_id_hackaton`);

--
-- Indices de la tabla `eventos`
--
ALTER TABLE `eventos`
  ADD PRIMARY KEY (`id_evento`,`jornadas_id_jornada`),
  ADD UNIQUE KEY `id_eventos_UNIQUE` (`id_evento`),
  ADD KEY `fk_eventos_jornadas1` (`jornadas_id_jornada`);

--
-- Indices de la tabla `grupos`
--
ALTER TABLE `grupos`
  ADD PRIMARY KEY (`id_grupo`);

--
-- Indices de la tabla `hackathones`
--
ALTER TABLE `hackathones`
  ADD PRIMARY KEY (`id_hackaton`,`jornadas_id_jornada`),
  ADD KEY `fk_hackathones_disponibilidades1_idx` (`disponibilidades_id_disponiblidad`),
  ADD KEY `fk_hackathones_jornadas1_idx` (`jornadas_id_jornada`);

--
-- Indices de la tabla `inscripcioneshackatons`
--
ALTER TABLE `inscripcioneshackatons`
  ADD PRIMARY KEY (`id_inscripcioneshackaton`,`hackathones_id_hackaton`,`usuarios_id_usuario`),
  ADD UNIQUE KEY `id_inscripcioneshackaton_UNIQUE` (`id_inscripcioneshackaton`),
  ADD KEY `fk_inscripcioneshackatons_hackathones1_idx` (`hackathones_id_hackaton`),
  ADD KEY `fk_inscripcioneshackatons_usuarios1_idx` (`usuarios_id_usuario`);

--
-- Indices de la tabla `inscripcionestalleres`
--
ALTER TABLE `inscripcionestalleres`
  ADD PRIMARY KEY (`id_inscripcionestaller`,`talleres_id_taller`,`usuarios_id_usuario`),
  ADD UNIQUE KEY `id_inscripcionestallere_UNIQUE` (`id_inscripcionestaller`),
  ADD KEY `fk_inscripcionestalleres_talleres1_idx` (`talleres_id_taller`),
  ADD KEY `fk_inscripcionestalleres_usuarios1_idx` (`usuarios_id_usuario`);

--
-- Indices de la tabla `inscripcionestorneos`
--
ALTER TABLE `inscripcionestorneos`
  ADD PRIMARY KEY (`idinscripcionestorneo`,`torneos_id_torneo`,`usuarios_id_usuario`),
  ADD UNIQUE KEY `idinscripcionestorneo_UNIQUE` (`idinscripcionestorneo`),
  ADD KEY `fk_inscripcionestorneos_torneos1_idx` (`torneos_id_torneo`),
  ADD KEY `fk_inscripcionestorneos_usuarios1_idx` (`usuarios_id_usuario`);

--
-- Indices de la tabla `jornadas`
--
ALTER TABLE `jornadas`
  ADD PRIMARY KEY (`id_jornada`,`unidadesregionales_id_ur`),
  ADD KEY `fk_jornadas_unidadesregionales1_idx` (`unidadesregionales_id_ur`);

--
-- Indices de la tabla `propuestas`
--
ALTER TABLE `propuestas`
  ADD PRIMARY KEY (`id_propuesta`,`usuarios_id_usuario`),
  ADD UNIQUE KEY `id_propuesta_UNIQUE` (`id_propuesta`),
  ADD KEY `fk_propuestas_usuarios1_idx` (`usuarios_id_usuario`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id_UNIQUE` (`id`);

--
-- Indices de la tabla `rubricas`
--
ALTER TABLE `rubricas`
  ADD PRIMARY KEY (`id_rubrica`,`hackathones_id_hackaton`),
  ADD UNIQUE KEY `id_rubrica_UNIQUE` (`id_rubrica`),
  ADD KEY `fk_Rubricas_hackathones1_idx` (`hackathones_id_hackaton`);

--
-- Indices de la tabla `talleres`
--
ALTER TABLE `talleres`
  ADD PRIMARY KEY (`id_taller`,`jornadas_id_jornada`,`usuarios_id_usuario`),
  ADD UNIQUE KEY `id_taller_UNIQUE` (`id_taller`),
  ADD KEY `fk_talleres_disponibilidades_idx` (`disponibilidades_id_disponiblidad`),
  ADD KEY `fk_talleres_jornadas1_idx` (`jornadas_id_jornada`),
  ADD KEY `fk_talleres_usuarios1_idx` (`usuarios_id_usuario`),
  ADD KEY `fk_talleres_responsable` (`responsable_usuario_id`);

--
-- Indices de la tabla `torneos`
--
ALTER TABLE `torneos`
  ADD PRIMARY KEY (`id_torneo`,`jornadas_id_jornada`),
  ADD KEY `fk_torneos_disponibilidades1_idx` (`disponibilidades_id_disponiblidad`),
  ADD KEY `fk_torneos_jornadas1_idx` (`jornadas_id_jornada`);

--
-- Indices de la tabla `unidadesregionales`
--
ALTER TABLE `unidadesregionales`
  ADD PRIMARY KEY (`id_ur`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`,`unidadesregionales_id_ur`,`roles_id`),
  ADD UNIQUE KEY `matricula_UNIQUE` (`id_usuario`),
  ADD KEY `fk_usuarios_unidadesregionales1_idx` (`unidadesregionales_id_ur`),
  ADD KEY `fk_usuarios_roles1_idx` (`roles_id`),
  ADD KEY `fk_usuarios_grupos1_idx` (`grupos_id_grupo`);

--
-- Indices de la tabla `votos`
--
ALTER TABLE `votos`
  ADD PRIMARY KEY (`id_voto`,`propuestas_id_propuesta`,`usuarios_id_usuario`),
  ADD UNIQUE KEY `id_votos_UNIQUE` (`id_voto`),
  ADD KEY `fk_votos_propuestas1_idx` (`propuestas_id_propuesta`),
  ADD KEY `fk_votos_usuarios1_idx` (`usuarios_id_usuario`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `anuncioweb`
--
ALTER TABLE `anuncioweb`
  MODIFY `id_anuncioweb` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `asistenciahackatons`
--
ALTER TABLE `asistenciahackatons`
  MODIFY `id_asistenciahackaton` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `asistenciashackathons`
--
ALTER TABLE `asistenciashackathons`
  MODIFY `id_asistenciashackaton` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `asistenciatalleres`
--
ALTER TABLE `asistenciatalleres`
  MODIFY `id_asistenciataller` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `aulas`
--
ALTER TABLE `aulas`
  MODIFY `id_aula` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `comentarios`
--
ALTER TABLE `comentarios`
  MODIFY `id_comentario` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `disponibilidades`
--
ALTER TABLE `disponibilidades`
  MODIFY `id_disponiblidad` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `entregables`
--
ALTER TABLE `entregables`
  MODIFY `id_entregable` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `eventos`
--
ALTER TABLE `eventos`
  MODIFY `id_evento` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `grupos`
--
ALTER TABLE `grupos`
  MODIFY `id_grupo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `hackathones`
--
ALTER TABLE `hackathones`
  MODIFY `id_hackaton` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `inscripcioneshackatons`
--
ALTER TABLE `inscripcioneshackatons`
  MODIFY `id_inscripcioneshackaton` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `inscripcionestalleres`
--
ALTER TABLE `inscripcionestalleres`
  MODIFY `id_inscripcionestaller` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `inscripcionestorneos`
--
ALTER TABLE `inscripcionestorneos`
  MODIFY `idinscripcionestorneo` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `jornadas`
--
ALTER TABLE `jornadas`
  MODIFY `id_jornada` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `propuestas`
--
ALTER TABLE `propuestas`
  MODIFY `id_propuesta` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `rubricas`
--
ALTER TABLE `rubricas`
  MODIFY `id_rubrica` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `talleres`
--
ALTER TABLE `talleres`
  MODIFY `id_taller` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `torneos`
--
ALTER TABLE `torneos`
  MODIFY `id_torneo` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT de la tabla `votos`
--
ALTER TABLE `votos`
  MODIFY `id_voto` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `anuncioweb`
--
ALTER TABLE `anuncioweb`
  ADD CONSTRAINT `fk_anuncioweb_eventos1` FOREIGN KEY (`eventos_id_evento`) REFERENCES `eventos` (`id_evento`),
  ADD CONSTRAINT `fk_anuncioweb_hackathones1` FOREIGN KEY (`hackathones_id_hackaton`) REFERENCES `hackathones` (`id_hackaton`),
  ADD CONSTRAINT `fk_anuncioweb_talleres1` FOREIGN KEY (`talleres_id_taller`) REFERENCES `talleres` (`id_taller`),
  ADD CONSTRAINT `fk_anuncioweb_torneos1` FOREIGN KEY (`torneos_id_torneo`) REFERENCES `torneos` (`id_torneo`);

--
-- Filtros para la tabla `asistenciahackatons`
--
ALTER TABLE `asistenciahackatons`
  ADD CONSTRAINT `fk_asistenciahackatons_asistenciashackathons1` FOREIGN KEY (`asistenciashackathons_id_asistenciashackaton`) REFERENCES `asistenciashackathons` (`id_asistenciashackaton`);

--
-- Filtros para la tabla `asistenciashackathons`
--
ALTER TABLE `asistenciashackathons`
  ADD CONSTRAINT `fk_asistenciashackathons_inscripcioneshackatons1` FOREIGN KEY (`inscripcioneshackatons_id_inscripcioneshackaton`) REFERENCES `inscripcioneshackatons` (`id_inscripcioneshackaton`);

--
-- Filtros para la tabla `asistenciatalleres`
--
ALTER TABLE `asistenciatalleres`
  ADD CONSTRAINT `fk_asistenciatalleres_inscripcionestalleres1` FOREIGN KEY (`inscripcionestalleres_id_inscripcionestaller`) REFERENCES `inscripcionestalleres` (`id_inscripcionestaller`);

--
-- Filtros para la tabla `comentarios`
--
ALTER TABLE `comentarios`
  ADD CONSTRAINT `fk_comentarios_propuestas1` FOREIGN KEY (`propuestas_id_propuesta`) REFERENCES `propuestas` (`id_propuesta`);

--
-- Filtros para la tabla `disponibilidades`
--
ALTER TABLE `disponibilidades`
  ADD CONSTRAINT `fk_disponibilidades_aulas1` FOREIGN KEY (`aulas_id_aula`) REFERENCES `aulas` (`id_aula`);

--
-- Filtros para la tabla `entregables`
--
ALTER TABLE `entregables`
  ADD CONSTRAINT `fk_Entregables_hackathones1` FOREIGN KEY (`hackathones_id_hackaton`) REFERENCES `hackathones` (`id_hackaton`);

--
-- Filtros para la tabla `eventos`
--
ALTER TABLE `eventos`
  ADD CONSTRAINT `fk_eventos_jornadas1` FOREIGN KEY (`jornadas_id_jornada`) REFERENCES `jornadas` (`id_jornada`);

--
-- Filtros para la tabla `hackathones`
--
ALTER TABLE `hackathones`
  ADD CONSTRAINT `fk_hackathones_disponibilidades1` FOREIGN KEY (`disponibilidades_id_disponiblidad`) REFERENCES `disponibilidades` (`id_disponiblidad`),
  ADD CONSTRAINT `fk_hackathones_jornadas1` FOREIGN KEY (`jornadas_id_jornada`) REFERENCES `jornadas` (`id_jornada`);

--
-- Filtros para la tabla `inscripcioneshackatons`
--
ALTER TABLE `inscripcioneshackatons`
  ADD CONSTRAINT `fk_inscripcioneshackatons_hackathones1` FOREIGN KEY (`hackathones_id_hackaton`) REFERENCES `hackathones` (`id_hackaton`),
  ADD CONSTRAINT `fk_inscripcioneshackatons_usuarios1` FOREIGN KEY (`usuarios_id_usuario`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `inscripcionestalleres`
--
ALTER TABLE `inscripcionestalleres`
  ADD CONSTRAINT `fk_inscripcionestalleres_talleres1` FOREIGN KEY (`talleres_id_taller`) REFERENCES `talleres` (`id_taller`),
  ADD CONSTRAINT `fk_inscripcionestalleres_usuarios1` FOREIGN KEY (`usuarios_id_usuario`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `inscripcionestorneos`
--
ALTER TABLE `inscripcionestorneos`
  ADD CONSTRAINT `fk_inscripcionestorneos_torneos1` FOREIGN KEY (`torneos_id_torneo`) REFERENCES `torneos` (`id_torneo`),
  ADD CONSTRAINT `fk_inscripcionestorneos_usuarios1` FOREIGN KEY (`usuarios_id_usuario`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `jornadas`
--
ALTER TABLE `jornadas`
  ADD CONSTRAINT `fk_jornadas_unidadesregionales1` FOREIGN KEY (`unidadesregionales_id_ur`) REFERENCES `unidadesregionales` (`id_ur`);

--
-- Filtros para la tabla `propuestas`
--
ALTER TABLE `propuestas`
  ADD CONSTRAINT `fk_propuestas_usuarios1` FOREIGN KEY (`usuarios_id_usuario`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `rubricas`
--
ALTER TABLE `rubricas`
  ADD CONSTRAINT `fk_Rubricas_hackathones1` FOREIGN KEY (`hackathones_id_hackaton`) REFERENCES `hackathones` (`id_hackaton`);

--
-- Filtros para la tabla `talleres`
--
ALTER TABLE `talleres`
  ADD CONSTRAINT `fk_talleres_disponibilidades` FOREIGN KEY (`disponibilidades_id_disponiblidad`) REFERENCES `disponibilidades` (`id_disponiblidad`),
  ADD CONSTRAINT `fk_talleres_jornadas1` FOREIGN KEY (`jornadas_id_jornada`) REFERENCES `jornadas` (`id_jornada`),
  ADD CONSTRAINT `fk_talleres_usuarios1` FOREIGN KEY (`usuarios_id_usuario`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `torneos`
--
ALTER TABLE `torneos`
  ADD CONSTRAINT `fk_torneos_disponibilidades1` FOREIGN KEY (`disponibilidades_id_disponiblidad`) REFERENCES `disponibilidades` (`id_disponiblidad`),
  ADD CONSTRAINT `fk_torneos_jornadas1` FOREIGN KEY (`jornadas_id_jornada`) REFERENCES `jornadas` (`id_jornada`);

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usuarios_grupos1` FOREIGN KEY (`grupos_id_grupo`) REFERENCES `grupos` (`id_grupo`),
  ADD CONSTRAINT `fk_usuarios_roles1` FOREIGN KEY (`roles_id`) REFERENCES `roles` (`id`),
  ADD CONSTRAINT `fk_usuarios_unidadesregionales1` FOREIGN KEY (`unidadesregionales_id_ur`) REFERENCES `unidadesregionales` (`id_ur`);

--
-- Filtros para la tabla `votos`
--
ALTER TABLE `votos`
  ADD CONSTRAINT `fk_votos_propuestas1` FOREIGN KEY (`propuestas_id_propuesta`) REFERENCES `propuestas` (`id_propuesta`),
  ADD CONSTRAINT `fk_votos_usuarios1` FOREIGN KEY (`usuarios_id_usuario`) REFERENCES `usuarios` (`id_usuario`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;


-- =====================================================================
-- PASO 2: MIGRACION v2 (MVP)
-- =====================================================================

-- =====================================================================
-- DATACODE — Migración v2 (MVP funcional)
-- =====================================================================
-- Se ejecuta UNA sola vez, después de importar el dump original
-- (datacode_db.sql) y ANTES de correr database/seed_datos_prueba.sql.
--
-- Qué hace:
--   1. Agrega el rol "Docente" (faltaba en el catálogo de roles).
--   2. Corrige la unicidad real de la matrícula (el índice existente
--      "matricula_UNIQUE" en realidad protegía id_usuario, no la
--      matrícula).
--   3. Permite que usuarios_id_usuario. grupos_id_grupo sea NULL para
--      roles que no pertenecen a un grupo (Docente, Coordinador,
--      Departamento de Ingeniería, Jefatura de Academia).
--   4. Agrega la llave foránea faltante de talleres.responsable_usuario_id.
--   5. Completa Torneos y Hackathones con nombre, descripción y
--      responsable (antes no existía forma de nombrarlos ni de
--      asignarles un Docente responsable).
--   6. Completa Eventos con descripción y aula (el Visitante necesita
--      ver descripción, y hoy no había dónde guardarla).
--   7. Agrega trazabilidad de revisión a Propuestas (quién y cuándo
--      aprobó/rechazó).
--   8. Crea la tabla asistenciatorneos (no existía; sí existe su
--      equivalente para talleres y hackathones).
--
-- Las columnas NOT NULL nuevas llevan un DEFAULT para no romper filas
-- que ya existan en Torneos/Hackathones al momento de aplicar esta
-- migración.
-- =====================================================================

START TRANSACTION;

-- 1) Rol Docente
INSERT INTO `roles` (`rol`) VALUES ('Docente');

-- 2) Unicidad real de la matrícula (se agrega, no se sustituye el índice
--    mal nombrado que ya existía, para no arriesgar romper nada que
--    dependa de su nombre actual)
ALTER TABLE `usuarios`
  ADD UNIQUE KEY `uq_matricula_usuario` (`matricula_usuario`);

-- 3) Grupo opcional para roles que no son Alumno/Comité
ALTER TABLE `usuarios`
  MODIFY `grupos_id_grupo` INT(11) NULL;

-- 4) Llave foránea faltante del responsable de taller
ALTER TABLE `talleres`
  ADD CONSTRAINT `fk_talleres_responsable`
    FOREIGN KEY (`responsable_usuario_id`) REFERENCES `usuarios` (`id_usuario`);

-- 5) Completar Torneos
ALTER TABLE `torneos`
  ADD COLUMN `nombre_torneo` VARCHAR(80) NOT NULL DEFAULT 'Torneo sin nombre' AFTER `id_torneo`,
  ADD COLUMN `horariofin_torneo` TIME NULL AFTER `horarioinicio_torneo`,
  ADD COLUMN `responsable_usuario_id` INT(11) NULL,
  ADD CONSTRAINT `fk_torneos_responsable`
    FOREIGN KEY (`responsable_usuario_id`) REFERENCES `usuarios` (`id_usuario`);

-- Completar Hackathones
ALTER TABLE `hackathones`
  ADD COLUMN `nombre_hackaton` VARCHAR(80) NOT NULL DEFAULT 'Hackathon sin nombre' AFTER `id_hackaton`,
  ADD COLUMN `descripcion_hackaton` MEDIUMTEXT NULL,
  ADD COLUMN `responsable_usuario_id` INT(11) NULL,
  ADD CONSTRAINT `fk_hackathones_responsable`
    FOREIGN KEY (`responsable_usuario_id`) REFERENCES `usuarios` (`id_usuario`);

-- 6) Completar Eventos
ALTER TABLE `eventos`
  ADD COLUMN `descripcion_evento` MEDIUMTEXT NULL,
  ADD COLUMN `aulas_id_aula` INT(11) NULL,
  ADD CONSTRAINT `fk_eventos_aulas`
    FOREIGN KEY (`aulas_id_aula`) REFERENCES `aulas` (`id_aula`);

-- 7) Trazabilidad de revisión de propuestas
ALTER TABLE `propuestas`
  ADD COLUMN `revisado_por_usuario_id` INT(11) NULL,
  ADD COLUMN `fecha_revision` DATETIME NULL,
  ADD CONSTRAINT `fk_propuestas_revisor`
    FOREIGN KEY (`revisado_por_usuario_id`) REFERENCES `usuarios` (`id_usuario`);

-- 8) Tabla de asistencia para Torneos (simétrica a asistenciatalleres)
CREATE TABLE `asistenciatorneos` (
  `id_asistenciatorneo` INT(11) NOT NULL AUTO_INCREMENT,
  `estado_asistenciatorneo` ENUM('Asistio','Falto','Justificado') NOT NULL,
  `inscripcionestorneos_idinscripcionestorneo` INT(11) NOT NULL,
  PRIMARY KEY (`id_asistenciatorneo`),
  KEY `fk_asistenciatorneos_inscripciones_idx` (`inscripcionestorneos_idinscripcionestorneo`),
  CONSTRAINT `fk_asistenciatorneos_inscripciones`
    FOREIGN KEY (`inscripcionestorneos_idinscripcionestorneo`)
    REFERENCES `inscripcionestorneos` (`idinscripcionestorneo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

COMMIT;


-- =====================================================================
-- PASO 3: DATOS DE EJEMPLO (SEED)
-- =====================================================================

-- =====================================================================
-- DATACODE — Script de llenado de tablas sin registros (v2)
-- =====================================================================
-- Requiere haber aplicado antes database/migracion_v2_mvp.sql sobre una
-- copia fresca de datacode_db.sql (en ese orden: dump original ->
-- migración v2 -> este script). Si ya habías corrido una versión
-- anterior de este seed sobre el dump sin migrar, parte de nuevo de una
-- copia limpia del dump para evitar choques de columnas/IDs.
--
-- Qué hace, en orden:
--   A) Agrega 3 usuarios de personal (1 Docente, 2 de jefatura) que
--      antes no existían en absoluto — necesarios para poder asignar
--      responsables reales a talleres/torneos/hackathones.
--   B) Reasigna el responsable de los talleres 1 y 2 (antes apuntaban
--      a un usuario con rol Alumno, dato inconsistente del dump
--      original) al nuevo usuario Docente.
--   C) Llena las 14 tablas que no tenían registros, ya con las columnas
--      nuevas de la migración v2 (nombre_torneo, nombre_hackaton,
--      descripcion_evento, responsables, etc.) y agrega también
--      asistenciatorneos (tabla nueva de la migración).
--   D) Promueve a un alumno a Comité como consecuencia de su propuesta
--      aprobada, para dejar el dato de ejemplo consistente de punta a
--      punta.
--
-- Se reutilizan a propósito las disponibilidades 1 y 2 (mismas que ya
-- usan los talleres 1 y 2, aula '51', 2027-09-11 09:00, 120 min) para
-- un torneo y para el hackathon: deja un caso real de traslape de
-- horario en la misma aula para probar la detección de conflictos del
-- horario general del Coordinador.
--
-- Probado con éxito contra el esquema migrado en MariaDB 10.11
-- (conexión con charset utf8mb4).
-- =====================================================================

START TRANSACTION;

-- ---------------------------------------------------------------------
-- A) Personal nuevo: 1 Docente + 2 usuarios de jefatura (solo consulta)
-- ---------------------------------------------------------------------
-- Contraseña con el mismo esquema que usa integrar-alumnos.php:
-- año actual (27) + "06" fijo + últimos 4 dígitos del id (0=A,1=B,...,9=J)
INSERT INTO `usuarios`
  (`nombres_usuario`, `apellidos_usuario`, `matricula_usuario`, `habilitado_usuario`,
   `unidadesregionales_id_ur`, `roles_id`, `grupos_id_grupo`, `password_usuario`,
   `rutaimagen_usuario`, `correo_usuario`)
VALUES
('Roberto', 'Ibarra Sánchez', '00060001', '1', '1', 6, NULL, '2706AADD', '', 'roberto.ibarra@docentes.datacode.mx'),
('Patricia', 'Leyva Cota', '00060002', '1', '1', 4, NULL, '2706AADE', '', 'patricia.leyva@datacode.mx'),
('Manuel', 'Beltrán Ruiz', '00060003', '1', '1', 5, NULL, '2706AADF', '', 'manuel.beltran@datacode.mx');
-- ids generados: 33 (Docente Roberto Ibarra), 34 (Depto. Ingeniería), 35 (Jefatura de Academia)

-- ---------------------------------------------------------------------
-- B) Corrige el responsable de los talleres existentes (antes era un
--    Alumno; ahora es el Docente recién creado)
-- ---------------------------------------------------------------------
UPDATE `talleres` SET `responsable_usuario_id` = 33 WHERE `id_taller` IN (1, 2);

-- ---------------------------------------------------------------------
-- 1) eventos  (requiere: jornadas.id_jornada = 1, aulas.id_aula = 1 -> ya existentes)
-- ---------------------------------------------------------------------
INSERT INTO `eventos` (`nombre_evento`, `descripcion_evento`, `fecha_evento`, `hora_evento`, `duracion_evento`, `jornadas_id_jornada`, `aulas_id_aula`) VALUES
('Ceremonia de Inauguración', 'Bienvenida oficial a la Jornada DataCode 2027 con las autoridades de la carrera.', '2027-09-11', '08:00:00', 30, 1, 1),
('Conferencia Magistral: Tendencias en Ingeniería de Software', 'Charla abierta sobre las tendencias actuales de la industria del software.', '2027-09-11', '12:30:00', 60, 1, 1),
('Clausura y Premiación', 'Cierre de la jornada y entrega de reconocimientos a talleres, torneos y hackathon.', '2027-09-11', '18:00:00', 45, 1, 1);
-- ids generados: 1, 2, 3

-- ---------------------------------------------------------------------
-- 2) torneos  (requiere: disponibilidades 1 y 2, jornada 1, docente 33 -> ya existentes)
-- ---------------------------------------------------------------------
INSERT INTO `torneos` (`nombre_torneo`, `formato_torneo`, `reglas_torneo`, `horarioinicio_torneo`, `horariofin_torneo`, `fechatorneo`, `disponibilidades_id_disponiblidad`, `jornadas_id_jornada`, `responsable_usuario_id`) VALUES
('Torneo de Debate Interuniversitario',
 'Eliminación directa, equipos de 3 integrantes.',
 'Cada equipo cuenta con 5 minutos para argumentar y 2 minutos de réplica. El jurado calificará claridad, evidencia y manejo del tiempo.',
 '09:00:00', '11:00:00', '2027-09-11', 1, 1, 33),
('Torneo de FIFA 26',
 'Modalidad individual, fase de grupos seguida de eliminación directa.',
 'Partidos a 6 minutos por tiempo. En caso de empate se define por penales. Se prohíbe el uso de controles distintos a los proporcionados por la organización.',
 '09:00:00', '11:00:00', '2027-09-11', 2, 1, NULL);
-- ids generados: 1 (Debate, responsable asignado), 2 (FIFA, responsable por asignar)
-- Ambos coinciden en fecha/hora/aula con los talleres 1 y 2 -> traslape intencional, ver nota de diseño arriba.

-- ---------------------------------------------------------------------
-- 3) hackathones  (requiere: disponibilidad 1, jornada 1, docente 33 -> ya existentes)
-- ---------------------------------------------------------------------
INSERT INTO `hackathones` (`nombre_hackaton`, `descripcion_hackaton`, `fechainicio_hackaton`, `fechafinal_hackaton`, `cuota_hackaton`, `disponibilidades_id_disponiblidad`, `jornadas_id_jornada`, `numasistencia_hackathon`, `responsable_usuario_id`) VALUES
('Hackathón DataCode 2027', 'Hackathon de 24 horas para desarrollar una solución de software a un reto real, en equipos.', '2027-09-11', '2027-09-12', 150.00, 1, 1, 2, 33);
-- id generado: 1. numasistencia_hackathon = 2 -> se esperan 2 pases de lista (día 1 y día 2).

-- ---------------------------------------------------------------------
-- 4) inscripcionestorneos  (requiere: torneos 1-2, usuarios alumnos existentes)
-- ---------------------------------------------------------------------
-- Torneo 1 (Debate, equipos de 3): usuarios 4,5,6 y 7,8,9 (dos equipos)
-- Torneo 2 (FIFA, individual): usuarios 10 al 17
INSERT INTO `inscripcionestorneos` (`torneos_id_torneo`, `usuarios_id_usuario`) VALUES
(1, 4), (1, 5), (1, 6), (1, 7), (1, 8), (1, 9),
(2, 10), (2, 11), (2, 12), (2, 13), (2, 14), (2, 15), (2, 16), (2, 17);
-- ids generados: 1 al 6 (torneo 1), 7 al 14 (torneo 2)

-- ---------------------------------------------------------------------
-- 5) inscripcioneshackatons  (requiere: hackathon 1, usuarios 18-25 existentes)
-- ---------------------------------------------------------------------
INSERT INTO `inscripcioneshackatons` (`hackathones_id_hackaton`, `usuarios_id_usuario`) VALUES
(1, 18), (1, 19), (1, 20), (1, 21), (1, 22), (1, 23), (1, 24), (1, 25);
-- ids generados: 1 al 8, en el mismo orden que los usuarios listados arriba

-- ---------------------------------------------------------------------
-- 6) asistenciatalleres  (requiere: inscripcionestalleres.id = 1, ya existente)
-- ---------------------------------------------------------------------
INSERT INTO `asistenciatalleres` (`estado_asistenciataller`, `inscripcionestalleres_id_inscripcionestaller`) VALUES
('Asistio', 1);

-- ---------------------------------------------------------------------
-- 7) asistenciatorneos  (requiere: inscripcionestorneos 1-6 del paso 4, tabla nueva de la migración)
-- ---------------------------------------------------------------------
INSERT INTO `asistenciatorneos` (`estado_asistenciatorneo`, `inscripcionestorneos_idinscripcionestorneo`) VALUES
('Asistio', 1), ('Asistio', 2), ('Asistio', 3), ('Asistio', 4), ('Falto', 5), ('Asistio', 6);

-- ---------------------------------------------------------------------
-- 8) asistenciashackathons  (requiere: inscripcioneshackatons 1-8 del paso 5)
-- ---------------------------------------------------------------------
-- cartaalumno / cartatutor_hackaton: 'S' = carta firmada entregada, 'N' = pendiente
INSERT INTO `asistenciashackathons` (`cartaalumno`, `cartatutor_hackaton`, `inscripcioneshackatons_id_inscripcioneshackaton`) VALUES
('S', 'S', 1),
('S', 'S', 2),
('S', 'N', 3),
('S', 'S', 4),
('N', 'S', 5),
('S', 'S', 6),
('S', 'S', 7),
('S', 'N', 8);
-- ids generados: 1 al 8

-- ---------------------------------------------------------------------
-- 9) asistenciahackatons  (requiere: asistenciashackathons 1-8 del paso 8)
-- ---------------------------------------------------------------------
-- Dos registros por inscrito: día 1 (num_asistenciahackaton=1) y día 2 (=2),
-- acorde a numasistencia_hackathon=2 del hackathon.
INSERT INTO `asistenciahackatons` (`estado_asistenciataller`, `asistenciashackathons_id_asistenciashackaton`, `num_asistenciahackaton`) VALUES
('Asistio', 1, 1), ('Asistio', 1, 2),
('Asistio', 2, 1), ('Falto', 2, 2),
('Asistio', 3, 1), ('Asistio', 3, 2),
('Justificado', 4, 1), ('Asistio', 4, 2),
('Asistio', 5, 1), ('Asistio', 5, 2),
('Asistio', 6, 1), ('Falto', 6, 2),
('Asistio', 7, 1), ('Asistio', 7, 2),
('Asistio', 8, 1), ('Asistio', 8, 2);

-- ---------------------------------------------------------------------
-- 10) rubricas  (requiere: hackathon 1)
-- ---------------------------------------------------------------------
INSERT INTO `rubricas` (`criterio_rubrica`, `descripcion_rubrica`, `puntaje_rubrica`, `hackathones_id_hackaton`) VALUES
('Funcionalidad', 'El proyecto cumple con los requerimientos mínimos y funciona sin errores críticos.', 25, 1),
('Innovación', 'Originalidad de la propuesta y uso creativo de la tecnología disponible.', 25, 1),
('Trabajo en equipo', 'Evidencia de colaboración y distribución de tareas entre los integrantes.', 25, 1),
('Presentación final', 'Claridad, orden y manejo del tiempo durante la exposición del proyecto.', 25, 1);

-- ---------------------------------------------------------------------
-- 11) entregables  (requiere: hackathon 1)
-- ---------------------------------------------------------------------
INSERT INTO `entregables` (`descripcion_entregable`, `hackathones_id_hackaton`) VALUES
('Repositorio de código fuente en GitHub con historial de commits de cada integrante del equipo.', 1),
('Presentación final en formato PDF, máximo 10 diapositivas.', 1);

-- ---------------------------------------------------------------------
-- 12) propuestas  (requiere: usuarios alumnos existentes)
-- ---------------------------------------------------------------------
-- usuario 5 = Carlos López, 6 = Ana Martínez, 8 = Sofía Pérez,
-- 9 = Diego Sánchez, 10 = Valeria Ramírez.
INSERT INTO `propuestas` (`titulo_propuesta`, `descripcion_propuesta`, `aprobacion_propuesta`, `tipo_propuesta`, `usuarios_id_usuario`, `revisado_por_usuario_id`, `fecha_revision`) VALUES
('Taller de Robótica con Arduino', 'Propuesta para un taller introductorio de robótica usando placas Arduino, orientado a alumnos de primer y segundo semestre.', 'a consideracion', 'Talleres', 5, NULL, NULL),
('Torneo de Ajedrez Rápido', 'Torneo relámpago de ajedrez, partidas a 5 minutos, formato suizo a 5 rondas.', 'sin visualizar', 'Torneos', 6, NULL, NULL),
('Solicitud para integrarme al Comité de Alumnos', 'Me gustaría formar parte del comité para apoyar en la organización de la próxima jornada.', 'aprobado', 'Comite', 8, 2, '2027-09-01 10:15:00'),
('Hackathon de Ciberseguridad', 'Hackathon de 24 horas enfocado en retos de tipo CTF (captura la bandera) para practicar seguridad ofensiva y defensiva.', 'no aprobado', 'Hackaton', 9, 2, '2027-09-01 10:20:00'),
('Ampliar horario del comedor durante la jornada', 'Sugerencia general para extender el horario del comedor durante los días del evento, dada la afluencia esperada.', 'sin visualizar', 'General', 10, NULL, NULL),
('Taller de Introducción a Inteligencia Artificial', 'Taller práctico de fundamentos de IA y machine learning con ejercicios en Python.', 'aprobado', 'Talleres', 8, 2, '2027-09-02 09:00:00');
-- ids generados: 1 al 6. `revisado_por_usuario_id` = 2 es el Coordinador
-- de ejemplo (Juan Hernandez) ya existente en el dump.
-- La propuesta 6 la crea el usuario 8 ya como miembro de comité (ver
-- punto D más abajo) -> en el foro, las propuestas de un usuario con
-- rol Comite deben listarse primero; esa regla es de consulta
-- (ORDER BY sobre el rol del autor), no requiere una columna aquí.

-- ---------------------------------------------------------------------
-- 13) comentarios  (requiere: propuestas 1-6 del paso 12)
-- ---------------------------------------------------------------------
INSERT INTO `comentarios` (`texto_comentario`, `propuestas_id_propuesta`) VALUES
('Buena propuesta, quedamos en validar disponibilidad de aula con proyector antes de aprobar.', 1),
('Podría coordinarse con el torneo de FIFA para compartir el mismo espacio en horarios distintos.', 2),
('Aprobada, bienvenida al comité de alumnos.', 3);

-- ---------------------------------------------------------------------
-- 14) votos  (requiere: propuestas 1-6, usuarios existentes)
-- ---------------------------------------------------------------------
INSERT INTO `votos` (`propuestas_id_propuesta`, `usuarios_id_usuario`) VALUES
(1, 1), (1, 3), (1, 4),
(2, 4), (2, 7),
(6, 1), (6, 3), (6, 4), (6, 5);

-- ---------------------------------------------------------------------
-- 15) anuncioweb  (requiere: eventos 1, hackathon 1, taller 1 -> existentes)
-- ---------------------------------------------------------------------
-- Las rutas de imagen son marcadores de posición; sube las imágenes
-- reales a src/img/anuncios/ con esos mismos nombres, o ajusta la ruta.
INSERT INTO `anuncioweb` (`rutaimagen_anuncionweb`, `eventos_id_evento`, `torneos_id_torneo`, `hackathones_id_hackaton`, `talleres_id_taller`) VALUES
('src/img/anuncios/ceremonia-inauguracion.jpg', 1, NULL, NULL, NULL),
('src/img/anuncios/hackathon-datacode-2027.jpg', NULL, NULL, 1, NULL),
('src/img/anuncios/taller-camaras.jpg', NULL, NULL, NULL, 1);

-- ---------------------------------------------------------------------
-- D) Consecuencia de la propuesta de Comité aprobada (propuesta 3):
--    el usuario 8 (Sofía Pérez) pasa de Alumno a Comité.
-- ---------------------------------------------------------------------
UPDATE `usuarios` SET `roles_id` = 2 WHERE `id_usuario` = 8;

COMMIT;
