# Análisis del sistema DATACODE — Jornada de Ingeniería de Software

Fecha del análisis: 12 de septiembre de 2026. Fuente: `datacode_db.sql` (dump de phpMyAdmin, MySQL 8.0.17) y el código fuente en `datacode_tiny_coders` (repositorio PHP procedural con PDO, Bootstrap 5, sin framework ni gestor de dependencias).

## Resumen ejecutivo

El proyecto tiene una base sólida para el módulo de **Talleres** y para el **alta y administración de usuarios/grupos**: login, cambio de contraseña, gafete imprimible, administración de aulas, disponibilidades, jornadas, grupos, publicación/edición de talleres e inscripción de alumnos ya están implementados y funcionan sobre un esquema relacional razonablemente normalizado. Sin embargo, al comparar el código y el esquema contra los cinco roles y sus necesidades descritas, se detectan tres tipos de brechas: (1) un desajuste entre el catálogo de roles de la base de datos y los roles que describes (falta "Docente" como tal), (2) módulos completos sin ninguna tabla de datos ni pantalla todavía (Torneos, Hackathones, Foro de propuestas/comité, Perfil de alumno, Panel de Docente, Imprimibles/horario general del Coordinador), y (3) algunos bugs de integridad de datos ya presentes en el esquema actual (una unicidad mal nombrada, una llave foránea faltante, un INSERT que no podría ejecutarse en producción). Ninguno de estos problemas es grave por sí solo, pero conviene resolverlos antes de construir los módulos que faltan, porque varios de ellos son la base sobre la que se apoyarán.

## 1. Roles y necesidades por usuario

### 1.1 Desajuste entre el catálogo de roles y los roles descritos

La tabla `roles` contiene: `1 Alumno`, `2 Comite`, `3 Coordinación`, `4 Departamento de Ingeniería`, `5 Jefatura de Academia`. Tu descripción de negocio, en cambio, habla de cinco actores: Visitante (sin login), Alumno, **Docente**, Comité, Coordinador. El rol "Docente" que necesitas —que hace login y ve el horario, lugar, fecha y listado de alumnos de su taller— **no existe en el catálogo actual**, y en su lugar hay dos roles ("Departamento de Ingeniería", "Jefatura de Academia") que no aparecen en tu descripción y que ningún archivo del código referencia todavía (`rol_id == 4` y `rol_id == 5` no aparecen en ninguna de las validaciones de `$_SESSION['rol_id']` del código).

Esto es lo primero que conviene decidir antes de seguir construyendo, porque el campo `talleres.responsable_usuario_id` (quien sería el Docente a cargo del taller) hoy apunta a cualquier usuario sin restricción de rol, y de hecho en los datos de ejemplo apunta a un usuario con rol Alumno. Dos caminos razonables: renombrar uno de los roles 4/5 a "Docente" si en realidad estaban pensados para eso, o agregar un sexto rol `Docente` y dejar 4 y 5 para una jerarquía administrativa futura (Departamento/Jefatura) que hoy no tiene ninguna pantalla. Lo dejo como pregunta abierta en vez de decidir por ti porque cambia el significado de datos ya cargados.

### 1.2 Visitante

No requiere tabla ni login; su necesidad ("ver descripciones, fechas y horarios de los eventos") ya la cubre parcialmente `talleres/ver-talleres.php`, pero solo para talleres. No hay página equivalente para torneos, hackathones ni para el listado general de `eventos` — hoy esas tres tablas no tienen ni pantalla de consulta pública ni registros. Además, la tabla `eventos` no tiene columna de descripción ni de lugar/aula, así que aunque se construyera la pantalla, no habría dónde guardar la descripción que el Visitante necesita ver.

### 1.3 Alumno

Login con matrícula + contraseña, inscripción a Torneos/Talleres/Hackathones, propuestas de taller y propuestas para entrar al comité. El login (`api/login.php`) y la inscripción a talleres (`talleres/inscribirme.php`, con validación de cupo por Unidad Regional y de "solo un taller por alumno") están implementados. Lo que falta por completo: inscripción a Torneos y Hackathones (no hay ninguna pantalla, aunque las tablas `inscripcionestorneos` e `inscripcioneshackatons` sí existen en el esquema), y todo el foro de propuestas: `src/includes/header.php` ya enlaza a `/talleres/propuestas.php` y `/talleres/nueva-propuesta.php`, pero **ninguno de los dos archivos existe** en el repositorio. Tampoco existen `perfil.php` ni `cambiar-foto.php`, aunque el menú de usuario ya los enlaza — un alumno que dé clic ahí hoy obtiene un error 404.

