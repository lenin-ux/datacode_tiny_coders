<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

requireRole([ROL_COORDINADOR]);

$error = '';
$exito = '';
$id = $_GET['id'] ?? ($_POST['id_hackaton'] ?? null);

// Guardar cambios generales del hackathon
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'guardar_datos') {
    $id_hackaton  = $_POST['id_hackaton'] ?? '';
    $nombre       = trim($_POST['nombre_hackaton'] ?? '');
    $descripcion  = trim($_POST['descripcion_hackaton'] ?? '');
    $fechaInicio  = $_POST['fechainicio_hackaton'] ?? '';
    $fechaFinal   = $_POST['fechafinal_hackaton'] ?? '';
    $cuota        = trim($_POST['cuota_hackaton'] ?? '');
    $disponibilidad = $_POST['disponibilidades_id_disponiblidad'] ?? '';
    $jornada      = $_POST['jornadas_id_jornada'] ?? '';
    $numAsistencia = trim($_POST['numasistencia_hackathon'] ?? '');
    $responsable  = $_POST['responsable_usuario_id'] ?? '';

    if (empty($nombre) || empty($fechaInicio) || empty($fechaFinal)
        || empty($disponibilidad) || empty($jornada) || $numAsistencia === '') {
        $error = 'Todos los campos son obligatorios excepto la descripción, la cuota y el responsable.';
    } elseif (mb_strlen($nombre) > 80) {
        $error = 'El nombre no puede superar 80 caracteres.';
    } elseif ($cuota !== '' && (float) $cuota > 999.99) {
        $error = 'La cuota no puede ser mayor a 999.99.';
    } elseif ($fechaFinal < $fechaInicio) {
        $error = 'La fecha final no puede ser anterior a la fecha de inicio.';
    } else {
        $stmt = $pdo->prepare("
            UPDATE hackathones SET
                nombre_hackaton = ?, descripcion_hackaton = ?,
                fechainicio_hackaton = ?, fechafinal_hackaton = ?, cuota_hackaton = ?,
                disponibilidades_id_disponiblidad = ?, jornadas_id_jornada = ?,
                numasistencia_hackathon = ?, responsable_usuario_id = ?
            WHERE id_hackaton = ?
        ");
        $stmt->execute([
            $nombre, $descripcion ?: null,
            $fechaInicio, $fechaFinal, $cuota !== '' ? $cuota : null,
            $disponibilidad, $jornada,
            $numAsistencia, $responsable ?: null,
            $id_hackaton
        ]);
        $exito = 'Hackathon actualizado correctamente.';
        $id = $id_hackaton;
    }
}

// Agregar rúbrica
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'agregar_rubrica') {
    $id = $_POST['id_hackaton'] ?? '';
    $criterio = trim($_POST['criterio_rubrica'] ?? '');
    $descripcionRubrica = trim($_POST['descripcion_rubrica'] ?? '');
    $puntaje = trim($_POST['puntaje_rubrica'] ?? '');

    if ($criterio !== '' && $descripcionRubrica !== '' && $puntaje !== '') {
        $pdo->prepare("INSERT INTO rubricas (criterio_rubrica, descripcion_rubrica, puntaje_rubrica, hackathones_id_hackaton) VALUES (?, ?, ?, ?)")
            ->execute([$criterio, $descripcionRubrica, $puntaje, $id]);
        $exito = 'Criterio de rúbrica agregado.';
    } else {
        $error = 'Completa criterio, descripción y puntaje para agregar la rúbrica.';
    }
}

