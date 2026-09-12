<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

// Solo alumnos o miembros de comité logueados pueden inscribirse
if (!usuarioLogueado() || !esAlumnado()) {
    header('Location: /torneos/ver-torneos.php');
    exit;
}

$id_torneo = $_POST['id_torneo'] ?? null;

if (!$id_torneo) {
    header('Location: /torneos/ver-torneos.php?error=faltan_datos');
    exit;
}

$stmtT = $pdo->prepare("SELECT id_torneo FROM torneos WHERE id_torneo = ?");
$stmtT->execute([$id_torneo]);
if (!$stmtT->fetch()) {
    header('Location: /torneos/ver-torneos.php?error=no_existe');
    exit;
}

// Revalidamos en servidor que no esté ya inscrito a este torneo
$chk = $pdo->prepare("SELECT COUNT(*) FROM inscripcionestorneos WHERE torneos_id_torneo = ? AND usuarios_id_usuario = ?");
$chk->execute([$id_torneo, $_SESSION['usuario_id']]);
if ($chk->fetchColumn() > 0) {
    header('Location: /torneos/ver-torneos.php?error=ya_inscrito');
    exit;
}

$insert = $pdo->prepare("INSERT INTO inscripcionestorneos (torneos_id_torneo, usuarios_id_usuario) VALUES (?, ?)");
$insert->execute([$id_torneo, $_SESSION['usuario_id']]);

header('Location: /torneos/ver-torneos.php?exito=inscrito');
exit;
