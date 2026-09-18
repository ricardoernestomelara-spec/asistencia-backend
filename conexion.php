<?php
$host = getenv('DB_HOST') ?: "mysql-eff2255-clases-8fe7.g.aivencloud.com";
$user = getenv('DB_USER') ?: "avnadmin";
$pass = getenv('DB_PASS'); // Sin contraseña por defecto en el código
$db   = getenv('DB_NAME') ?: "defaultdb";
$port = getenv('DB_PORT') ?: 22133;

$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    throw new Exception("Error de conexión a la BD: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>