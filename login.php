<?php
// 1. Desactivar compresión y búfer para entrega inmediata de encabezados
if (function_exists('apache_setenv')) {
    @apache_setenv('no-gzip', 1);
}
@ini_set('zlib.output_compression', 0);

// 2. Encabezados CORS obligatorios
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

// 3. Responder a peticiones Preflight (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    if (ob_get_level()) ob_end_clean();
    flush();
    exit(0);
}

// 4. Configuración de errores para peticiones POST/GET
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

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