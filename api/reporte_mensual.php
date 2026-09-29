<?php
// Limpiar cabeceras
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

require_once __DIR__ . '/../conexion.php';

if (!isset($pdo) && isset($conn)) {
    $pdo =$conn;
}

// Capturar parámetros
$seccionInput    =$_GET['seccion_id'] ?? $_GET['seccion'] ?? $_GET['id_seccion'] ?? null;
$asignaturaInput =$_GET['asignatura_id'] ?? $_GET['asignatura'] ?? $_GET['id_asignatura'] ?? null;
$mes_param       =$_GET['mes'] ?? null;
$anio            =$_GET['anio'] ?? date('Y');

if (!$seccionInput \vert{}\vert{} !$mes_param) {
    echo json_encode([
        'success' => false,
        'message' => 'Faltan parámetros requeridos',
        'reporte' => []
    ]);
    exit;
}

try {
    // 1. Obtener ID de Sección si viene en texto
    $seccion_id = null;
    if (is_numeric($seccionInput)) {
        $seccion_id = (int)$seccionInput;
    } else {
        $stmtSec =$pdo->prepare("SELECT id FROM secciones WHERE TRIM(nombre) = TRIM(:nombre) LIMIT 1");
        $stmtSec->execute([':nombre' =>$seccionInput]);
        $sec =$stmtSec->fetch(PDO::FETCH_ASSOC);
        if ($sec) {
            $seccion_id = (int)$sec['id'];
        }
    }

    // 2. Obtener Nombre de Asignatura (si le pasan ID buscar su nombre/código)
    $asignatura_nombre = null;
    if (!empty($asignaturaInput)) {
        if (is_numeric($asignaturaInput)) {
            $stmtAsig =$pdo->prepare("SELECT nombre FROM asignaturas WHERE id = :id LIMIT 1");
            $stmtAsig->execute([':id' =>$asignaturaInput]);
            $asig =$stmtAsig->fetch(PDO::FETCH_ASSOC);
            if ($asig) {
                $asignatura_nombre =$asig['nombre'];
            }
        } else {
            $asignatura_nombre = trim($asignaturaInput);
        }
    }

    if (!$seccion_id) {
        echo json_encode(['success' => true, 'reporte' => []]);
        exit;
    }

    // 3. Mapear Mes
    if (is_numeric($mes_param)) {
        $mes = intval($mes_param);
    } else {
        $meses = [             'enero' => 1, 'febrero' => 2, 'marzo' => 3, 'abril' => 4,             'mayo' => 5, 'junio' => 6, 'julio' => 7, 'agosto' => 8,             'septiembre' => 9, 'octubre' => 10, 'noviembre' => 11, 'diciembre' => 12         ];$mes = $meses[mb_strtolower(trim($mes_param))] ?? date('n');
    }

    $mes_pad = str_pad($mes, 2, '0', STR_PAD_LEFT);

    // 4. Consulta Final adaptada al campo `a.asignatura`
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
            LEFT JOIN asistencias a ON a.estudiante_id = e.id
                AND (:asignatura_nombre IS NULL OR TRIM(a.asignatura) = :asignatura_nombre)
                AND (
                    (MONTH(a.fecha) = :mes AND YEAR(a.fecha) = :anio)
                    OR (a.fecha LIKE CONCAT(:anio, '-', :mes_pad, '-%'))
                    OR (a.fecha LIKE CONCAT('%/', :mes_pad, '/', :anio))
                )
            WHERE e.seccion_id = :seccion_id
            GROUP BY e.id, e.nie, e.apellidos, e.nombres
            ORDER BY e.apellidos ASC, e.nombres ASC";

    $stmt =$pdo->prepare($sql);$stmt->execute([
        ':seccion_id'        => $seccion_id,
        ':asignatura_nombre' => $asignatura_nombre,
        ':mes'               => $mes,
        ':mes_pad'           => $mes_pad,
        ':anio'              => intval($anio)
    ]);

    $reporte =$stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'reporte' => $reporte
    ]);

} catch (Throwable $e) {
    http_response_code(200);
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage(),
        'reporte' => []
    ]);
}
?>