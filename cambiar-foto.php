<?php
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';

requireLogin();

$error = '';
$exito = '';

// MVP: subida simple de imagen, validando tipo y tamaño. La revisión de
// seguridad más estricta (nombre de archivo, límites de servidor, etc.)
// queda pendiente para una pasada posterior, como se acordó.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
    $extensionesPermitidas = ['jpg', 'jpeg', 'png'];
    $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
    $tamanoMaximo = 2 * 1024 * 1024; // 2 MB

    if (!in_array($ext, $extensionesPermitidas, true)) {
        $error = 'Solo se aceptan imágenes JPG o PNG.';
    } elseif ($_FILES['foto']['size'] > $tamanoMaximo) {
        $error = 'La imagen no puede pesar más de 2 MB.';
    } else {
        $nombreArchivo = 'usuario_' . $_SESSION['usuario_id'] . '.' . $ext;
        $rutaDestino = __DIR__ . '/src/img/usuarios/' . $nombreArchivo;

        if (move_uploaded_file($_FILES['foto']['tmp_name'], $rutaDestino)) {
            $rutaRelativa = 'src/img/usuarios/' . $nombreArchivo;
            $pdo->prepare("UPDATE usuarios SET rutaimagen_usuario = ? WHERE id_usuario = ?")
                ->execute([$rutaRelativa, $_SESSION['usuario_id']]);
            $exito = 'Foto de perfil actualizada.';
        } else {
            $error = 'No se pudo guardar la imagen, intenta de nuevo.';
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $error = 'Selecciona una imagen antes de subir.';
}

require_once __DIR__ . '/src/includes/header.php';
?>

<div class="container mt-5 mb-5 d-flex justify-content-center">
    <div class="card shadow-sm" style="max-width: 420px; width: 100%;">
        <div class="card-body">
            <h4 class="text-vino mb-3"><i class="bi bi-camera me-1"></i> Cambiar foto de perfil</h4>

            <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <?php if ($exito): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($exito) ?>
                    &middot; <a href="/perfil.php">Ver mi perfil</a>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label">Nueva foto (JPG o PNG, máx. 2 MB)</label>
                    <input type="file" name="foto" accept=".jpg,.jpeg,.png" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-login w-100">Subir foto</button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/src/includes/footer.php'; ?>
