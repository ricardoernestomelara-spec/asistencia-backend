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
$mes = $_GET['mes'] ?? date('m');
$anio = $_GET['anio'] ?? date('Y');

if (!$seccion_id) {
    echo json_encode(["success" => false, "message" => "Sección requerida"]);
    exit();
}

try {
    // Consulta agrupada por estudiante para el mes y año solicitados
    $stmt = $pdo->prepare("
        SELECT 
            e.id AS estudiante_id,
            e.nie,
            e.nombre AS estudiante,
            SUM(CASE WHEN a.estado = 'Asistió' THEN 1 ELSE 0 END) AS asistencias,
            SUM(CASE WHEN a.estado IN ('Faltó', 'Retirado') THEN 1 ELSE 0 END) AS inasistencias,
            SUM(CASE WHEN a.estado IN ('Permiso', 'Incapacidad', 'Tardía') THEN 1 ELSE 0 END) AS permisos
        FROM estudiantes e
        LEFT JOIN asistencia a ON a.estudiante_id = e.id 
            AND MONTH(a.fecha) = :mes 
            AND YEAR(a.fecha) = :anio
        WHERE e.seccion_id = :seccion_id
        GROUP BY e.id, e.nie, e.nombre
        ORDER BY e.nombre ASC
    ");
    
    $stmt->execute([
        ':seccion_id' => $seccion_id, 
        ':mes' => $mes, 
        ':anio' => $anio
    ]);

    echo json_encode([
        "success" => true, 
        "reporte" => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>