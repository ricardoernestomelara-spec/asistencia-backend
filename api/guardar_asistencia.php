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

$asistencias = $data['asistencias'] ?? [];
$fecha = $data['fecha'] ?? date('Y-m-d');

if (empty($asistencias)) {
    echo json_encode(["success" => false, "message" => "No hay datos de asistencia para guardar"]);
    exit();
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO asistencia (estudiante_id, fecha, estado) 
        VALUES (:estudiante_id, :fecha, :estado)
        ON DUPLICATE KEY UPDATE estado = VALUES(estado)
    ");

    foreach ($asistencias as $item) {
        $stmt->execute([
            ':estudiante_id' => $item['estudiante_id'],
            ':fecha' => $fecha,
            ':estado' => $item['estado']
        ]);
    }

    $pdo->commit();
    echo json_encode(["success" => true, "message" => "Asistencia guardada correctamente"]);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Error al guardar asistencia: " . $e->getMessage()]);
}
?>