// Agregar entregable
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'agregar_entregable') {
    $id = $_POST['id_hackaton'] ?? '';
    $descripcionEntregable = trim($_POST['descripcion_entregable'] ?? '');

    if ($descripcionEntregable !== '') {
        $pdo->prepare("INSERT INTO entregables (descripcion_entregable, hackathones_id_hackaton) VALUES (?, ?)")
            ->execute([$descripcionEntregable, $id]);
        $exito = 'Entregable agregado.';
    } else {
        $error = 'Describe el entregable antes de agregarlo.';
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

$responsables = $pdo->query("
    SELECT id_usuario, nombres_usuario, apellidos_usuario
    FROM usuarios
    WHERE roles_id = " . ROL_DOCENTE . "
    ORDER BY nombres_usuario ASC
")->fetchAll(PDO::FETCH_ASSOC);

$hackathonEdit = null;
$rubricas = [];
$entregables = [];
if ($id) {
    $stmtH = $pdo->prepare("SELECT * FROM hackathones WHERE id_hackaton = ?");
    $stmtH->execute([$id]);
    $hackathonEdit = $stmtH->fetch(PDO::FETCH_ASSOC);

    if ($hackathonEdit) {
        $stmtR = $pdo->prepare("SELECT * FROM rubricas WHERE hackathones_id_hackaton = ?");
        $stmtR->execute([$id]);
        $rubricas = $stmtR->fetchAll(PDO::FETCH_ASSOC);

        $stmtE = $pdo->prepare("SELECT * FROM entregables WHERE hackathones_id_hackaton = ?");
        $stmtE->execute([$id]);
        $entregables = $stmtE->fetchAll(PDO::FETCH_ASSOC);
    }
}

$hackathones = $pdo->query("
    SELECT h.id_hackaton, h.nombre_hackaton, h.fechainicio_hackaton, u.nombres_usuario, u.apellidos_usuario
    FROM hackathones h
    LEFT JOIN usuarios u ON u.id_usuario = h.responsable_usuario_id
    ORDER BY h.fechainicio_hackaton DESC
")->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../src/includes/header.php';
?>

<div class="container mt-5 mb-5">
    <h2 class="text-vino mb-4">Modificar hackathones</h2>

    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($exito): ?><div class="alert alert-success"><?= htmlspecialchars($exito) ?></div><?php endif; ?>

    <?php if (!$hackathonEdit): ?>

        <div class="table-responsive">
        <table class="table table-striped">
            <thead><tr><th>Hackathon</th><th>Inicio</th><th>Responsable</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($hackathones as $h): ?>
                    <tr>
                        <td><?= htmlspecialchars($h['nombre_hackaton']) ?></td>
                        <td><?= htmlspecialchars($h['fechainicio_hackaton']) ?></td>
                        <td><?= $h['nombres_usuario'] ? htmlspecialchars($h['nombres_usuario'] . ' ' . $h['apellidos_usuario']) : 'Sin asignar' ?></td>
                        <td>
                            <a href="?id=<?= $h['id_hackaton'] ?>" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-pencil"></i> Editar
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($hackathones)): ?>
                    <tr><td colspan="4" class="text-muted">No hay hackathones para editar. <a href="/hackathones/publicar-hackathon.php">Publica uno primero</a>.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>

    <?php else: ?>

        <a href="/hackathones/modificar-hackathon.php" class="btn btn-link mb-3 ps-0">&larr; Volver a la lista</a>

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="mb-3">Datos generales</h5>
                        <form method="POST">
                            <input type="hidden" name="accion" value="guardar_datos">
                            <input type="hidden" name="id_hackaton" value="<?= $hackathonEdit['id_hackaton'] ?>">

                            <div class="mb-3">
                                <label class="form-label">Nombre</label>
                                <input type="text" name="nombre_hackaton" maxlength="80" class="form-control"
                                       value="<?= htmlspecialchars($hackathonEdit['nombre_hackaton']) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Descripción</label>
                                <textarea name="descripcion_hackaton" class="form-control" rows="3"><?= htmlspecialchars($hackathonEdit['descripcion_hackaton'] ?? '') ?></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Fecha inicio</label>
                                    <input type="date" name="fechainicio_hackaton" class="form-control"
                                           value="<?= htmlspecialchars($hackathonEdit['fechainicio_hackaton']) ?>" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Fecha final</label>
                                    <input type="date" name="fechafinal_hackaton" class="form-control"
                                           value="<?= htmlspecialchars($hackathonEdit['fechafinal_hackaton']) ?>" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Cuota</label>
                                    <input type="number" step="0.01" min="0" max="999.99" name="cuota_hackaton" class="form-control"
                                           value="<?= htmlspecialchars($hackathonEdit['cuota_hackaton'] ?? '') ?>">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Número de pases de lista</label>
                                <input type="number" name="numasistencia_hackathon" class="form-control" min="1"
                                       value="<?= htmlspecialchars($hackathonEdit['numasistencia_hackathon']) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Jornada</label>
                                <select name="jornadas_id_jornada" class="form-select" required>
                                    <?php foreach ($jornadas as $j): ?>
                                        <option value="<?= $j['id_jornada'] ?>" <?= $j['id_jornada'] == $hackathonEdit['jornadas_id_jornada'] ? 'selected' : '' ?>>
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
                                        <option value="<?= $d['id_disponiblidad'] ?>" <?= $d['id_disponiblidad'] == $hackathonEdit['disponibilidades_id_disponiblidad'] ? 'selected' : '' ?>>
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
                                        <option value="<?= $r['id_usuario'] ?>" <?= $r['id_usuario'] == $hackathonEdit['responsable_usuario_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($r['nombres_usuario'] . ' ' . $r['apellidos_usuario']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-login w-100">Guardar cambios</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h6 class="mb-3">Rúbrica de evaluación</h6>
                        <ul class="small mb-3">
                            <?php foreach ($rubricas as $r): ?>
                                <li><?= htmlspecialchars($r['criterio_rubrica']) ?> — <?= (int) $r['puntaje_rubrica'] ?> pts</li>
                            <?php endforeach; ?>
                            <?php if (empty($rubricas)): ?><li class="text-muted">Sin criterios todavía.</li><?php endif; ?>
                        </ul>
                        <form method="POST" class="border-top pt-3">
                            <input type="hidden" name="accion" value="agregar_rubrica">
                            <input type="hidden" name="id_hackaton" value="<?= $hackathonEdit['id_hackaton'] ?>">
                            <div class="mb-2">
                                <input type="text" name="criterio_rubrica" maxlength="48" class="form-control form-control-sm" placeholder="Criterio" required>
                            </div>
                            <div class="mb-2">
                                <input type="text" name="descripcion_rubrica" class="form-control form-control-sm" placeholder="Descripción" required>
                            </div>
                            <div class="mb-2">
                                <input type="number" name="puntaje_rubrica" min="1" max="99" class="form-control form-control-sm" placeholder="Puntaje" required>
                            </div>
                            <button type="submit" class="btn btn-outline-secondary btn-sm w-100">Agregar criterio</button>
                        </form>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-body">
                        <h6 class="mb-3">Entregables</h6>
                        <ul class="small mb-3">
                            <?php foreach ($entregables as $e): ?>
                                <li><?= htmlspecialchars($e['descripcion_entregable']) ?></li>
                            <?php endforeach; ?>
                            <?php if (empty($entregables)): ?><li class="text-muted">Sin entregables todavía.</li><?php endif; ?>
                        </ul>
                        <form method="POST" class="border-top pt-3">
                            <input type="hidden" name="accion" value="agregar_entregable">
                            <input type="hidden" name="id_hackaton" value="<?= $hackathonEdit['id_hackaton'] ?>">
                            <div class="mb-2">
                                <textarea name="descripcion_entregable" class="form-control form-control-sm" rows="2" placeholder="Describe el entregable" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-outline-secondary btn-sm w-100">Agregar entregable</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../src/includes/footer.php'; ?>
