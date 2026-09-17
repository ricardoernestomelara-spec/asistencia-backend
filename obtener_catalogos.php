<?php
error_reporting(0);
ini_set('display_errors', 0);

if (ob_get_length()) ob_clean();

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/conexion.php';

// 1. Obtener Docentes
$sqlDocentes = "SELECT id, nombre FROM docentes ORDER BY nombre ASC";
$resDocentes = $conn->query($sqlDocentes);
$docentes = [];
while ($row = $resDocentes->fetch_assoc()) {
    $docentes[] = $row;
}

// 2. Obtener Secciones (usa la tabla que ya existe en tu BD)
$sqlSecciones = "SELECT id, nombre FROM secciones ORDER BY id ASC";
$resSecciones = $conn->query($sqlSecciones);
$secciones = [];
while ($row = $resSecciones->fetch_assoc()) {
    $secciones[] = $row;
}

// 3. Obtener Asignaturas
$sqlAsignaturas = "SELECT id, nombre FROM asignaturas ORDER BY nombre ASC";
$resAsignaturas = $conn->query($sqlAsignaturas);
$asignaturas = [];
while ($row = $resAsignaturas->fetch_assoc()) {
    $asignaturas[] = $row;
}

$conn->close();

echo json_encode([
    "success" => true,
    "docentes" => $docentes,
    "secciones" => $secciones,
    "asignaturas" => $asignaturas
]);