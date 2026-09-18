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
    echo json_encode(["success" => false, "message" => "Seccion no requerida"]);
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT e.nombre AS estudiante, a.fecha, a.estado 
        FROM asistencia a
        INNER JOIN estudiantes e ON a.estudiante_id = e.id
        WHERE e.seccion_id = :seccion_id 
          AND MONTH(a.fecha) = :mes 
          AND YEAR(a.fecha) = :anio
        ORDER BY e.nombre, a.fecha ASC
    ");
    $stmt->execute([':seccion_id' => $seccion_id, ':mes' => $mes, ':anio' => $anio]);

    echo json_encode(["success" => true, "reporte" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>