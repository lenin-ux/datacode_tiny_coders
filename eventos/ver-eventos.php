<?php

session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../src/includes/header.php';

$stmt = $pdo->query("
    SELECT e.id_evento, e.nombre_evento, e.descripcion_evento, e.fecha_evento,
           e.hora_evento, e.duracion_evento, a.nombre_aula
    FROM eventos e
    LEFT JOIN aulas a ON a.id_aula = e.aulas_id_aula
    ORDER BY e.fecha_evento ASC, e.hora_evento ASC
");
$eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-5 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-vino">Eventos generales de la jornada</h2>

        <?php if (usuarioLogueado() && esCoordinador()): ?>
            <a href="crear-evento.php" class="btn btn-login">
                <i class="bi bi-plus-circle me-1"></i> Crear evento
            </a>
        <?php endif; ?>
    </div>

    <?php if (empty($eventos)): ?>
        <div class="alert alert-secondary text-center">
            Aún no hay eventos generales publicados. Vuelve pronto.
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($eventos as $e): ?>
                <div class="col-md-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($e['nombre_evento']) ?></h5>
                            <?php if ($e['descripcion_evento']): ?>
                                <p class="card-text small text-muted"><?= htmlspecialchars($e['descripcion_evento']) ?></p>
                            <?php endif; ?>
                            <ul class="list-unstyled small mb-0">
                                <li><i class="bi bi-calendar me-1"></i> <?= htmlspecialchars($e['fecha_evento']) ?></li>
                                <li><i class="bi bi-clock me-1"></i> <?= substr($e['hora_evento'], 0, 5) ?> (<?= (int) $e['duracion_evento'] ?> min)</li>
                                <?php if ($e['nombre_aula']): ?>
                                    <li><i class="bi bi-geo-alt me-1"></i> Aula <?= htmlspecialchars($e['nombre_aula']) ?></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../src/includes/footer.php'; ?>
