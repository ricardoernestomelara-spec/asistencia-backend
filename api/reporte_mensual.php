<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../conexion.php';

try {
    if (!isset($pdo) && isset($conn)) {
        $pdo = $conn;
    }

    // El frontend envía seccion_id y asignatura_id (numéricos)
    $seccionId = $_GET['seccion_id'] ?? $_GET['seccion'] ?? null;
    $asignaturaId = $_GET['asignatura_id'] ?? $_GET['asignatura'] ?? null;
    $anio = $_GET['anio'] ?? date('Y');
    $mesParam = $_GET['mes'] ?? date('m');

    if (empty($seccionId)) {
        echo json_encode(['success' => false, 'message' => 'La sección es requerida.']);
        exit;
    }

    // Convertir el mes a número en caso de que venga como texto o '09'
    $mesesMap = [
        'enero' => 1, 'febrero' => 2, 'marzo' => 3, 'abril' => 4,
        'mayo' => 5, 'junio' => 6, 'julio' => 7, 'agosto' => 8,
        'septiembre' => 9, 'octubre' => 10, 'noviembre' => 11, 'diciembre' => 12
    ];

    $mesNum = is_numeric($mesParam) ? (int)$mesParam : ($mesesMap[mb_strtolower(trim($mesParam))] ?? (int)date('m'));

    // Consulta con LEFT JOIN usando seccion_id para traer a TODOS los estudiantes de esa sección
    $sql = "SELECT 
                e.id AS estudiante_id,
                e.nie,
                CONCAT(e.apellidos, ', ', e.nombres) AS nombre_completo,
                COUNT(CASE WHEN LOWER(a.estado) = 'asistió' OR LOWER(a.estado) = 'asistio' THEN 1 END) AS asistencias,
                COUNT(CASE WHEN LOWER(a.estado) = 'faltó' OR LOWER(a.estado) = 'falto' THEN 1 END) AS faltas,
                COUNT(CASE WHEN LOWER(a.estado) = 'permiso' THEN 1 END) AS permisos,
                COUNT(CASE WHEN LOWER(a.estado) = 'incapacidad' THEN 1 END) AS incapacidades,
                COUNT(CASE WHEN LOWER(a.estado) = 'tardía' OR LOWER(a.estado) = 'tardia' THEN 1 END) AS tardias,
                COUNT(CASE WHEN LOWER(a.estado) = 'retirado' THEN 1 END) AS retirados,
                COUNT(a.id) AS total_registros
            FROM estudiantes e
            LEFT JOIN asistencias a ON e.id = a.estudiante_id 
                AND a.seccion_id = :seccion_id
                " . ($asignaturaId ? "AND a.asignatura_id = :asignatura_id" : "") . "
                AND YEAR(a.fecha) = :anio 
                AND MONTH(a.fecha) = :mes
            WHERE e.seccion_id = :seccion_id
            GROUP BY e.id, e.nie, e.apellidos, e.nombres
            ORDER BY e.apellidos ASC, e.nombres ASC";

    $stmt = $pdo->prepare($sql);

    $params = [
        ':seccion_id' => $seccionId,
        ':anio' => $anio,
        ':mes' => $mesNum
    ];

    if ($asignaturaId) {
        $params[':asignatura_id'] = $asignaturaId;
    }

    $stmt->execute($params);
    $reporte = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $reporte
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>