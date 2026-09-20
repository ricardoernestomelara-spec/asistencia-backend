<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

require_once 'conexion.php'; // Asegúrate de ajustar la ruta a tu archivo de conexión

$seccion = $_GET['seccion'] ?? '';
$asignatura = $_GET['asignatura'] ?? '';
$periodo = $_GET['periodo'] ?? '';
$fecha = $_GET['fecha'] ?? date('Y-m-d');

if (empty($seccion)) {
    echo json_encode(["error" => "La sección es requerida."]);
    exit;
}

try {
    // Consulta que prioriza el estado guardado en la tabla asistencias si existe para la fecha/materia/periodo
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
            WHERE e.seccion = :seccion
            ORDER BY e.apellidos ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':fecha' => $fecha,
        ':asignatura' => $asignatura,
        ':periodo' => $periodo,
        ':seccion' => $seccion
    ]);

    $estudiantes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($estudiantes);

} catch (PDOException $e) {
    echo json_encode(["error" => "Error en la consulta: " . $e->getMessage()]);
}
?>