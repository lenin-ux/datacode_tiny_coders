<?php
session_start();
require_once __DIR__ . '/config/db.php';

// Solo usuarios logueados pueden ver/imprimir su gafete
if (!isset($_SESSION['usuario_id'])) {
    header('Location: /index.php');
    exit;
}

// Traemos toda la info necesaria en una sola consulta
$stmt = $pdo->prepare("
    SELECT u.id_usuario, u.nombres_usuario, u.apellidos_usuario, u.matricula_usuario,
           r.rol,
           ur.nombre_ur,
           g.turno_grupo, g.numero_grupo, g.semestre_grupo
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

// El grupo solo se muestra si el rol es Alumno (1) o Comité (2)
$mostrarGrupo = in_array($_SESSION['rol_id'], [1, 2]);

// Folio simple: año actual + id de usuario con ceros a la izquierda
$folio = 'DC' . date('y') . str_pad($u['id_usuario'], 5, '0', STR_PAD_LEFT);

$bodyClass = 'pagina-gafete';
require_once __DIR__ . '/src/includes/header.php';
?>

<div class="container mt-5 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <h2 class="text-vino">Mi gafete</h2>
        <button onclick="window.print()" class="btn btn-login">
            <i class="bi bi-printer me-1"></i> Imprimir gafete
        </button>
    </div>

    <div class="d-flex justify-content-center">
        <div class="gafete" id="gafete">

            <div class="gafete-header">
                <span class="gafete-marca">DATACODE</span>
                <span class="gafete-folio">#<?= htmlspecialchars($folio) ?></span>
            </div>

            <div class="gafete-body">
                <div class="gafete-icono">
                    <i class="bi bi-person-circle"></i>
                </div>

                <h4 class="gafete-nombre">
                    <?= htmlspecialchars($u['nombres_usuario'] . ' ' . $u['apellidos_usuario']) ?>
                </h4>

                <span class="gafete-rol"><?= htmlspecialchars($u['rol']) ?></span>

                <table class="gafete-datos">
                    <tr>
                        <td>Matrícula</td>
                        <td><?= htmlspecialchars($u['matricula_usuario']) ?></td>
                    </tr>
                    <tr>
                        <td>Unidad Regional</td>
                        <td><?= htmlspecialchars($u['nombre_ur']) ?></td>
                    </tr>

                    <?php if ($mostrarGrupo && $u['numero_grupo']): ?>
                        <tr>
                            <td>Grupo</td>
                            <td>
                                <?= htmlspecialchars($u['numero_grupo']) ?>
                                (<?= htmlspecialchars($u['turno_grupo']) ?>,
                                <?= htmlspecialchars($u['semestre_grupo']) ?>° semestre)
                            </td>
                        </tr>
                    <?php endif; ?>
                </table>
            </div>

            <div class="gafete-footer">
                Este gafete es personal e intransferible &middot; DATACODE 2.0
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/src/includes/footer.php'; ?>