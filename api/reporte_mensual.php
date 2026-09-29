<?php
// Desactivar despliegue de errores en salida HTML para garantizar un JSON válido
error_reporting(0);
ini_set('display_errors', 0);

// Cabeceras CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    require_once __DIR__ . '/../conexion.php';

    if (!isset($pdo) && isset($conn)) {
        $pdo = $conn;
    }

    if (!isset($pdo) || !$pdo) {
        throw new Exception("Error de conexión a la base de datos.");
    }

    // Recibir parámetros del Frontend
    $seccionParam    = $_GET['seccion_id'] ?? $_GET['seccion'] ?? null;
    $asignaturaParam = $_GET['asignatura_id'] ?? $_GET['asignatura'] ?? null;
    $anio            = (int)($_GET['anio'] ?? date('Y'));
    $mesParam        = $_GET['mes'] ?? date('m');

    if (empty($seccionParam)) {
        echo json_encode(['success' => false, 'message' => 'La sección es requerida.', 'reporte' => [], 'data' => []]);
        exit;
    }

    // 1. Obtener ID y Nombre de la Sección
    $seccionId = null;
    $seccionNombre = trim($seccionParam);

    if (is_numeric($seccionParam)) {
        $seccionId = (int)$seccionParam;
        $stmtSec = $pdo->prepare("SELECT nombre FROM secciones WHERE id = :id LIMIT 1");
        $stmtSec->execute([':id' => $seccionId]);
        $fetched = $stmtSec->fetchColumn();
        if ($fetched) {
            $seccionNombre = trim($fetched);
        }
    } else {
        $stmtSec = $pdo->prepare("SELECT id FROM secciones WHERE LOWER(TRIM(nombre)) = LOWER(TRIM(:nombre)) LIMIT 1");
        $stmtSec->execute([':nombre' => $seccionParam]);
        $fetched = $stmtSec->fetchColumn();
        if ($fetched) {
            $seccionId = (int)$fetched;
        }
    }

    // 2. Normalizar mes (1-12) y formatear a dos dígitos (ej. "09")
    $mesesMap = [
        'enero' => 1, 'febrero' => 2, 'marzo' => 3, 'abril' => 4,
        'mayo' => 5, 'junio' => 6, 'julio' => 7, 'agosto' => 8,
        'septiembre' => 9, 'octubre' => 10, 'noviembre' => 11, 'diciembre' => 12
    ];
    
    $mesLower = strtolower(trim((string)$mesParam));
    $mesNum = is_numeric($mesParam) ? (int)$mesParam : ($mesesMap[$mesLower] ?? (int)date('m'));
    $mesPadded = str_pad($mesNum, 2, '0', STR_PAD_LEFT);

    // 3. Preparación de parámetros y filtro de sección
    $params = [
        ':anio'          => (string)$anio,
        ':mes_num'       => $mesNum,
        ':filtro_dash'   => "{$anio}-{$mesPadded}",
        ':filtro_slash'  => "/{$mesPadded}/{$anio}"
    ];

    if (!empty($seccionId)) {
        $params[':seccion_id'] = $seccionId;
        $whereSeccionEst = "e.seccion_id = :seccion_id";
    } else {
        $params[':seccion_nombre'] = $seccionNombre;
        $whereSeccionEst = "e.seccion_id IN (SELECT id FROM secciones WHERE LOWER(TRIM(nombre)) = LOWER(TRIM(:seccion_nombre)))";
    }

    // 4. Filtro ultra flexible de asignatura
    $whereAsignatura = "";
    if (!empty($asignaturaParam) && strtolower(trim($asignaturaParam)) !== 'todas') {
        $whereAsignatura = " AND (
            LOWER(TRIM(a.asignatura)) = LOWER(TRIM(:asig_raw))
            OR a.asignatura_id = :asig_raw
            OR LOWER(TRIM(a.asignatura)) LIKE LOWER(CONCAT('%', :asig_raw, '%'))
        )";
        $params[':asig_raw'] = trim($asignaturaParam);
    }

    // 5. Consulta SQL con compatibilidad de fechas e imprecisiones
    $sql = "SELECT 
                e.id AS estudiante_id,
                e.nie,
                CONCAT(e.apellidos, ', ', e.nombres) AS estudiante,
                e.apellidos,
                e.nombres,
                COUNT(CASE WHEN LOWER(TRIM(a.estado)) IN ('asistió', 'asistio', 'presente') THEN 1 END) AS asistencias,
                COUNT(CASE WHEN LOWER(TRIM(a.estado)) IN ('faltó', 'falto', 'inasistencia', 'ausente') THEN 1 END) AS inasistencias,
                COUNT(CASE WHEN LOWER(TRIM(a.estado)) IN ('permiso', 'incapacidad') THEN 1 END) AS permisos,
                COUNT(CASE WHEN LOWER(TRIM(a.estado)) IN ('tardía', 'tardia') THEN 1 END) AS tardias,
                COUNT(a.id) AS total_registros
            FROM estudiantes e
            LEFT JOIN asistencia a ON e.id = a.estudiante_id 
                AND (
                    (YEAR(a.fecha) = :anio AND MONTH(a.fecha) = :mes_num)
                    OR a.fecha LIKE CONCAT(:filtro_dash, '%')
                    OR a.fecha LIKE CONCAT('%', :filtro_slash, '%')
                )
                {$whereAsignatura}
            WHERE {$whereSeccionEst}
            GROUP BY e.id, e.nie, e.apellidos, e.nombres
            ORDER BY e.apellidos ASC, e.nombres ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $reporte = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'reporte' => $reporte,
        'data'    => $reporte
    ]);

} catch (Throwable $e) {
    http_response_code(200);
    echo json_encode([
        'success' => false,
        'message' => 'Error backend: ' . $e->getMessage(),
        'reporte' => [],
        'data'    => []
    ]);
}
?>