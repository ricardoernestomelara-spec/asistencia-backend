<?php
require_once __DIR__ . '/conexion.php';

// Encabezados CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

$seccion = $_GET['seccion'] ?? '';
$asignatura_id = $_GET['asignatura_id'] ?? '';
$periodo_id = $_GET['periodo_id'] ?? '';

if (empty($seccion)) {
    echo json_encode([]);
    exit;
}

try {
    if (!isset($pdo) && isset($conn)) {
        $pdo = $conn;
    }

    // Consulta DISTINCT / GROUP BY por estudiante para garantizar filas únicas
    $query = "
        SELECT 
            e.id AS estudiante_id,
            e.nie,
            e.apellidos,
            e.nombres,
            s.nombre AS seccion,
            MAX(a.estado) AS estado_asistencia,
            MAX(a.fecha) AS fecha
        FROM estudiantes e
        INNER JOIN secciones s ON e.seccion_id = s.id
        LEFT JOIN asistencia a 
            ON e.id = a.estudiante_id 
            AND (:asignatura_id = '' OR a.asignatura_id = :asignatura_id)
            AND (:periodo_id = '' OR a.periodo_id = :periodo_id)
        WHERE s.nombre = :seccion
          AND e.nie NOT LIKE 'TEMP-%'
        GROUP BY e.id, e.nie, e.apellidos, e.nombres, s.nombre
        ORDER BY e.apellidos ASC, e.nombres ASC
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute([
        ':seccion' => $seccion,
        ':asignatura_id' => $asignatura_id,
        ':periodo_id' => $periodo_id
    ]);

    $estudiantes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($estudiantes);

} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>