Respecto a "querrá tener la información de sus eventos de forma accesible" después de inscribirse: no hay todavía ninguna vista tipo "mis eventos" o "mi horario" para el alumno; hoy solo puede ver en qué taller está inscrito desde la lista general de talleres.

Sobre la generación de contraseña: la describes como derivada de nombre + apellidos + matrícula, pero la función real en `usuarios/integrar-alumnos.php` (`generarPassword()`) construye la contraseña a partir del año actual + "06" fijo + el id autoincremental del usuario codificado como letras — no usa nombre, apellido ni matrícula en absoluto. No es un error del sistema (la función es consistente y genera contraseñas de 8 caracteres, únicas y no muy predecibles), pero si el requisito de negocio real es que la contraseña derive de esos tres datos, el código actual no lo cumple y habría que ajustarlo.

### 1.4 Docente

Ver horario, lugar, fecha y listado de alumnos de su taller. La relación existe en el esquema (`talleres.responsable_usuario_id` → `usuarios`, y `inscripcionestalleres` para el listado de alumnos), pero **no hay ninguna pantalla para el Docente**: no existe una carpeta `docentes/` ni un archivo tipo `mi-taller.php`, y el menú de navegación (`header.php`) no tiene ninguna entrada condicionada a un rol Docente. Como se explicó en 1.1, tampoco hay claridad sobre qué `roles_id` correspondería a este usuario.

### 1.5 Comité

Mismos privilegios que Alumno más: no puede proponerse a sí mismo al comité, puede crear propuestas de Talleres/Torneos/Hackathon/General, y sus propuestas deben aparecer primero en el listado. El enum `propuestas.tipo_propuesta` ya contempla los cinco tipos correctos (`Talleres`, `Torneos`, `Comite`, `Hackaton`, `General`), y `header.php` ya diferencia el menú de Comité (rol 2) del de Alumno (rol 1) mostrando "Proponer nuevo taller" solo a Comité. Pero como el foro de propuestas no existe todavía (ver 1.3), tampoco existe la regla de ordenamiento "las propuestas de comité aparecen arriba". Esa regla, cuando se construya, es puramente de consulta (`ORDER BY` por rol del autor) y no requiere una columna nueva — conviene resolverla con un `JOIN` a `usuarios`/`roles` en el `SELECT`, no con un campo de "prioridad" guardado en la tabla.

### 1.6 Coordinador

Inserta la mayoría de catálogos, aprueba propuestas, da de alta grupos/alumnos/talleres/torneos/hackathones/eventos/aulas/jornadas/docentes, y necesita una ventana de imprimibles con un horario general cronológico de los 4 tipos de evento que detecte traslapes. De este rol están construidas las partes de catálogo (aulas, disponibilidades, jornadas, grupos, integración de alumnos) y la publicación/edición de talleres. **No existe nada** para: aprobar propuestas (no hay pantalla que liste `propuestas` ni que cambie `aprobacion_propuesta`), crear torneos/hackathones/eventos (no hay carpetas `torneos/`, `hackathones/`, ni un `eventos/crear-evento.php`), dar de alta Docentes con un rol específico, ni la ventana de imprimibles/horario general.

Sobre el horario general cronológico: hoy `talleres`, `torneos`, `hackathones` y `eventos` son cuatro tablas con columnas de fecha/hora distintas entre sí (por ejemplo `talleres` tiene `horainicio_taller`/`horatermino_taller`, `torneos` solo tiene `horarioinicio_torneo` sin hora de término, `eventos` tiene `duracion_evento` en minutos, y `hackathones` ni siquiera tiene una hora, solo fecha de inicio/fin). Para construir el horario unificado que pide el Coordinador, y para que la detección de traslapes sea manejable, conviene definir una vista SQL (`CREATE VIEW`) que normalice las cuatro tablas a columnas comunes (`tipo`, `nombre`, `fecha`, `hora_inicio`, `hora_fin`, `aula`) en vez de resolverlo con cuatro consultas distintas cada vez que se necesite. Esto no cambia el esquema base, solo agrega una vista de lectura; lo incluyo como sugerencia en la sección de recomendaciones.

Sobre la detección de traslapes en sí: hoy nada en el esquema ni en el código impide que dos `disponibilidades` ocupen la misma aula a la misma hora (de hecho, en los datos de ejemplo ya existen dos registros de disponibilidad idénticos en aula y horario). El script de llenado que preparo abajo aprovecha justo ese hueco para dejar un caso de traslape real y así puedas probar la función de detección cuando la construyas.

## 2. Qué falta en la base de datos

