<?php
error_reporting(0);
ini_set('display_errors', 0);

$db_url = getenv('DATABASE_URL') ?: getenv('MYSQL_URL');

if ($db_url) {
    $url = parse_url($db_url);
    $host = $url['host'];
    $user = $url['user'];
    $pass = $url['pass'];
    $db   = ltrim($url['path'], '/');
    $port = $url['port'] ?: 22133;
} else {
    $host = trim(getenv('DB_HOST'));
    $user = trim(getenv('DB_USER') ?: 'avnadmin');
    $pass = trim(getenv('DB_PASS') ?: getenv('DB_PASSWORD'));
    $db   = trim(getenv('DB_NAME') ?: 'defaultdb');
    $port = (int)(getenv('DB_PORT') ?: 22133);
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