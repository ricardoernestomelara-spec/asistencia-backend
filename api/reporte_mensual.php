<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../conexion.php';

$seccion_id = $_GET['seccion_id'] ?? null;
$asignatura_id = $_GET['asignatura_id'] ?? null;
$mes = $_GET['mes'] ?? date('m');
$anio = $_GET['anio'] ?? date('Y');

if (!$seccion_id) {
    echo json_encode(["success" => false, "message" => "Sección requerida"]);
    exit();
}

try {
    // Si la sección viene con nombre (ej. "1° A Software"), obtenemos su ID numérico
    $id_sec = $seccion_id;
    if (!is_numeric($seccion_id)) {
        $stmtSec = $pdo->prepare("SELECT id FROM secciones WHERE nombre = :nom LIMIT 1");
        $stmtSec->execute([':nom' => $seccion_id]);
        $rowSec = $stmtSec->fetch(PDO::FETCH_ASSOC);
        if ($rowSec) {
            $id_sec = $rowSec['id'];
        }
    }

    // Consulta adaptada para validar tanto textos completos como letras cortas (A, F, P)
    $sql = "
        SELECT 
            e.id AS estudiante_id,
            e.nie,
            COALESCE(e.nombre, CONCAT(COALESCE(e.apellidos, ''), ' ', COALESCE(e.nombres, ''))) AS estudiante,
            SUM(CASE WHEN a.estado IN ('Asistió', 'A', 'presente', 'P') THEN 1 ELSE 0 END) AS asistencias,
            SUM(CASE WHEN a.estado IN ('Faltó', 'F', 'ausente', 'Retirado') THEN 1 ELSE 0 END) AS inasistencias,
            SUM(CASE WHEN a.estado IN ('Permiso', 'P', 'Incapacidad', 'Tardía', 'Justificado') THEN 1 ELSE 0 END) AS permisos
        FROM estudiantes e
        LEFT JOIN asistencia a ON a.estudiante_id = e.id 
            AND MONTH(a.fecha) = :mes 
            AND YEAR(a.fecha) = :anio
            " . (!empty($asignatura_id) ? " AND (a.asignatura_id = :asig OR :asig = '')" : "") . "
        WHERE e.seccion_id = :id_sec OR e.seccion_id = :nom_sec
        GROUP BY e.id, e.nie, estudiante
        ORDER BY estudiante ASC
    ";
    
    $stmt = $pdo->prepare($sql);
    
    $params = [
        ':id_sec' => $id_sec,
        ':nom_sec' => $seccion_id,
        ':mes' => intval($mes), 
        ':anio' => intval($anio)
    ];

    if (!empty($asignatura_id)) {
        $params[':asig'] = $asignatura_id;
    }

    $stmt->execute($params);
    $reporte = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true, 
        "total_estudiantes" => count($reporte),
        "reporte" => $reporte
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>