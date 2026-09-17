<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once('conexion.php');

echo json_encode([
    "success" => true,
    "message" => "Conexión correcta"
]);

?>