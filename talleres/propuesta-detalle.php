<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

requireRole(array_merge(ROLES_ALUMNADO, [ROL_COORDINADOR]));

$id = $_GET['id'] ?? ($_POST['id_propuesta'] ?? null);
$error = '';
$exito = '';

if (!$id) {
    header('Location: /talleres/propuestas.php');
    exit;
}

// Agregar comentario (cualquier alumno o miembro de comité logueado)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'comentar' && esAlumnado()) {
    $texto = trim($_POST['texto_comentario'] ?? '');
    if ($texto !== '') {
        $pdo->prepare("INSERT INTO comentarios (texto_comentario, propuestas_id_propuesta) VALUES (?, ?)")
            ->execute([$texto, $id]);
        $exito = 'Comentario agregado.';
    }
}

// Votar (una vez por usuario y propuesta)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'votar' && esAlumnado()) {
    $chk = $pdo->prepare("SELECT COUNT(*) FROM votos WHERE propuestas_id_propuesta = ? AND usuarios_id_usuario = ?");
    $chk->execute([$id, $_SESSION['usuario_id']]);
    if ($chk->fetchColumn() == 0) {
        $pdo->prepare("INSERT INTO votos (propuestas_id_propuesta, usuarios_id_usuario) VALUES (?, ?)")
            ->execute([$id, $_SESSION['usuario_id']]);
        $exito = 'Tu voto quedó registrado.';
    } else {
        $error = 'Ya habías votado por esta propuesta.';
    }
}

// Si la petición de votar/comentar viene por AJAX, respondemos JSON y
// cortamos aquí (progressive enhancement: sin JS, el form normal sigue
// funcionando con recarga completa de página).
$esAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
if ($esAjax && $_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['accion'] ?? '', ['votar', 'comentar'], true)) {
    $numVotosAjax = $pdo->prepare("SELECT COUNT(*) FROM votos WHERE propuestas_id_propuesta = ?");
    $numVotosAjax->execute([$id]);

    $yaVoteAjax = false;
    if (esAlumnado()) {
        $chkVotoAjax = $pdo->prepare("SELECT COUNT(*) FROM votos WHERE propuestas_id_propuesta = ? AND usuarios_id_usuario = ?");
        $chkVotoAjax->execute([$id, $_SESSION['usuario_id']]);
        $yaVoteAjax = $chkVotoAjax->fetchColumn() > 0;
    }

    header('Content-Type: application/json');
    echo json_encode([
        'success'    => $error === '',
        'message'    => $error !== '' ? $error : $exito,
        'numVotos'   => (int) $numVotosAjax->fetchColumn(),
        'yaVote'     => $yaVoteAjax,
        'comentario' => ($_POST['accion'] === 'comentar' && $error === '') ? htmlspecialchars($texto ?? '', ENT_QUOTES, 'UTF-8') : null,
    ]);
    exit;
}

// Aprobar/rechazar (solo Coordinador)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'revisar' && esCoordinador()) {
    $nuevoEstado = $_POST['nuevo_estado'] ?? '';
    $estadosValidos = ['aprobado', 'no aprobado', 'a consideracion'];

    if (in_array($nuevoEstado, $estadosValidos, true)) {
        $pdo->prepare("
            UPDATE propuestas
            SET aprobacion_propuesta = ?, revisado_por_usuario_id = ?, fecha_revision = NOW()
            WHERE id_propuesta = ?
        ")->execute([$nuevoEstado, $_SESSION['usuario_id'], $id]);

        // Si es una propuesta de tipo Comite y se aprueba, el alumno pasa a
        // tener rol Comité automáticamente.
        if ($nuevoEstado === 'aprobado') {
            $propTipo = $pdo->prepare("SELECT tipo_propuesta, usuarios_id_usuario FROM propuestas WHERE id_propuesta = ?");
            $propTipo->execute([$id]);
            $datosProp = $propTipo->fetch(PDO::FETCH_ASSOC);
            if ($datosProp && $datosProp['tipo_propuesta'] === 'Comite') {
                $pdo->prepare("UPDATE usuarios SET roles_id = ? WHERE id_usuario = ?")
                    ->execute([ROL_COMITE, $datosProp['usuarios_id_usuario']]);
            }
        }

        $exito = 'Propuesta actualizada.';
    }
}

