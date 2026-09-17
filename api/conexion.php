<?php
error_reporting(0);
ini_set('display_errors', 0);

// Host exacto sin espacios ocultos
$host = trim('mysql-3d44bc41-ricardoernestomelara-spec.k.aivencloud.com');
$port = 22133;
$user = 'avnadmin';

$password = getenv('DB_PASSWORD') ?: getenv('DB_PASS') ?: $_ENV['DB_PASS'] ?: $_ENV['DB_PASSWORD']; 
$database = 'defaultdb';

$conn = mysqli_init();
$conn->ssl_set(NULL, NULL, NULL, NULL, NULL);

if (!$conn->real_connect($host, $user, $password, $database, $port, NULL, MYSQLI_CLIENT_SSL)) {
    if (ob_get_length()) ob_clean();
    header("Content-Type: application/json; charset=UTF-8");
    echo json_encode([
        "success" => false, 
        "message" => "Error de conexión a la base de datos: " . $conn->connect_error
    ]);
    exit();
}
?>