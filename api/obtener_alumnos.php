<?php
header_remove('Access-Control-Allow-Origin');
header("Access-Control-Allow-Origin: *", true);
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Origin, Accept", true);
header("Access-Control-Allow-Methods: GET, POST, OPTIONS", true);

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

header("Content-Type: application/json; charset=UTF-8");

error_reporting(0);
ini_set('display_errors', 0);

try {
    require_once __DIR__ . '/../conexion.php';

    if (!isset($pdo) && isset($conn)) {
        $pdo = $conn;
    }

    // Auto-creación de la tabla de estudiantes si aún no existe
    $pdo->exec("CREATE TABLE IF NOT EXISTS estudiantes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nie VARCHAR(20) UNIQUE NOT NULL,
        apellidos VARCHAR(100) NOT NULL,
        nombres VARCHAR(100) NOT NULL,
        seccion_id INT NOT NULL,
        FOREIGN KEY (seccion_id) REFERENCES secciones(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $seccion_id = $_GET['seccion_id'] ?? null;

    if (!$seccion_id) {
        echo json_encode(["success" => true, "alumnos" => []]);
        exit();
    }

    $stmt = $pdo->prepare("SELECT id, nie, apellidos, nombres FROM estudiantes WHERE seccion_id = :seccion_id ORDER BY apellidos ASC, nombres ASC");
    $stmt->execute([':seccion_id' => $seccion_id]);
    $alumnos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "alumnos" => $alumnos
    ]);

} catch (Throwable $e) {
    http_response_code(200);
    echo json_encode([
        "success" => false,
        "message" => "Error al consultar alumnos: " . $e->getMessage(),
        "alumnos" => []
    ]);
}
?>