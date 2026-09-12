<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

requireRole([ROL_COORDINADOR]);

$error = '';
$exito = '';
$urFija = $_SESSION['ur_id'];

// Roles de personal que el Coordinador puede dar de alta desde aquí
// (Alumno y Comité se dan de alta desde "Integrar alumnos" o por
// aprobación de propuestas; Coordinador no se auto-asigna desde un form).
$rolesPersonal = [
    ROL_DOCENTE           => 'Docente',
    ROL_DEPTO_INGENIERIA  => 'Departamento de Ingeniería (solo consulta)',
    ROL_JEFATURA_ACADEMIA => 'Jefatura de Academia (solo consulta)',
];

/**
 * Mismo esquema de contraseña que usa integrar-alumnos.php:
 * año actual (2 dígitos) + "06" fijo + 4 caracteres derivados del id.
 */
function generarPasswordPersonal(int $consecutivo): string
{
    $anio = date('y');
    $numero = str_pad((string) $consecutivo, 4, '0', STR_PAD_LEFT);
    $numero = substr($numero, -4);

    $mapaLetras = ['0'=>'A','1'=>'B','2'=>'C','3'=>'D','4'=>'E','5'=>'F','6'=>'G','7'=>'H','8'=>'I','9'=>'J'];
    $letras = '';
    foreach (str_split($numero) as $digito) {
        $letras .= $mapaLetras[$digito] ?? 'X';
    }

    return $anio . '06' . $letras;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombres   = trim($_POST['nombres_usuario'] ?? '');
    $apellidos = trim($_POST['apellidos_usuario'] ?? '');
    $matricula = trim($_POST['matricula_usuario'] ?? '');
    $correo    = trim($_POST['correo_usuario'] ?? '');
    $rol       = (int) ($_POST['roles_id'] ?? 0);

    if (empty($nombres) || empty($apellidos) || empty($matricula) || !isset($rolesPersonal[$rol])) {
        $error = 'Completa nombres, apellidos, matrícula y selecciona un puesto válido.';
    } elseif (strlen($matricula) > 8) {
        $error = 'La matrícula no puede tener más de 8 caracteres.';
    } elseif (mb_strlen($nombres) > 80 || mb_strlen($apellidos) > 80) {
        $error = 'Nombres y apellidos no pueden superar 80 caracteres.';
    } elseif ($correo !== '' && mb_strlen($correo) > 120) {
        $error = 'El correo no puede superar 120 caracteres.';
    } elseif ($correo !== '' && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $error = 'El correo no es válido.';
    } else {
        $chk = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE matricula_usuario = ?");
        $chk->execute([$matricula]);

        if ($chk->fetchColumn() > 0) {
            $error = 'Ya existe un usuario con esa matrícula.';
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO usuarios (
                    nombres_usuario, apellidos_usuario, matricula_usuario,
                    habilitado_usuario, unidadesregionales_id_ur, roles_id,
                    grupos_id_grupo, password_usuario, rutaimagen_usuario, correo_usuario
                ) VALUES (?, ?, ?, 1, ?, ?, NULL, 'TEMP0000', '', ?)
            ");
            $stmt->execute([$nombres, $apellidos, $matricula, $urFija, $rol, $correo ?: null]);
            $idNuevo = $pdo->lastInsertId();

            $passwordFinal = generarPasswordPersonal((int) $idNuevo);
            $pdo->prepare("UPDATE usuarios SET password_usuario = ? WHERE id_usuario = ?")
                ->execute([$passwordFinal, $idNuevo]);

            $exito = "{$nombres} {$apellidos} dado de alta como {$rolesPersonal[$rol]}, matrícula {$matricula}. "
                   . "Contraseña generada: {$passwordFinal} (compártela de forma segura).";
        }
    }
}

$personal = $pdo->query("
    SELECT u.id_usuario, u.nombres_usuario, u.apellidos_usuario, u.matricula_usuario, r.rol
    FROM usuarios u
    JOIN roles r ON r.id = u.roles_id
    WHERE u.roles_id IN (" . ROL_DOCENTE . ", " . ROL_DEPTO_INGENIERIA . ", " . ROL_JEFATURA_ACADEMIA . ")
    ORDER BY u.id_usuario DESC
")->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../src/includes/header.php';
?>

<div class="container mt-5 mb-5">
    <h2 class="text-vino mb-4">Dar de alta Docentes / Jefaturas</h2>

    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($exito): ?><div class="alert alert-success"><?= htmlspecialchars($exito) ?></div><?php endif; ?>

    <div class="row">
        <div class="col-md-5">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="mb-3">Nuevo integrante de personal</h5>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Puesto</label>
                            <select name="roles_id" class="form-select" required>
                                <option value="">Selecciona...</option>
                                <?php foreach ($rolesPersonal as $idRol => $nombreRol): ?>
                                    <option value="<?= $idRol ?>"><?= htmlspecialchars($nombreRol) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nombre(s)</label>
                            <input type="text" name="nombres_usuario" class="form-control" maxlength="80" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Apellidos</label>
                            <input type="text" name="apellidos_usuario" class="form-control" maxlength="80" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Matrícula (máx. 8 caracteres)</label>
                            <input type="text" name="matricula_usuario" maxlength="8" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Correo (opcional)</label>
                            <input type="email" name="correo_usuario" class="form-control" maxlength="120">
                        </div>
                        <button type="submit" class="btn btn-login w-100">Dar de alta</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            <h5 class="mb-3">Personal registrado</h5>
            <div class="table-responsive">
            <table class="table table-striped">
                <thead><tr><th>Nombre</th><th>Matrícula</th><th>Puesto</th></tr></thead>
                <tbody>
                    <?php foreach ($personal as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['nombres_usuario'] . ' ' . $p['apellidos_usuario']) ?></td>
                            <td><?= htmlspecialchars($p['matricula_usuario']) ?></td>
                            <td><?= htmlspecialchars($p['rol']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($personal)): ?>
                        <tr><td colspan="3" class="text-muted">Aún no hay Docentes ni personal de jefatura registrados.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../src/includes/footer.php'; ?>
