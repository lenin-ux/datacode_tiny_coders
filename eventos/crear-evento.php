<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

requireRole([ROL_COORDINADOR]);

$error = '';
$exito = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre      = trim($_POST['nombre_evento'] ?? '');
    $descripcion = trim($_POST['descripcion_evento'] ?? '');
    $fecha       = $_POST['fecha_evento'] ?? '';
    $hora        = $_POST['hora_evento'] ?? '';
    $duracion    = trim($_POST['duracion_evento'] ?? '');
    $jornada     = $_POST['jornadas_id_jornada'] ?? '';
    $aula        = $_POST['aulas_id_aula'] ?? '';

    if (empty($nombre) || empty($fecha) || empty($hora) || empty($duracion) || empty($jornada)) {
        $error = 'Todos los campos son obligatorios, excepto la descripción y el aula.';
    } elseif (mb_strlen($nombre) > 60) {
        $error = 'El nombre no puede superar 60 caracteres.';
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO eventos (nombre_evento, descripcion_evento, fecha_evento, hora_evento, duracion_evento, jornadas_id_jornada, aulas_id_aula)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$nombre, $descripcion ?: null, $fecha, $hora, $duracion, $jornada, $aula ?: null]);
        $exito = 'Evento creado correctamente.';
    }
}

$jornadas = $pdo->query("
    SELECT j.*, ur.nombre_ur
    FROM jornadas j
    JOIN unidadesregionales ur ON ur.id_ur = j.unidadesregionales_id_ur
    ORDER BY j.fecha_jornada DESC
")->fetchAll(PDO::FETCH_ASSOC);

$aulas = $pdo->query("SELECT * FROM aulas ORDER BY nombre_aula ASC")->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../src/includes/header.php';
?>

<div class="container mt-5 mb-5">
    <h2 class="text-vino mb-4">Crear evento general</h2>

    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($exito): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($exito) ?>
            &middot; <a href="/eventos/ver-eventos.php">Ver eventos publicados</a>
        </div>
    <?php endif; ?>

    <?php if (empty($jornadas)): ?>
        <div class="alert alert-warning">
            No hay jornadas registradas. <a href="/talleres/crear-jornada.php">Crea una jornada primero</a>.
        </div>
    <?php endif; ?>

    <div class="card shadow-sm" style="max-width: 600px;">
        <div class="card-body">
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Nombre del evento</label>
                    <input type="text" name="nombre_evento" maxlength="60" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Descripción</label>
                    <textarea name="descripcion_evento" class="form-control" rows="3"></textarea>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Fecha</label>
                        <input type="date" name="fecha_evento" class="form-control" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Hora</label>
                        <input type="time" name="hora_evento" class="form-control" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Duración (min)</label>
                        <input type="number" name="duracion_evento" class="form-control" min="1" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Jornada</label>
                    <select name="jornadas_id_jornada" class="form-select" required>
                        <option value="">Selecciona...</option>
                        <?php foreach ($jornadas as $j): ?>
                            <option value="<?= $j['id_jornada'] ?>">
                                <?= htmlspecialchars($j['nombre_jornada']) ?> — <?= htmlspecialchars($j['fecha_jornada']) ?>
                                (<?= htmlspecialchars($j['nombre_ur']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="form-label">Aula (opcional)</label>
                    <select name="aulas_id_aula" class="form-select">
                        <option value="">Sin aula específica</option>
                        <?php foreach ($aulas as $a): ?>
                            <option value="<?= $a['id_aula'] ?>"><?= htmlspecialchars($a['nombre_aula']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-login w-100" <?= empty($jornadas) ? 'disabled' : '' ?>>Crear evento</button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../src/includes/footer.php'; ?>
