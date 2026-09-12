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
