<?php
// Permitir solicitudes CORS desde el Frontend
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Subir un nivel para encontrar conexion.php en la raíz
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

    // Consulta limpia para traer los 36 estudiantes de la sección
    $query = "
        SELECT 
            e.id,
            e.id AS estudiante_id,
            e.id AS alumno_id,
            e.nie,
            e.apellidos,
            e.nombres,
            CONCAT(e.apellidos, ' ', e.nombres) AS nombre_completo,
            s.nombre AS seccion,
            'Asistió' AS estado_asistencia,
            'Asistió' AS estado
        FROM estudiantes e
        INNER JOIN secciones s ON e.seccion_id = s.id
        WHERE TRIM(s.nombre) = TRIM(:seccion)
          AND e.nie NOT LIKE 'TEMP-%'
        ORDER BY e.apellidos ASC, e.nombres ASC
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute([':seccion' => $seccion]);

    $estudiantes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($estudiantes, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>