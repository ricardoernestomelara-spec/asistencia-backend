<?php
error_reporting(0);
ini_set('display_errors', 0);

$host = trim(getenv('DB_HOST'));
$user = trim(getenv('DB_USER') ?: 'avnadmin');
$pass = trim(getenv('DB_PASS') ?: getenv('DB_PASSWORD'));
$db   = trim(getenv('DB_NAME') ?: 'defaultdb');
$port = (int)(getenv('DB_PORT') ?: 22133);

if (empty($host) || empty($pass)) {
    throw new Exception("Error: Faltan variables DB_HOST o DB_PASS en Render.");
}

$conn = mysqli_init();
if (!$conn) {
    throw new Exception("Error al inicializar mysqli");
}

$conn->ssl_set(NULL, NULL, NULL, NULL, NULL);

if (!$conn->real_connect($host, $user, $pass, $db, $port, NULL, MYSQLI_CLIENT_SSL)) {
    throw new Exception("Error de conexion BD: " . mysqli_connect_error());
}

$conn->set_charset("utf8mb4");
?>