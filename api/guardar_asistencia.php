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

$input = json_decode(file_get_contents('php://input'), true);

$fecha = trim($input['fecha'] ?? '');
$seccionNombre = trim($input['seccion'] ?? '');
$asignaturaNombre = trim($input['asignatura'] ?? '');
$detalles = $input['detalles'] ?? [];

if (empty($fecha) || empty($seccionNombre) || empty($asignaturaNombre) || empty($detalles)) {
    ob_end_clean();
    echo json_encode(["success" => false, "message" => "Datos incompletos para guardar"]);
    exit();
}

// 1. Obtener ID de la sección (usando TRIM para coincidencia exacta)
$seccion_id = 0;
$stmtS = $conn->prepare("SELECT id FROM secciones WHERE TRIM(nombre) = ?");
$stmtS->bind_param("s", $seccionNombre);
$stmtS->execute();
$resS = $stmtS->get_result();
if ($rowS = $resS->fetch_assoc()) {
    $seccion_id = intval($rowS['id']);
}
$stmtS->close();

// 2. Obtener ID de la asignatura (usando TRIM para coincidencia exacta)
$asignatura_id = 0;
$stmtA = $conn->prepare("SELECT id FROM asignaturas WHERE TRIM(nombre) = ?");
$stmtA->bind_param("s", $asignaturaNombre);
$stmtA->execute();
$resA = $stmtA->get_result();
if ($rowA = $resA->fetch_assoc()) {
    $asignatura_id = intval($rowA['id']);
}
$stmtA->close();

if ($seccion_id === 0 || $asignatura_id === 0) {
    ob_end_clean();
    echo json_encode([
        "success" => false, 
        "message" => "Sección o Asignatura no identificada en la BD",
        "debug" => ["seccion" => $seccionNombre, "asignatura" => $asignaturaNombre]
    ]);
    exit();
}

// 3. Guardar o actualizar la asistencia con las IDs obtenidas
$sql = "INSERT INTO asistencia (estudiante_id, fecha, estado, seccion_id, asignatura_id) 
        VALUES (?, ?, ?, ?, ?) 
        ON DUPLICATE KEY UPDATE estado = VALUES(estado), seccion_id = VALUES(seccion_id), asignatura_id = VALUES(asignatura_id)";

$stmt = $conn->prepare($sql);

foreach ($detalles as $det) {
    $estudiante_id = intval($det['estudiante_id']);
    $estado = $det['estado'];

    $stmt->bind_param("issii", $estudiante_id, $fecha, $estado, $seccion_id, $asignatura_id);
    $stmt->execute();
}

$stmt->close();
$conn->close();

ob_end_clean();
echo json_encode(["success" => true, "message" => "Asistencia guardada correctamente"]);
exit();
?>