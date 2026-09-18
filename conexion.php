<?php
error_reporting(0);
ini_set('display_errors', 0);

// Forzar lectura de DATABASE_URL o MYSQL_URL desde Render
$db_url = getenv('DATABASE_URL') ?: getenv('MYSQL_URL');

if ($db_url) {
    $url = parse_url($db_url);
    $host = $url['host'];
    $user = $url['user'];
    $pass = $url['pass'];
    $db   = ltrim($url['path'], '/');
    $port = $url['port'] ?: 22133;
} else {
    // Si no detecta la URL, usar la URI de Aiven como valor por defecto
    $host = 'mysql-eff2255-clases-8fe7.g.aivencloud.com'; // Asegúrate de quitar el guion extra si el host real no lo lleva
    $user = 'avnadmin';
    $pass = 'AVNS_CNDqZqgot6GyR9ZIcbV';
    $db   = 'defaultdb';
    $port = 22133;
}

$conn = mysqli_init();
if (!$conn) {
    die(json_encode(["success" => false, "message" => "Error al inicializar mysqli"]));
}

$conn->ssl_set(NULL, NULL, NULL, NULL, NULL);

if (!$conn->real_connect($host, $user, $pass, $db, $port, NULL, MYSQLI_CLIENT_SSL)) {
    die(json_encode(["success" => false, "message" => "Error de conexión BD: " . mysqli_connect_error()]));
}

$conn->set_charset("utf8mb4");
?>