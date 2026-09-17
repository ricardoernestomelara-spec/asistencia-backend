<?php
// Evita que PHP muestre advertencias como texto HTML
error_reporting(0);
ini_set('display_errors', 0);

$host = 'mysql-3d44bc41-ricardoernestomelara-spec.k.aivencloud.com';
$port = 22133;
$user = 'avnadmin';
$password = 'AVNS_CNDqZqgot6GyR9ZldBV';
$database = 'defaultdb';

// Crear conexión
$conn = new mysqli($host, $user, $password, $database, $port);

// Si falla la conexión, devolver JSON puro y detener
if ($conn->connect_error) {
    if (ob_get_length()) ob_clean();
    header("Content-Type: application/json; charset=UTF-8");
    echo json_encode([
        "success" => false, 
        "message" => "Error de conexión a la base de datos"
    ]);
    exit();
}
?>