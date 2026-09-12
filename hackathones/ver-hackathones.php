<?php

session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../src/includes/header.php';

$stmt = $pdo->query("
    SELECT h.id_hackaton, h.nombre_hackaton, h.descripcion_hackaton,
           h.fechainicio_hackaton, h.fechafinal_hackaton, h.cuota_hackaton,
           h.numasistencia_hackathon, h.responsable_usuario_id,
           u.nombres_usuario, u.apellidos_usuario
    FROM hackathones h
    LEFT JOIN usuarios u ON u.id_usuario = h.responsable_usuario_id
    ORDER BY h.fechainicio_hackaton ASC
");
$hackathones = $stmt->fetchAll(PDO::FETCH_ASSOC);

$misHackathones = [];
if (usuarioLogueado() && esAlumnado()) {
    $miInsc = $pdo->prepare("SELECT hackathones_id_hackaton FROM inscripcioneshackatons WHERE usuarios_id_usuario = ?");
    $miInsc->execute([$_SESSION['usuario_id']]);
    $misHackathones = $miInsc->fetchAll(PDO::FETCH_COLUMN);
}

$stmtInsc = $pdo->prepare("SELECT COUNT(*) FROM inscripcioneshackatons WHERE hackathones_id_hackaton = ?");
$stmtEntregables = $pdo->prepare("SELECT descripcion_entregable FROM entregables WHERE hackathones_id_hackaton = ?");
$stmtRubricas = $pdo->prepare("SELECT criterio_rubrica, puntaje_rubrica FROM rubricas WHERE hackathones_id_hackaton = ?");
?>

<div class="container mt-5 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-vino">Hackathones disponibles</h2>

        <?php if (usuarioLogueado() && esCoordinador()): ?>
            <a href="publicar-hackathon.php" class="btn btn-login">
                <i class="bi bi-plus-circle me-1"></i> Publicar hackathon
            </a>
        <?php endif; ?>
    </div>

    <?php if (isset($_GET['exito']) && $_GET['exito'] === 'inscrito'): ?>
        <div class="alert alert-success">¡Te inscribiste correctamente al hackathon!</div>
    <?php elseif (isset($_GET['error'])): ?>
        <div class="alert alert-danger">
            <?php
            $mensajes = [
                'ya_inscrito'  => 'Ya estás inscrito en ese hackathon.',
                'no_existe'    => 'El hackathon ya no existe.',
                'faltan_datos' => 'Ocurrió un error, intenta de nuevo.'
            ];
            echo $mensajes[$_GET['error']] ?? 'Ocurrió un error.';
            ?>
        </div>
    <?php endif; ?>

    <?php if (empty($hackathones)): ?>
        <div class="alert alert-secondary text-center">
            Aún no hay hackathones disponibles. Vuelve pronto.
        </div>
    <?php else: ?>

        <div class="row g-4">
            <?php foreach ($hackathones as $h):
                $stmtInsc->execute([$h['id_hackaton']]);
                $inscritos = $stmtInsc->fetchColumn();
                $yaInscrito = in_array($h['id_hackaton'], $misHackathones);

                $stmtEntregables->execute([$h['id_hackaton']]);
                $entregables = $stmtEntregables->fetchAll(PDO::FETCH_COLUMN);

                $stmtRubricas->execute([$h['id_hackaton']]);
                $rubricas = $stmtRubricas->fetchAll(PDO::FETCH_ASSOC);
            ?>
                <div class="col-md-6">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($h['nombre_hackaton']) ?></h5>
                            <p class="card-text small text-muted"><?= htmlspecialchars($h['descripcion_hackaton'] ?? '') ?></p>

                            <ul class="list-unstyled small mb-3">
                                <li><i class="bi bi-calendar-range me-1"></i>
                                    <?= htmlspecialchars($h['fechainicio_hackaton']) ?> al <?= htmlspecialchars($h['fechafinal_hackaton']) ?>
                                </li>
                                <?php if ($h['cuota_hackaton'] !== null): ?>
                                    <li><i class="bi bi-cash me-1"></i> Cuota: $<?= number_format((float) $h['cuota_hackaton'], 2) ?></li>
                                <?php endif; ?>
                                <li><i class="bi bi-person-badge me-1"></i>
                                    Responsable:
                                    <?= $h['nombres_usuario']
                                        ? htmlspecialchars($h['nombres_usuario'] . ' ' . $h['apellidos_usuario'])
                                        : 'Por asignar' ?>
                                </li>
                                <li><i class="bi bi-people me-1"></i> Inscritos: <?= (int) $inscritos ?></li>
                            </ul>

                            <?php if (!empty($entregables)): ?>
                                <p class="small mb-1"><strong>Entregables:</strong></p>
                                <ul class="small text-muted">
                                    <?php foreach ($entregables as $e): ?>
                                        <li><?= htmlspecialchars($e) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>

                            <?php if (!empty($rubricas)): ?>
                                <p class="small mb-1"><strong>Rúbrica de evaluación:</strong></p>
                                <ul class="small text-muted">
                                    <?php foreach ($rubricas as $r): ?>
                                        <li><?= htmlspecialchars($r['criterio_rubrica']) ?> (<?= (int) $r['puntaje_rubrica'] ?> pts)</li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>

                            <?php if (!usuarioLogueado()): ?>
                                <small class="text-muted">Inicia sesión para inscribirte</small>

                            <?php elseif (esAlumnado()): ?>
                                <?php if ($yaInscrito): ?>
                                    <button class="btn btn-success btn-sm w-100" disabled>
                                        <i class="bi bi-check-circle me-1"></i> Ya estás inscrito
                                    </button>
                                <?php else: ?>
                                    <form action="inscribirme.php" method="POST"
                                          onsubmit="return confirm('¿Confirmas tu inscripción a <?= htmlspecialchars(addslashes($h['nombre_hackaton'])) ?>?');">
                                        <input type="hidden" name="id_hackaton" value="<?= $h['id_hackaton'] ?>">
                                        <button type="submit" class="btn btn-login btn-sm w-100">Inscribirme</button>
                                    </form>
                                <?php endif; ?>

                            <?php elseif (esCoordinador()): ?>
                                <a href="modificar-hackathon.php?id=<?= $h['id_hackaton'] ?>" class="btn btn-outline-secondary btn-sm w-100">
                                    <i class="bi bi-pencil me-1"></i> Modificar
                                </a>
                            <?php endif; ?>

                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../src/includes/footer.php'; ?>
