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

// AGREGAR O EDITAR DOCENTE
if ($action === 'guardar') {
    $id = intval($input['id'] ?? 0);
    $nombre = trim($input['nombre'] ?? '');
    $email = trim($input['usuario'] ?? $input['email'] ?? ''); // Mapea usuario a email
    $password = trim($input['password'] ?? '');

    if (empty($nombre) || empty($email)) {
        ob_end_clean();
        echo json_encode(["success" => false, "message" => "El Nombre y el Correo son obligatorios"]);
        exit();
    }

    if ($id > 0) {
        if (!empty($password)) {
            $stmt = $conn->prepare("UPDATE docentes SET nombre = ?, email = ?, password = ? WHERE id = ?");
            $stmt->bind_param("sssi", $nombre, $email, $password, $id);
        } else {
            $stmt = $conn->prepare("UPDATE docentes SET nombre = ?, email = ? WHERE id = ?");
            $stmt->bind_param("ssi", $nombre, $email, $id);
        }
    } else {
        if (empty($password)) {
            ob_end_clean();
            echo json_encode(["success" => false, "message" => "La contraseña es requerida"]);
            exit();
        }
        $stmt = $conn->prepare("INSERT INTO docentes (nombre, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $nombre, $email, $password);
    }

    if ($stmt->execute()) {
        ob_end_clean();
        echo json_encode([
            "success" => true, 
            "message" => $id > 0 ? "Docente actualizado correctamente" : "Docente registrado con éxito"
        ]);
    } else {
        ob_end_clean();
        echo json_encode(["success" => false, "message" => "Error al guardar en la base de datos"]);
    }
    $stmt->close();
    $conn->close();
    exit();
}

// ELIMINAR DOCENTE
if ($action === 'eliminar') {
    $id = intval($input['id'] ?? 0);

    if ($id <= 0) {
        ob_end_clean();
        echo json_encode(["success" => false, "message" => "ID no válido"]);
        exit();
    }

    $stmt = $conn->prepare("DELETE FROM docentes WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        ob_end_clean();
        echo json_encode(["success" => true, "message" => "Docente eliminado correctamente"]);
    } else {
        ob_end_clean();
        echo json_encode(["success" => false, "message" => "Error al eliminar el docente"]);
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