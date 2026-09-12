<?php

session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../src/includes/header.php';

$stmt = $pdo->query("
    SELECT t.id_taller, t.nombre_taller, t.descripcion_taller,
           t.fecha_taller, t.horainicio_taller, t.horatermino_taller,
           t.jornadas_id_jornada, t.responsable_usuario_id,
           u.nombres_usuario, u.apellidos_usuario,
           j.unidadesregionales_id_ur
    FROM talleres t
    LEFT JOIN usuarios u ON u.id_usuario = t.responsable_usuario_id
    JOIN jornadas j ON j.id_jornada = t.jornadas_id_jornada
    ORDER BY t.fecha_taller ASC
");
$talleres = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Cupo total = alumnos de la misma Unidad Regional que la jornada del taller
$stmtCupoTotal = $pdo->prepare("
    SELECT COUNT(*) FROM usuarios WHERE roles_id = 1 AND unidadesregionales_id_ur = ?
");

// Inscritos actuales en ese taller
$stmtInsc = $pdo->prepare("SELECT COUNT(*) FROM inscripcionestalleres WHERE talleres_id_taller = ?");

// ¿El alumno actual ya está inscrito en algún taller? (regla: solo 1 por alumno)
$yaInscrito = false;
if (isset($_SESSION['usuario_id']) && $_SESSION['rol_id'] == 1) {
    $chk = $pdo->prepare("SELECT COUNT(*) FROM inscripcionestalleres WHERE usuarios_id_usuario = ?");
    $chk->execute([$_SESSION['usuario_id']]);
    $yaInscrito = $chk->fetchColumn() > 0;
}

// ¿En qué taller específico está inscrito el alumno actual? (para marcar esa tarjeta)
$miTallerId = null;
if (isset($_SESSION['usuario_id']) && $_SESSION['rol_id'] == 1) {
    $miTaller = $pdo->prepare("SELECT talleres_id_taller FROM inscripcionestalleres WHERE usuarios_id_usuario = ?");
    $miTaller->execute([$_SESSION['usuario_id']]);
    $miTallerId = $miTaller->fetchColumn(); // false si no tiene ninguno
}
?>

<div class="container mt-5 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-vino">Talleres disponibles</h2>

        <?php if (isset($_SESSION['usuario_id']) && $_SESSION['rol_id'] == 3): ?>
            <a href="publicar-taller.php" class="btn btn-login">
                <i class="bi bi-plus-circle me-1"></i> Publicar taller
            </a>
        <?php endif; ?>
    </div>

    <?php if (isset($_GET['exito']) && $_GET['exito'] === 'inscrito'): ?>
        <div class="alert alert-success">¡Te inscribiste correctamente!</div>
    <?php elseif (isset($_GET['error'])): ?>
        <div class="alert alert-danger">
            <?php
            $mensajes = [
                'ya_inscrito'   => 'Ya estás inscrito en otro taller.',
                'sin_cupo'      => 'Ese taller ya no tiene cupo disponible.',
                'no_existe'     => 'El taller ya no existe.',
                'faltan_datos'  => 'Ocurrió un error, intenta de nuevo.'
            ];
            echo $mensajes[$_GET['error']] ?? 'Ocurrió un error.';
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['usuario_id']) && $_SESSION['rol_id'] == 1 && $yaInscrito): ?>
        <div class="alert alert-info">Ya estás inscrito en un taller. Solo puedes tomar uno a la vez.</div>
    <?php endif; ?>

    <?php if (empty($talleres)): ?>
        <div class="alert alert-secondary text-center">
            Aún no hay talleres disponibles. Vuelve pronto.
        </div>
    <?php else: ?>

        <div class="row g-4">
            <?php foreach ($talleres as $t):
                $stmtCupoTotal->execute([$t['unidadesregionales_id_ur']]);
                $cupoTotal = $stmtCupoTotal->fetchColumn();

                $stmtInsc->execute([$t['id_taller']]);
                $inscritos = $stmtInsc->fetchColumn();

                $disponibles = $cupoTotal - $inscritos;
            ?>
                <div class="col-md-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($t['nombre_taller']) ?></h5>
                            <p class="card-text small text-muted"><?= htmlspecialchars($t['descripcion_taller']) ?></p>

                            <ul class="list-unstyled small mb-3">
                                <li><i class="bi bi-calendar me-1"></i> <?= htmlspecialchars($t['fecha_taller']) ?></li>
                                <li><i class="bi bi-clock me-1"></i>
                                    <?= substr($t['horainicio_taller'], 0, 5) ?> - <?= substr($t['horatermino_taller'], 0, 5) ?>
                                </li>
                                <li><i class="bi bi-person-badge me-1"></i>
                                    Responsable:
                                    <?= $t['nombres_usuario']
                                        ? htmlspecialchars($t['nombres_usuario'] . ' ' . $t['apellidos_usuario'])
                                        : 'Por asignar' ?>
                                </li>
                                <li><i class="bi bi-people me-1"></i>
                                    Cupo: <?= $disponibles > 0 ? "$disponibles / $cupoTotal disponibles" : "Sin cupo" ?>
                                </li>
                            </ul>

                            <?php if (!isset($_SESSION['usuario_id'])): ?>
                                <small class="text-muted">Inicia sesión para inscribirte</small>

                            <?php elseif ($_SESSION['rol_id'] == 1): ?>

                                <?php if ($miTallerId == $t['id_taller']): ?>
                                    <button class="btn btn-success btn-sm w-100" disabled>
                                        <i class="bi bi-check-circle me-1"></i> Ya estás inscrito aquí
                                    </button>

                                <?php elseif ($yaInscrito): ?>
                                    <button class="btn btn-secondary btn-sm w-100" disabled>
                                        No disponible (ya tienes otro taller)
                                    </button>

                                <?php elseif ($disponibles <= 0): ?>
                                    <button class="btn btn-secondary btn-sm w-100" disabled>Sin cupo</button>

                                <?php else: ?>
                                    <form action="inscribirme.php" method="POST"
                                          onsubmit="return confirm('¿Confirmas tu inscripción a <?= htmlspecialchars(addslashes($t['nombre_taller'])) ?>?');">
                                        <input type="hidden" name="id_taller" value="<?= $t['id_taller'] ?>">
                                        <button type="submit" class="btn btn-login btn-sm w-100">Inscribirme</button>
                                    </form>
                                <?php endif; ?>

                            <?php elseif ($_SESSION['rol_id'] == 3): ?>
                                <a href="modificar-taller.php?id=<?= $t['id_taller'] ?>" class="btn btn-outline-secondary btn-sm w-100">
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