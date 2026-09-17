<?php
error_reporting(0);
ini_set('display_errors', 0);

// Host limpio sin caracteres especiales ocultos
$host = 'mysql-3d44bc41-ricardoernestomelara-spec.k.aivencloud.com';
$port = 22133;
$user = 'avnadmin';

// Obtiene la contraseña configurada en Render
$password = getenv('DB_PASS') ?: getenv('DB_PASSWORD') ?: $_ENV['DB_PASS'] ?: $_ENV['DB_PASSWORD'];
$database = 'defaultdb';

// Inicialización SSL para Aiven
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