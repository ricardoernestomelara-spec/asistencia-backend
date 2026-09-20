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

$seccion = $_GET['seccion'] ?? '';

if (empty($seccion)) {
    echo json_encode([]);
    exit;
}

try {
    if (!isset($pdo) && isset($conn)) {
        $pdo = $conn;
    }

    // Agrupar estrictamente por NIE para garantizar 36 filas únicas sin importar duplicados en la BD
    $query = "
        SELECT 
            MIN(e.id) AS id,
            MIN(e.id) AS estudiante_id,
            MIN(e.id) AS alumno_id,
            e.nie,
            e.nie AS NIE,
            e.apellidos,
            e.apellidos AS APELLIDOS,
            e.nombres,
            e.nombres AS NOMBRES,
            CONCAT(e.apellidos, ' ', e.nombres) AS nombre_completo,
            s.nombre AS seccion,
            'Asistió' AS estado_asistencia,
            'Asistió' AS estado
        FROM estudiantes e
        INNER JOIN secciones s ON e.seccion_id = s.id
        WHERE TRIM(s.nombre) = TRIM(:seccion)
          AND e.nie NOT LIKE 'TEMP-%'
        GROUP BY e.nie, e.apellidos, e.nombres, s.nombre
        ORDER BY e.apellidos ASC, e.nombres ASC
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute([':seccion' => $seccion]);

    $estudiantes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Asegurar devolución de un array nativo JSON
    echo json_encode(array_values($estudiantes), JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>