<?php
session_start();
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

$matricula = trim($_POST['matricula'] ?? '');
$password  = trim($_POST['password'] ?? '');

if (empty($matricula) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Completa todos los campos']);
    exit;
}

$stmt = $pdo->prepare('
    SELECT id_usuario, nombres_usuario, apellidos_usuario, habilitado_usuario, roles_id, unidadesregionales_id_ur
    FROM usuarios
    WHERE matricula_usuario = ? AND password_usuario = ?
');
$stmt->execute([$matricula, $password]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    echo json_encode(['success' => false, 'message' => 'Matrícula o contraseña incorrecta']);
    exit;
}

if ($usuario['habilitado_usuario'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Tu cuenta está deshabilitada']);
    exit;
}

$_SESSION['usuario_id'] = $usuario['id_usuario'];
$_SESSION['nombre']     = $usuario['nombres_usuario'] . ' ' . $usuario['apellidos_usuario'];
$_SESSION['rol_id']     = $usuario['roles_id'];
$_SESSION['ur_id']      = $usuario['unidadesregionales_id_ur'];

echo json_encode([
    'success' => true,
    'nombre' => $_SESSION['nombre']
]);