<?php
// Silenciar cualquier advertencia HTML que rompa la respuesta JSON
error_reporting(0);
ini_set('display_errors', 0);

$host = 'mysql-eff2255-clases-8fe7.g.aivencloud.com';
$user = 'avnadmin';
$pass = 'AVNS_CNDqZqgot6GyR9ZldBV'; // Tu contraseña de Aiven
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

// Desactivar la validación del certificado SSL estricto para evitar bloqueos en servidores cloud
$conn->options(MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, false);
$conn->ssl_set(NULL, NULL, NULL, NULL, NULL);

// Conectar a la base de datos de Aiven (Sin el arroba @ para capturar errores reales)
if (!$conn->real_connect($host, $user, $pass, $db, $port, NULL, MYSQLI_CLIENT_SSL)) {
    header("Content-Type: application/json; charset=UTF-8");
    echo json_encode([
        "success" => false, 
        "message" => "Error de conexión a Aiven: " . mysqli_connect_error()
    ]);
    exit();
}

$conn->set_charset("utf8mb4");
?>