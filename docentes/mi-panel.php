<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

requireRole([ROL_DOCENTE]);

$idDocente = $_SESSION['usuario_id'];

$talleres = $pdo->prepare("
    SELECT t.id_taller, t.nombre_taller, t.fecha_taller, t.horainicio_taller, t.horatermino_taller, a.nombre_aula
    FROM talleres t
    JOIN disponibilidades d ON d.id_disponiblidad = t.disponibilidades_id_disponiblidad
    JOIN aulas a ON a.id_aula = d.aulas_id_aula
    WHERE t.responsable_usuario_id = ?
    ORDER BY t.fecha_taller ASC
");
$talleres->execute([$idDocente]);
$talleres = $talleres->fetchAll(PDO::FETCH_ASSOC);

$torneos = $pdo->prepare("
    SELECT t.id_torneo, t.nombre_torneo, t.fechatorneo, t.horarioinicio_torneo, t.horariofin_torneo, a.nombre_aula
    FROM torneos t
    JOIN disponibilidades d ON d.id_disponiblidad = t.disponibilidades_id_disponiblidad
    JOIN aulas a ON a.id_aula = d.aulas_id_aula
    WHERE t.responsable_usuario_id = ?
    ORDER BY t.fechatorneo ASC
");
$torneos->execute([$idDocente]);
$torneos = $torneos->fetchAll(PDO::FETCH_ASSOC);

$hackathones = $pdo->prepare("
    SELECT h.id_hackaton, h.nombre_hackaton, h.fechainicio_hackaton, h.fechafinal_hackaton, a.nombre_aula
    FROM hackathones h
    JOIN disponibilidades d ON d.id_disponiblidad = h.disponibilidades_id_disponiblidad
    JOIN aulas a ON a.id_aula = d.aulas_id_aula
    WHERE h.responsable_usuario_id = ?
    ORDER BY h.fechainicio_hackaton ASC
");
$hackathones->execute([$idDocente]);
$hackathones = $hackathones->fetchAll(PDO::FETCH_ASSOC);

$alumnosTaller = $pdo->prepare("
    SELECT u.nombres_usuario, u.apellidos_usuario, u.matricula_usuario
    FROM inscripcionestalleres i
    JOIN usuarios u ON u.id_usuario = i.usuarios_id_usuario
    WHERE i.talleres_id_taller = ?
    ORDER BY u.apellidos_usuario ASC
");

$alumnosTorneo = $pdo->prepare("
    SELECT u.nombres_usuario, u.apellidos_usuario, u.matricula_usuario
    FROM inscripcionestorneos i
    JOIN usuarios u ON u.id_usuario = i.usuarios_id_usuario
    WHERE i.torneos_id_torneo = ?
    ORDER BY u.apellidos_usuario ASC
");

$alumnosHackathon = $pdo->prepare("
    SELECT u.nombres_usuario, u.apellidos_usuario, u.matricula_usuario
    FROM inscripcioneshackatons i
    JOIN usuarios u ON u.id_usuario = i.usuarios_id_usuario
    WHERE i.hackathones_id_hackaton = ?
    ORDER BY u.apellidos_usuario ASC
");

require_once __DIR__ . '/../src/includes/header.php';
?>

<div class="container mt-5 mb-5">
    <h2 class="text-vino mb-4">Mi panel de Docente</h2>

    <?php if (empty($talleres) && empty($torneos) && empty($hackathones)): ?>
        <div class="alert alert-secondary">Todavía no tienes talleres, torneos ni hackathones asignados como responsable.</div>
    <?php endif; ?>

    <?php if (!empty($talleres)): ?>
        <h5 class="mb-3">Mis talleres</h5>
        <?php foreach ($talleres as $t):
            $alumnosTaller->execute([$t['id_taller']]);
            $alumnos = $alumnosTaller->fetchAll(PDO::FETCH_ASSOC);
        ?>
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <h6 class="text-vino"><?= htmlspecialchars($t['nombre_taller']) ?></h6>
                    <p class="small text-muted mb-2">
                        <i class="bi bi-calendar me-1"></i> <?= htmlspecialchars($t['fecha_taller']) ?>
                        &middot; <i class="bi bi-clock me-1"></i> <?= substr($t['horainicio_taller'], 0, 5) ?> - <?= substr($t['horatermino_taller'], 0, 5) ?>
                        &middot; <i class="bi bi-geo-alt me-1"></i> Aula <?= htmlspecialchars($t['nombre_aula']) ?>
                    </p>
                    <p class="small fw-semibold mb-1">Alumnos inscritos (<?= count($alumnos) ?>)</p>
                    <ul class="small mb-0">
                        <?php foreach ($alumnos as $a): ?>
                            <li><?= htmlspecialchars($a['nombres_usuario'] . ' ' . $a['apellidos_usuario']) ?> — <?= htmlspecialchars($a['matricula_usuario']) ?></li>
                        <?php endforeach; ?>
                        <?php if (empty($alumnos)): ?><li class="text-muted">Sin inscritos todavía.</li><?php endif; ?>
                    </ul>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if (!empty($torneos)): ?>
        <h5 class="mb-3 mt-4">Mis torneos</h5>
        <?php foreach ($torneos as $t):
            $alumnosTorneo->execute([$t['id_torneo']]);
            $alumnos = $alumnosTorneo->fetchAll(PDO::FETCH_ASSOC);
        ?>
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <h6 class="text-vino"><?= htmlspecialchars($t['nombre_torneo']) ?></h6>
                    <p class="small text-muted mb-2">
                        <i class="bi bi-calendar me-1"></i> <?= htmlspecialchars($t['fechatorneo']) ?>
                        &middot; <i class="bi bi-clock me-1"></i> <?= substr($t['horarioinicio_torneo'], 0, 5) ?><?= $t['horariofin_torneo'] ? ' - ' . substr($t['horariofin_torneo'], 0, 5) : '' ?>
                        &middot; <i class="bi bi-geo-alt me-1"></i> Aula <?= htmlspecialchars($t['nombre_aula']) ?>
                    </p>
                    <p class="small fw-semibold mb-1">Participantes (<?= count($alumnos) ?>)</p>
                    <ul class="small mb-0">
                        <?php foreach ($alumnos as $a): ?>
                            <li><?= htmlspecialchars($a['nombres_usuario'] . ' ' . $a['apellidos_usuario']) ?> — <?= htmlspecialchars($a['matricula_usuario']) ?></li>
                        <?php endforeach; ?>
                        <?php if (empty($alumnos)): ?><li class="text-muted">Sin inscritos todavía.</li><?php endif; ?>
                    </ul>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if (!empty($hackathones)): ?>
        <h5 class="mb-3 mt-4">Mis hackathones</h5>
        <?php foreach ($hackathones as $h):
            $alumnosHackathon->execute([$h['id_hackaton']]);
            $alumnos = $alumnosHackathon->fetchAll(PDO::FETCH_ASSOC);
        ?>
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <h6 class="text-vino"><?= htmlspecialchars($h['nombre_hackaton']) ?></h6>
                    <p class="small text-muted mb-2">
                        <i class="bi bi-calendar-range me-1"></i> <?= htmlspecialchars($h['fechainicio_hackaton']) ?> al <?= htmlspecialchars($h['fechafinal_hackaton']) ?>
                        &middot; <i class="bi bi-geo-alt me-1"></i> Aula <?= htmlspecialchars($h['nombre_aula']) ?>
                    </p>
                    <p class="small fw-semibold mb-1">Participantes (<?= count($alumnos) ?>)</p>
                    <ul class="small mb-0">
                        <?php foreach ($alumnos as $a): ?>
                            <li><?= htmlspecialchars($a['nombres_usuario'] . ' ' . $a['apellidos_usuario']) ?> — <?= htmlspecialchars($a['matricula_usuario']) ?></li>
                        <?php endforeach; ?>
                        <?php if (empty($alumnos)): ?><li class="text-muted">Sin inscritos todavía.</li><?php endif; ?>
                    </ul>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../src/includes/footer.php'; ?>
