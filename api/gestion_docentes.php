<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../conexion.php';

if (!isset($pdo) && isset($conn)) {
    $pdo = $conn;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        // Consultar directamente de la tabla 'docentes'
        $stmt = $pdo->query("SELECT id, nombre, email FROM docentes ORDER BY nombre ASC");
        echo json_encode(["success" => true, "docentes" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);

    } elseif ($method === 'POST') {
        $data = json_decode(file_get_contents("php://input"), true);
        $nombre = trim($data['nombre'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = password_hash($data['password'] ?? '123456', PASSWORD_DEFAULT);

        if (empty($nombre) || empty($email)) {
            echo json_encode(["success" => false, "message" => "El nombre y correo son obligatorios."]);
            exit();
        }

        // Insertar directamente en la tabla 'docentes'
        $stmt = $pdo->prepare("INSERT INTO docentes (nombre, email, password) VALUES (:nombre, :email, :password)");
        $stmt->execute([':nombre' => $nombre, ':email' => $email, ':password' => $password]);

        echo json_encode(["success" => true, "message" => "Docente registrado"]);
    }
} catch (Exception $e) {
    http_response_code(200);
    echo json_encode(["success" => false, "message" => "Error al gestionar docente: " . $e->getMessage()]);
}
?>