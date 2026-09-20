<?php
// Permitir solicitudes desde el Frontend
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../conexion.php';

$seccion = $_GET['seccion'] ?? '';
$fecha = $_GET['fecha'] ?? date('Y-m-d');

if (empty($seccion)) {
    echo json_encode([]);
    exit;
}

try {
    if (!isset($pdo) && isset($conn)) {
        $pdo = $conn;
    }

    // Traer TODOS los estudiantes de la sección y cruzar su estado de asistencia para la fecha seleccionada
    $query = "
        SELECT 
            e.id AS estudiante_id,
            e.nie,
            e.apellidos,
            e.nombres,
            s.nombre AS seccion,
            COALESCE(a.estado, 'Asistió') AS estado_asistencia
        FROM estudiantes e
        INNER JOIN secciones s ON e.seccion_id = s.id
        LEFT JOIN asistencia a 
            ON e.id = a.estudiante_id 
            AND DATE(a.fecha) = :fecha
        WHERE s.nombre = :seccion
          AND e.nie NOT LIKE 'TEMP-%'
        GROUP BY e.id, e.nie, e.apellidos, e.nombres, s.nombre, a.estado
        ORDER BY e.apellidos ASC, e.nombres ASC
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute([
        ':seccion' => $seccion,
        ':fecha' => $fecha
    ]);

    $estudiantes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($estudiantes);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>