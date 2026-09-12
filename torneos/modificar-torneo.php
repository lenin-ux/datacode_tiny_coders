<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

requireRole([ROL_COORDINADOR]);

$error = '';
$exito = '';
$id = $_GET['id'] ?? ($_POST['id_torneo'] ?? null);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_torneo    = $_POST['id_torneo'] ?? '';
    $nombre       = trim($_POST['nombre_torneo'] ?? '');
    $formato      = trim($_POST['formato_torneo'] ?? '');
    $reglas       = trim($_POST['reglas_torneo'] ?? '');
    $fecha        = $_POST['fechatorneo'] ?? '';
    $horaInicio   = $_POST['horarioinicio_torneo'] ?? '';
    $horaFin      = $_POST['horariofin_torneo'] ?? '';
    $disponibilidad = $_POST['disponibilidades_id_disponiblidad'] ?? '';
    $jornada      = $_POST['jornadas_id_jornada'] ?? '';
    $responsable  = $_POST['responsable_usuario_id'] ?? '';

    if (empty($nombre) || empty($formato) || empty($reglas) || empty($fecha)
        || empty($horaInicio) || empty($disponibilidad) || empty($jornada)) {
        $error = 'Todos los campos son obligatorios excepto la hora de término y el responsable.';
    } elseif (mb_strlen($nombre) > 80) {
        $error = 'El nombre no puede superar 80 caracteres.';
    } elseif ($horaFin !== '' && $horaFin <= $horaInicio) {
        $error = 'La hora de término debe ser posterior a la hora de inicio.';
    } else {
        $stmt = $pdo->prepare("
            UPDATE torneos SET
                nombre_torneo = ?, formato_torneo = ?, reglas_torneo = ?,
                horarioinicio_torneo = ?, horariofin_torneo = ?, fechatorneo = ?,
                disponibilidades_id_disponiblidad = ?, jornadas_id_jornada = ?,
                responsable_usuario_id = ?
            WHERE id_torneo = ?
        ");
        $stmt->execute([
            $nombre, $formato, $reglas,
            $horaInicio, $horaFin ?: null, $fecha,
            $disponibilidad, $jornada,
            $responsable ?: null,
            $id_torneo
        ]);
        $exito = 'Torneo actualizado correctamente.';
        $id = $id_torneo;
    }
}

