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

$docente_id = intval($_GET['docente_id'] ?? $_GET['id'] ?? 0);
$carga = [];
$orientacion = null;

if ($docente_id > 0) {
    // 1. Obtener carga académica regular
    $sql = "SELECT dc.id, a.nombre AS asignatura, s.nombre AS seccion 
            FROM docente_carga dc
            INNER JOIN asignaturas a ON dc.asignatura_id = a.id
            INNER JOIN secciones s ON dc.seccion_id = s.id
            WHERE dc.docente_id = ?";

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $docente_id);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $carga[] = [
                "id" => $row['id'],
                "asignatura" => $row['asignatura'],
                "seccion" => $row['seccion']
            ];
        }
        $stmt->close();
    }

    // 2. Obtener sección de orientación asignada
    $sqlOri = "SELECT s.nombre AS seccion, IFNULL(do.aula, 'N/A') AS aula
               FROM docente_orientacion do
               INNER JOIN secciones s ON do.seccion_id = s.id
               WHERE do.docente_id = ? LIMIT 1";

    $stmtO = $conn->prepare($sqlOri);
    if ($stmtO) {
        $stmtO->bind_param("i", $docente_id);
        $stmtO->execute();
        $resO = $stmtO->get_result();

        if ($rowO = $resO->fetch_assoc()) {
            $orientacion = [
                "seccion" => $rowO['seccion'],
                "aula" => $rowO['aula']
            ];
        }
        $stmtO->close();
    }
}

$conn->close();

ob_end_clean();
echo json_encode([
    "success" => true, 
    "carga" => $carga, 
    "orientacion" => $orientacion
]);
exit();
?>