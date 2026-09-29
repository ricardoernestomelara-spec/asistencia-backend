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

    $anio = $_GET['anio'] ?? date('Y');
    $mes = $_GET['mes'] ?? date('m');
    $seccionParam = $_GET['seccion'] ?? '';
    $asignaturaParam = $_GET['asignatura'] ?? '';

    if (empty($seccionParam)) {
        echo json_encode(['success' => false, 'message' => 'La sección es requerida.']);
        exit;
    }

    // 1. Buscar la sección tolerando diferencias de '°' y espacios
    $seccionLimpia = str_replace('°', '', $seccionParam);
    
    $stmtSec = $pdo->prepare("
        SELECT id FROM secciones 
        WHERE LOWER(REPLACE(nombre, '°', '')) LIKE LOWER(:nombre) 
        LIMIT 1
    ");
    $stmtSec->execute([':nombre' => '%' . trim($seccionLimpia) . '%']);
    $sec = $stmtSec->fetch(PDO::FETCH_ASSOC);

    if (!$sec) {
        // Si no se encuentra por nombre exacto, intentar buscar todos los estudiantes de esa sección directamente
        echo json_encode(['success' => true, 'data' => []]);
        exit;
    }

    $seccionId = $sec['id'];

    // 2. Consulta de estudiantes cruzada con asistencia mediante LEFT JOIN
    $sql = "SELECT 
                e.id AS estudiante_id,
                e.nie,
                CONCAT(e.apellidos, ', ', e.nombres) AS nombre_completo,
                COUNT(CASE WHEN a.estado = 'Asistió' THEN 1 END) AS asistencias,
                COUNT(CASE WHEN a.estado = 'Faltó' THEN 1 END) AS faltas,
                COUNT(CASE WHEN a.estado = 'Permiso' THEN 1 END) AS permisos,
                COUNT(CASE WHEN a.estado = 'Incapacidad' THEN 1 END) AS incapacidades,
                COUNT(CASE WHEN a.estado = 'Tardía' THEN 1 END) AS tardias,
                COUNT(CASE WHEN a.estado = 'Retirado' THEN 1 END) AS retirados,
                COUNT(a.id) AS total_registros
            FROM estudiantes e
            LEFT JOIN asistencias a ON e.id = a.estudiante_id 
                AND a.seccion_id = :seccion_id
                AND YEAR(a.fecha) = :anio 
                AND MONTH(a.fecha) = :mes
            WHERE e.seccion_id = :seccion_id
            GROUP BY e.id, e.nie, e.apellidos, e.nombres
            ORDER BY e.apellidos ASC, e.nombres ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':seccion_id' => $seccionId,
        ':anio' => $anio,
        ':mes' => $mes
    ]);

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