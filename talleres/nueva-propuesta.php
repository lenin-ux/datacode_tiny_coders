<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

requireRole(ROLES_ALUMNADO);

$error = '';
$exito = '';

// Un Alumno regular solo puede proponer Talleres o pedir entrar al Comité.
// Un miembro de Comité ya no puede proponerse a sí mismo al comité, pero sí
// puede proponer Talleres, Torneos, Hackathon y temas Generales.
$tiposPermitidos = rolActual() === ROL_COMITE
    ? ['Talleres', 'Torneos', 'Hackaton', 'General']
    : ['Talleres', 'Comite'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo      = trim($_POST['titulo_propuesta'] ?? '');
    $descripcion = trim($_POST['descripcion_propuesta'] ?? '');
    $tipo        = $_POST['tipo_propuesta'] ?? '';

    if (empty($titulo) || empty($descripcion) || empty($tipo)) {
        $error = 'Todos los campos son obligatorios.';
    } elseif (strlen($titulo) > 48) {
        $error = 'El título no puede tener más de 48 caracteres.';
    } elseif (!in_array($tipo, $tiposPermitidos, true)) {
        $error = 'No tienes permiso para crear propuestas de ese tipo.';
    } else {
        $pdo->prepare("
            INSERT INTO propuestas (titulo_propuesta, descripcion_propuesta, tipo_propuesta, usuarios_id_usuario)
            VALUES (?, ?, ?, ?)
        ")->execute([$titulo, $descripcion, $tipo, $_SESSION['usuario_id']]);
        $exito = 'Propuesta enviada correctamente. El Coordinador la revisará pronto.';
    }
}

require_once __DIR__ . '/../src/includes/header.php';
?>

<div class="container mt-5 mb-5 d-flex justify-content-center">
    <div class="card shadow-sm" style="max-width: 600px; width: 100%;">
        <div class="card-body">
            <h4 class="text-vino mb-3"><i class="bi bi-megaphone me-1"></i> Nueva propuesta</h4>

            <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <?php if ($exito): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($exito) ?>
                    &middot; <a href="/talleres/propuestas.php">Ver foro de propuestas</a>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Tipo de propuesta</label>
                    <select name="tipo_propuesta" class="form-select" required>
                        <option value="">Selecciona...</option>
                        <?php foreach ($tiposPermitidos as $t): ?>
                            <option value="<?= htmlspecialchars($t) ?>"><?= htmlspecialchars($t) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (rolActual() === ROL_ALUMNO): ?>
                        <small class="text-muted">Como Alumno puedes proponer un taller o solicitar unirte al Comité.</small>
                    <?php else: ?>
                        <small class="text-muted">Como miembro del Comité ya no puedes proponerte a ti mismo al comité.</small>
                    <?php endif; ?>
                </div>
                <div class="mb-3">
                    <label class="form-label">Título (máx. 48 caracteres)</label>
                    <input type="text" name="titulo_propuesta" maxlength="48" class="form-control" required>
                </div>
                <div class="mb-4">
                    <label class="form-label">Descripción</label>
                    <textarea name="descripcion_propuesta" class="form-control" rows="4" required></textarea>
                </div>
                <button type="submit" class="btn btn-login w-100">Enviar propuesta</button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../src/includes/footer.php'; ?>
