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

    $fecha         = $input['fecha'] ?? date('Y-m-d');
    $seccionInput  = $input['seccion_id'] ?? $input['seccion'] ?? null;
    $asignaturaInput = $input['asignatura_id'] ?? $input['asignatura'] ?? null;
    $periodo       = $input['periodo'] ?? '1° Período';
    $asistencias   = $input['asistencias'] ?? [];

    if (!$seccionInput || empty($asistencias)) {
        echo json_encode(['success' => false, 'message' => 'Faltan datos de sección o la lista de asistencias.']);
        exit;
    }

    // 1. Resolver ID de sección
    $seccionId = null;
    if (is_numeric($seccionInput)) {
        $seccionId = (int)$seccionInput;
    } else {
        $stmtSec = $pdo->prepare("SELECT id FROM secciones WHERE TRIM(nombre) = TRIM(:nombre) LIMIT 1");
        $stmtSec->execute([':nombre' => $seccionInput]);
        $sec = $stmtSec->fetch(PDO::FETCH_ASSOC);
        if ($sec) {
            $seccionId = (int)$sec['id'];
        }
    }

    // 2. Resolver Nombre e ID de Asignatura
    $asignaturaNombre = null;
    $asignaturaId = null;

    if (is_numeric($asignaturaInput)) {
        $asignaturaId = (int)$asignaturaInput;
        $stmtAsig = $pdo->prepare("SELECT nombre FROM asignaturas WHERE id = :id LIMIT 1");
        $stmtAsig->execute([':id' => $asignaturaId]);
        $asig = $stmtAsig->fetch(PDO::FETCH_ASSOC);
        if ($asig) {
            $asignaturaNombre = $asig['nombre'];
        }
    } else {
        $asignaturaNombre = trim($asignaturaInput);
        $stmtAsig = $pdo->prepare("SELECT id FROM asignaturas WHERE TRIM(nombre) = TRIM(:nombre) OR TRIM(codigo) = TRIM(:nombre) LIMIT 1");
        $stmtAsig->execute([':nombre' => $asignaturaNombre]);
        $asig = $stmtAsig->fetch(PDO::FETCH_ASSOC);
        if ($asig) {
            $asignaturaId = (int)$asig['id'];
        }
    }

    $pdo->beginTransaction();

    // 3. Sentencia flexible que actualiza tanto 'asignatura' (nombre) como 'asignatura_id'
    $stmt = $pdo->prepare("
        INSERT INTO asistencias (estudiante_id, seccion_id, asignatura_id, asignatura, periodo, fecha, estado, motivo, inasistencia_por, observacion)
        VALUES (:estudiante_id, :seccion_id, :asignatura_id, :asignatura, :periodo, :fecha, :estado, :motivo, :inasistencia_por, :observacion)
        ON DUPLICATE KEY UPDATE 
            estado = VALUES(estado),
            motivo = VALUES(motivo),
            inasistencia_por = VALUES(inasistencia_por),
            observacion = VALUES(observacion),
            asignatura = VALUES(asignatura),
            periodo = VALUES(periodo)
    ");

    foreach ($asistencias as $item) {
        $stmt->execute([
            ':estudiante_id'    => $item['estudiante_id'] ?? $item['id_estudiante'] ?? $item['id'],
            ':seccion_id'       => $seccionId,
            ':asignatura_id'    => $asignaturaId,
            ':asignatura'       => $asignaturaNombre,
            ':periodo'          => $periodo,
            ':fecha'            => $fecha,
            ':estado'           => $item['estado'] ?? $item['asistencia'] ?? 'Asistió',
            ':motivo'           => $item['motivo'] ?? null,
            ':inasistencia_por' => $item['inasistencia_por'] ?? $item['motivo'] ?? null,
            ':observacion'      => $item['observacion'] ?? null
        ]);
    }

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Asistencia guardada correctamente.']);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(200);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>