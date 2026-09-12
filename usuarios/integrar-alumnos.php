<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_id'] != 3) {
    header('Location: /index.php');
    exit;
}

$error = '';
$exito = '';
$reporteImportacion = [];

// Unidad Regional fija: la del coordinador que está haciendo el registro
$urFija = $_SESSION['ur_id'];

/**
 * Genera una contraseña de 8 caracteres con el patrón:
 * AA (año actual, 2 dígitos) + "06" (fijo) + 4 caracteres derivados
 * de un consecutivo, convertidos a letras (para que no sea 100% numérica).
 */
function generarPassword(int $consecutivo): string
{
    $anio = date('y');
    $numero = str_pad((string) $consecutivo, 4, '0', STR_PAD_LEFT);
    $numero = substr($numero, -4); // por seguridad, nos quedamos con los últimos 4 dígitos

    $mapaLetras = ['0'=>'A','1'=>'B','2'=>'C','3'=>'D','4'=>'E','5'=>'F','6'=>'G','7'=>'H','8'=>'I','9'=>'J'];
    $letras = '';
    foreach (str_split($numero) as $digito) {
        $letras .= $mapaLetras[$digito] ?? 'X';
    }

    return $anio . '06' . $letras; // 2 + 2 + 4 = 8 caracteres
}

/**
 * Intenta enviar la contraseña generada por correo.
 * NOTA: en un servidor local (XAMPP/Laragon) mail() normalmente NO envía
 * correos reales sin configurar un SMTP. Por eso siempre mostramos también
 * la contraseña en pantalla como respaldo.
 */
function enviarCorreoPassword(string $correo, string $nombre, string $matricula, string $password): bool
{
    $asunto = 'Tus credenciales de acceso - DATACODE';
    $mensaje = "Hola $nombre,\n\n"
             . "Tu cuenta fue creada exitosamente en el sistema DATACODE.\n\n"
             . "Matrícula: $matricula\n"
             . "Contraseña: $password\n\n"
             . "Te recomendamos cambiar tu contraseña después de tu primer inicio de sesión.\n";
    $headers = "From: no-reply@datacode.local\r\n";

    return @mail($correo, $asunto, $mensaje, $headers);
}

/**
 * Lector mínimo de archivos .xlsx sin librerías externas.
 */
function leerXlsxSimple(string $rutaArchivo): array
{
    $zip = new ZipArchive();
    if ($zip->open($rutaArchivo) !== true) {
        throw new Exception('No se pudo abrir el archivo. ¿Seguro que es un .xlsx válido?');
    }

    $sharedStrings = [];
    $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
    if ($sharedXml !== false) {
        $sharedObj = simplexml_load_string($sharedXml);
        foreach ($sharedObj->si as $si) {
            if (isset($si->t)) {
                $sharedStrings[] = (string) $si->t;
            } else {
                $texto = '';
                foreach ($si->r as $r) {
                    $texto .= (string) $r->t;
                }
                $sharedStrings[] = $texto;
            }
        }
    }

    $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
    if ($sheetXml === false) {
        throw new Exception('No se encontró la hoja de datos en el archivo.');
    }
    $sheetObj = simplexml_load_string($sheetXml);
    $zip->close();

    $filas = [];

    foreach ($sheetObj->sheetData->row as $row) {
        $filaActual = [];
        $colIndexEsperado = 0;

        foreach ($row->c as $celda) {
            $ref = (string) $celda['r'];
            preg_match('/^([A-Z]+)/', $ref, $m);
            $colLetra = $m[1] ?? '';
            $colIndexReal = 0;
            foreach (str_split($colLetra) as $char) {
                $colIndexReal = $colIndexReal * 26 + (ord($char) - ord('A') + 1);
            }
            $colIndexReal -= 1;

            while ($colIndexEsperado < $colIndexReal) {
                $filaActual[] = '';
                $colIndexEsperado++;
            }

            $tipo = (string) $celda['t'];
            $valorCrudo = isset($celda->v) ? (string) $celda->v : '';

            if ($tipo === 's') {
                $filaActual[] = $sharedStrings[(int) $valorCrudo] ?? '';
            } else {
                $filaActual[] = $valorCrudo;
            }

            $colIndexEsperado++;
        }

        $filas[] = $filaActual;
    }

    return $filas;
}

