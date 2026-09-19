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

// Capturar parámetros
$seccion_id = $_GET['seccion_id'] ?? null;
$seccion_nombre = $_GET['seccion'] ?? null;
$asignatura = $_GET['asignatura'] ?? null;

// Normalizar la captura de fecha (prioriza si viene por GET en cualquier formato)
$fecha = $_GET['fecha'] ?? $_GET['fecha_asistencia'] ?? $_GET['fecha_registro'] ?? date('Y-m-d');

if (!$seccion_id && !$seccion_nombre) {
    echo json_encode(["success" => false, "message" => "Sección no especificada"]);
    exit();
}

try {
    // Si se envía el nombre textual de la sección (ej. "1° A Software"), buscar su ID numérico
    if (!$seccion_id && $seccion_nombre) {
        $stmtSec = $pdo->prepare("SELECT id FROM secciones WHERE nombre = :nombre LIMIT 1");
        $stmtSec->execute([':nombre' => $seccion_nombre]);
        $secResult = $stmtSec->fetch(PDO::FETCH_ASSOC);
        
        if ($secResult) {
            $seccion_id = $secResult['id'];
        } else {
            $seccion_id = $seccion_nombre; 
        }
    }

    // Consulta SQL con coincidencia de estudiante_id y la fecha seleccionada
    $sql = "
        SELECT 
            e.id AS id,
            e.id AS estudiante_id, 
            e.nombres,
            e.apellidos,
            e.nie, 
            COALESCE(a.estado, '--') AS estado,
            COALESCE(a.estado, '--') AS asistencia
        FROM estudiantes e
        LEFT JOIN asistencia a 
            ON e.id = a.estudiante_id 
            AND DATE(a.fecha) = :fecha
        WHERE e.seccion_id = :seccion_id
        ORDER BY e.apellidos ASC, e.nombres ASC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':seccion_id' => $seccion_id, 
        ':fecha' => $fecha
    ]);
    
    $estudiantes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true, 
        "fecha_consultada" => $fecha,
        "alumnos" => $estudiantes,
        "estudiantes" => $estudiantes
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Error al consultar asistencia: " . $e->getMessage()]);
}
?>