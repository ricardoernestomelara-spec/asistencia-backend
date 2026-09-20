<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

// Incluir conexión ubicada en la raíz
require_once '../conexion.php'; // cite: 1, 2

$seccion = $_GET['seccion'] ?? '';
$asignatura = $_GET['asignatura'] ?? '';
$periodo = $_GET['periodo'] ?? '';
$fecha = $_GET['fecha'] ?? date('Y-m-d');

if (empty($seccion)) {
    echo json_encode(["error" => "La sección es requerida."]);
    exit;
}

try {
    // Si viene "1° A Software", preparamos el término para buscar también "1° A Desarrollo de Software"
    $seccionBusqueda = '%' . str_replace('Software', '%', $seccion) . '%';

    $sql = "SELECT 
                e.id AS estudiante_id,
                e.nie,
                e.apellidos,
                e.nombres,
                CONCAT(e.apellidos, ' ', e.nombres) AS nombre_completo,
                IFNULL(a.estado, 'Asistió') AS asistencia,
                IFNULL(a.estado, 'Asistió') AS estado,
                a.inasistencia_por,
                a.observacion
            FROM estudiantes e
            LEFT JOIN asistencias a ON e.id = a.estudiante_id 
                AND a.fecha = :fecha 
                AND a.asignatura = :asignatura 
                AND a.periodo = :periodo
            WHERE e.seccion LIKE :seccion
            ORDER BY e.apellidos ASC"; // cite: 1

    $stmt = $pdo->prepare($sql); // cite: 1, 2
    $stmt->execute([
        ':fecha' => $fecha, // cite: 1
        ':asignatura' => $asignatura, // cite: 1
        ':periodo' => $periodo, // cite: 1
        ':seccion' => $seccionBusqueda
    ]);

    $estudiantes = $stmt->fetchAll(PDO::FETCH_ASSOC); // cite: 1

    echo json_encode($estudiantes); // cite: 1

} catch (PDOException $e) {
    echo json_encode(["error" => "Error en la consulta: " . $e->getMessage()]); // cite: 1
}
?>