// ============================
// Procesar importación por Excel
// ============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file']) && $_FILES['excel_file']['error'] === UPLOAD_ERR_OK) {

    $grupoDestino = $_POST['grupo_destino'] ?? '';
    $grupoIdFinal = null;

    if ($grupoDestino === 'nuevo') {
        $nuevoTurno    = $_POST['nuevo_turno_grupo'] ?? '';
        $nuevoNumero   = $_POST['nuevo_numero_grupo'] ?? '';
        $nuevoSemestre = $_POST['nuevo_semestre_grupo'] ?? '';

        if (empty($nuevoTurno) || empty($nuevoNumero) || empty($nuevoSemestre)) {
            $error = 'Completa turno, número y semestre para crear el nuevo grupo.';
        } else {
            $insertGrupo = $pdo->prepare("INSERT INTO grupos (turno_grupo, numero_grupo, semestre_grupo) VALUES (?, ?, ?)");
            $insertGrupo->execute([$nuevoTurno, $nuevoNumero, $nuevoSemestre]);
            $grupoIdFinal = $pdo->lastInsertId();
        }
    } elseif (!empty($grupoDestino)) {
        $grupoIdFinal = $grupoDestino;
    } else {
        $error = 'Selecciona un grupo existente o crea uno nuevo antes de importar.';
    }

    $ext = strtolower(pathinfo($_FILES['excel_file']['name'], PATHINFO_EXTENSION));

    if (!$error && $ext !== 'xlsx') {
        $error = 'Solo se aceptan archivos .xlsx (Excel). Si tu archivo es .xls o .csv, guárdalo como .xlsx primero.';
    } elseif (!$error) {
        try {
            $filas = leerXlsxSimple($_FILES['excel_file']['tmp_name']);
            array_shift($filas); // quitamos el encabezado

            $insertUsuario = $pdo->prepare("
                INSERT INTO usuarios (
                    nombres_usuario, apellidos_usuario, matricula_usuario,
                    habilitado_usuario, unidadesregionales_id_ur, roles_id,
                    grupos_id_grupo, password_usuario, correo_usuario
                ) VALUES (?, ?, ?, 1, ?, 1, ?, ?, ?)
            ");
            $chkMatricula = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE matricula_usuario = ?");
            $updatePassword = $pdo->prepare("UPDATE usuarios SET password_usuario = ? WHERE id_usuario = ?");

            $exitosos = 0;
            $fallidos = 0;

            foreach ($filas as $i => $fila) {
                $numFila = $i + 2;

                $nombres   = trim($fila[0] ?? '');
                $apellidos = trim($fila[1] ?? '');
                $matricula = trim($fila[2] ?? '');
                $correo    = trim($fila[3] ?? '');

                if (empty($nombres) || empty($apellidos) || empty($matricula) || empty($correo)) {
                    $reporteImportacion[] = "Fila $numFila: omitida, faltan datos.";
                    $fallidos++;
                    continue;
                }
                if (strlen($matricula) > 8) {
                    $reporteImportacion[] = "Fila $numFila: la matrícula excede 8 caracteres.";
                    $fallidos++;
                    continue;
                }
                if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                    $reporteImportacion[] = "Fila $numFila: correo inválido.";
                    $fallidos++;
                    continue;
                }

                $chkMatricula->execute([$matricula]);
                if ($chkMatricula->fetchColumn() > 0) {
                    $reporteImportacion[] = "Fila $numFila: la matrícula '$matricula' ya existe, se omitió.";
                    $fallidos++;
                    continue;
                }

                try {
                    // Insertamos primero con una contraseña temporal, para obtener el id_usuario autoincremental
                    $insertUsuario->execute([$nombres, $apellidos, $matricula, $urFija, $grupoIdFinal, 'TEMP0000', $correo]);
                    $idNuevo = $pdo->lastInsertId();

                    $passwordFinal = generarPassword((int) $idNuevo);
                    $updatePassword->execute([$passwordFinal, $idNuevo]);

                    enviarCorreoPassword($correo, $nombres, $matricula, $passwordFinal);

                    $exitosos++;
                } catch (PDOException $e) {
                   $reporteImportacion[] = "Fila $numFila: " . $e->getMessage();
                   $fallidos++;
                }     
            }

            $exito = "Importación terminada: $exitosos alumnos integrados al grupo ID $grupoIdFinal, $fallidos omitidos. Se intentó enviar la contraseña a cada correo.";

        } catch (Exception $e) {
            $error = 'Error al leer el archivo: ' . $e->getMessage();
        }
    }
}

