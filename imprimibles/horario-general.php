<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

requireCoordinadorOVisualizador();

// Unifica los 4 tipos de evento (Talleres, Torneos, Hackathones, Eventos
// generales) en un solo horario cronológico. Cada tipo tiene columnas de
// fecha/hora distintas en su tabla original, así que se normalizan aquí
// con UNION ALL a: tipo, nombre, fecha, hora_inicio, hora_fin, aula.
$sql = "
    SELECT 'Taller' AS tipo, t.nombre_taller AS nombre, t.fecha_taller AS fecha,
           t.horainicio_taller AS hora_inicio, t.horatermino_taller AS hora_fin,
           a.nombre_aula AS aula
    FROM talleres t
    JOIN disponibilidades d ON d.id_disponiblidad = t.disponibilidades_id_disponiblidad
    JOIN aulas a ON a.id_aula = d.aulas_id_aula

    UNION ALL

    SELECT 'Torneo', tor.nombre_torneo, tor.fechatorneo,
           tor.horarioinicio_torneo, tor.horariofin_torneo,
           a2.nombre_aula
    FROM torneos tor
    JOIN disponibilidades d2 ON d2.id_disponiblidad = tor.disponibilidades_id_disponiblidad
    JOIN aulas a2 ON a2.id_aula = d2.aulas_id_aula

    UNION ALL

    SELECT 'Hackathon', h.nombre_hackaton, d3.fecha_disponibilidad,
           d3.hora_disponibilidad,
           ADDTIME(d3.hora_disponibilidad, SEC_TO_TIME(d3.duracion_disponibilidad * 60)),
           a3.nombre_aula
    FROM hackathones h
    JOIN disponibilidades d3 ON d3.id_disponiblidad = h.disponibilidades_id_disponiblidad
    JOIN aulas a3 ON a3.id_aula = d3.aulas_id_aula

    UNION ALL

    SELECT 'Evento', e.nombre_evento, e.fecha_evento,
           e.hora_evento,
           ADDTIME(e.hora_evento, SEC_TO_TIME(e.duracion_evento * 60)),
           a4.nombre_aula
    FROM eventos e
    LEFT JOIN aulas a4 ON a4.id_aula = e.aulas_id_aula

    ORDER BY fecha ASC, hora_inicio ASC
";
$eventos = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

/**
 * Dos rangos de horario se traslapan si uno empieza antes de que el otro
 * termine y viceversa. Si falta la hora de fin de alguno, se trata como
 * un evento puntual (no se puede evaluar traslape de duración).
 */
function seTraslapan(?string $inicioA, ?string $finA, ?string $inicioB, ?string $finB): bool
{
    if (!$finA || !$finB) {
        return $inicioA === $inicioB;
    }
    return $inicioA < $finB && $inicioB < $finA;
}

// Marca qué filas tienen conflicto: mismo día, misma aula, horario traslapado.
$conflictos = array_fill(0, count($eventos), false);
foreach ($eventos as $i => $a) {
    foreach ($eventos as $j => $b) {
        if ($i === $j || $a['aula'] === null || $b['aula'] === null) {
            continue;
        }
        if ($a['fecha'] === $b['fecha'] && $a['aula'] === $b['aula']
            && seTraslapan($a['hora_inicio'], $a['hora_fin'], $b['hora_inicio'], $b['hora_fin'])) {
            $conflictos[$i] = true;
        }
    }
}

$totalConflictos = count(array_filter($conflictos));

$coloresTipo = [
    'Taller'    => 'primary',
    'Torneo'    => 'success',
    'Hackathon' => 'danger',
    'Evento'    => 'secondary',
];

require_once __DIR__ . '/../src/includes/header.php';
?>

<div class="container mt-5 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <h2 class="text-vino mb-0">Horario general de la jornada</h2>
        <button onclick="window.print()" class="btn btn-login">
            <i class="bi bi-printer me-1"></i> Imprimir
        </button>
    </div>

    <?php if (esVisualizador()): ?>
        <p class="text-muted small no-print">Estás en modo de solo consulta: puedes ver e imprimir este horario, pero no editarlo.</p>
    <?php endif; ?>

    <?php if ($totalConflictos > 0): ?>
        <div class="alert alert-danger no-print">
            <i class="bi bi-exclamation-triangle me-1"></i>
            Se detectaron <?= $totalConflictos ?> evento(s) con traslape de horario (misma aula, mismo día, horas encimadas) — están marcados en rojo abajo.
        </div>
    <?php endif; ?>

    <?php if (empty($eventos)): ?>
        <div class="alert alert-secondary">Todavía no hay eventos registrados en ningún módulo.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Tipo</th>
                        <th>Nombre</th>
                        <th>Fecha</th>
                        <th>Hora inicio</th>
                        <th>Hora fin</th>
                        <th>Aula</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($eventos as $i => $e): ?>
                        <tr class="<?= $conflictos[$i] ? 'table-danger' : '' ?>">
                            <td><span class="badge bg-<?= $coloresTipo[$e['tipo']] ?? 'secondary' ?>"><?= htmlspecialchars($e['tipo']) ?></span></td>
                            <td>
                                <?= htmlspecialchars($e['nombre']) ?>
                                <?php if ($conflictos[$i]): ?>
                                    <i class="bi bi-exclamation-triangle-fill text-danger ms-1" title="Traslape de horario"></i>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($e['fecha']) ?></td>
                            <td><?= $e['hora_inicio'] ? substr($e['hora_inicio'], 0, 5) : '—' ?></td>
                            <td><?= $e['hora_fin'] ? substr($e['hora_fin'], 0, 5) : '—' ?></td>
                            <td><?= $e['aula'] ? htmlspecialchars($e['aula']) : '—' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../src/includes/footer.php'; ?>
