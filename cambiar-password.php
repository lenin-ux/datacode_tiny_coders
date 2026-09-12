<?php
session_start();
require_once __DIR__ . '/config/db.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: /index.php');
    exit;
}

$error = '';
$exito = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $actual  = trim($_POST['password_actual'] ?? '');
    $nueva   = trim($_POST['password_nueva'] ?? '');
    $confirm = trim($_POST['password_confirmar'] ?? '');

    if (empty($actual) || empty($nueva) || empty($confirm)) {
        $error = 'Completa todos los campos.';
    } elseif (strlen($nueva) > 8) {
        $error = 'La nueva contraseña no puede tener más de 8 caracteres.';
    } elseif ($nueva !== $confirm) {
        $error = 'La confirmación no coincide con la nueva contraseña.';
    } else {
        $stmt = $pdo->prepare("SELECT password_usuario FROM usuarios WHERE id_usuario = ?");
        $stmt->execute([$_SESSION['usuario_id']]);
        $passActual = $stmt->fetchColumn();

        if ($passActual !== $actual) {
            $error = 'Tu contraseña actual no es correcta.';
        } else {
            $pdo->prepare("UPDATE usuarios SET password_usuario = ? WHERE id_usuario = ?")
                ->execute([$nueva, $_SESSION['usuario_id']]);
            $exito = 'Tu contraseña se actualizó correctamente.';
        }
    }
}

require_once __DIR__ . '/src/includes/header.php';
?>

<div class="container mt-5 mb-5 d-flex justify-content-center">
    <div class="card shadow-sm" style="max-width: 420px; width: 100%;">
        <div class="card-body">
            <h4 class="text-vino mb-3"><i class="bi bi-key me-1"></i> Cambiar contraseña</h4>

            <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <?php if ($exito): ?><div class="alert alert-success"><?= htmlspecialchars($exito) ?></div><?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Contraseña actual</label>
                    <input type="password" name="password_actual" class="form-control" maxlength="8" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Nueva contraseña (máx. 8 caracteres)</label>
                    <input type="password" name="password_nueva" class="form-control" maxlength="8" required>
                </div>
                <div class="mb-4">
                    <label class="form-label">Confirmar nueva contraseña</label>
                    <input type="password" name="password_confirmar" class="form-control" maxlength="8" required>
                </div>
                <button type="submit" class="btn btn-login w-100">Actualizar contraseña</button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/src/includes/footer.php'; ?>