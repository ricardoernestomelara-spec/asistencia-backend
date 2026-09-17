<?php
$host = 'mysql-eff2255-clases-8fe7.g.aivencloud.com';
$user = 'avnadmin';
$pass = 'TU_CONTRASEÑA_DE_AIVEN'; // Reemplaza con tu contraseña de Aiven
$db   = 'defaultdb';
$port = 22133;

$conn = @new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Error de conexión BD"]);
    exit();
}

$conn->set_charset("utf8mb4");
?>