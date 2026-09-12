<?php
/**
 * Conexión a la base de datos.
 * Las credenciales ya no viven aquí: se leen de un archivo .env en la raíz
 * del proyecto (ver .env.example). Ese .env NO se versiona (ver .gitignore).
 */

function cargarEnv(string $ruta): void
{
    if (!file_exists($ruta)) {
        return;
    }

    foreach (file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linea) {
        $linea = trim($linea);
        if ($linea === '' || substr($linea, 0, 1) === '#') {
            continue;
        }

        $partes = explode('=', $linea, 2);
        if (count($partes) !== 2) {
            continue;
        }

        [$clave, $valor] = $partes;
        $clave = trim($clave);
        $valor = trim($valor, " \t\n\r\0\x0B\"'");

        if ($clave !== '' && getenv($clave) === false) {
            putenv("$clave=$valor");
            $_ENV[$clave] = $valor;
        }
    }
}

cargarEnv(__DIR__ . '/../.env');

$host    = getenv('DB_HOST') ?: 'localhost';
$db      = getenv('DB_NAME') ?: 'datacode.db';
$user    = getenv('DB_USER') ?: 'root';
$pass    = getenv('DB_PASS') ?: '';
$charset = getenv('DB_CHARSET') ?: 'utf8mb4';
$port    = getenv('DB_PORT') ?: null; // opcional, por si tu MySQL/Laragon usa un puerto distinto al estandar

try {
    $dsn = "mysql:host=$host;dbname=$db;charset=$charset" . ($port ? ";port=$port" : "");
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Error de conexión: ' . $e->getMessage());
}
