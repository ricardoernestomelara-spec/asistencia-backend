<?php
// Limpieza y configuración estricta de cabeceras CORS
header_remove('Access-Control-Allow-Origin');
header_remove('Access-Control-Allow-Headers');
header_remove('Access-Control-Allow-Methods');

header("Access-Control-Allow-Origin: *", true);
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Origin, Accept", true);
header("Access-Control-Allow-Methods: GET, POST, OPTIONS", true);

// Manejo de la petición Preflight de CORS
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
        throw new Exception("Error interno: No hay conexión activa con la base de datos.");
    }

    // Acepta tanto 'seccion' (texto) como 'seccion_id' (numérico)
    $seccion_param = $_GET['seccion'] ?? $_GET['seccion_id'] ?? null;
    $fecha = $_GET['fecha'] ?? date('Y-m-d');

    if (!$seccion_param) {
        echo json_encode(["success" => false, "message" => "Sección no especificada"]);
        exit();
    }

    // Consulta que vincula estudiantes y secciones, concatenando nombres
    $sql = "
        SELECT 
            e.id, 
            e.id AS estudiante_id, 
            e.nie, 
            e.apellidos, 
            e.nombres, 
            CONCAT(e.apellidos, ', ', e.nombres) AS nombre,
            COALESCE(a.estado, 'Asistió') AS estado
        FROM estudiantes e
        INNER JOIN secciones s ON e.seccion_id = s.id
        LEFT JOIN asistencia a ON e.id = a.estudiante_id AND a.fecha = :fecha
        WHERE s.nombre = :seccion_texto OR e.seccion_id = :seccion_id
        ORDER BY e.apellidos ASC, e.nombres ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':seccion_texto' => $seccion_param,
        ':seccion_id'    => is_numeric($seccion_param) ? (int)$seccion_param : 0,
        ':fecha'         => $fecha
    ]);
    
    $alumnos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Consulta de fechas registradas para el encabezado del reporte/tabla
    $stmtFechas = $pdo->prepare("
        SELECT DISTINCT DATE_FORMAT(fecha, '%m/%d') AS fecha_corta 
        FROM asistencia a
        INNER JOIN estudiantes e ON a.estudiante_id = e.id
        INNER JOIN secciones s ON e.seccion_id = s.id
        WHERE s.nombre = :seccion_texto OR e.seccion_id = :seccion_id
        ORDER BY a.fecha ASC
    ");
    $stmtFechas->execute([
        ':seccion_texto' => $seccion_param,
        ':seccion_id'    => is_numeric($seccion_param) ? (int)$seccion_param : 0
    ]);
    $fechas = $stmtFechas->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        "success"     => true,
        "alumnos"     => $alumnos,
        "estudiantes" => $alumnos, // Compatibilidad con ambas propiedades en React
        "fechas"      => $fechas,
        "asistencias" => new stdClass()
    ]);

} catch (Throwable $e) {
    http_response_code(200);
    echo json_encode(["success" => false, "message" => "Error al consultar asistencia: " . $e->getMessage()]);
}
?>