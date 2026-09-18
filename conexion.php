<?php
error_reporting(0);
ini_set('display_errors', 0);

// Host y credenciales de Aiven
$hostname = 'mysql-eff2255-clases-8fe7.g.aivencloud.com';
$user     = 'avnadmin';
$pass     = 'AVNS_CNDqZqgot6GyR9ZldBV';
$db       = 'defaultdb';
$port     = 22133;

// Intentar resolver la IP directamente para evitar fallos de DNS en Render
$ip = gethostbyname($hostname);
$host = ($ip !== $hostname) ? $ip : $hostname;

try {
    $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
    $options = [
        PDO::MYSQL_ATTR_SSL_CA => NULL,
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ];

    $pdo = new PDO($dsn, $user, $pass, $options);

    // Compatibilidad para scripts que usan $conn en mysqli
    $conn = mysqli_init();
    $conn->ssl_set(NULL, NULL, NULL, NULL, NULL);
    @$conn->real_connect($host, $user, $pass, $db, $port, NULL, MYSQLI_CLIENT_SSL);

} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(["success" => false, "message" => "Error de conexion: " . $e->getMessage()]);
    exit();
}
?>