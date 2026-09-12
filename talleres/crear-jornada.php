<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_id'] != 3) {
    header('Location: /index.php');
    exit;
}

$error = '';
$exito = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre_jornada'] ?? '');
    $fecha  = $_POST['fecha_jornada'] ?? '';
    $ur     = $_POST['unidadesregionales_id_ur'] ?? '';

    if (empty($nombre) || empty($fecha) || empty($ur)) {
        $error = 'Todos los campos son obligatorios.';
    } elseif (mb_strlen($nombre) > 45) {
        $error = 'El nombre de la jornada no puede superar 45 caracteres.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO jornadas (nombre_jornada, fecha_jornada, unidadesregionales_id_ur) VALUES (?, ?, ?)");
        $stmt->execute([$nombre, $fecha, $ur]);
        $exito = 'Jornada creada correctamente.';
    }
}

$unidades = $pdo->query("SELECT * FROM unidadesregionales ORDER BY nombre_ur ASC")->fetchAll(PDO::FETCH_ASSOC);

$jornadas = $pdo->query("
    SELECT j.*, ur.nombre_ur
    FROM jornadas j
    JOIN unidadesregionales ur ON ur.id_ur = j.unidadesregionales_id_ur
    ORDER BY j.fecha_jornada DESC
")->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../src/includes/header.php';
?>

<div class="container mt-5 mb-5">
    <h2 class="text-vino mb-4">Administrar Jornadas</h2>

    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($exito): ?><div class="alert alert-success"><?= htmlspecialchars($exito) ?></div><?php endif; ?>

    <?php if (empty($unidades)): ?>
        <div class="alert alert-warning">
            No hay Unidades Regionales registradas todavía. Necesitas al menos una en la tabla <code>unidadesregionales</code> antes de crear una jornada.
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-5">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="mb-3">Nueva jornada</h5>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Nombre de la jornada</label>
                            <input type="text" name="nombre_jornada" class="form-control" maxlength="45" placeholder="Ej. Jornada Otoño 2026" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Fecha</label>
                            <input type="date" name="fecha_jornada" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Unidad Regional</label>
                            <select name="unidadesregionales_id_ur" class="form-select" required>
                                <option value="">Selecciona...</option>
                                <?php foreach ($unidades as $u): ?>
                                    <option value="<?= htmlspecialchars($u['id_ur']) ?>"><?= htmlspecialchars($u['nombre_ur']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-login w-100" <?= empty($unidades) ? 'disabled' : '' ?>>Guardar</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            <h5 class="mb-3">Jornadas existentes</h5>
            <div class="table-responsive">
            <table class="table table-striped">
                <thead><tr><th>ID</th><th>Nombre</th><th>Fecha</th><th>Unidad Regional</th></tr></thead>
                <tbody>
                    <?php foreach ($jornadas as $j): ?>
                        <tr>
                            <td><?= $j['id_jornada'] ?></td>
                            <td><?= htmlspecialchars($j['nombre_jornada']) ?></td>
                            <td><?= htmlspecialchars($j['fecha_jornada']) ?></td>
                            <td><?= htmlspecialchars($j['nombre_ur']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($jornadas)): ?>
                        <tr><td colspan="4" class="text-muted">Aún no hay jornadas registradas.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>

    <a href="/talleres/crear-disponibilidad.php" class="btn btn-outline-secondary mt-3">Siguiente: Crear Disponibilidad &rarr;</a>
</div>

<?php require_once __DIR__ . '/../src/includes/footer.php'; ?>