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

    // Auto-creación de la tabla si no existe en Aiven
    $createTableSQL = "CREATE TABLE IF NOT EXISTS carga_academica (
        id INT AUTO_INCREMENT PRIMARY KEY,
        docente_id INT NOT NULL,
        asignatura_id INT NOT NULL,
        seccion_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (docente_id) REFERENCES docentes(id) ON DELETE CASCADE,
        FOREIGN KEY (asignatura_id) REFERENCES asignaturas(id) ON DELETE CASCADE,
        FOREIGN KEY (seccion_id) REFERENCES secciones(id) ON DELETE CASCADE,
        UNIQUE KEY unique_carga (docente_id, asignatura_id, seccion_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $pdo->exec($createTableSQL);

    $data = json_decode(file_get_contents("php://input"), true);

    $docente_id = $data['docente_id'] ?? null;
    $asignatura_id = $data['asignatura_id'] ?? null;
    $seccion_id = $data['seccion_id'] ?? null;

    if (!$docente_id || !$asignatura_id || !$seccion_id) {
        echo json_encode(["success" => false, "message" => "Faltan datos obligatorios."]);
        exit();
    }

    $stmt = $pdo->prepare("INSERT INTO carga_academica (docente_id, asignatura_id, seccion_id) VALUES (:docente_id, :asignatura_id, :seccion_id)");
    $stmt->execute([
        ':docente_id' => $docente_id,
        ':asignatura_id' => $asignatura_id,
        ':seccion_id' => $seccion_id
    ]);

    echo json_encode(["success" => true, "message" => "Carga asignada correctamente."]);

} catch (Throwable $e) {
    http_response_code(200);
    echo json_encode(["success" => false, "message" => "Error al guardar carga: " . $e->getMessage()]);
}
?>