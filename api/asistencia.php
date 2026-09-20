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

    // 1. Obtener la lista de estudiantes según la sección solicitada
    $query = "
        SELECT 
            e.id AS id,
            e.id AS estudiante_id,
            e.nie,
            e.apellidos,
            e.nombres,
            CONCAT(e.apellidos, ' ', e.nombres) AS nombre_completo,
            s.nombre AS seccion
        FROM estudiantes e
        INNER JOIN secciones s ON e.seccion_id = s.id
        WHERE TRIM(s.nombre) = TRIM(:seccion)
          AND e.nie NOT LIKE 'TEMP-%'
        ORDER BY e.apellidos ASC, e.nombres ASC
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute([':seccion' => $seccion]);
    $estudiantes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Detectar dinámicamente si la tabla se llama 'asistencia' o 'asistencias'
    $nombreTabla = "asistencia";
    try {
        $pdo->query("SELECT 1 FROM asistencia LIMIT 1");
    } catch (Throwable $t) {
        $nombreTabla = "asistencias";
    }

    // 2. Consultar la asistencia registrada para esos alumnos en la fecha dada
    $asistenciaMapa = [];
    if (!empty($estudiantes)) {
        $ids = array_column($estudiantes, 'id');
        $inQuery = implode(',', array_fill(0, count($ids), '?'));

        $asistenciaQuery = "
            SELECT estudiante_id, estado 
            FROM {$nombreTabla} 
            WHERE fecha = ? AND estudiante_id IN ($inQuery)
        ";
        
        try {
            $stmtAsig = $pdo->prepare($asistenciaQuery);
            $stmtAsig->execute(array_merge([$fecha], $ids));
            while ($row = $stmtAsig->fetch(PDO::FETCH_ASSOC)) {
                $asistenciaMapa[$row['estudiante_id']] = $row['estado'];
            }
        } catch (Exception $ex) {
            // Si hay error en la tabla, continuará con el valor predeterminado
        }
    }

    // 3. Mapear los datos al objeto final
    foreach ($estudiantes as &$est) {
        $estadoReal = $asistenciaMapa[$est['id']] ?? 'Asistió';
        $est['asistencia'] = $estadoReal;
        $est['estado'] = $estadoReal;
    }

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