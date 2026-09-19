<?php
// Limpieza y configuración estricta de cabeceras CORS
header_remove('Access-Control-Allow-Origin');
header_remove('Access-Control-Allow-Headers');
header_remove('Access-Control-Allow-Methods');

header("Access-Control-Allow-Origin: *", true);
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Origin, Accept", true);
header("Access-Control-Allow-Methods: POST, OPTIONS", true);

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

header("Content-Type: application/json; charset=UTF-8");

error_reporting(0);
ini_set('display_errors', 0);

try {
    require_once __DIR__ . '/../conexion.php';

    if (!isset($pdo) && isset($conn)) {
        $pdo = $conn;
    }

    if (!isset($pdo) || !$pdo) {
        throw new Exception("Sin conexión a la base de datos.");
    }

    // Auto-crear la tabla asistencia si no existe en Aiven
    $pdo->exec("CREATE TABLE IF NOT EXISTS asistencia (
        id INT AUTO_INCREMENT PRIMARY KEY,
        estudiante_id INT NOT NULL,
        fecha DATE NOT NULL,
        estado VARCHAR(20) NOT NULL,
        observacion VARCHAR(255) NULL,
        FOREIGN KEY (estudiante_id) REFERENCES estudiantes(id) ON DELETE CASCADE,
        UNIQUE KEY unique_asistencia (estudiante_id, fecha)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $data = json_decode(file_get_contents("php://input"), true);

    // Soporte para arreglos enviados directamente o bajo la clave 'asistencias' / 'alumnos'
    $asistencias = $data['asistencias'] ?? $data['alumnos'] ?? $data ?? [];
    $fecha = $data['fecha'] ?? date('Y-m-d');

    if (!is_array($asistencias) || empty($asistencias)) {
        echo json_encode(["success" => false, "message" => "No hay datos de asistencia para guardar"]);
        exit();
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO asistencia (estudiante_id, fecha, estado, observacion) 
        VALUES (:estudiante_id, :fecha, :estado, :observacion)
        ON DUPLICATE KEY UPDATE 
            estado = VALUES(estado),
            observacion = VALUES(observacion)
    ");

    $insertados = 0;

    foreach ($asistencias as $item) {
        // Extraer id de estudiante soportando 'estudiante_id' o 'id'
        $estudiante_id = $item['estudiante_id'] ?? $item['id'] ?? null;
        $estado = $item['estado'] ?? 'Asistió';
        $observacion = $item['observacion'] ?? $item['inasistencia_por'] ?? null;

        if ($estudiante_id) {
            $stmt->execute([
                ':estudiante_id' => $estudiante_id,
                ':fecha'         => $fecha,
                ':estado'        => $estado,
                ':observacion'   => $observacion
            ]);
            $insertados++;
        }
    }

    $pdo->commit();

    if ($insertados > 0) {
        echo json_encode(["success" => true, "message" => "Asistencia guardada correctamente ($insertados registros)"]);
    } else {
        echo json_encode(["success" => false, "message" => "No se encontraron IDs de estudiantes válidos en el envío"]);
    }

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(200);
    echo json_encode(["success" => false, "message" => "Error al guardar asistencia: " . $e->getMessage()]);
}
?>