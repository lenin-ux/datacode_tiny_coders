<?php

session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

// Alumnos, miembros de comité y Coordinador comparten el mismo foro.
requireRole(array_merge(ROLES_ALUMNADO, [ROL_COORDINADOR]));

require_once __DIR__ . '/../src/includes/header.php';

// Las propuestas de un autor con rol Comité se listan primero, como pide
// el negocio; dentro de cada grupo, las más nuevas primero.
$stmt = $pdo->query("
    SELECT p.id_propuesta, p.titulo_propuesta, p.tipo_propuesta, p.aprobacion_propuesta,
           p.usuarios_id_usuario, u.nombres_usuario, u.apellidos_usuario, u.roles_id,
           (SELECT COUNT(*) FROM votos v WHERE v.propuestas_id_propuesta = p.id_propuesta) AS num_votos,
           (SELECT COUNT(*) FROM comentarios c WHERE c.propuestas_id_propuesta = p.id_propuesta) AS num_comentarios
    FROM propuestas p
    JOIN usuarios u ON u.id_usuario = p.usuarios_id_usuario
    ORDER BY (u.roles_id = " . ROL_COMITE . ") DESC, p.id_propuesta DESC
");
$propuestas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$etiquetasEstado = [
    'sin visualizar'  => 'secondary',
    'a consideracion' => 'warning',
    'aprobado'        => 'success',
    'no aprobado'     => 'danger',
];
?>

<div class="container mt-5 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-vino">Foro de propuestas</h2>
        <?php if (rolActual() === ROL_COMITE): ?>
            <a href="nueva-propuesta.php" class="btn btn-login">
                <i class="bi bi-plus-circle me-1"></i> Nueva propuesta
            </a>
        <?php elseif (rolActual() === ROL_ALUMNO): ?>
            <a href="nueva-propuesta.php" class="btn btn-login">
                <i class="bi bi-plus-circle me-1"></i> Proponer taller / unirme al comité
            </a>
        <?php endif; ?>
    </div>

    <?php if (empty($propuestas)): ?>
        <div class="alert alert-secondary text-center">Aún no hay propuestas en el foro.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead>
                    <tr>
                        <th>Título</th>
                        <th>Tipo</th>
                        <th>Autor</th>
                        <th>Estado</th>
                        <th>Votos</th>
                        <th>Comentarios</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($propuestas as $p): ?>
                        <tr>
                            <td>
                                <?= htmlspecialchars($p['titulo_propuesta']) ?>
                                <?php if ((int) $p['roles_id'] === ROL_COMITE): ?>
                                    <span class="badge bg-vino ms-1" title="Propuesta de un miembro de comité">Comité</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($p['tipo_propuesta']) ?></td>
                            <td><?= htmlspecialchars($p['nombres_usuario'] . ' ' . $p['apellidos_usuario']) ?></td>
                            <td><span class="badge bg-<?= $etiquetasEstado[$p['aprobacion_propuesta']] ?? 'secondary' ?>"><?= htmlspecialchars($p['aprobacion_propuesta']) ?></span></td>
                            <td><?= (int) $p['num_votos'] ?></td>
                            <td><?= (int) $p['num_comentarios'] ?></td>
                            <td><a href="propuesta-detalle.php?id=<?= $p['id_propuesta'] ?>" class="btn btn-outline-secondary btn-sm">Ver</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../src/includes/footer.php'; ?>
