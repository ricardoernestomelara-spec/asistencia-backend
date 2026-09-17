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
$action = $input['action'] ?? $_GET['action'] ?? '';

// AGREGAR O EDITAR ASIGNATURA
if ($action === 'guardar') {
    $id = intval($input['id'] ?? 0);
    $codigo = trim($input['codigo'] ?? '');
    $nombre = trim($input['nombre'] ?? '');

    if (empty($codigo) || empty($nombre)) {
        ob_end_clean();
        echo json_encode(["success" => false, "message" => "El código y el nombre son obligatorios"]);
        exit();
    }

    if ($id > 0) {
        $stmt = $conn->prepare("UPDATE asignaturas SET codigo = ?, nombre = ? WHERE id = ?");
        $stmt->bind_param("ssi", $codigo, $nombre, $id);
    } else {
        $stmt = $conn->prepare("INSERT INTO asignaturas (codigo, nombre) VALUES (?, ?)");
        $stmt->bind_param("ss", $codigo, $nombre);
    }

    if ($stmt->execute()) {
        ob_end_clean();
        echo json_encode([
            "success" => true, 
            "message" => $id > 0 ? "Asignatura actualizada correctamente" : "Asignatura creada con éxito"
        ]);
    } else {
        ob_end_clean();
        echo json_encode(["success" => false, "message" => "Error al guardar en la base de datos"]);
    }
    $stmt->close();
    $conn->close();
    exit();
}

// ELIMINAR ASIGNATURA
if ($action === 'eliminar') {
    $id = intval($input['id'] ?? 0);

    if ($id <= 0) {
        ob_end_clean();
        echo json_encode(["success" => false, "message" => "ID no válido"]);
        exit();
    }

    $stmt = $conn->prepare("DELETE FROM asignaturas WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        ob_end_clean();
        echo json_encode(["success" => true, "message" => "Asignatura eliminada correctamente"]);
    } else {
        ob_end_clean();
        echo json_encode(["success" => false, "message" => "No se pudo eliminar la asignatura (comprueba que no esté en uso)"]);
    }
    $stmt->close();
    $conn->close();
    exit();
}

$conn->close();
ob_end_clean();
echo json_encode(["success" => false, "message" => "Acción no válida"]);
exit();
?>