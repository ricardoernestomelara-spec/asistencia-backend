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

    // Asegurar tabla de asistencia
    $pdo->exec("CREATE TABLE IF NOT EXISTS asistencia (
        id INT AUTO_INCREMENT PRIMARY KEY,
        estudiante_id INT NOT NULL,
        fecha DATE NOT NULL,
        estado VARCHAR(50) NOT NULL,
        observacion VARCHAR(255) NULL,
        FOREIGN KEY (estudiante_id) REFERENCES estudiantes(id) ON DELETE CASCADE,
        UNIQUE KEY unique_asistencia (estudiante_id, fecha)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $rawInput = file_get_contents("php://input");
    $data = json_decode($rawInput, true);

    if (!$data) {
        echo json_encode(["success" => false, "message" => "No se recibieron datos JSON válidos."]);
        exit();
    }

    $fecha = $data['fecha'] ?? date('Y-m-d');
    
    // Identificar el bloque de datos enviado
    $items = $data['asistencias'] ?? $data['alumnos'] ?? $data['estudiantes'] ?? $data['datos'] ?? $data;

    if (!is_array($items) || empty($items)) {
        echo json_encode(["success" => false, "message" => "No hay datos de asistencia para guardar"]);
        exit();
    }

    $pdo->beginTransaction();

    $stmtInsert = $pdo->prepare("
        INSERT INTO asistencia (estudiante_id, fecha, estado, observacion) 
        VALUES (:estudiante_id, :fecha, :estado, :observacion)
        ON DUPLICATE KEY UPDATE 
            estado = VALUES(estado),
            observacion = VALUES(observacion)
    ");

    // Consulta de respaldo si el ID enviado es NIE en lugar de ID primario
    $stmtFindId = $pdo->prepare("SELECT id FROM estudiantes WHERE id = :val OR nie = :val LIMIT 1");

    $insertados = 0;

    foreach ($items as $key => $val) {
        $identificador = null;
        $estado = 'Asistió';
        $observacion = null;

        if (is_array($val)) {
            // Caso A: Arreglo de objetos [{ id: 1, estado: '...' }]
            $identificador = $val['estudiante_id'] ?? $val['id'] ?? $val['nie'] ?? $val['id_estudiante'] ?? $key;
            $estado = $val['estado'] ?? $val['asistencia'] ?? 'Asistió';
            $observacion = $val['observacion'] ?? $val['inasistencia_por'] ?? $val['motivo'] ?? null;
        } else {
            // Caso B: Objeto Mapa { "10293841": "Asistió" } o { "1": "Permiso" }
            if ($key !== 'fecha' && $key !== 'seccion' && $key !== 'asignatura') {
                $identificador = $key;
                $estado = (string)$val;
            }
        }

        if ($identificador !== null && $identificador !== '' && is_scalar($identificador)) {
            // Resolver ID de estudiante
            $stmtFindId->execute([':val' => $identificador]);
            $est = $stmtFindId->fetch(PDO::FETCH_ASSOC);

            if ($est) {
                $realStudentId = $est['id'];
                $stmtInsert->execute([
                    ':estudiante_id' => $realStudentId,
                    ':fecha'         => $fecha,
                    ':estado'        => $estado,
                    ':observacion'   => $observacion
                ]);
                $insertados++;
            }
        }
    }

    $pdo->commit();

    if ($insertados > 0) {
        echo json_encode([
            "success" => true, 
            "message" => "Asistencia guardada con éxito ($insertados registros procesados)."
        ]);
    } else {
        echo json_encode([
            "success" => false, 
            "message" => "No se pudieron asociar los IDs recibidos con ningún estudiante en la BD."
        ]);
    }

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(200);
    echo json_encode(["success" => false, "message" => "Error al guardar asistencia: " . $e->getMessage()]);
}
?>