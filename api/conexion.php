<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

$host = getenv('DB_HOST') ?: 'mysql-eff2255-clases-8fe7.g.aivencloud.com';
$user = getenv('DB_USER') ?: 'avnadmin';
$pass = getenv('DB_PASS') ?: '';
$db   = getenv('DB_NAME') ?: 'defaultdb';
$port = (int)(getenv('DB_PORT') ?: 22133);

try {
    $conn = new mysqli($host, $user, $pass, $db, $port);
    if ($conn->connect_error) {
        die(json_encode(["error" => "Conexión fallida: " . $conn->connect_error]));
    }
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    echo json_encode(["error" => "Excepción: " . $e->getMessage()]);
    exit();
}
?>