### 2.1 Tablas que no existen y convendría agregar

| Tabla sugerida | Para qué | Prioridad |
|---|---|---|
| `asistenciatorneos` | Hoy existe asistencia para talleres (`asistenciatalleres`) y hackathones (`asistenciashackathons` + `asistenciahackatons`), pero no para torneos. Rompe la simetría entre los tres tipos de evento con inscripción. | Alta |
| `equipos` (y `equipos_integrantes`) | Si los Torneos u Hackathones se juegan en equipo (común en ambos formatos), hoy la inscripción es 100% individual (`inscripcionestorneos`, `inscripcioneshackatons` solo llevan un `usuarios_id_usuario`). Si de verdad se compite en equipos, falta modelar esto. Si toda competencia es individual, no se necesita. | Depende de la regla de negocio — vale la pena confirmarlo antes de construir Torneos/Hackathones. |
| Auditoría de revisión de `propuestas` | No es necesariamente una tabla nueva — ver 2.2 — pero si quieres conservar historial de cambios de estado (quién aprobó, cuándo, y si hubo una propuesta rechazada dos veces), sí conviene una tabla `propuestas_historial`. | Baja/opcional |

### 2.2 Columnas que faltan en tablas existentes

| Tabla | Columna faltante | Por qué hace falta |
|---|---|---|
| `torneos` | `nombre_torneo` | No existe ningún campo de nombre/título para un torneo — solo `formato_torneo` (texto largo) y `reglas_torneo`. Hoy no hay forma de listar torneos por nombre. |
| `torneos` | `horariofin_torneo` o `duracion_torneo` | Solo existe hora de inicio; no se puede calcular cuándo termina para el horario general ni para detectar traslapes. |
| `torneos` | `responsable_usuario_id` | Talleres sí tiene un responsable asignable; Torneos no tiene equivalente. |
| `hackathones` | `nombre_hackaton`, `descripcion_hackaton` | Igual que Torneos, no hay título ni descripción — el Visitante no tendría qué mostrar. |
| `hackathones` | `responsable_usuario_id` | Mismo caso que Torneos. |
| `eventos` | `descripcion_evento` | El Visitante necesita ver "descripciones" de los eventos según tu propia definición del rol, y la tabla no tiene dónde guardarla. |
| `eventos` | `aulas_id_aula` o `disponibilidades_id_disponiblidad` | Eventos no tiene lugar asociado; los otros tres tipos sí. |
| `propuestas` | `revisado_por_usuario_id`, `fecha_revision` | Para saber qué Coordinador aprobó/rechazó y cuándo (trazabilidad de la aprobación). |
| `usuarios` | — | `grupos_id_grupo` es `NOT NULL` para **todos** los roles, pero solo Alumno y Comité pertenecen a un grupo real; hoy el Coordinador de ejemplo (id 2) está forzado a tener un grupo asignado sin sentido. Convendría permitir `NULL`. |

### 2.3 Bugs de integridad ya presentes en el esquema y el código

Estos no son "tablas faltantes" pero sí conviene que los conozcas porque afectan directamente la confiabilidad de los datos que vas a construir encima:

**Unicidad de matrícula mal definida.** El índice `matricula_UNIQUE` de la tabla `usuarios` en realidad está aplicado sobre `id_usuario`, no sobre `matricula_usuario` (`ADD UNIQUE KEY matricula_UNIQUE (id_usuario)`). Es decir, la base de datos **no impide matrículas duplicadas** — hoy esa validación existe solo en el código de `integrar-alumnos.php` (un `SELECT COUNT(*)` antes de insertar), lo que deja una ventana de condición de carrera si dos altas ocurren casi al mismo tiempo.

**Llave foránea faltante en `talleres.responsable_usuario_id`.** La columna tiene un índice (`fk_talleres_responsable`) pero nunca se agregó el `ADD CONSTRAINT` correspondiente en la sección de "Filtros" del dump. Hoy se puede asignar como responsable un `id_usuario` que no existe, y MySQL no lo va a rechazar.

**`publicar-taller.php` no debería poder ejecutarse tal cual está en producción.** El `INSERT INTO talleres` de ese archivo no incluye la columna `usuarios_id_usuario`, que es `NOT NULL` y no tiene valor por defecto ni `AUTO_INCREMENT`. Con el `sql_mode` estricto que trae MySQL 8 por defecto, ese INSERT debería fallar. Los dos talleres que ya existen en el dump tienen `usuarios_id_usuario = 1` cargado directamente (probablemente vía phpMyAdmin), no a través del formulario — lo que sugiere que el formulario de publicar taller nunca se probó de punta a punta contra esta base.

