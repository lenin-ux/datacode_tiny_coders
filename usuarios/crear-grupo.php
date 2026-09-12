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
    $turno    = $_POST['turno_grupo'] ?? '';
    $numero   = $_POST['numero_grupo'] ?? '';
    $semestre = $_POST['semestre_grupo'] ?? '';

    if (empty($turno) || empty($numero) || empty($semestre)) {
        $error = 'Todos los campos son obligatorios.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO grupos (turno_grupo, numero_grupo, semestre_grupo) VALUES (?, ?, ?)");
        $stmt->execute([$turno, $numero, $semestre]);
        $exito = 'Grupo creado correctamente.';
    }
}

$grupos = $pdo->query("SELECT * FROM grupos ORDER BY semestre_grupo ASC, numero_grupo ASC")->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../src/includes/header.php';
?>

<div class="container mt-5 mb-5">
    <h2 class="text-vino mb-4">Administrar Grupos</h2>

    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($exito): ?><div class="alert alert-success"><?= htmlspecialchars($exito) ?></div><?php endif; ?>

    <div class="row">
        <div class="col-md-5">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="mb-3">Nuevo grupo</h5>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Turno</label>
                            <select name="turno_grupo" class="form-select" required>
                                <option value="">Selecciona...</option>
                                <option value="Matutino">Matutino</option>
                                <option value="Vespertino">Vespertino</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Número de grupo</label>
                            <input type="number" name="numero_grupo" class="form-control" min="1" placeholder="Ej. 5" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Semestre</label>
                            <input type="number" name="semestre_grupo" class="form-control" min="1" max="12" placeholder="Ej. 3" required>
                        </div>
                        <button type="submit" class="btn btn-login w-100">Guardar</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            <h5 class="mb-3">Grupos existentes</h5>
            <table class="table table-striped">
                <thead><tr><th>ID</th><th>Turno</th><th>Número</th><th>Semestre</th></tr></thead>
                <tbody>
                    <?php foreach ($grupos as $g): ?>
                        <tr>
                            <td><?= $g['id_grupo'] ?></td>
                            <td><?= htmlspecialchars($g['turno_grupo']) ?></td>
                            <td><?= htmlspecialchars($g['numero_grupo']) ?></td>
                            <td><?= htmlspecialchars($g['semestre_grupo']) ?>°</td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($grupos)): ?>
                        <tr><td colspan="4" class="text-muted">Aún no hay grupos registrados.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <a href="/usuarios/integrar-alumnos.php" class="btn btn-outline-secondary mt-3">Siguiente: Integrar Alumnos &rarr;</a>
</div>

<?php require_once __DIR__ . '/../src/includes/footer.php'; ?>