$jornadas = $pdo->query("
    SELECT j.*, ur.nombre_ur
    FROM jornadas j
    JOIN unidadesregionales ur ON ur.id_ur = j.unidadesregionales_id_ur
    ORDER BY j.fecha_jornada DESC
")->fetchAll(PDO::FETCH_ASSOC);

$disponibilidades = $pdo->query("
    SELECT d.*, a.nombre_aula
    FROM disponibilidades d
    JOIN aulas a ON a.id_aula = d.aulas_id_aula
    ORDER BY d.fecha_disponibilidad DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Solo usuarios con rol Docente pueden ser responsables de un torneo
$responsables = $pdo->query("
    SELECT id_usuario, nombres_usuario, apellidos_usuario
    FROM usuarios
    WHERE roles_id = " . ROL_DOCENTE . "
    ORDER BY nombres_usuario ASC
")->fetchAll(PDO::FETCH_ASSOC);

$torneoEdit = null;
if ($id) {
    $stmtT = $pdo->prepare("SELECT * FROM torneos WHERE id_torneo = ?");
    $stmtT->execute([$id]);
    $torneoEdit = $stmtT->fetch(PDO::FETCH_ASSOC);
}

$torneos = $pdo->query("
    SELECT t.id_torneo, t.nombre_torneo, t.fechatorneo, u.nombres_usuario, u.apellidos_usuario
    FROM torneos t
    LEFT JOIN usuarios u ON u.id_usuario = t.responsable_usuario_id
    ORDER BY t.fechatorneo DESC
")->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../src/includes/header.php';
?>

<div class="container mt-5 mb-5">
    <h2 class="text-vino mb-4">Modificar torneos</h2>

    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($exito): ?><div class="alert alert-success"><?= htmlspecialchars($exito) ?></div><?php endif; ?>

    <?php if (!$torneoEdit): ?>

        <div class="table-responsive">
        <table class="table table-striped">
            <thead><tr><th>Torneo</th><th>Fecha</th><th>Responsable</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($torneos as $t): ?>
                    <tr>
                        <td><?= htmlspecialchars($t['nombre_torneo']) ?></td>
                        <td><?= htmlspecialchars($t['fechatorneo']) ?></td>
                        <td><?= $t['nombres_usuario'] ? htmlspecialchars($t['nombres_usuario'] . ' ' . $t['apellidos_usuario']) : 'Sin asignar' ?></td>
                        <td>
                            <a href="?id=<?= $t['id_torneo'] ?>" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-pencil"></i> Editar
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($torneos)): ?>
                    <tr><td colspan="4" class="text-muted">No hay torneos para editar. <a href="/torneos/publicar-torneo.php">Publica uno primero</a>.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>

    <?php else: ?>

        <a href="/torneos/modificar-torneo.php" class="btn btn-link mb-3 ps-0">&larr; Volver a la lista</a>

        <div class="card shadow-sm" style="max-width: 700px;">
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="id_torneo" value="<?= $torneoEdit['id_torneo'] ?>">

                    <div class="mb-3">
                        <label class="form-label">Nombre del torneo</label>
                        <input type="text" name="nombre_torneo" maxlength="80" class="form-control"
                               value="<?= htmlspecialchars($torneoEdit['nombre_torneo']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Formato</label>
                        <textarea name="formato_torneo" class="form-control" rows="2" required><?= htmlspecialchars($torneoEdit['formato_torneo']) ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Reglas</label>
                        <textarea name="reglas_torneo" class="form-control" rows="3" required><?= htmlspecialchars($torneoEdit['reglas_torneo']) ?></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Fecha</label>
                            <input type="date" name="fechatorneo" class="form-control"
                                   value="<?= htmlspecialchars($torneoEdit['fechatorneo']) ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Hora inicio</label>
                            <input type="time" name="horarioinicio_torneo" class="form-control"
                                   value="<?= substr($torneoEdit['horarioinicio_torneo'], 0, 5) ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Hora término</label>
                            <input type="time" name="horariofin_torneo" class="form-control"
                                   value="<?= $torneoEdit['horariofin_torneo'] ? substr($torneoEdit['horariofin_torneo'], 0, 5) : '' ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Jornada</label>
                        <select name="jornadas_id_jornada" class="form-select" required>
                            <?php foreach ($jornadas as $j): ?>
                                <option value="<?= $j['id_jornada'] ?>" <?= $j['id_jornada'] == $torneoEdit['jornadas_id_jornada'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($j['nombre_jornada']) ?> — <?= htmlspecialchars($j['fecha_jornada']) ?>
                                    (<?= htmlspecialchars($j['nombre_ur']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Disponibilidad</label>
                        <select name="disponibilidades_id_disponiblidad" class="form-select" required>
                            <?php foreach ($disponibilidades as $d): ?>
                                <option value="<?= $d['id_disponiblidad'] ?>" <?= $d['id_disponiblidad'] == $torneoEdit['disponibilidades_id_disponiblidad'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($d['nombre_aula']) ?> — <?= htmlspecialchars($d['fecha_disponibilidad']) ?>
                                    <?= substr($d['hora_disponibilidad'], 0, 5) ?> (<?= $d['duracion_disponibilidad'] ?> min)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Responsable (Docente)</label>
                        <select name="responsable_usuario_id" class="form-select">
                            <option value="">Sin asignar</option>
                            <?php foreach ($responsables as $r): ?>
                                <option value="<?= $r['id_usuario'] ?>" <?= $r['id_usuario'] == $torneoEdit['responsable_usuario_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($r['nombres_usuario'] . ' ' . $r['apellidos_usuario']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (empty($responsables)): ?>
                            <small class="text-muted">Aún no hay Docentes dados de alta. <a href="/usuarios/dar-de-alta-personal.php">Dar de alta un Docente</a>.</small>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="btn btn-login w-100">Guardar cambios</button>
                </form>
            </div>
        </div>

    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../src/includes/footer.php'; ?>
