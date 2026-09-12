<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

requireRole([ROL_COORDINADOR]);

$error = '';
$exito = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre       = trim($_POST['nombre_torneo'] ?? '');
    $formato      = trim($_POST['formato_torneo'] ?? '');
    $reglas       = trim($_POST['reglas_torneo'] ?? '');
    $fecha        = $_POST['fechatorneo'] ?? '';
    $horaInicio   = $_POST['horarioinicio_torneo'] ?? '';
    $horaFin      = $_POST['horariofin_torneo'] ?? '';
    $disponibilidad = $_POST['disponibilidades_id_disponiblidad'] ?? '';
    $jornada      = $_POST['jornadas_id_jornada'] ?? '';

    if (empty($nombre) || empty($formato) || empty($reglas) || empty($fecha)
        || empty($horaInicio) || empty($disponibilidad) || empty($jornada)) {
        $error = 'Todos los campos son obligatorios excepto la hora de término.';
    } elseif (mb_strlen($nombre) > 80) {
        $error = 'El nombre no puede superar 80 caracteres.';
    } elseif ($horaFin !== '' && $horaFin <= $horaInicio) {
        $error = 'La hora de término debe ser posterior a la hora de inicio.';
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO torneos (
                nombre_torneo, formato_torneo, reglas_torneo,
                horarioinicio_torneo, horariofin_torneo, fechatorneo,
                disponibilidades_id_disponiblidad, jornadas_id_jornada
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $nombre, $formato, $reglas,
            $horaInicio, $horaFin ?: null, $fecha,
            $disponibilidad, $jornada
        ]);
        $exito = 'Torneo publicado correctamente.';
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
    <h2 class="text-vino mb-4">Publicar nuevo torneo</h2>

    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($exito): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($exito) ?>
            &middot; <a href="/torneos/ver-torneos.php">Ver torneos publicados</a>
        </div>
    <?php endif; ?>

    <?php if (empty($jornadas) || empty($disponibilidades)): ?>
        <div class="alert alert-warning">
            Antes de publicar un torneo necesitas al menos:
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
                    <label class="form-label">Nombre del torneo</label>
                    <input type="text" name="nombre_torneo" maxlength="80" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Formato</label>
                    <textarea name="formato_torneo" class="form-control" rows="2" placeholder="Ej. Eliminación directa, equipos de 3 integrantes" required></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Reglas</label>
                    <textarea name="reglas_torneo" class="form-control" rows="3" required></textarea>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Fecha</label>
                        <input type="date" name="fechatorneo" class="form-control" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Hora inicio</label>
                        <input type="time" name="horarioinicio_torneo" class="form-control" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Hora término (opcional)</label>
                        <input type="time" name="horariofin_torneo" class="form-control">
                    </div>
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
                    <label class="form-label">Disponibilidad (aula, fecha y horario)</label>
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
                    <small class="text-muted">El responsable (Docente) se asignará después desde "Modificar torneos".</small>
                </div>

                <button type="submit" class="btn btn-login w-100"
                    <?= (empty($jornadas) || empty($disponibilidades)) ? 'disabled' : '' ?>>
                    Publicar torneo
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../src/includes/footer.php'; ?>
