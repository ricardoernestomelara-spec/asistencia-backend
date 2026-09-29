<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../conexion.php';

try {
    if (!isset($pdo) && isset($conn)) {
        $pdo = $conn;
    }

    $input = json_decode(file_get_contents("php://input"), true);

    $fecha = $input['fecha'] ?? date('Y-m-d');
    $seccionId = $input['seccion_id'] ?? null;
    $asignaturaId = $input['asignatura_id'] ?? null;
    $asistencias = $input['asistencias'] ?? [];

    if (!$seccionId || empty($asistencias)) {
        echo json_encode(['success' => false, 'message' => 'Faltan datos de sección o la lista de asistencias.']);
        exit;
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO asistencias (estudiante_id, seccion_id, asignatura_id, fecha, estado, motivo, observacion)
        VALUES (:estudiante_id, :seccion_id, :asignatura_id, :fecha, :estado, :motivo, :observacion)
        ON DUPLICATE KEY UPDATE 
            estado = VALUES(estado),
            motivo = VALUES(motivo),
            observacion = VALUES(observacion)
    ");

    foreach ($asistencias as $item) {
        $stmt->execute([
            ':estudiante_id' => $item['estudiante_id'],
            ':seccion_id' => $seccionId,
            ':asignatura_id' => $asignaturaId,
            ':fecha' => $fecha,
            ':estado' => $item['estado'],
            ':motivo' => $item['motivo'] ?? null,
            ':observacion' => $item['observacion'] ?? null
        ]);
    }

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Asistencia guardada correctamente.']);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>