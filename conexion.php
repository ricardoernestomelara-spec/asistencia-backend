<?php
error_reporting(0);
ini_set('display_errors', 0);

// Extraer credenciales directamente de DATABASE_URL
$db_url = getenv('DATABASE_URL') ?: 'mysql://avnadmin:AVNS_CNDqZqgot6GyR9ZIcbV@mysql-eff2255-clases-8fe7.g.aivencloud.com:22133/defaultdb?ssl-mode=REQUIRED';

$url = parse_url($db_url);

$host = $url['host'];
$user = $url['user'];
$pass = $url['pass'];
$db   = ltrim($url['path'], '/');
$port = $url['port'] ?: 22133;

try {
    $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
    
    // Opciones para deshabilitar verificación estricta de certificado SSL en Aiven
    $options = [
        PDO::MYSQL_ATTR_SSL_CA => NULL,
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ];

    $pdo = new PDO($dsn, $user, $pass, $options);

    // Compatibilidad para scripts que utilicen mysqli_query($conn, ...)
    $conn = new mysqli($host, $user, $pass, $db, $port);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(["success" => false, "message" => "Error de conexion: " . $e->getMessage()]);
    exit();
}
?>