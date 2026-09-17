<?php
error_reporting(0);
ini_set('display_errors', 0);

if (ob_get_length()) ob_clean();

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/conexion.php';

$input = json_decode(file_get_contents("php://input"), true);
$docente_id = intval($input['docente_id'] ?? 0);
$seccion_id = intval($input['seccion_id'] ?? 0);
$asignatura_id = intval($input['asignatura_id'] ?? 0);

if ($docente_id <= 0 || $seccion_id <= 0 || $asignatura_id <= 0) {
    echo json_encode(["success" => false, "message" => "Datos incompletos"]);
    exit;
}

// 1. COMPROBAR SI LA ASIGNATURA Y SECCIÓN YA FUERON ASIGNADAS A CUALQUIER DOCENTE
$sqlCheck = "SELECT id FROM docente_carga WHERE asignatura_id = ? AND seccion_id = ?";
$stmtCheck = $conn->prepare($sqlCheck);
$stmtCheck->bind_param("ii", $asignatura_id, $seccion_id);
$stmtCheck->execute();
$resCheck = $stmtCheck->get_result();

if ($resCheck->num_rows > 0) {
    echo json_encode([
        "success" => false, 
        "message" => "Esta asignatura en esta sección ya se encuentra asignada a un docente."
    ]);
    $stmtCheck->close();
    $conn->close();
    exit;
}
$stmtCheck->close();

// 2. GUARDAR ASIGNACIÓN
$sql = "INSERT INTO docente_carga (docente_id, asignatura_id, seccion_id) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $docente_id, $asignatura_id, $seccion_id);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Asignación realizada con éxito"]);
} else {
    echo json_encode(["success" => false, "message" => "Error al guardar en base de datos"]);
}

$stmt->close();
$conn->close();