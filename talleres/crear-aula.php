<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_id'] != 3) {
    header('Location: /index.php');
    exit;
}

$error = '';
$exito = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre_aula'] ?? '');

    if (empty($nombre)) {
        $error = 'El nombre del aula es obligatorio.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO aulas (nombre_aula) VALUES (?)");
        $stmt->execute([$nombre]);
        $exito = 'Aula creada correctamente.';
    }
}

$aulas = $pdo->query("SELECT * FROM aulas ORDER BY nombre_aula ASC")->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../src/includes/header.php';
?>

<div class="container mt-5 mb-5">
    <h2 class="text-vino mb-4">Administrar Aulas</h2>

    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($exito): ?><div class="alert alert-success"><?= htmlspecialchars($exito) ?></div><?php endif; ?>

    <div class="row">
        <div class="col-md-5">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="mb-3">Nueva aula</h5>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Nombre del aula</label>
                            <input type="text" name="nombre_aula" class="form-control" placeholder="Ej. Laboratorio 3" required>
                        </div>
                        <button type="submit" class="btn btn-login w-100">Guardar</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            <h5 class="mb-3">Aulas existentes</h5>
            <table class="table table-striped">
                <thead><tr><th>ID</th><th>Nombre</th></tr></thead>
                <tbody>
                    <?php foreach ($aulas as $a): ?>
                        <tr>
                            <td><?= $a['id_aula'] ?></td>
                            <td><?= htmlspecialchars($a['nombre_aula']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($aulas)): ?>
                        <tr><td colspan="2" class="text-muted">Aún no hay aulas registradas.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <a href="/talleres/crear-jornada.php" class="btn btn-outline-secondary mt-3">Siguiente: Crear Jornada &rarr;</a>
</div>

<?php require_once __DIR__ . '/../src/includes/footer.php'; ?>