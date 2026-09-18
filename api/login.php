<?php
// 1. Cabeceras CORS obligatorias (Deben ir al inicio)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

// 2. Si el navegador pregunta permisos (OPTIONS), responder OK de inmediato sin tocar la BD
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 3. Silenciar errores de HTML para evitar corromper la respuesta JSON
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

// 4. Conexión a la base de datos
require_once __DIR__ . '/conexion.php';

$input = json_decode(file_get_contents("php://input"), true);

$email = trim($input['email'] ?? $input['usuario'] ?? '');
$pass = trim($input['password'] ?? '');

if (empty($email) || empty($pass)) {
    if (ob_get_length()) ob_clean();
    echo json_encode(["success" => false, "message" => "Ingresa correo/usuario y contraseña"]);
    exit();
}

$sql = "SELECT id, nombre, email, password, IFNULL(rol, 'docente') AS rol FROM docentes WHERE email = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    if (ob_get_length()) ob_clean();
    echo json_encode(["success" => false, "message" => "Error al preparar la consulta"]);
    exit();
}

$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if (ob_get_length()) ob_clean();

if ($userBD = $result->fetch_assoc()) {
    if (password_verify($pass, $userBD['password']) || $pass === $userBD['password']) {
        echo json_encode([
            "success" => true,
            "usuario" => [
                "id" => $userBD['id'],
                "nombre" => $userBD['nombre'],
                "email" => $userBD['email'],
                "rol" => $userBD['rol']
            ]
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Contraseña incorrecta"]);
    }
} else {
    echo json_encode(["success" => false, "message" => "El usuario no existe"]);
}

$stmt->close();
$conn->close();
?>