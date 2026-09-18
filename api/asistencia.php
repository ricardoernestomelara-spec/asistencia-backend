<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../conexion.php';

$seccion_id = $_GET['seccion_id'] ?? null;
$fecha = $_GET['fecha'] ?? date('Y-m-d');

if (!$seccion_id) {
    echo json_encode(["success" => false, "message" => "Seccion no especificada"]);
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT e.id AS estudiante_id, e.nombre, e.nie, COALESCE(a.estado, 'presente') AS estado
        FROM estudiantes e
        LEFT JOIN asistencia a ON e.id = a.estudiante_id AND a.fecha = :fecha
        WHERE e.seccion_id = :seccion_id
        ORDER BY e.nombre ASC
    ");
    $stmt->execute([':seccion_id' => $seccion_id, ':fecha' => $fecha]);
    $estudiantes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["success" => true, "estudiantes" => $estudiantes]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Error al consultar asistencia: " . $e->getMessage()]);
}
?>