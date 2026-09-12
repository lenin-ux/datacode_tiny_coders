<?php
/**
 * Roles y helpers de autorización centralizados.
 * Antes cada página repetía manualmente "if ($_SESSION['rol_id'] != 3) ...".
 * Este archivo concentra esa lógica para que agregar módulos nuevos no
 * implique volver a copiar/pegar el mismo bloque en cada archivo.
 *
 * Catálogo real de `roles` (tabla `roles`):
 *   1 Alumno
 *   2 Comite            (alumno miembro de comité)
 *   3 Coordinación
 *   4 Departamento de Ingeniería  (solo visualiza)
 *   5 Jefatura de Academia        (solo visualiza)
 *   6 Docente
 */

const ROL_ALUMNO             = 1;
const ROL_COMITE             = 2;
const ROL_COORDINADOR        = 3;
const ROL_DEPTO_INGENIERIA   = 4;
const ROL_JEFATURA_ACADEMIA  = 5;
const ROL_DOCENTE            = 6;

// Roles que solo pueden visualizar información (paneles/imprimibles del
// Coordinador), sin crear ni editar nada.
const ROLES_VISUALIZADORES = [ROL_DEPTO_INGENIERIA, ROL_JEFATURA_ACADEMIA];

// Roles que conviven en el mismo foro de propuestas y pueden inscribirse
// a actividades como alumnos.
const ROLES_ALUMNADO = [ROL_ALUMNO, ROL_COMITE];

function usuarioLogueado(): bool
{
    return isset($_SESSION['usuario_id']);
}

function rolActual(): ?int
{
    return isset($_SESSION['rol_id']) ? (int) $_SESSION['rol_id'] : null;
}

/**
 * Corta la ejecución y redirige si no hay sesión iniciada.
 */
function requireLogin(string $redirect = '/index.php'): void
{
    if (!usuarioLogueado()) {
        header("Location: $redirect");
        exit;
    }
}

/**
 * Corta la ejecución y redirige si el usuario no tiene uno de los roles
 * permitidos. Uso: requireRole([ROL_COORDINADOR]);
 */
function requireRole(array $rolesPermitidos, string $redirect = '/index.php'): void
{
    requireLogin($redirect);
    if (!in_array(rolActual(), $rolesPermitidos, true)) {
        header("Location: $redirect");
        exit;
    }
}

/**
 * Coordinador o alguno de los roles de jefatura que solo visualizan.
 * Útil para páginas de imprimibles/horario general: el Coordinador puede
 * crear/editar, las jefaturas solo consultan la misma pantalla.
 */
function requireCoordinadorOVisualizador(string $redirect = '/index.php'): void
{
    requireRole(array_merge([ROL_COORDINADOR], ROLES_VISUALIZADORES), $redirect);
}

function esCoordinador(): bool
{
    return rolActual() === ROL_COORDINADOR;
}

function esDocente(): bool
{
    return rolActual() === ROL_DOCENTE;
}

function esVisualizador(): bool
{
    return in_array(rolActual(), ROLES_VISUALIZADORES, true);
}

function esComiteOSuperior(): bool
{
    return in_array(rolActual(), [ROL_COMITE, ROL_COORDINADOR], true);
}

function esAlumnado(): bool
{
    return in_array(rolActual(), ROLES_ALUMNADO, true);
}
