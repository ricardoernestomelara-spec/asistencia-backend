<?php
// Configuración de errores
error_reporting(0);
ini_set('display_errors', 0);

// Forzar respuesta JSON
header("Content-Type: application/json; charset=UTF-8");

// Manejo de petición preflight (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Incluir la conexión a la base de datos
    require_once __DIR__ . '/../conexion.php';

    // Verificar si $conn existe
    if (!isset($conn) || !$conn) {
        throw new Exception("Error interno: No hay conexion activa a la BD.");
    }

    // Obtener y decodificar el cuerpo JSON
    $input = json_decode(file_get_contents("php://input"), true);

    $email = trim($input['email'] ?? $input['usuario'] ?? '');
    $pass = trim($input['password'] ?? '');

    if (empty($email) || empty($pass)) {
        echo json_encode(["success" => false, "message" => "Ingresa correo y contraseña"]);
        exit();
    }

    // Consulta SQL a la tabla docentes
    $sql = "SELECT id, nombre, email, password, IFNULL(rol, 'docente') AS rol FROM docentes WHERE email = ?";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new Exception("Error en la consulta SQL: " . $conn->error);
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

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

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>