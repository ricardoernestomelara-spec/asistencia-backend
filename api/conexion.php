<?php
// Silenciar cualquier advertencia que corrompa la respuesta JSON
error_reporting(0);
ini_set('display_errors', 0);

// Credenciales tomadas directamente de tu panel de Aiven
$host = 'mysql-eff2255-clases-8fe7.g.aivencloud.com';
$user = 'avnadmin';
$pass = 'AVNS_CNDqZqgot6GyR9ZldBV'; // Asegúrate de colocar aquí tu contraseña real de Aiven
$db   = 'defaultdb';
$port = 22133;

// Inicializar MySQLi
$conn = mysqli_init();

if (!$conn) {
    header("Content-Type: application/json; charset=UTF-8");
    echo json_encode([
        "success" => false, 
        "message" => "Error al inicializar la conexión MySQLi"
    ]);
    exit();
}

// Configurar SSL (Aiven requiere SSL_mode = REQUIRED)
$conn->ssl_set(NULL, NULL, NULL, NULL, NULL);

// Conectar utilizando el puerto y host correcto de Aiven
if (!@$conn->real_connect($host, $user, $pass, $db, $port, NULL, MYSQLI_CLIENT_SSL)) {
    header("Content-Type: application/json; charset=UTF-8");
    echo json_encode([
        "success" => false, 
        "message" => "Error de conexión a la base de datos: " . mysqli_connect_error()
    ]);
    exit();
}

$conn->set_charset("utf8mb4");
?>