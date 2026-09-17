<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");


require_once __DIR__ . '/conexion.php';
$data = json_decode(file_get_contents("php://input"), true);
$action = $data['action'] ?? '';

if ($action === 'guardar') {
    $id = $data['id'] ?? null;
    $nombre = trim($data['nombre'] ?? '');

    if (empty($nombre)) {
        echo json_encode(["success" => false, "message" => "El nombre es obligatorio."]);
        exit;
    }

    if ($id) {
        $stmt = $conn->prepare("UPDATE secciones SET nombre = ? WHERE id = ?");
        $stmt->bind_param("si", $nombre, $id);
    } else {
        $stmt = $conn->prepare("INSERT INTO secciones (nombre) VALUES (?)");
        $stmt->bind_param("s", $nombre);
    }

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => $id ? "Sección actualizada" : "Sección agregada correctamente"]);
    } else {
        echo json_encode(["success" => false, "message" => "Error en la base de datos"]);
    }
} elseif ($action === 'eliminar') {
    $id = $data['id'] ?? null;
    $stmt = $conn->prepare("DELETE FROM secciones WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Sección eliminada"]);
    } else {
        echo json_encode(["success" => false, "message" => "No se pudo eliminar la sección (posiblemente esté en uso)"]);
    }
}
?>