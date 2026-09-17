<?php
error_reporting(0);
ini_set('display_errors', 0);

if (ob_get_length()) ob_clean();

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/conexion.php';

$input = json_decode(file_get_contents("php://input"), true);
$carga_id = intval($input['id'] ?? 0);

if ($carga_id <= 0) {
    echo json_encode(["success" => false, "message" => "ID de carga no válido"]);
    exit;
}

$sql = "DELETE FROM docente_carga WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $carga_id);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Asignación eliminada correctamente"]);
} else {
    echo json_encode(["success" => false, "message" => "Error al eliminar la asignación"]);
}

$stmt->close();
$conn->close();
?>