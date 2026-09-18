<?php
// Obtener variables de entorno o usar valores exactos de Aiven por defecto
$host = trim(getenv('DB_HOST') ?: 'mysql-eff2255-clases-8fe7.g.aivencloud.com');
$user = trim(getenv('DB_USER') ?: 'avnadmin');
$pass = trim(getenv('DB_PASS') ?: getenv('DB_PASSWORD') ?: 'AVNS_CNDqZqgot6GyR9ZIcbV');
$db   = trim(getenv('DB_NAME') ?: 'defaultdb');
$port = (int)(getenv('DB_PORT') ?: 22133);

// Crear conexión MySQLi
$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    throw new Exception("Error de conexión a la BD: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>