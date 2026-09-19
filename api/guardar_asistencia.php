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
    require_once __DIR__ . '/../conexion.php';

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
    $seccion_nombre = $data['seccion'] ?? $data['seccion_nombre'] ?? '1° A Software';
    
    // CORRECCIÓN AQUÍ: Se añade $data['detalles'] para capturar el payload que envía React
    $items = $data['detalles'] ?? $data['asistencias'] ?? $data['alumnos'] ?? $data['estudiantes'] ?? $data['datos'] ?? $data;

    if (!is_array($items) || empty($items)) {
        echo json_encode(["success" => false, "message" => "No hay datos de asistencia para guardar"]);
        exit();
    }

    // Obtener o crear ID de sección
    $stmtSec = $pdo->prepare("SELECT id FROM secciones WHERE nombre = :nombre LIMIT 1");
    $stmtSec->execute([':nombre' => $seccion_nombre]);
    $sec = $stmtSec->fetch(PDO::FETCH_ASSOC);

    if ($sec) {
        $seccion_id = $sec['id'];
    } else {
        $stmtInsSec = $pdo->prepare("INSERT INTO secciones (nombre) VALUES (:nombre)");
        $stmtInsSec->execute([':nombre' => $seccion_nombre]);
        $seccion_id = $pdo->lastInsertId();
    }

    $stmtFindEst = $pdo->prepare("SELECT id FROM estudiantes WHERE id = :id_val OR nie = :nie_val LIMIT 1");
    
    $stmtAutoCreateEst = $pdo->prepare("
        INSERT INTO estudiantes (nie, apellidos, nombres, seccion_id) 
        VALUES (:nie, :apellidos, :nombres, :seccion_id)
    ");

    $stmtInsertAsis = $pdo->prepare("
        INSERT INTO asistencia (estudiante_id, fecha, estado, observacion) 
        VALUES (:estudiante_id, :fecha, :estado, :observacion)
        ON DUPLICATE KEY UPDATE 
            estado = VALUES(estado),
            observacion = VALUES(observacion)
    ");

    $insertados = 0;

    foreach ($items as $index => $val) {
        if (!is_array($val)) continue;

        // Búsqueda profunda de ID y NIE
        $idVal = $val['id'] ?? $val['estudiante_id'] ?? $val['id_estudiante'] ?? null;
        $nieVal = trim((string)($val['nie'] ?? $val['NIE'] ?? ''));

        $apellidos = trim((string)($val['apellidos'] ?? $val['APELLIDOS'] ?? $val['apellido'] ?? ''));
        $nombres = trim((string)($val['nombres'] ?? $val['NOMBRES'] ?? $val['nombre'] ?? ''));
        $estado = $val['estado'] ?? $val['ESTADO'] ?? $val['asistencia'] ?? 'Asistió';
        $observacion = $val['observacion'] ?? $val['inasistencia_por'] ?? $val['OBSERVACION'] ?? null;

        $realStudentId = null;

        // 1. Intentar buscar por ID o NIE existente
        try {
            $stmtFindEst->execute([
                ':id_val'  => $idVal ?? 0,
                ':nie_val' => $nieVal !== '' ? $nieVal : '---'
            ]);
            $est = $stmtFindEst->fetch(PDO::FETCH_ASSOC);
            if ($est) {
                $realStudentId = $est['id'];
            }
        } catch (Throwable $t) {}

        // 2. Si no se encontró, crear estudiante usando los nombres/apellidos recibidos
        if (!$realStudentId && ($apellidos !== '' || $nombres !== '' || $nieVal !== '')) {
            if ($nieVal === '') {
                $nieVal = sprintf("NIE-%d-%d", (int)$index + 1, time());
            }
            try {
                $stmtAutoCreateEst->execute([
                    ':nie'        => $nieVal,
                    ':apellidos'  => $apellidos !== '' ? $apellidos : 'Sin Apellido',
                    ':nombres'    => $nombres !== '' ? $nombres : 'Sin Nombre',
                    ':seccion_id' => $seccion_id
                ]);
                $realStudentId = $pdo->lastInsertId();
            } catch (Throwable $t) {}
        }

        // 3. Registrar o actualizar la asistencia
        if ($realStudentId) {
            try {
                $stmtInsertAsis->execute([
                    ':estudiante_id' => $realStudentId,
                    ':fecha'         => $fecha,
                    ':estado'        => $estado,
                    ':observacion'   => $observacion
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