<?php
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

    $seccion_nombre = trim($_GET['seccion'] ?? $_GET['seccion_nombre'] ?? '');
    $fecha = $_GET['fecha'] ?? date('Y-m-d');

    // 1. Buscar ID de la sección si se proporcionó un nombre
    $seccion_id = null;
    if ($seccion_nombre !== '') {
        $stmtSec = $pdo->prepare("SELECT id FROM secciones WHERE LOWER(TRIM(nombre)) = LOWER(TRIM(:nombre)) LIMIT 1");
        $stmtSec->execute([':nombre' => $seccion_nombre]);
        $sec = $stmtSec->fetch(PDO::FETCH_ASSOC);

        if ($sec) {
            $seccion_id = $sec['id'];
        }
    }

    // 2. Consulta de estudiantes
    if ($seccion_id) {
        $sql = "
            SELECT 
                e.id,
                e.id AS estudiante_id,
                e.nie,
                e.apellidos,
                e.nombres,
                a.estado,
                a.observacion,
                a.fecha
            FROM estudiantes e
            LEFT JOIN asistencia a 
                ON e.id = a.estudiante_id AND a.fecha = :fecha
            WHERE e.seccion_id = :seccion_id
            ORDER BY e.apellidos ASC, e.nombres ASC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':seccion_id' => $seccion_id,
            ':fecha'      => $fecha
        ]);
    } else {
        // Fallback: traer todos si no se especificó sección válida
        $sql = "
            SELECT 
                e.id,
                e.id AS estudiante_id,
                e.nie,
                e.apellidos,
                e.nombres,
                a.estado,
                a.observacion,
                a.fecha
            FROM estudiantes e
            LEFT JOIN asistencia a 
                ON e.id = a.estudiante_id AND a.fecha = :fecha
            ORDER BY e.apellidos ASC, e.nombres ASC
            LIMIT 100
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':fecha' => $fecha]);
    }

    $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Mapeo exhaustivo de nombres de atributos
    $alumnos = array_map(function($row) {
        $estadoVal = $row['estado'] ?? null;
        return [
            'id'                => (int)$row['id'],
            'estudiante_id'     => (int)$row['estudiante_id'],
            'nie'               => $row['nie'] ?? '',
            'NIE'               => $row['nie'] ?? '',
            'apellidos'         => $row['apellidos'] ?? '',
            'APELLIDOS'         => $row['apellidos'] ?? '',
            'nombres'           => $row['nombres'] ?? '',
            'NOMBRES'           => $row['nombres'] ?? '',
            'estado'            => $estadoVal,
            'ESTADO'            => $estadoVal,
            'observacion'       => $row['observacion'] ?? '',
            'OBSERVACION'       => $row['observacion'] ?? '',
            'inasistencia_por'  => $row['observacion'] ?? '',
            'asistencia'        => $estadoVal,
            'asistencia_estado' => $estadoVal
        ];
    }, $resultado);

    // Respuesta híbrida: entrega el objeto completo Y la raíz plana mediante JsonSerializable/array wrapping
    echo json_encode([
        "success"     => true,
        "data"        => $alumnos,
        "alumnos"     => $alumnos,
        "estudiantes" => $alumnos,
        "datos"       => $alumnos
    ]);

} catch (Throwable $e) {
    http_response_code(200);
    echo json_encode([
        "success" => false,
        "data"    => [],
        "alumnos" => [],
        "error"   => $e->getMessage()
    ]);
}
?>