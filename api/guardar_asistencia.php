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

    $fecha           = $input['fecha'] ?? date('Y-m-d');
    $seccionInput    = $input['seccion_id'] ?? $input['seccion'] ?? null;
    $asignaturaInput = $input['asignatura_id'] ?? $input['asignatura'] ?? null;
    $periodo         = $input['periodo'] ?? '1° Período';
    $asistencias     = $input['asistencias'] ?? [];

    if (!$seccionInput || empty($asistencias)) {
        echo json_encode(['success' => false, 'message' => 'Faltan datos de sección o la lista de asistencias.']);
        exit;
    }

    // 1. Obtener nombre exacto de Asignatura
    $asignaturaNombre = null;
    if (is_numeric($asignaturaInput)) {
        $stmtAsig = $pdo->prepare("SELECT nombre FROM asignaturas WHERE id = :id LIMIT 1");
        $stmtAsig->execute([':id' => $asignaturaInput]);
        $asig = $stmtAsig->fetch(PDO::FETCH_ASSOC);
        if ($asig) {
            $asignaturaNombre = trim($asig['nombre']);
        }
    } else {
        $asignaturaNombre = trim($asignaturaInput);
    }

    if (!$asignaturaNombre) {
        $asignaturaNombre = trim($asignaturaInput);
    }

    $pdo->beginTransaction();

    // 2. Eliminar registros previos de esa fecha, asignatura y período para evitar duplicados/bloqueos
    $stmtDel = $pdo->prepare("
        DELETE FROM asistencias 
        WHERE fecha = :fecha 
          AND TRIM(asignatura) = :asignatura 
          AND TRIM(periodo) = :periodo
    ");
    $stmtDel->execute([
        ':fecha'      => $fecha,
        ':asignatura' => $asignaturaNombre,
        ':periodo'    => trim($periodo)
    ]);

    // 3. Insertar registros frescos para la materia y período indicados
    $stmtIns = $pdo->prepare("
        INSERT INTO asistencias (estudiante_id, fecha, asignatura, periodo, estado, inasistencia_por, observacion)
        VALUES (:estudiante_id, :fecha, :asignatura, :periodo, :estado, :inasistencia_por, :observacion)
    ");

    foreach ($asistencias as $item) {
        $estudianteId = $item['estudiante_id'] ?? $item['id_estudiante'] ?? $item['id'] ?? null;
        if (!$estudianteId) continue;

        $estado = $item['estado'] ?? $item['asistencia'] ?? 'Asistió';
        $motivo = $item['inasistencia_por'] ?? $item['motivo'] ?? null;
        $obs    = $item['observacion'] ?? null;

        $stmtIns->execute([
            ':estudiante_id'    => $estudianteId,
            ':fecha'            => $fecha,
            ':asignatura'       => $asignaturaNombre,
            ':periodo'          => trim($periodo),
            ':estado'           => $estado,
            ':inasistencia_por' => $motivo,
            ':observacion'      => $obs
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