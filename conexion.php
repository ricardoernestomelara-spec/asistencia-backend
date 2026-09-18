<?php
error_reporting(0);
ini_set('display_errors', 0);

$host = 'mysql-eff2255-clases-8fe7.g.aivencloud.com';
$user = 'avnadmin';
$pass = 'AVNS_CNDqZqgot6GyR9ZldBV';
$db   = 'defaultdb';
$port = 22133;

$conn = mysqli_init();

if (!$conn) {
    header("Content-Type: application/json; charset=UTF-8");
    echo json_encode(["success" => false, "message" => "Error al inicializar MySQLi"]);
    exit();
}

$conn->options(MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, false);
$conn->ssl_set(NULL, NULL, NULL, NULL, NULL);

if (!$conn->real_connect($host, $user, $pass, $db, $port, NULL, MYSQLI_CLIENT_SSL)) {
    header("Content-Type: application/json; charset=UTF-8");
    echo json_encode(["success" => false, "message" => "Error de conexión BD: " . mysqli_connect_error()]);
    exit();
}

$conn->set_charset("utf8mb4");
?>