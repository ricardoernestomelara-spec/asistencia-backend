<?php
// Limpiar cabeceras
header_remove('Access-Control-Allow-Origin');
header_remove('Access-Control-Allow-Headers');
header_remove('Access-Control-Allow-Methods');

// Cabeceras CORS
header("Access-Control-Allow-Origin: *", true);
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Origin, Accept", true);
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS", true);

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

    if (!isset($pdo) || !$pdo) {
        throw new Exception("Error interno: No hay conexión a la base de datos.");
    }

    $docente_id = $_GET['docente_id'] ?? null;

    if (!$docente_id) {
        echo json_encode(["success" => true, "carga" => []]);
        exit();
    }

    // Consulta de carga académica asociada a docentes, asignaturas y secciones
    $sql = "SELECT ca.id, 
                   a.nombre AS asignatura, 
                   a.codigo AS codigo_asignatura, 
                   s.nombre AS seccion
            FROM carga_academica ca
            INNER JOIN asignaturas a ON ca.asignatura_id = a.id
            INNER JOIN secciones s ON ca.seccion_id = s.id
            WHERE ca.docente_id = :docente_id";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':docente_id' => $docente_id]);
    $carga = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "carga" => $carga
    ]);

} catch (Throwable $e) {
    // Retornamos 200 con mensaje controlado para evitar errores 500 en la consola
    http_response_code(200);
    echo json_encode([
        "success" => false,
        "message" => "Error al obtener la carga académica: " . $e->getMessage()
    ]);
}
?>