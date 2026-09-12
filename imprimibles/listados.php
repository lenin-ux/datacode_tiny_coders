<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

requireCoordinadorOVisualizador();

$talleres = $pdo->query("
    SELECT t.nombre_taller, t.fecha_taller, t.horainicio_taller, t.horatermino_taller,
           a.nombre_aula, u.nombres_usuario, u.apellidos_usuario,
           (SELECT COUNT(*) FROM inscripcionestalleres i WHERE i.talleres_id_taller = t.id_taller) AS inscritos
    FROM talleres t
    JOIN disponibilidades d ON d.id_disponiblidad = t.disponibilidades_id_disponiblidad
    JOIN aulas a ON a.id_aula = d.aulas_id_aula
    LEFT JOIN usuarios u ON u.id_usuario = t.responsable_usuario_id
    ORDER BY t.fecha_taller ASC
")->fetchAll(PDO::FETCH_ASSOC);

$torneos = $pdo->query("
    SELECT tor.nombre_torneo, tor.fechatorneo, tor.horarioinicio_torneo, tor.horariofin_torneo,
           a.nombre_aula, u.nombres_usuario, u.apellidos_usuario,
           (SELECT COUNT(*) FROM inscripcionestorneos i WHERE i.torneos_id_torneo = tor.id_torneo) AS inscritos
    FROM torneos tor
    JOIN disponibilidades d ON d.id_disponiblidad = tor.disponibilidades_id_disponiblidad
    JOIN aulas a ON a.id_aula = d.aulas_id_aula
    LEFT JOIN usuarios u ON u.id_usuario = tor.responsable_usuario_id
    ORDER BY tor.fechatorneo ASC
")->fetchAll(PDO::FETCH_ASSOC);

$hackathones = $pdo->query("
    SELECT h.nombre_hackaton, h.fechainicio_hackaton, h.fechafinal_hackaton,
           a.nombre_aula, u.nombres_usuario, u.apellidos_usuario,
           (SELECT COUNT(*) FROM inscripcioneshackatons i WHERE i.hackathones_id_hackaton = h.id_hackaton) AS inscritos
    FROM hackathones h
    JOIN disponibilidades d ON d.id_disponiblidad = h.disponibilidades_id_disponiblidad
    JOIN aulas a ON a.id_aula = d.aulas_id_aula
    LEFT JOIN usuarios u ON u.id_usuario = h.responsable_usuario_id
    ORDER BY h.fechainicio_hackaton ASC
")->fetchAll(PDO::FETCH_ASSOC);

$eventosGenerales = $pdo->query("
    SELECT e.nombre_evento, e.fecha_evento, e.hora_evento, e.duracion_evento, a.nombre_aula
    FROM eventos e
    LEFT JOIN aulas a ON a.id_aula = e.aulas_id_aula
    ORDER BY e.fecha_evento ASC
")->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../src/includes/header.php';
?>

<div class="container mt-5 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <h2 class="text-vino mb-0">Listados imprimibles</h2>
        <div class="dropdown">
            <button class="btn btn-login dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-printer me-1"></i> Imprimir
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="#" onclick="imprimirListado('todo'); return false;">Todo</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="#" onclick="imprimirListado('talleres'); return false;">Solo Talleres</a></li>
                <li><a class="dropdown-item" href="#" onclick="imprimirListado('torneos'); return false;">Solo Torneos</a></li>
                <li><a class="dropdown-item" href="#" onclick="imprimirListado('hackathones'); return false;">Solo Hackathones</a></li>
                <li><a class="dropdown-item" href="#" onclick="imprimirListado('eventos'); return false;">Solo Eventos generales</a></li>
            </ul>
        </div>
    </div>

    <div class="listado-seccion" data-seccion="talleres">
    <h5 class="text-vino mt-4">Talleres</h5>
    <div class="table-responsive">
    <table class="table table-striped table-sm mb-5">
        <thead><tr><th>Nombre</th><th>Fecha</th><th>Horario</th><th>Aula</th><th>Responsable</th><th>Inscritos</th></tr></thead>
        <tbody>
            <?php foreach ($talleres as $t): ?>
                <tr>
                    <td><?= htmlspecialchars($t['nombre_taller']) ?></td>
                    <td><?= htmlspecialchars($t['fecha_taller']) ?></td>
                    <td><?= substr($t['horainicio_taller'], 0, 5) ?> - <?= substr($t['horatermino_taller'], 0, 5) ?></td>
                    <td><?= htmlspecialchars($t['nombre_aula']) ?></td>
                    <td><?= $t['nombres_usuario'] ? htmlspecialchars($t['nombres_usuario'] . ' ' . $t['apellidos_usuario']) : 'Sin asignar' ?></td>
                    <td><?= (int) $t['inscritos'] ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($talleres)): ?><tr><td colspan="6" class="text-muted">Sin talleres registrados.</td></tr><?php endif; ?>
        </tbody>
    </table>
    </div>
    </div>

    <div class="listado-seccion" data-seccion="torneos">
    <h5 class="text-vino">Torneos</h5>
    <div class="table-responsive">
    <table class="table table-striped table-sm mb-5">
        <thead><tr><th>Nombre</th><th>Fecha</th><th>Horario</th><th>Aula</th><th>Responsable</th><th>Inscritos</th></tr></thead>
        <tbody>
            <?php foreach ($torneos as $t): ?>
                <tr>
                    <td><?= htmlspecialchars($t['nombre_torneo']) ?></td>
                    <td><?= htmlspecialchars($t['fechatorneo']) ?></td>
                    <td><?= substr($t['horarioinicio_torneo'], 0, 5) ?><?= $t['horariofin_torneo'] ? ' - ' . substr($t['horariofin_torneo'], 0, 5) : '' ?></td>
                    <td><?= htmlspecialchars($t['nombre_aula']) ?></td>
                    <td><?= $t['nombres_usuario'] ? htmlspecialchars($t['nombres_usuario'] . ' ' . $t['apellidos_usuario']) : 'Sin asignar' ?></td>
                    <td><?= (int) $t['inscritos'] ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($torneos)): ?><tr><td colspan="6" class="text-muted">Sin torneos registrados.</td></tr><?php endif; ?>
        </tbody>
    </table>
    </div>
    </div>

    <div class="listado-seccion" data-seccion="hackathones">
    <h5 class="text-vino">Hackathones</h5>
    <div class="table-responsive">
    <table class="table table-striped table-sm mb-5">
        <thead><tr><th>Nombre</th><th>Fecha inicio</th><th>Fecha fin</th><th>Aula</th><th>Responsable</th><th>Inscritos</th></tr></thead>
        <tbody>
            <?php foreach ($hackathones as $h): ?>
                <tr>
                    <td><?= htmlspecialchars($h['nombre_hackaton']) ?></td>
                    <td><?= htmlspecialchars($h['fechainicio_hackaton']) ?></td>
                    <td><?= htmlspecialchars($h['fechafinal_hackaton']) ?></td>
                    <td><?= htmlspecialchars($h['nombre_aula']) ?></td>
                    <td><?= $h['nombres_usuario'] ? htmlspecialchars($h['nombres_usuario'] . ' ' . $h['apellidos_usuario']) : 'Sin asignar' ?></td>
                    <td><?= (int) $h['inscritos'] ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($hackathones)): ?><tr><td colspan="6" class="text-muted">Sin hackathones registrados.</td></tr><?php endif; ?>
        </tbody>
    </table>
    </div>
    </div>

    <div class="listado-seccion" data-seccion="eventos">
    <h5 class="text-vino">Eventos generales</h5>
    <div class="table-responsive">
    <table class="table table-striped table-sm">
        <thead><tr><th>Nombre</th><th>Fecha</th><th>Hora</th><th>Duración</th><th>Aula</th></tr></thead>
        <tbody>
            <?php foreach ($eventosGenerales as $e): ?>
                <tr>
                    <td><?= htmlspecialchars($e['nombre_evento']) ?></td>
                    <td><?= htmlspecialchars($e['fecha_evento']) ?></td>
                    <td><?= substr($e['hora_evento'], 0, 5) ?></td>
                    <td><?= (int) $e['duracion_evento'] ?> min</td>
                    <td><?= $e['nombre_aula'] ? htmlspecialchars($e['nombre_aula']) : '—' ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($eventosGenerales)): ?><tr><td colspan="5" class="text-muted">Sin eventos generales registrados.</td></tr><?php endif; ?>
        </tbody>
    </table>
    </div>
    </div>
</div>

<?php require_once __DIR__ . '/../src/includes/footer.php'; ?>
