<?php
ob_start();

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_end_clean();
    http_response_code(200);
    exit();
}

error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/conexion.php';

$seccion = trim($_GET['seccion'] ?? '');
$asignatura = trim($_GET['asignatura'] ?? '');

$alumnos = [];
$asistencias = [];
$fechas = [];

if (!empty($seccion)) {
    // 1. Obtener los alumnos pertenecientes a la sección
    $sqlAlumnos = "SELECT e.id, e.nie, e.apellidos, e.nombres 
                   FROM estudiantes e
                   INNER JOIN secciones s ON e.seccion_id = s.id
                   WHERE TRIM(s.nombre) = ?
                   ORDER BY e.apellidos, e.nombres";

    $stmtA = $conn->prepare($sqlAlumnos);
    if ($stmtA) {
        $stmtA->bind_param("s", $seccion);
        $stmtA->execute();
        $resA = $stmtA->get_result();
        while ($row = $resA->fetch_assoc()) {
            $alumnos[] = $row;
        }
        $stmtA->close();
    }

    // 2. Obtener registros de asistencia
    if (!empty($asignatura)) {
        $sqlAsistencia = "SELECT a.estudiante_id, 
                                 DATE_FORMAT(a.fecha, '%d/%m') AS fecha_corta, 
                                 a.estado 
                          FROM asistencia a
                          INNER JOIN asignaturas asig ON a.asignatura_id = asig.id
                          INNER JOIN secciones s ON a.seccion_id = s.id
                          WHERE TRIM(s.nombre) = ? AND TRIM(asig.nombre) = ?
                          ORDER BY a.fecha ASC";

        $stmtAst = $conn->prepare($sqlAsistencia);
        if ($stmtAst) {
            $stmtAst->bind_param("ss", $seccion, $asignatura);
            $stmtAst->execute();
            $resAst = $stmtAst->get_result();

            while ($row = $resAst->fetch_assoc()) {
                $fechaCorta = $row['fecha_corta'];

                if (!in_array($fechaCorta, $fechas)) {
                    $fechas[] = $fechaCorta;
                }

                $clave = $row['estudiante_id'] . '-' . $fechaCorta;
                $asistencias[$clave] = $row['estado'];
            }
            $stmtAst->close();
        }
    }
}

$conn->close();

ob_end_clean();
echo json_encode([
    "success" => true,
    "alumnos" => $alumnos,
    "fechas" => array_values($fechas),
    "asistencias" => $asistencias
]);
exit();
?>