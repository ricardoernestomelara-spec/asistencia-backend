<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../conexion.php';

$docente_id = isset($_GET['docente_id']) ? intval($_GET['docente_id']) : 0;

try {
    $sql = "SELECT c.id, a.nombre AS asignatura, s.nombre AS seccion 
            FROM carga_academica c
            INNER JOIN asignaturas a ON c.asignatura_id = a.id
            INNER JOIN secciones s ON c.seccion_id = s.id";
    
    if ($docente_id > 0) {
        $sql .= " WHERE c.docente_id = :docente_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':docente_id' => $docente_id]);
    } else {
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
    }

    $carga = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "carga" => $carga
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Error al consultar carga academica: " . $e->getMessage()
    ]);
}
?>