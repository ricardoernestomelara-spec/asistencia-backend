<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../conexion.php';

$data = json_decode(file_get_contents("php://input"), true);

$docente_id = $data['docente_id'] ?? null;
$asignatura_id = $data['asignatura_id'] ?? null;
$seccion_id = $data['seccion_id'] ?? null;

if (!$docente_id || !$asignatura_id || !$seccion_id) {
    echo json_encode(["success" => false, "message" => "Todos los campos son obligatorios"]);
    exit();
}

try {
    $stmt = $pdo->prepare("INSERT INTO carga_academica (docente_id, asignatura_id, seccion_id) VALUES (:docente_id, :asignatura_id, :seccion_id)");
    $stmt->execute([
        ':docente_id' => $docente_id,
        ':asignatura_id' => $asignatura_id,
        ':seccion_id' => $seccion_id
    ]);

    echo json_encode(["success" => true, "message" => "Carga asignada correctamente"]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Error al guardar carga: " . $e->getMessage()]);
}
?>