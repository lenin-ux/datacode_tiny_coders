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