// ============================
// Alta manual (un alumno a la vez)
// ============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nombres_usuario'])) {
    $nombres    = trim($_POST['nombres_usuario'] ?? '');
    $apellidos  = trim($_POST['apellidos_usuario'] ?? '');
    $matricula  = trim($_POST['matricula_usuario'] ?? '');
    $correo     = trim($_POST['correo_usuario'] ?? '');
    $grupo      = $_POST['grupos_id_grupo'] ?? '';

    if (empty($nombres) || empty($apellidos) || empty($matricula) || empty($correo) || empty($grupo)) {
        $error = 'Todos los campos son obligatorios.';
    } elseif (strlen($matricula) > 8) {
        $error = 'La matrícula no puede tener más de 8 caracteres.';
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $error = 'El correo no es válido.';
    } else {
        $chk = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE matricula_usuario = ?");
        $chk->execute([$matricula]);

        if ($chk->fetchColumn() > 0) {
            $error = 'Ya existe un usuario con esa matrícula.';
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO usuarios (
                    nombres_usuario, apellidos_usuario, matricula_usuario,
                    habilitado_usuario, unidadesregionales_id_ur, roles_id,
                    grupos_id_grupo, password_usuario, correo_usuario
                ) VALUES (?, ?, ?, 1, ?, 1, ?, ?, ?)
            ");
            $stmt->execute([$nombres, $apellidos, $matricula, $urFija, $grupo, 'TEMP0000', $correo]);
            $idNuevo = $pdo->lastInsertId();

            $passwordFinal = generarPassword((int) $idNuevo);
            $pdo->prepare("UPDATE usuarios SET password_usuario = ? WHERE id_usuario = ?")->execute([$passwordFinal, $idNuevo]);

            $enviado = enviarCorreoPassword($correo, $nombres, $matricula, $passwordFinal);

            $exito = "Alumno {$nombres} {$apellidos} integrado con matrícula {$matricula}. "
                   . "Contraseña generada: {$passwordFinal}"
                   . ($enviado ? " (enviada a {$correo})." : " (no se pudo enviar el correo automáticamente, compártela manualmente).");
        }
    }
}

$grupos = $pdo->query("SELECT * FROM grupos ORDER BY semestre_grupo ASC, numero_grupo ASC")->fetchAll(PDO::FETCH_ASSOC);

