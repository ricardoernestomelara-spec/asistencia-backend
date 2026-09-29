<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

// Incluir conexión a base de datos
require_once __DIR__ . '/../conexion.php';

if (!isset($pdo) && isset($conn)) {
    $pdo = $conn;
}

// Capturar parámetros enviados desde el Frontend
$seccion_id = $_GET['seccion_id'] ?? $_GET['id_seccion'] ?? null;
$asignatura_id = $_GET['asignatura_id'] ?? $_GET['id_asignatura'] ?? null;
$mes_param = $_GET['mes'] ?? null;
$anio = $_GET['anio'] ?? date('Y');

if (!$seccion_id || !$asignatura_id || !$mes_param) {
    echo json_encode([
        'success' => false,
        'message' => 'Faltan parámetros requeridos',
        'reporte' => []
    ]);
    exit;
}

// Convertir mes escrito (ej. "Septiembre") o numérico a entero (1-12)
if (is_numeric($mes_param)) {
    $mes = intval($mes_param);
} else {
    $meses = [
        'enero' => 1, 'febrero' => 2, 'marzo' => 3, 'abril' => 4,
        'mayo' => 5, 'junio' => 6, 'julio' => 7, 'agosto' => 8,
        'septiembre' => 9, 'octubre' => 10, 'noviembre' => 11, 'diciembre' => 12
    ];
    $mes = $meses[mb_strtolower(trim($mes_param))] ?? date('n');
}

$mes_pad = str_pad($mes, 2, '0', STR_PAD_LEFT);

try {
    // Consulta a la tabla correcta 'asistencias' (plural)
    $sql = "SELECT 
                e.id AS estudiante_id,
                e.nie,
                CONCAT(e.apellidos, ' ', e.nombres) AS estudiante,
                e.apellidos,
                e.nombres,
                SUM(CASE WHEN LOWER(a.estado) IN ('asistió', 'asistio', 'presente', 'tardía', 'tardia', 'tarde') THEN 1 ELSE 0 END) AS asistencias,
                SUM(CASE WHEN LOWER(a.estado) IN ('faltó', 'falto', 'inasistencia', 'ausente') THEN 1 ELSE 0 END) AS inasistencias,
                SUM(CASE WHEN LOWER(a.estado) IN ('permiso', 'incapacidad', 'justificado') THEN 1 ELSE 0 END) AS permisos
            FROM estudiantes e
            LEFT JOIN asistencias a ON (a.estudiante_id = e.id OR a.id_estudiante = e.id)
                AND (a.seccion_id = :seccion_id OR a.id_seccion = :seccion_id)
                AND (a.asignatura_id = :asignatura_id OR a.id_asignatura = :asignatura_id)
                AND (
                    (MONTH(a.fecha) = :mes AND YEAR(a.fecha) = :anio)
                    OR 
                    (a.fecha LIKE CONCAT(:anio, '-', :mes_pad, '-%'))
                    OR
                    (a.fecha LIKE CONCAT('%/', :mes_pad, '/', :anio))
                )
            WHERE (e.seccion_id = :seccion_id OR e.id_seccion = :seccion_id)
            GROUP BY e.id, e.nie, e.apellidos, e.nombres
            ORDER BY e.apellidos ASC, e.nombres ASC";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':seccion_id' => $seccion_id,
        ':asignatura_id' => $asignatura_id,
        ':mes' => $mes,
        ':mes_pad' => $mes_pad,
        ':anio' => intval($anio)
    ]);

    $reporte = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'reporte' => $reporte
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'reporte' => []
    ]);
}
?>