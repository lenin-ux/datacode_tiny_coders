<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Solo alumnos logueados pueden inscribirse
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_id'] != 1) {
    header('Location: /talleres/ver-talleres.php');
    exit;
}

$id_taller = $_POST['id_taller'] ?? null;

if (!$id_taller) {
    header('Location: /talleres/ver-talleres.php?error=faltan_datos');
    exit;
}

// Revalidamos en servidor: ¿ya está inscrito en algún taller? (nunca confiar solo en el botón deshabilitado del frontend)
$chk = $pdo->prepare("SELECT COUNT(*) FROM inscripcionestalleres WHERE usuarios_id_usuario = ?");
$chk->execute([$_SESSION['usuario_id']]);
if ($chk->fetchColumn() > 0) {
    header('Location: /talleres/ver-talleres.php?error=ya_inscrito');
    exit;
}

// Obtenemos la Unidad Regional del taller para calcular cupo
$stmt = $pdo->prepare("
    SELECT j.unidadesregionales_id_ur
    FROM talleres t
    JOIN jornadas j ON j.id_jornada = t.jornadas_id_jornada
    WHERE t.id_taller = ?
");
$stmt->execute([$id_taller]);
$taller = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$taller) {
    header('Location: /talleres/ver-talleres.php?error=no_existe');
    exit;
}

$cupoTotalStmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE roles_id = 1 AND unidadesregionales_id_ur = ?");
$cupoTotalStmt->execute([$taller['unidadesregionales_id_ur']]);
$cupoTotal = $cupoTotalStmt->fetchColumn();

$inscritosStmt = $pdo->prepare("SELECT COUNT(*) FROM inscripcionestalleres WHERE talleres_id_taller = ?");
$inscritosStmt->execute([$id_taller]);
$inscritos = $inscritosStmt->fetchColumn();

if ($inscritos >= $cupoTotal) {
    header('Location: /talleres/ver-talleres.php?error=sin_cupo');
    exit;
}

// Todo válido: registramos la inscripción
$insert = $pdo->prepare("INSERT INTO inscripcionestalleres (talleres_id_taller, usuarios_id_usuario) VALUES (?, ?)");
$insert->execute([$id_taller, $_SESSION['usuario_id']]);

header('Location: /talleres/ver-talleres.php?exito=inscrito');
exit;