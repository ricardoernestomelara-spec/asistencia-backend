<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../conexion.php'; // cite: 2

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["success" => false, "message" => "Datos no válidos."]);
    exit();
}

$fecha = $data['fecha'] ?? date('Y-m-d');
$asignatura = $data['asignatura'] ?? '';
$periodo = $data['periodo'] ?? '';
$asistencias = $data['asistencias'] ?? [];

if (empty($asistencias)) {
    echo json_encode(["success" => false, "message" => "No hay asistencias para guardar."]);
    exit();
}

try {
    // Consulta con INSERT ... ON DUPLICATE KEY UPDATE para actualizar si ya existe la fecha/estudiante
    $sql = "INSERT INTO asistencias (estudiante_id, fecha, asignatura, periodo, estado, inasistencia_por, observacion)
            VALUES (:estudiante_id, :fecha, :asignatura, :periodo, :estado, :inasistencia_por, :observacion)
            ON DUPLICATE KEY UPDATE 
                estado = VALUES(estado),
                inasistencia_por = VALUES(inasistencia_por),
                observacion = VALUES(observacion)";

    $stmt = $pdo->prepare($sql); // cite: 1, 2

    $registrosProcesados = 0;
    foreach ($asistencias as $ast) {
        $estudiante_id = $ast['estudiante_id'] ?? null;
        $estado = $ast['asistencia'] ?? $ast['estado'] ?? 'Asistió';
        $inasistencia_por = $ast['inasistencia_por'] ?? null;
        $observacion = $ast['observacion'] ?? null;

        if ($estudiante_id) {
            $stmt->execute([
                ':estudiante_id' => $estudiante_id,
                ':fecha' => $fecha,
                ':asignatura' => $asignatura,
                ':periodo' => $periodo,
                ':estado' => $estado,
                ':inasistencia_por' => $inasistencia_por,
                ':observacion' => $observacion
            ]);
            $registrosProcesados++;
        }
    }

    echo json_encode([
        "success" => true,
        "message" => "Asistencia procesada correctamente ({$registrosProcesados} registros)."
    ]);

} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Error al guardar: " . $e->getMessage()]);
}
?>