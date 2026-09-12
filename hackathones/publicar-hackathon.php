<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

requireRole([ROL_COORDINADOR]);

$error = '';
$exito = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre       = trim($_POST['nombre_hackaton'] ?? '');
    $descripcion  = trim($_POST['descripcion_hackaton'] ?? '');
    $fechaInicio  = $_POST['fechainicio_hackaton'] ?? '';
    $fechaFinal   = $_POST['fechafinal_hackaton'] ?? '';
    $cuota        = trim($_POST['cuota_hackaton'] ?? '');
    $disponibilidad = $_POST['disponibilidades_id_disponiblidad'] ?? '';
    $jornada      = $_POST['jornadas_id_jornada'] ?? '';
    $numAsistencia = trim($_POST['numasistencia_hackathon'] ?? '');

    if (empty($nombre) || empty($fechaInicio) || empty($fechaFinal)
        || empty($disponibilidad) || empty($jornada) || $numAsistencia === '') {
        $error = 'Todos los campos son obligatorios excepto la descripción y la cuota.';
    } elseif (mb_strlen($nombre) > 80) {
        $error = 'El nombre no puede superar 80 caracteres.';
    } elseif ($cuota !== '' && (float) $cuota > 999.99) {
        $error = 'La cuota no puede ser mayor a 999.99.';
    } elseif ($fechaFinal < $fechaInicio) {
        $error = 'La fecha final no puede ser anterior a la fecha de inicio.';
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO hackathones (
                nombre_hackaton, descripcion_hackaton,
                fechainicio_hackaton, fechafinal_hackaton, cuota_hackaton,
                disponibilidades_id_disponiblidad, jornadas_id_jornada,
                numasistencia_hackathon
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $nombre, $descripcion ?: null,
            $fechaInicio, $fechaFinal, $cuota !== '' ? $cuota : null,
            $disponibilidad, $jornada,
            $numAsistencia
        ]);
        $exito = 'Hackathon publicado correctamente.';
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

require_once __DIR__ . '/../src/includes/header.php';
?>

<div class="container mt-5 mb-5">
    <h2 class="text-vino mb-4">Publicar nuevo hackathon</h2>

    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($exito): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($exito) ?>
            &middot; <a href="/hackathones/ver-hackathones.php">Ver hackathones publicados</a>
        </div>
    <?php endif; ?>

    <?php if (empty($jornadas) || empty($disponibilidades)): ?>
        <div class="alert alert-warning">
            Antes de publicar un hackathon necesitas al menos:
            <ul class="mb-0">
                <?php if (empty($jornadas)): ?><li><a href="/talleres/crear-jornada.php">Crear una jornada</a></li><?php endif; ?>
                <?php if (empty($disponibilidades)): ?><li><a href="/talleres/crear-disponibilidad.php">Crear una disponibilidad</a></li><?php endif; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm" style="max-width: 700px;">
        <div class="card-body">
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Nombre del hackathon</label>
                    <input type="text" name="nombre_hackaton" maxlength="80" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Descripción</label>
                    <textarea name="descripcion_hackaton" class="form-control" rows="3"></textarea>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Fecha inicio</label>
                        <input type="date" name="fechainicio_hackaton" class="form-control" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Fecha final</label>
                        <input type="date" name="fechafinal_hackaton" class="form-control" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Cuota (opcional)</label>
                        <input type="number" step="0.01" min="0" max="999.99" name="cuota_hackaton" class="form-control" placeholder="0.00">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Número de pases de lista esperados</label>
                    <input type="number" name="numasistencia_hackathon" class="form-control" min="1" value="2" required>
                    <small class="text-muted">Cuántos registros de asistencia se tomarán durante el evento (por ejemplo, 2 si dura 2 días).</small>
                </div>

                <div class="mb-3">
                    <label class="form-label">Jornada</label>
                    <select name="jornadas_id_jornada" class="form-select" required>
                        <option value="">Selecciona...</option>
                        <?php foreach ($jornadas as $j): ?>
                            <option value="<?= $j['id_jornada'] ?>">
                                <?= htmlspecialchars($j['nombre_jornada']) ?> —
                                <?= htmlspecialchars($j['fecha_jornada']) ?>
                                (<?= htmlspecialchars($j['nombre_ur']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label">Disponibilidad (aula, fecha y horario de inicio)</label>
                    <select name="disponibilidades_id_disponiblidad" class="form-select" required>
                        <option value="">Selecciona...</option>
                        <?php foreach ($disponibilidades as $d): ?>
                            <option value="<?= $d['id_disponiblidad'] ?>">
                                <?= htmlspecialchars($d['nombre_aula']) ?> —
                                <?= htmlspecialchars($d['fecha_disponibilidad']) ?>
                                <?= substr($d['hora_disponibilidad'], 0, 5) ?>
                                (<?= $d['duracion_disponibilidad'] ?> min)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">El responsable (Docente), las rúbricas y los entregables se agregan después desde "Modificar hackathones".</small>
                </div>

                <button type="submit" class="btn btn-login w-100"
                    <?= (empty($jornadas) || empty($disponibilidades)) ? 'disabled' : '' ?>>
                    Publicar hackathon
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../src/includes/footer.php'; ?>
