<?php
// Limpiar cabeceras previas para evitar duplicados en Apache
header_remove('Access-Control-Allow-Origin');
header_remove('Access-Control-Allow-Headers');
header_remove('Access-Control-Allow-Methods');

// Cabeceras CORS forzadas
header("Access-Control-Allow-Origin: *", true);
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Origin, Accept", true);
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE", true);

// Manejo directo e inmediato del Preflight (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

header("Content-Type: application/json; charset=UTF-8");

error_reporting(0);
ini_set('display_errors', 0);

try {
    require_once __DIR__ . '/../conexion.php';

    if (!isset($conn) && isset($pdo)) {
        $conn = $pdo;
    }

    if (!isset($conn) || !$conn) {
        throw new Exception("Error interno: No hay conexión activa a la BD.");
    }

    $input = json_decode(file_get_contents("php://input"), true);

    $email = trim($input['email'] ?? $input['usuario'] ?? '');
    $pass = trim($input['password'] ?? '');

    if (empty($email) || empty($pass)) {
        echo json_encode(["success" => false, "message" => "Ingresa correo y contraseña"]);
        exit();
    }

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

} catch (Throwable $e) {
    http_response_code(200);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>