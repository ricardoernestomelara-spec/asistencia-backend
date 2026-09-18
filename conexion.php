<?php
// Desactivar reporte de errores visuales para mantener respuestas JSON limpias
error_reporting(0);
ini_set('display_errors', 0);

$host = trim(getenv('DB_HOST') ?: 'mysql-eff2255-clases-8fe7.g.aivencloud.com');
$user = trim(getenv('DB_USER') ?: 'avnadmin');
$pass = trim(getenv('DB_PASS') ?: getenv('DB_PASSWORD') ?: 'AVNS_CNDqZqgot6GyR9ZIcbV');
$db   = trim(getenv('DB_NAME') ?: 'defaultdb');
$port = (int)(getenv('DB_PORT') ?: 22133);

// Inicializar MySQLi para configurar la bandera SSL requerida por Aiven
$conn = mysqli_init();

if (!$conn) {
    throw new Exception("Error al inicializar mysqli");
}

// Configurar cliente SSL (permite conexiones cifradas sin verificar certificado local)
$conn->ssl_set(NULL, NULL, NULL, NULL, NULL);

// Establecer la conexión con el flag MYSQLI_CLIENT_SSL
if (!$conn->real_connect($host, $user, $pass, $db, $port, NULL, MYSQLI_CLIENT_SSL)) {
    throw new Exception("Error de conexión BD: " . mysqli_connect_error());
}

$conn->set_charset("utf8mb4");
?>