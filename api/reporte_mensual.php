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

    $seccionParam = $_GET['seccion_id'] ?? $_GET['seccion'] ?? null;
    $asignaturaParam = $_GET['asignatura_id'] ?? $_GET['asignatura'] ?? null;
    $anio = $_GET['anio'] ?? date('Y');
    $mesParam = $_GET['mes'] ?? date('m');

    if (empty($seccionParam)) {
        echo json_encode(['success' => false, 'message' => 'La sección es requerida.', 'reporte' => []]);
        exit;
    }

    // Resolver ID de Sección (soporta tanto ID numérico como Nombre de Sección)
    if (!is_numeric($seccionParam)) {
        $stmtSec = $pdo->prepare("SELECT id FROM secciones WHERE LOWER(TRIM(nombre)) = LOWER(TRIM(:nombre)) LIMIT 1");
        $stmtSec->execute([':nombre' => $seccionParam]);
        $seccionId = $stmtSec->fetchColumn();
    } else {
        $seccionId = $seccionParam;
    }

    // Resolver ID de Asignatura (si aplica)
    $asignaturaId = null;
    if (!empty($asignaturaParam)) {
        if (!is_numeric($asignaturaParam)) {
            $stmtAsig = $pdo->prepare("SELECT id FROM asignaturas WHERE LOWER(TRIM(nombre)) = LOWER(TRIM(:nombre)) LIMIT 1");
            $stmtAsig->execute([':nombre' => $asignaturaParam]);
            $asignaturaId = $stmtAsig->fetchColumn();
        } else {
            $asignaturaId = $asignaturaParam;
        }
    }

    if (!$seccionId) {
        echo json_encode(['success' => true, 'reporte' => [], 'data' => []]);
        exit;
    }

    // Conversión de mes
    $mesesMap = [
        'enero' => 1, 'febrero' => 2, 'marzo' => 3, 'abril' => 4,
        'mayo' => 5, 'junio' => 6, 'julio' => 7, 'agosto' => 8,
        'septiembre' => 9, 'octubre' => 10, 'noviembre' => 11, 'diciembre' => 12
    ];
    $mesNum = is_numeric($mesParam) ? (int)$mesParam : ($mesesMap[mb_strtolower(trim($mesParam))] ?? (int)date('m'));

    // Consulta con LEFT JOIN
    $sql = "SELECT 
                e.id AS estudiante_id,
                e.nie,
                CONCAT(e.apellidos, ' ', e.nombres) AS estudiante,
                e.apellidos,
                e.nombres,
                COUNT(CASE WHEN LOWER(a.estado) IN ('asistió', 'asistio', 'presente') THEN 1 END) AS asistencias,
                COUNT(CASE WHEN LOWER(a.estado) IN ('faltó', 'falto', 'inasistencia', 'ausente') THEN 1 END) AS inasistencias,
                COUNT(CASE WHEN LOWER(a.estado) IN ('permiso', 'incapacidad') THEN 1 END) AS permisos,
                COUNT(CASE WHEN LOWER(a.estado) IN ('tardía', 'tardia') THEN 1 END) AS tardias,
                COUNT(a.id) AS total_registros
            FROM estudiantes e
            LEFT JOIN asistencias a ON e.id = a.estudiante_id 
                AND a.seccion_id = :seccion_id
                " . (!empty($asignaturaId) ? "AND a.asignatura_id = :asignatura_id" : "") . "
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

    if (!empty($asignaturaId)) {
        $params[':asignatura_id'] = $asignaturaId;
    }

    $stmt->execute($params);
    $reporte = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Mantiene compatibilidad total devolviendo 'reporte' y 'data'
    echo json_encode([
        'success' => true,
        'reporte' => $reporte,
        'data' => $reporte
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage(), 'reporte' => [], 'data' => []]);
}
?>