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
    $hora     = $_POST['hora_disponibilidad'] ?? '';
    $duracion = $_POST['duracion_disponibilidad'] ?? '';
    $fecha    = $_POST['fecha_disponibilidad'] ?? '';
    $aula     = $_POST['aulas_id_aula'] ?? '';

    if (empty($hora) || empty($duracion) || empty($fecha) || empty($aula)) {
        $error = 'Todos los campos son obligatorios.';
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO disponibilidades (hora_disponibilidad, duracion_disponibilidad, fecha_disponibilidad, aulas_id_aula)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$hora, $duracion, $fecha, $aula]);
        $exito = 'Disponibilidad creada correctamente.';
    }
}

$aulas = $pdo->query("SELECT * FROM aulas ORDER BY nombre_aula ASC")->fetchAll(PDO::FETCH_ASSOC);

$disponibilidades = $pdo->query("
    SELECT d.*, a.nombre_aula
    FROM disponibilidades d
    JOIN aulas a ON a.id_aula = d.aulas_id_aula
    ORDER BY d.fecha_disponibilidad DESC
")->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../src/includes/header.php';
?>

<div class="container mt-5 mb-5">
    <h2 class="text-vino mb-4">Administrar Disponibilidades</h2>

    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($exito): ?><div class="alert alert-success"><?= htmlspecialchars($exito) ?></div><?php endif; ?>

    <?php if (empty($aulas)): ?>
        <div class="alert alert-warning">
            No hay aulas registradas. <a href="/talleres/crear-aula.php">Crea una aula primero</a>.
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-5">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="mb-3">Nueva disponibilidad</h5>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Aula</label>
                            <select name="aulas_id_aula" class="form-select" required>
                                <option value="">Selecciona...</option>
                                <?php foreach ($aulas as $a): ?>
                                    <option value="<?= $a['id_aula'] ?>"><?= htmlspecialchars($a['nombre_aula']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Fecha</label>
                            <input type="date" name="fecha_disponibilidad" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Hora de inicio</label>
                            <input type="time" name="hora_disponibilidad" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Duración (minutos)</label>
                            <input type="number" name="duracion_disponibilidad" class="form-control" min="1" placeholder="Ej. 120" required>
                        </div>
                        <button type="submit" class="btn btn-login w-100" <?= empty($aulas) ? 'disabled' : '' ?>>Guardar</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            <h5 class="mb-3">Disponibilidades existentes</h5>
            <table class="table table-striped">
                <thead><tr><th>ID</th><th>Aula</th><th>Fecha</th><th>Hora</th><th>Duración</th></tr></thead>
                <tbody>
                    <?php foreach ($disponibilidades as $d): ?>
                        <tr>
                            <td><?= $d['id_disponiblidad'] ?></td>
                            <td><?= htmlspecialchars($d['nombre_aula']) ?></td>
                            <td><?= htmlspecialchars($d['fecha_disponibilidad']) ?></td>
                            <td><?= substr($d['hora_disponibilidad'], 0, 5) ?></td>
                            <td><?= $d['duracion_disponibilidad'] ?> min</td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($disponibilidades)): ?>
                        <tr><td colspan="5" class="text-muted">Aún no hay disponibilidades registradas.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <a href="/talleres/publicar-taller.php" class="btn btn-outline-secondary mt-3">Siguiente: Publicar Taller &rarr;</a>
</div>

<?php require_once __DIR__ . '/../src/includes/footer.php'; ?>