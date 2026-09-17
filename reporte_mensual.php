<?php
ob_start();

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_end_clean();
    http_response_code(200);
    exit();
}

error_reporting(0);
ini_set('display_errors', 0);

$host = "localhost";
$user = "root";
$password = "";
$database = "asistencia"; 

require_once __DIR__ . '/conexion.php';

$seccion = isset($_GET['seccion']) ? $conn->real_escape_string($_GET['seccion']) : '';
$asignatura = isset($_GET['asignatura']) ? $conn->real_escape_string($_GET['asignatura']) : '';
$mes = isset($_GET['mes']) ? intval($_GET['mes']) : date('m'); // Mes actual por defecto

$reporte = [];

if (!empty($seccion)) {
    // Consulta para consolidar asistencias de los estudiantes por sección
    $sql = "SELECT e.id, e.nie, e.apellidos, e.nombres,
            SUM(CASE WHEN a.estado = 'P' THEN 1 ELSE 0 END) AS presentes,
            SUM(CASE WHEN a.estado = 'A' THEN 1 ELSE 0 END) AS ausentes,
            SUM(CASE WHEN a.estado = 'J' THEN 1 ELSE 0 END) AS justificadas,
            SUM(CASE WHEN a.estado = 'L' THEN 1 ELSE 0 END) AS llegadas_tarde
            FROM estudiantes e
            INNER JOIN secciones s ON e.seccion_id = s.id
            LEFT JOIN asistencia a ON e.id = a.estudiante_id AND MONTH(a.fecha) = $mes
            WHERE s.nombre = '$seccion'
            GROUP BY e.id
            ORDER BY e.apellidos ASC";

    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $reporte[] = $row;
        }
    }
}

$conn->close();

ob_end_clean();
echo json_encode(["success" => true, "reporte" => $reporte]);
exit();
?>