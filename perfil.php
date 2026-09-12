<?php
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';

requireLogin();

$stmt = $pdo->prepare("
    SELECT u.nombres_usuario, u.apellidos_usuario, u.matricula_usuario, u.correo_usuario,
           u.rutaimagen_usuario, r.rol,
           ur.nombre_ur, g.turno_grupo, g.numero_grupo, g.semestre_grupo
    FROM usuarios u
    JOIN roles r ON r.id = u.roles_id
    JOIN unidadesregionales ur ON ur.id_ur = u.unidadesregionales_id_ur
    LEFT JOIN grupos g ON g.id_grupo = u.grupos_id_grupo
    WHERE u.id_usuario = ?
");
$stmt->execute([$_SESSION['usuario_id']]);
$u = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$u) {
    header('Location: /index.php');
    exit;
}

$tieneGrupo = esAlumnado() && $u['numero_grupo'];
$fotoUrl = $u['rutaimagen_usuario'] ? '/' . ltrim($u['rutaimagen_usuario'], '/') : null;

require_once __DIR__ . '/src/includes/header.php';
?>

<div class="container mt-5 mb-5 d-flex justify-content-center">
    <div class="card shadow-sm" style="max-width: 480px; width: 100%;">
        <div class="card-body text-center">
            <?php if ($fotoUrl): ?>
                <img src="<?= htmlspecialchars($fotoUrl) ?>" alt="Foto de perfil" class="rounded-circle mb-3" style="width:110px; height:110px; object-fit:cover;">
            <?php else: ?>
                <i class="bi bi-person-circle mb-3" style="font-size: 6rem; color:#9C2C53;"></i>
            <?php endif; ?>

            <h4 class="text-vino mb-0"><?= htmlspecialchars($u['nombres_usuario'] . ' ' . $u['apellidos_usuario']) ?></h4>
            <p class="text-muted mb-4"><?= htmlspecialchars($u['rol']) ?></p>

            <div class="table-responsive">
            <table class="table table-borderless text-start small">
                <tr><td class="text-muted">Matrícula</td><td><?= htmlspecialchars($u['matricula_usuario']) ?></td></tr>
                <?php if ($u['correo_usuario']): ?>
                    <tr><td class="text-muted">Correo</td><td><?= htmlspecialchars($u['correo_usuario']) ?></td></tr>
                <?php endif; ?>
                <tr><td class="text-muted">Unidad Regional</td><td><?= htmlspecialchars($u['nombre_ur']) ?></td></tr>
                <?php if ($tieneGrupo): ?>
                    <tr>
                        <td class="text-muted">Grupo</td>
                        <td><?= htmlspecialchars($u['numero_grupo']) ?> (<?= htmlspecialchars($u['turno_grupo']) ?>, <?= htmlspecialchars($u['semestre_grupo']) ?>° semestre)</td>
                    </tr>
                <?php endif; ?>
            </table>
            </div>

            <div class="d-grid gap-2">
                <a href="/cambiar-foto.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-camera me-1"></i> Cambiar foto de perfil</a>
                <a href="/cambiar-password.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-key me-1"></i> Cambiar contraseña</a>
                <a href="/imprimir_credencial.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-printer me-1"></i> Imprimir gafete</a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/src/includes/footer.php'; ?>
