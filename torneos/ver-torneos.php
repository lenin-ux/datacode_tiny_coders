<?php

session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../src/includes/header.php';

$stmt = $pdo->query("
    SELECT t.id_torneo, t.nombre_torneo, t.formato_torneo, t.reglas_torneo,
           t.fechatorneo, t.horarioinicio_torneo, t.horariofin_torneo,
           t.responsable_usuario_id,
           u.nombres_usuario, u.apellidos_usuario
    FROM torneos t
    LEFT JOIN usuarios u ON u.id_usuario = t.responsable_usuario_id
    ORDER BY t.fechatorneo ASC, t.horarioinicio_torneo ASC
");
$torneos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Torneos en los que ya está inscrito el usuario actual (para no ofrecer inscribirse dos veces al mismo)
$misTorneos = [];
if (usuarioLogueado() && esAlumnado()) {
    $miInsc = $pdo->prepare("SELECT torneos_id_torneo FROM inscripcionestorneos WHERE usuarios_id_usuario = ?");
    $miInsc->execute([$_SESSION['usuario_id']]);
    $misTorneos = $miInsc->fetchAll(PDO::FETCH_COLUMN);
}

$stmtInsc = $pdo->prepare("SELECT COUNT(*) FROM inscripcionestorneos WHERE torneos_id_torneo = ?");
?>

<div class="container mt-5 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-vino">Torneos disponibles</h2>

        <?php if (usuarioLogueado() && esCoordinador()): ?>
            <a href="publicar-torneo.php" class="btn btn-login">
                <i class="bi bi-plus-circle me-1"></i> Publicar torneo
            </a>
        <?php endif; ?>
    </div>

    <?php if (isset($_GET['exito']) && $_GET['exito'] === 'inscrito'): ?>
        <div class="alert alert-success">¡Te inscribiste correctamente al torneo!</div>
    <?php elseif (isset($_GET['error'])): ?>
        <div class="alert alert-danger">
            <?php
            $mensajes = [
                'ya_inscrito'  => 'Ya estás inscrito en ese torneo.',
                'no_existe'    => 'El torneo ya no existe.',
                'faltan_datos' => 'Ocurrió un error, intenta de nuevo.'
            ];
            echo $mensajes[$_GET['error']] ?? 'Ocurrió un error.';
            ?>
        </div>
    <?php endif; ?>

    <?php if (empty($torneos)): ?>
        <div class="alert alert-secondary text-center">
            Aún no hay torneos disponibles. Vuelve pronto.
        </div>
    <?php else: ?>

        <div class="row g-4">
            <?php foreach ($torneos as $t):
                $stmtInsc->execute([$t['id_torneo']]);
                $inscritos = $stmtInsc->fetchColumn();
                $yaInscrito = in_array($t['id_torneo'], $misTorneos);
            ?>
                <div class="col-md-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($t['nombre_torneo']) ?></h5>
                            <p class="card-text small text-muted"><?= htmlspecialchars($t['formato_torneo']) ?></p>

                            <ul class="list-unstyled small mb-3">
                                <li><i class="bi bi-calendar me-1"></i> <?= htmlspecialchars($t['fechatorneo']) ?></li>
                                <li><i class="bi bi-clock me-1"></i>
                                    <?= substr($t['horarioinicio_torneo'], 0, 5) ?><?= $t['horariofin_torneo'] ? ' - ' . substr($t['horariofin_torneo'], 0, 5) : '' ?>
                                </li>
                                <li><i class="bi bi-person-badge me-1"></i>
                                    Responsable:
                                    <?= $t['nombres_usuario']
                                        ? htmlspecialchars($t['nombres_usuario'] . ' ' . $t['apellidos_usuario'])
                                        : 'Por asignar' ?>
                                </li>
                                <li><i class="bi bi-people me-1"></i> Inscritos: <?= (int) $inscritos ?></li>
                            </ul>

                            <?php if (!usuarioLogueado()): ?>
                                <small class="text-muted">Inicia sesión para inscribirte</small>

                            <?php elseif (esAlumnado()): ?>
                                <?php if ($yaInscrito): ?>
                                    <button class="btn btn-success btn-sm w-100" disabled>
                                        <i class="bi bi-check-circle me-1"></i> Ya estás inscrito
                                    </button>
                                <?php else: ?>
                                    <form action="inscribirme.php" method="POST"
                                          onsubmit="return confirm('¿Confirmas tu inscripción a <?= htmlspecialchars(addslashes($t['nombre_torneo'])) ?>?');">
                                        <input type="hidden" name="id_torneo" value="<?= $t['id_torneo'] ?>">
                                        <button type="submit" class="btn btn-login btn-sm w-100">Inscribirme</button>
                                    </form>
                                <?php endif; ?>

                            <?php elseif (esCoordinador()): ?>
                                <a href="modificar-torneo.php?id=<?= $t['id_torneo'] ?>" class="btn btn-outline-secondary btn-sm w-100">
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
