<?php
// Cabeceras CORS
header_remove('Access-Control-Allow-Origin');
header_remove('Access-Control-Allow-Headers');
header_remove('Access-Control-Allow-Methods');

header("Access-Control-Allow-Origin: *", true);
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Origin, Accept", true);
header("Access-Control-Allow-Methods: GET, POST, OPTIONS", true);

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

    // Aceptar 'seccion', 'seccion_id' o parámetro por defecto
    $seccion_param = $_GET['seccion'] ?? $_GET['seccion_id'] ?? null;
    $fecha = $_GET['fecha'] ?? date('Y-m-d');

    // Si no viene sección especificada, tomamos la primera disponible en la BD
    if (!$seccion_param) {
        $stmtSec = $pdo->query("SELECT nombre FROM secciones LIMIT 1");
        $secRow = $stmtSec->fetch(PDO::FETCH_ASSOC);
        $seccion_param = $secRow ? $secRow['nombre'] : '';
    }

    // Consulta amplia que obtiene a los estudiantes mapeando apellidos y nombres
    $sql = "
        SELECT 
            e.id, 
            e.id AS estudiante_id, 
            e.nie, 
            e.apellidos, 
            e.nombres, 
            CONCAT(e.apellidos, ' ', e.nombres) AS nombre,
            COALESCE(a.estado, 'presente') AS estado
        FROM estudiantes e
        LEFT JOIN secciones s ON e.seccion_id = s.id
        LEFT JOIN asistencia a ON e.id = a.estudiante_id AND a.fecha = :fecha
        WHERE s.nombre = :seccion_texto 
           OR e.seccion_id = :seccion_id 
           OR :seccion_vacia = ''
        ORDER BY e.apellidos ASC, e.nombres ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':seccion_texto' => $seccion_param,
        ':seccion_id'    => is_numeric($seccion_param) ? (int)$seccion_param : 0,
        ':seccion_vacia' => $seccion_param,
        ':fecha'         => $fecha
    ]);
    
    $listaEstudiantes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Respuesta JSON multi-compatible
    echo json_encode([
        "success"     => true,
        "estudiantes" => $listaEstudiantes,
        "alumnos"     => $listaEstudiantes,
        "fechas"      => [],
        "asistencias" => new stdClass()
    ]);

} catch (Throwable $e) {
    http_response_code(200);
    echo json_encode([
        "success"     => false, 
        "message"     => "Error: " . $e->getMessage(),
        "estudiantes" => [],
        "alumnos"     => []
    ]);
}
?>