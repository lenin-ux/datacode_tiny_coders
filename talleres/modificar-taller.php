<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_id'] != 3) {
    header('Location: /index.php');
    exit;
}

$error = '';
$exito = '';
$id = $_GET['id'] ?? ($_POST['id_taller'] ?? null);

// Guardar cambios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_taller    = $_POST['id_taller'] ?? '';
    $nombre       = trim($_POST['nombre_taller'] ?? '');
    $materiales   = trim($_POST['materiales_taller'] ?? '');
    $descripcion  = trim($_POST['descripcion_taller'] ?? '');
    $fecha        = $_POST['fecha_taller'] ?? '';
    $horaInicio   = $_POST['horainicio_taller'] ?? '';
    $horaTermino  = $_POST['horatermino_taller'] ?? '';
    $disponibilidad = $_POST['disponibilidades_id_disponiblidad'] ?? '';
    $jornada      = $_POST['jornadas_id_jornada'] ?? '';
    $responsable  = $_POST['responsable_usuario_id'] ?? '';

    if (empty($nombre) || empty($materiales) || empty($descripcion) || empty($fecha)
        || empty($horaInicio) || empty($horaTermino) || empty($disponibilidad) || empty($jornada)) {
        $error = 'Todos los campos son obligatorios excepto el responsable.';
    } elseif ($horaTermino <= $horaInicio) {
        $error = 'La hora de término debe ser posterior a la hora de inicio.';
    } else {
        $stmt = $pdo->prepare("
            UPDATE talleres SET
                nombre_taller = ?, materiales_taller = ?, descripcion_taller = ?,
                fecha_taller = ?, horainicio_taller = ?, horatermino_taller = ?,
                disponibilidades_id_disponiblidad = ?, jornadas_id_jornada = ?,
                responsable_usuario_id = ?
            WHERE id_taller = ?
        ");
        $stmt->execute([
            $nombre, $materiales, $descripcion,
            $fecha, $horaInicio, $horaTermino,
            $disponibilidad, $jornada,
            $responsable ?: null,
            $id_taller
        ]);
        $exito = 'Taller actualizado correctamente.';
        $id = $id_taller; // para recargar el mismo formulario con los datos actualizados
    }
}

// Listas para los selects
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

// Solo usuarios que no sean Alumno pueden ser responsables
$responsables = $pdo->query("
    SELECT u.id_usuario, u.nombres_usuario, u.apellidos_usuario, r.rol
    FROM usuarios u
    JOIN roles r ON r.id = u.roles_id
    WHERE u.roles_id != 1
    ORDER BY u.nombres_usuario ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Si hay un id específico, cargamos ese taller para editar
$tallerEdit = null;
if ($id) {
    $stmtT = $pdo->prepare("SELECT * FROM talleres WHERE id_taller = ?");
    $stmtT->execute([$id]);
    $tallerEdit = $stmtT->fetch(PDO::FETCH_ASSOC);
}

// Lista general de talleres (para elegir cuál editar si no viene id en la URL)
$talleres = $pdo->query("
    SELECT t.id_taller, t.nombre_taller, t.fecha_taller, u.nombres_usuario, u.apellidos_usuario
    FROM talleres t
    LEFT JOIN usuarios u ON u.id_usuario = t.responsable_usuario_id
    ORDER BY t.fecha_taller DESC
")->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../src/includes/header.php';
?>

<div class="container mt-5 mb-5">
    <h2 class="text-vino mb-4">Modificar talleres</h2>

    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($exito): ?><div class="alert alert-success"><?= htmlspecialchars($exito) ?></div><?php endif; ?>

    <?php if (!$tallerEdit): ?>

        <!-- Lista de talleres para elegir cuál editar -->
        <table class="table table-striped">
            <thead><tr><th>Taller</th><th>Fecha</th><th>Responsable</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($talleres as $t): ?>
                    <tr>
                        <td><?= htmlspecialchars($t['nombre_taller']) ?></td>
                        <td><?= htmlspecialchars($t['fecha_taller']) ?></td>
                        <td><?= $t['nombres_usuario'] ? htmlspecialchars($t['nombres_usuario'] . ' ' . $t['apellidos_usuario']) : 'Sin asignar' ?></td>
                        <td>
                            <a href="?id=<?= $t['id_taller'] ?>" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-pencil"></i> Editar
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($talleres)): ?>
                    <tr><td colspan="4" class="text-muted">No hay talleres para editar. <a href="/talleres/publicar-taller.php">Publica uno primero</a>.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

    <?php else: ?>

        <!-- Formulario de edición precargado -->
        <a href="/talleres/modificar-taller.php" class="btn btn-link mb-3 ps-0">&larr; Volver a la lista</a>

        <div class="card shadow-sm" style="max-width: 700px;">
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="id_taller" value="<?= $tallerEdit['id_taller'] ?>">

                    <div class="mb-3">
                        <label class="form-label">Nombre del taller</label>
                        <input type="text" name="nombre_taller" class="form-control"
                               value="<?= htmlspecialchars($tallerEdit['nombre_taller']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea name="descripcion_taller" class="form-control" rows="3" required><?= htmlspecialchars($tallerEdit['descripcion_taller']) ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Materiales necesarios</label>
                        <input type="text" name="materiales_taller" class="form-control"
                               value="<?= htmlspecialchars($tallerEdit['materiales_taller']) ?>" required>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Fecha</label>
                            <input type="date" name="fecha_taller" class="form-control"
                                   value="<?= htmlspecialchars($tallerEdit['fecha_taller']) ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Hora inicio</label>
                            <input type="time" name="horainicio_taller" class="form-control"
                                   value="<?= substr($tallerEdit['horainicio_taller'], 0, 5) ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Hora término</label>
                            <input type="time" name="horatermino_taller" class="form-control"
                                   value="<?= substr($tallerEdit['horatermino_taller'], 0, 5) ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Jornada</label>
                        <select name="jornadas_id_jornada" class="form-select" required>
                            <?php foreach ($jornadas as $j): ?>
                                <option value="<?= $j['id_jornada'] ?>" <?= $j['id_jornada'] == $tallerEdit['jornadas_id_jornada'] ? 'selected' : '' ?>>
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
                                <option value="<?= $d['id_disponiblidad'] ?>" <?= $d['id_disponiblidad'] == $tallerEdit['disponibilidades_id_disponiblidad'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($d['nombre_aula']) ?> — <?= htmlspecialchars($d['fecha_disponibilidad']) ?>
                                    <?= substr($d['hora_disponibilidad'], 0, 5) ?> (<?= $d['duracion_disponibilidad'] ?> min)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Responsable del taller</label>
                        <select name="responsable_usuario_id" class="form-select">
                            <option value="">Sin asignar</option>
                            <?php foreach ($responsables as $r): ?>
                                <option value="<?= $r['id_usuario'] ?>" <?= $r['id_usuario'] == $tallerEdit['responsable_usuario_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($r['nombres_usuario'] . ' ' . $r['apellidos_usuario']) ?> (<?= htmlspecialchars($r['rol']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-login w-100">Guardar cambios</button>
                </form>
            </div>
        </div>

    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../src/includes/footer.php'; ?>