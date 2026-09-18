<?php
// Permitir solicitudes desde el frontend (CORS)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

// Manejo de petición preflight (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

error_reporting(0);
ini_set('display_errors', 0);

// Credenciales de Aiven
$hostname = 'mysql-eff2255-clases-8fe7.g.aivencloud.com';
$user     = 'avnadmin';
$pass     = 'AVNS_CNDqZqgot6GyR9ZldBV';
$db       = 'defaultdb';
$port     = 22133;

// Resolución de IP para evitar fallos DNS en Render
$ip = gethostbyname($hostname);
$host = ($ip !== $hostname) ? $ip : '146.190.168.190';

try {
    $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
    $options = [
        PDO::MYSQL_ATTR_SSL_CA => NULL,
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ];

    // Instancia PDO principal
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // Apuntamos $conn al mismo objeto PDO para compatibilidad total
    $conn = $pdo;

} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Error de conexion: " . $e->getMessage()]);
    exit();
}
?>