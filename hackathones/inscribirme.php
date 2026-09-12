<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

if (!usuarioLogueado() || !esAlumnado()) {
    header('Location: /hackathones/ver-hackathones.php');
    exit;
}

$id_hackaton = $_POST['id_hackaton'] ?? null;

if (!$id_hackaton) {
    header('Location: /hackathones/ver-hackathones.php?error=faltan_datos');
    exit;
}

$stmtH = $pdo->prepare("SELECT id_hackaton FROM hackathones WHERE id_hackaton = ?");
$stmtH->execute([$id_hackaton]);
if (!$stmtH->fetch()) {
    header('Location: /hackathones/ver-hackathones.php?error=no_existe');
    exit;
}

$chk = $pdo->prepare("SELECT COUNT(*) FROM inscripcioneshackatons WHERE hackathones_id_hackaton = ? AND usuarios_id_usuario = ?");
$chk->execute([$id_hackaton, $_SESSION['usuario_id']]);
if ($chk->fetchColumn() > 0) {
    header('Location: /hackathones/ver-hackathones.php?error=ya_inscrito');
    exit;
}

$insert = $pdo->prepare("INSERT INTO inscripcioneshackatons (hackathones_id_hackaton, usuarios_id_usuario) VALUES (?, ?)");
$insert->execute([$id_hackaton, $_SESSION['usuario_id']]);

header('Location: /hackathones/ver-hackathones.php?exito=inscrito');
exit;
