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

    $seccion_nombre = $_GET['seccion'] ?? $_GET['seccion_nombre'] ?? '1° A Software';
    $fecha = $_GET['fecha'] ?? date('Y-m-d');

    // Obtener ID de la sección
    $stmtSec = $pdo->prepare("SELECT id FROM secciones WHERE nombre = :nombre LIMIT 1");
    $stmtSec->execute([':nombre' => $seccion_nombre]);
    $sec = $stmtSec->fetch(PDO::FETCH_ASSOC);

    if (!$sec) {
        echo json_encode([]);
        exit();
    }

    $seccion_id = $sec['id'];

    // Consultar estudiantes y su asistencia en la fecha dada
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

    $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formatear respuesta asegurando compatibilidad con React
    $alumnos = array_map(function($row) {
        return [
            'id'            => (int)$row['id'],
            'estudiante_id' => (int)$row['estudiante_id'],
            'nie'           => $row['nie'] ?? '',
            'apellidos'     => $row['apellidos'] ?? '',
            'nombres'       => $row['nombres'] ?? '',
            'estado'        => $row['estado'] ?? null,
            'observacion'   => $row['observacion'] ?? null,
            'asistencia'    => $row['estado'] ?? null
        ];
    }, $resultado);

    echo json_encode($alumnos);

} catch (Throwable $e) {
    http_response_code(200);
    echo json_encode(["error" => $e->getMessage()]);
}
?>