**Contraseñas en texto plano.** `password_usuario` es `char(8)` y se compara/actualiza sin ningún hash (`WHERE ... AND password_usuario = ?`). Cambiar esto a un hash (`password_hash`/`password_verify`) implica también ampliar la columna (un hash bcrypt no cabe en 8 caracteres), así que es un cambio de esquema, no solo de código.

## 3. Arquitectura y estructura de archivos

El repositorio es PHP procedural puro (sin Composer, sin autoload, sin framework), con PDO + prepared statements para las consultas — un enfoque razonable para un equipo pequeño y un proyecto de este tamaño, y el código existente sí protege consistentemente contra XSS (`htmlspecialchars` en todas las salidas revisadas) e inyección SQL (siempre prepared statements). La estructura actual es:

```
/config/db.php              credenciales de conexión (en texto plano, sin variables de entorno)
/api/login.php, logout.php  endpoints JSON consumidos por src/js/script.js
/src/includes/               header.php (nav + sesión) y footer.php, incluidos en cada página
/src/css, /src/js, /src/img  estáticos
/talleres/*.php              todo el módulo de Talleres (7 archivos)
/usuarios/*.php               alta de grupos y de alumnos (2 archivos)
index.php, cambiar-password.php, imprimir_credencial.php   páginas sueltas en la raíz
```

Comparado con los cinco roles y sus necesidades, la cobertura funcional real hoy es:

| Módulo | Estado |
|---|---|
| Autenticación (login/logout/cambiar contraseña) | Implementado |
| Gafete/credencial imprimible | Implementado |
| Catálogos (aulas, disponibilidades, jornadas, grupos) | Implementado |
| Alta de alumnos (manual y por Excel) | Implementado |
| Talleres (publicar, editar, ver, inscribirse) | Implementado |
| Torneos | No existe ninguna pantalla |
| Hackathones | No existe ninguna pantalla |
| Foro de propuestas (crear, ver, comentar, votar, aprobar) | Enlazado en el menú pero los archivos no existen |
| Perfil de usuario / cambiar foto | Enlazado en el menú pero los archivos no existen |
| Panel de Docente | No existe |
| Imprimibles y horario general del Coordinador | No existe |

Tres observaciones de arquitectura, más allá de qué falta por construir:

**Autorización repetida y sin centralizar.** Cada archivo de administración repite manualmente `if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_id'] != 3) { header('Location: /index.php'); exit; }`. Funciona, pero al agregar Torneos, Hackathones, Docentes y el foro de propuestas —cada uno con reglas de acceso ligeramente distintas por rol— este patrón se va a repetir muchas veces más y es fácil que alguna pantalla nueva olvide la validación. Conviene extraer un helper (`src/includes/auth.php` con algo como `requireRole([3])` o `requireLogin()`) antes de seguir agregando módulos.

**Consultas repetidas entre archivos.** El `JOIN` de `jornadas` con `unidadesregionales`, y el de `disponibilidades` con `aulas`, aparece copiado y pegado en `crear-jornada.php`, `publicar-taller.php` y `modificar-taller.php`. No es grave todavía, pero con cuatro módulos de eventos compartiendo las mismas tablas de catálogo, vale la pena moverlas a funciones reutilizables.

**Credenciales de base de datos en el repositorio.** `config/db.php` tiene usuario y contraseña de MySQL escritos directamente en el archivo versionado en git. Antes de subir esto a un servidor real conviene moverlas a variables de entorno (o al menos a un archivo `config/db.local.php` fuera de git) y agregar un `.gitignore` — hoy el repositorio no tiene ninguno.

Cuando construyas los módulos que faltan, la extensión natural de la estructura actual sería agregar `torneos/`, `hackathones/`, `propuestas/` (o los dos archivos que ya están enlazados dentro de `talleres/`), `docentes/` y una carpeta `imprimibles/` para las vistas del Coordinador — manteniendo el mismo patrón de "una carpeta por módulo, un archivo por acción" que ya usa el proyecto, sin necesidad de introducir un framework.

## 4. Script de llenado de las tablas vacías

Catorce tablas existen en el esquema pero no tienen ningún registro todavía: `anuncioweb`, `asistenciahackatons`, `asistenciashackathons`, `asistenciatalleres`, `comentarios`, `disponibilidades` ya tiene datos así que no aplica — la lista real vacía es: `anuncioweb`, `asistenciahackatons`, `asistenciashackathons`, `asistenciatalleres`, `comentarios`, `entregables`, `eventos`, `hackathones`, `inscripcioneshackatons`, `inscripcionestorneos`, `propuestas`, `rubricas`, `torneos`, `votos`.

