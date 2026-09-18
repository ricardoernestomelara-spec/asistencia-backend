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
    // Incluir conexión (PDO) desde la raíz
    require_once __DIR__ . '/../conexion.php';

    // Asegurar variable de conexión PDO
    if (!isset($conn) && isset($pdo)) {
        $conn = $pdo;
    }

    if (!isset($conn) || !$conn) {
        throw new Exception("Error interno: No hay conexión activa a la BD.");
    }

    // Obtener y decodificar el cuerpo JSON
    $input = json_decode(file_get_contents("php://input"), true);

    $email = trim($input['email'] ?? $input['usuario'] ?? '');
    $pass = trim($input['password'] ?? '');

    if (empty($email) || empty($pass)) {
        echo json_encode(["success" => false, "message" => "Ingresa correo y contraseña"]);
        exit();
    }

    // Consulta con PDO en lugar de mysqli
    $sql = "SELECT id, nombre, email, password, IFNULL(rol, 'docente') AS rol FROM docentes WHERE email = :email LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':email' => $email]);
    $userBD = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($userBD) {
        if (password_verify($pass, $userBD['password']) || $pass === $userBD['password']) {
            echo json_encode([
                "success" => true,
                "usuario" => [
                    "id"     => $userBD['id'],
                    "nombre" => $userBD['nombre'],
                    "email"  => $userBD['email'],
                    "rol"    => $userBD['rol']
                ]
            ]);
        } else {
            echo json_encode(["success" => false, "message" => "Contraseña incorrecta"]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "El usuario no existe"]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>