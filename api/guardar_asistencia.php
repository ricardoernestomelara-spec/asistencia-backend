<?php
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
    if (file_exists(__DIR__ . '/conexion.php')) {
        require_once __DIR__ . '/conexion.php';
    } elseif (file_exists(__DIR__ . '/../conexion.php')) {
        require_once __DIR__ . '/../conexion.php';
    } else {
        echo json_encode(["success" => false, "message" => "No se encontró el archivo conexion.php"]);
        exit();
    }

    if (!isset($pdo) && isset($conn)) {
        $pdo = $conn;
    }

    if (!isset($pdo) || !$pdo) {
        throw new Exception("Sin conexión a la base de datos.");
    }

    $rawInput = file_get_contents("php://input");
    $data = json_decode($rawInput, true);

    if (!$data) {
        echo json_encode(["success" => false, "message" => "No se recibieron datos JSON válidos."]);
        exit();
    }

    $fecha = $data['fecha'] ?? date('Y-m-d');
    $seccion_nombre = $data['seccion'] ?? $data['seccion_nombre'] ?? '';
    
    $items = $data['detalles'] ?? $data['asistencias'] ?? $data['alumnos'] ?? $data['estudiantes'] ?? $data['datos'] ?? $data;

    if (!is_array($items) || empty($items)) {
        echo json_encode(["success" => false, "message" => "No hay datos de asistencia para guardar"]);
        exit();
    }

    // Determinar nombre de la tabla
    $nombreTabla = "asistencia";
    try {
        $pdo->query("SELECT 1 FROM asistencia LIMIT 1");
    } catch (Throwable $t) {
        $nombreTabla = "asistencias";
    }

    // Preparar guardado con actualización si ya existe el registro para ese alumno y fecha
    $stmtInsertAsis = $pdo->prepare("
        INSERT INTO {$nombreTabla} (estudiante_id, fecha, estado, observacion, inasistencia_por) 
        VALUES (:estudiante_id, :fecha, :estado, :observacion, :inasistencia_por)
        ON DUPLICATE KEY UPDATE 
            estado = VALUES(estado),
            observacion = VALUES(observacion),
            inasistencia_por = VALUES(inasistencia_por)
    ");

    $stmtFindEst = $pdo->prepare("SELECT id FROM estudiantes WHERE id = :id_val OR nie = :nie_val LIMIT 1");

    $insertados = 0;

    foreach ($items as $index => $val) {
        if (!is_array($val)) continue;

        $idVal = $val['id'] ?? $val['estudiante_id'] ?? $val['id_estudiante'] ?? null;
        $nieVal = trim((string)($val['nie'] ?? $val['NIE'] ?? ''));
        $estado = $val['estado'] ?? $val['ESTADO'] ?? $val['asistencia'] ?? 'Asistió';
        
        $observacion = $val['observacion'] ?? $val['OBSERVACION'] ?? null;
        $inasistencia_por = $val['inasistencia_por'] ?? $val['motivo'] ?? null;

        $realStudentId = null;

        // Buscar el ID real del estudiante
        if ($idVal) {
            $realStudentId = $idVal;
        } else if ($nieVal !== '') {
            try {
                $stmtFindEst->execute([':id_val' => 0, ':nie_val' => $nieVal]);
                $est = $stmtFindEst->fetch(PDO::FETCH_ASSOC);
                if ($est) $realStudentId = $est['id'];
            } catch (Throwable $t) {}
        }

        // Registrar o actualizar
        if ($realStudentId) {
            try {
                $stmtInsertAsis->execute([
                    ':estudiante_id'    => $realStudentId,
                    ':fecha'            => $fecha,
                    ':estado'           => $estado,
                    ':observacion'      => $observacion,
                    ':inasistencia_por' => $inasistencia_por
                ]);
                $insertados++;
            } catch (Throwable $t) {}
        }
    }

    echo json_encode([
        "success" => true, 
        "message" => "Asistencia guardada correctamente ($insertados registros procesados)."
    ]);

} catch (Throwable $e) {
    http_response_code(200);
    echo json_encode(["success" => false, "message" => "Error al guardar asistencia: " . $e->getMessage()]);
}
?>