$alumnosRecientes = $pdo->query("
    SELECT u.id_usuario, u.nombres_usuario, u.apellidos_usuario, u.matricula_usuario,
           g.numero_grupo, g.turno_grupo
    FROM usuarios u
    LEFT JOIN grupos g ON g.id_grupo = u.grupos_id_grupo
    WHERE u.roles_id = 1
    ORDER BY u.id_usuario DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../src/includes/header.php';
?>

<div class="container mt-5 mb-5">
    <h2 class="text-vino mb-4">Integrar alumnos al sistema</h2>

    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($exito): ?><div class="alert alert-success"><?= htmlspecialchars($exito) ?></div><?php endif; ?>

    <?php if (!empty($reporteImportacion)): ?>
        <div class="alert alert-warning">
            <strong>Detalle de filas omitidas:</strong>
            <ul class="mb-0 small">
                <?php foreach ($reporteImportacion as $linea): ?>
                    <li><?= htmlspecialchars($linea) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (empty($grupos)): ?>
        <div class="alert alert-warning">
            No hay grupos registrados. <a href="/usuarios/crear-grupo.php">Crea uno primero</a> o crea uno directamente al importar.
        </div>
    <?php endif; ?>

    <!-- ============================
         IMPORTACIÓN MASIVA POR EXCEL
         ============================ -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h5 class="mb-2"><i class="bi bi-file-earmark-excel me-1"></i> Importar desde Excel</h5>
            <p class="small text-muted mb-3">
                El archivo debe ser <strong>.xlsx</strong> con estas columnas en la primera fila (encabezado):
                <code>Nombres | Apellidos | Matricula | Correo</code><br>
                La contraseña se genera automáticamente y se envía a cada correo.
                La Unidad Regional será la tuya (<?= htmlspecialchars($urFija) ?>) para todos los alumnos del archivo.
            </p>

            <form method="POST" enctype="multipart/form-data" id="formExcel">
                <div id="dropZone" class="drop-zone">
                    <i class="bi bi-cloud-arrow-up-fill"></i>
                    <p class="mb-1">Arrastra aquí tu archivo Excel (.xlsx)</p>
                    <p class="small text-muted mb-2">o haz clic para seleccionarlo</p>
                    <input type="file" name="excel_file" id="excel_file" accept=".xlsx" hidden required>
                </div>
                <div id="nombreArchivo" class="small mt-2 text-vino fw-bold"></div>

                <hr class="my-3">

                <label class="form-label fw-bold">¿A qué grupo pertenecen estos alumnos?</label>
                <select name="grupo_destino" id="grupoDestino" class="form-select mb-3" required>
                    <option value="">Selecciona...</option>
                    <?php foreach ($grupos as $g): ?>
                        <option value="<?= $g['id_grupo'] ?>">
                            Grupo <?= htmlspecialchars($g['numero_grupo']) ?>
                            (<?= htmlspecialchars($g['turno_grupo']) ?>, <?= htmlspecialchars($g['semestre_grupo']) ?>° sem)
                        </option>
                    <?php endforeach; ?>
                    <option value="nuevo">+ Crear grupo nuevo</option>
                </select>

                <div id="camposGrupoNuevo" class="row" style="display:none;">
                    <div class="col-md-4 mb-3">
                        <label class="form-label small">Turno</label>
                        <select name="nuevo_turno_grupo" class="form-select">
                            <option value="">Selecciona...</option>
                            <option value="Matutino">Matutino</option>
                            <option value="Vespertino">Vespertino</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label small">Número de grupo</label>
                        <input type="number" name="nuevo_numero_grupo" class="form-control" min="1" placeholder="Ej. 5">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label small">Semestre</label>
                        <input type="number" name="nuevo_semestre_grupo" class="form-control" min="1" max="12" placeholder="Ej. 3">
                    </div>
                </div>

                <button type="submit" class="btn btn-login mt-2" id="btnImportar" disabled>
                    <i class="bi bi-upload me-1"></i> Importar alumnos
                </button>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="mb-3">Alta manual (un alumno)</h5>
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nombre(s)</label>
                                <input type="text" name="nombres_usuario" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Apellidos</label>
                                <input type="text" name="apellidos_usuario" class="form-control" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Matrícula (máx. 8)</label>
                                <input type="text" name="matricula_usuario" class="form-control" maxlength="8" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Correo del alumno</label>
                                <input type="email" name="correo_usuario" class="form-control" required>
                            </div>
                        </div>

                        <div class="mb-2">
                            <small class="text-muted">
                                Unidad Regional: <strong><?= htmlspecialchars($urFija) ?></strong> (la tuya, asignada automáticamente).
                                La contraseña se genera sola y se envía al correo.
                            </small>
                        </div>

                        <div class="mb-4 mt-2">
                            <label class="form-label">Grupo</label>
                            <select name="grupos_id_grupo" class="form-select" required>
                                <option value="">Selecciona...</option>
                                <?php foreach ($grupos as $g): ?>
                                    <option value="<?= $g['id_grupo'] ?>">
                                        Grupo <?= htmlspecialchars($g['numero_grupo']) ?>
                                        (<?= htmlspecialchars($g['turno_grupo']) ?>, <?= htmlspecialchars($g['semestre_grupo']) ?>° sem)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-login w-100" <?= empty($grupos) ? 'disabled' : '' ?>>
                            Integrar alumno
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <h5 class="mb-3">Últimos alumnos integrados</h5>
            <table class="table table-striped">
                <thead><tr><th>Nombre</th><th>Matrícula</th><th>Grupo</th></tr></thead>
                <tbody>
                    <?php foreach ($alumnosRecientes as $a): ?>
                        <tr>
                            <td><?= htmlspecialchars($a['nombres_usuario'] . ' ' . $a['apellidos_usuario']) ?></td>
                            <td><?= htmlspecialchars($a['matricula_usuario']) ?></td>
                            <td><?= $a['numero_grupo'] ? htmlspecialchars($a['numero_grupo'] . ' (' . $a['turno_grupo'] . ')') : '—' ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($alumnosRecientes)): ?>
                        <tr><td colspan="3" class="text-muted">Aún no se ha integrado ningún alumno.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.drop-zone {
    border: 2px dashed #9C2C53;
    border-radius: 12px;
    padding: 40px 20px;
    text-align: center;
    cursor: pointer;
    transition: background-color 0.2s ease;
    color: #9C2C53;
}
.drop-zone.dragover {
    background-color: #f9e9ee;
}
.drop-zone i {
    font-size: 2.5rem;
    display: block;
    margin-bottom: 8px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const dropZone = document.getElementById('dropZone');
    const inputFile = document.getElementById('excel_file');
    const nombreArchivo = document.getElementById('nombreArchivo');
    const btnImportar = document.getElementById('btnImportar');

    const grupoDestino = document.getElementById('grupoDestino');
    const camposGrupoNuevo = document.getElementById('camposGrupoNuevo');
    const inputsGrupoNuevo = camposGrupoNuevo.querySelectorAll('select, input');

    grupoDestino.addEventListener('change', function () {
        const esNuevo = grupoDestino.value === 'nuevo';
        camposGrupoNuevo.style.display = esNuevo ? 'flex' : 'none';
        inputsGrupoNuevo.forEach(campo => {
            campo.required = esNuevo;
        });
    });

    function mostrarArchivo(file) {
        if (file) {
            nombreArchivo.textContent = 'Archivo seleccionado: ' + file.name;
            btnImportar.disabled = false;
        }
    }

    dropZone.addEventListener('click', () => inputFile.click());

    inputFile.addEventListener('change', () => {
        mostrarArchivo(inputFile.files[0]);
    });

    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.classList.add('dragover');
    });

    dropZone.addEventListener('dragleave', () => {
        dropZone.classList.remove('dragover');
    });

    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('dragover');

        const file = e.dataTransfer.files[0];
        if (file && file.name.toLowerCase().endsWith('.xlsx')) {
            inputFile.files = e.dataTransfer.files;
            mostrarArchivo(file);
        } else {
            alert('Solo se aceptan archivos .xlsx');
        }
    });
});
</script>

<?php require_once __DIR__ . '/../src/includes/footer.php'; ?>