$stmtP = $pdo->prepare("
    SELECT p.*, u.nombres_usuario, u.apellidos_usuario, u.roles_id,
           r.nombres_usuario AS revisor_nombres, r.apellidos_usuario AS revisor_apellidos
    FROM propuestas p
    JOIN usuarios u ON u.id_usuario = p.usuarios_id_usuario
    LEFT JOIN usuarios r ON r.id_usuario = p.revisado_por_usuario_id
    WHERE p.id_propuesta = ?
");
$stmtP->execute([$id]);
$propuesta = $stmtP->fetch(PDO::FETCH_ASSOC);

if (!$propuesta) {
    header('Location: /talleres/propuestas.php');
    exit;
}

$comentarios = $pdo->prepare("SELECT * FROM comentarios WHERE propuestas_id_propuesta = ? ORDER BY id_comentario ASC");
$comentarios->execute([$id]);
$comentarios = $comentarios->fetchAll(PDO::FETCH_ASSOC);

$numVotos = $pdo->prepare("SELECT COUNT(*) FROM votos WHERE propuestas_id_propuesta = ?");
$numVotos->execute([$id]);
$numVotos = $numVotos->fetchColumn();

$yaVote = false;
if (esAlumnado()) {
    $chkVoto = $pdo->prepare("SELECT COUNT(*) FROM votos WHERE propuestas_id_propuesta = ? AND usuarios_id_usuario = ?");
    $chkVoto->execute([$id, $_SESSION['usuario_id']]);
    $yaVote = $chkVoto->fetchColumn() > 0;
}

$etiquetasEstado = [
    'sin visualizar'  => 'secondary',
    'a consideracion' => 'warning',
    'aprobado'        => 'success',
    'no aprobado'     => 'danger',
];

require_once __DIR__ . '/../src/includes/header.php';
?>

<div class="container mt-5 mb-5">
    <a href="/talleres/propuestas.php" class="btn btn-link mb-3 ps-0">&larr; Volver al foro</a>

    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($exito): ?><div class="alert alert-success"><?= htmlspecialchars($exito) ?></div><?php endif; ?>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                <h3 class="text-vino mb-1"><?= htmlspecialchars($propuesta['titulo_propuesta']) ?></h3>
                <span class="badge bg-<?= $etiquetasEstado[$propuesta['aprobacion_propuesta']] ?? 'secondary' ?> fs-6">
                    <?= htmlspecialchars($propuesta['aprobacion_propuesta']) ?>
                </span>
            </div>
            <p class="text-muted small mb-3">
                Tipo: <?= htmlspecialchars($propuesta['tipo_propuesta']) ?>
                &middot; Autor: <?= htmlspecialchars($propuesta['nombres_usuario'] . ' ' . $propuesta['apellidos_usuario']) ?>
                <?php if ((int) $propuesta['roles_id'] === ROL_COMITE): ?><span class="badge bg-vino">Comité</span><?php endif; ?>
            </p>
            <p><?= nl2br(htmlspecialchars($propuesta['descripcion_propuesta'])) ?></p>

            <?php if ($propuesta['revisado_por_usuario_id']): ?>
                <p class="small text-muted mb-0">
                    Revisada por <?= htmlspecialchars($propuesta['revisor_nombres'] . ' ' . $propuesta['revisor_apellidos']) ?>
                    el <?= htmlspecialchars($propuesta['fecha_revision']) ?>
                </p>
            <?php endif; ?>

            <div class="d-flex align-items-center gap-3 mt-3">
                <span><i class="bi bi-hand-thumbs-up me-1"></i> <span id="numVotos"><?= (int) $numVotos ?></span> votos</span>

                <?php if (esAlumnado()): ?>
                    <form method="POST" class="d-inline" data-ajax-votar>
                        <input type="hidden" name="accion" value="votar">
                        <input type="hidden" name="id_propuesta" value="<?= $id ?>">
                        <button type="submit" class="btn btn-outline-secondary btn-sm" id="btnVotar" <?= $yaVote ? 'disabled' : '' ?>>
                            <span id="textoVotar"><?= $yaVote ? 'Ya votaste' : 'Votar por esta propuesta' ?></span>
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <?php if (esCoordinador()): ?>
                <div class="border-top mt-3 pt-3">
                    <p class="fw-semibold mb-2">Revisión del Coordinador</p>
                    <form method="POST" class="d-flex gap-2 flex-wrap">
                        <input type="hidden" name="accion" value="revisar">
                        <input type="hidden" name="id_propuesta" value="<?= $id ?>">
                        <button type="submit" name="nuevo_estado" value="a consideracion" class="btn btn-warning btn-sm">Marcar a consideración</button>
                        <button type="submit" name="nuevo_estado" value="aprobado" class="btn btn-success btn-sm">Aprobar</button>
                        <button type="submit" name="nuevo_estado" value="no aprobado" class="btn btn-danger btn-sm">No aprobar</button>
                    </form>
                    <?php if ($propuesta['tipo_propuesta'] === 'Comite'): ?>
                        <small class="text-muted d-block mt-2">Al aprobar esta propuesta, el usuario pasará automáticamente a tener rol Comité.</small>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <h5 class="mb-3">Comentarios</h5>
            <div id="listaComentarios">
                <?php foreach ($comentarios as $c): ?>
                    <div class="border-bottom pb-2 mb-2">
                        <p class="mb-0"><?= nl2br(htmlspecialchars($c['texto_comentario'])) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
            <p class="text-muted small" id="sinComentarios" <?= empty($comentarios) ? '' : 'hidden' ?>>Aún no hay comentarios.</p>

            <?php if (esAlumnado()): ?>
                <form method="POST" class="mt-3" data-ajax-comentar>
                    <input type="hidden" name="accion" value="comentar">
                    <input type="hidden" name="id_propuesta" value="<?= $id ?>">
                    <div class="mb-2">
                        <textarea name="texto_comentario" id="textoComentarioInput" class="form-control" rows="2" maxlength="2000" placeholder="Escribe un comentario..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-outline-secondary btn-sm">Comentar</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../src/includes/footer.php'; ?>