El script adjunto (`seed_datos_prueba.sql`) llena únicamente esas catorce tablas, reutilizando los catálogos que ya existen en el dump (la única jornada, las dos disponibilidades, los usuarios y grupos ya cargados) — no modifica ninguna tabla que ya tenía datos. Deliberadamente reutiliza las mismas `disponibilidades` que ya usan los dos talleres existentes para un torneo y para el hackathon, de modo que quede un caso real de traslape de horario en la misma aula el mismo día — útil para probar la función de detección de conflictos del Coordinador en cuanto la construyas. Estos son los supuestos con los que se escribió: se asume que las catorce tablas están vacías (`AUTO_INCREMENT` en 1, como en el dump proporcionado) y que el script se ejecuta una sola vez sobre esa misma copia de la base; si ya insertaste datos manualmente en alguna de ellas, habrá que ajustar los IDs referenciados.

El archivo se guardó en tu repositorio, en `database/seed_datos_prueba.sql`.

## 5. Cambios de esquema sugeridos (opcional, no incluido en el script de arriba)

Estos `ALTER TABLE`/`CREATE TABLE` no se ejecutaron — son la traducción directa de la sección 2 a SQL, para que los tengas a la mano si decides aplicarlos. Conviene aplicarlos (o decidir no hacerlo) antes de construir Torneos, Hackathones y el foro de propuestas, porque varias pantallas nuevas dependerán de estas columnas.

```sql
-- Corregir la unicidad real de la matrícula
ALTER TABLE usuarios ADD UNIQUE KEY uq_matricula (matricula_usuario);

-- Agregar la llave foránea que falta
ALTER TABLE talleres ADD CONSTRAINT fk_talleres_responsable
    FOREIGN KEY (responsable_usuario_id) REFERENCES usuarios (id_usuario);

-- Permitir que roles distintos a Alumno/Comité no requieran grupo
ALTER TABLE usuarios MODIFY grupos_id_grupo INT(11) NULL;

-- Completar Torneos
ALTER TABLE torneos
    ADD COLUMN nombre_torneo VARCHAR(60) NOT NULL AFTER id_torneo,
    ADD COLUMN horariofin_torneo TIME NULL AFTER horarioinicio_torneo,
    ADD COLUMN responsable_usuario_id INT(11) NULL,
    ADD CONSTRAINT fk_torneos_responsable FOREIGN KEY (responsable_usuario_id) REFERENCES usuarios (id_usuario);

-- Completar Hackathones
ALTER TABLE hackathones
    ADD COLUMN nombre_hackaton VARCHAR(60) NOT NULL AFTER id_hackaton,
    ADD COLUMN descripcion_hackaton MEDIUMTEXT NULL,
    ADD COLUMN responsable_usuario_id INT(11) NULL,
    ADD CONSTRAINT fk_hackathones_responsable FOREIGN KEY (responsable_usuario_id) REFERENCES usuarios (id_usuario);

-- Completar Eventos
ALTER TABLE eventos
    ADD COLUMN descripcion_evento MEDIUMTEXT NULL,
    ADD COLUMN aulas_id_aula INT(11) NULL,
    ADD CONSTRAINT fk_eventos_aulas FOREIGN KEY (aulas_id_aula) REFERENCES aulas (id_aula);

-- Trazabilidad de aprobación de propuestas
ALTER TABLE propuestas
    ADD COLUMN revisado_por_usuario_id INT(11) NULL,
    ADD COLUMN fecha_revision DATETIME NULL,
    ADD CONSTRAINT fk_propuestas_revisor FOREIGN KEY (revisado_por_usuario_id) REFERENCES usuarios (id_usuario);

-- Tabla de asistencia para Torneos (simétrica a asistenciatalleres)
CREATE TABLE asistenciatorneos (
    id_asistenciatorneo INT(11) NOT NULL AUTO_INCREMENT,
    estado_asistenciatorneo ENUM('Asistio','Falto','Justificado') NOT NULL,
    inscripcionestorneos_idinscripcionestorneo INT(11) NOT NULL,
    PRIMARY KEY (id_asistenciatorneo),
    CONSTRAINT fk_asistenciatorneos_inscripciones
        FOREIGN KEY (inscripcionestorneos_idinscripcionestorneo)
        REFERENCES inscripcionestorneos (idinscripcionestorneo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

No incluyo aquí una tabla de `equipos` porque depende de si Torneos/Hackathones se juegan en equipo o individualmente — conviene confirmarlo antes de modelarla.
