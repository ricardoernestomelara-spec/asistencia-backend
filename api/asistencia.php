<?php
// Permitir solicitudes CORS desde el Frontend
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../conexion.php';

// Leer parámetros enviados por TablaAsistencia.jsx
$seccion = $_GET['seccion'] ?? '';
$asignatura = $_GET['asignatura'] ?? '';
$periodo = $_GET['periodo'] ?? '1';
$fecha = $_GET['fecha'] ?? date('Y-m-d');

if (empty($seccion)) {
    echo json_encode([
        'success' => false,
        'message' => 'El parámetro sección es requerido.',
        'alumnos' => []
    ]);
    exit;
}

try {
    if (!isset($pdo) && isset($conn)) {
        $pdo = $conn;
    }

    // Consulta para obtener la lista de estudiantes según la sección solicitada
    $query = "
        SELECT 
            MIN(e.id) AS id,
            MIN(e.id) AS estudiante_id,
            e.nie,
            e.apellidos,
            e.nombres,
            CONCAT(e.apellidos, ' ', e.nombres) AS nombre_completo,
            s.nombre AS seccion
        FROM estudiantes e
        INNER JOIN secciones s ON e.seccion_id = s.id
        WHERE TRIM(s.nombre) = TRIM(:seccion)
          AND e.nie NOT LIKE 'TEMP-%'
        GROUP BY e.nie, e.apellidos, e.nombres, s.nombre
        ORDER BY e.apellidos ASC, e.nombres ASC
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute([':seccion' => $seccion]);
    $estudiantes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Consulta adicional para cruzar si ya existe asistencia registrada para la fecha
    $asistenciaQuery = "
        SELECT estudiante_id, estado 
        FROM asistencias 
        WHERE fecha = :fecha 
          AND seccion = :seccion
    ";
    
    $asistenciaMapa = [];
    try {
        $stmtAsig = $pdo->prepare($asistenciaQuery);
        $stmtAsig->execute([':fecha' => $fecha, ':seccion' => $seccion]);
        while ($row = $stmtAsig->fetch(PDO::FETCH_ASSOC)) {
            $asistenciaMapa[$row['estudiante_id']] = $row['estado'];
        }
    } catch (Exception $ex) {
        // En caso de que la tabla de asistencias no exista aún o la consulta falle
    }

    // Unificar estado de asistencia por estudiante
    foreach ($estudiantes as &$est) {
        $est['asistencia'] = $asistenciaMapa[$est['id']] ?? 'Asistió';
        $est['estado'] = $est['asistencia'];
    }

    // Retorno estructurado para el componente React TablaAsistencia.jsx
    echo json_encode([
        'success' => true,
        'alumnos' => $estudiantes,
        'estudiantes' => $estudiantes
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'alumnos' => []
    ]);
}
?>