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

// Acepta tanto seccion_id como el nombre de seccion
$seccion_id = $_GET['seccion_id'] ?? null;
$seccion_nombre = $_GET['seccion'] ?? null;
$fecha = $_GET['fecha'] ?? $_GET['fecha_asistencia'] ?? date('Y-m-d');

if (!$seccion_id && !$seccion_nombre) {
    echo json_encode(["success" => false, "message" => "Seccion no especificada"]);
    exit();
}

try {
    // Si viene el nombre de la sección (ej. "1° A Software"), buscamos primero su ID
    if (!$seccion_id && $seccion_nombre) {
        $stmtSec = $pdo->prepare("SELECT id FROM secciones WHERE nombre = :nombre LIMIT 1");
        $stmtSec->execute([':nombre' => $seccion_nombre]);
        $secResult = $stmtSec->fetch(PDO::FETCH_ASSOC);
        
        if ($secResult) {
            $seccion_id = $secResult['id'];
        } else {
            // Si la tabla secciones no existe o no coincide, intentamos filtrar directo si estudiantes tiene el texto
            $seccion_id = $seccion_nombre; 
        }
    }

    // Consulta con la estructura REAL de tus tablas: estudiantes y asistencia
    $stmt = $pdo->prepare("
        SELECT 
            e.id AS id,
            e.id AS estudiante_id, 
            e.nombre AS nombres,
            '' AS apellidos,
            e.nie, 
            COALESCE(a.estado, '--') AS estado,
            COALESCE(a.estado, '--') AS asistencia
        FROM estudiantes e
        LEFT JOIN asistencia a ON e.id = a.estudiante_id AND a.fecha = :fecha
        WHERE e.seccion_id = :seccion_id
        ORDER BY e.nombre ASC
    ");
    
    $stmt->execute([':seccion_id' => $seccion_id, ':fecha' => $fecha]);
    $estudiantes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Retornamos 'alumnos' y 'estudiantes' para que sirva con cualquier versión del frontend
    echo json_encode([
        "success" => true, 
        "alumnos" => $estudiantes,
        "estudiantes" => $estudiantes
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Error al consultar asistencia: " . $e->getMessage()]);
}
?>