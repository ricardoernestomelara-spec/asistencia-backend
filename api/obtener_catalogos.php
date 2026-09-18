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

try {
    // 1. Obtener Docentes
    $stmtDocentes = $pdo->query("SELECT id, nombre, email FROM usuarios WHERE rol = 'docente' ORDER BY nombre ASC");
    $docentes = $stmtDocentes->fetchAll(PDO::FETCH_ASSOC);

    // 2. Obtener Asignaturas / Módulos
    $stmtAsignaturas = $pdo->query("SELECT id, nombre, codigo FROM asignaturas ORDER BY nombre ASC");
    $asignaturas = $stmtAsignaturas->fetchAll(PDO::FETCH_ASSOC);

    // 3. Obtener Secciones
    $stmtSecciones = $pdo->query("SELECT id, nombre FROM secciones ORDER BY nombre ASC");
    $secciones = $stmtSecciones->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "docentes" => $docentes,
        "asignaturas" => $asignaturas,
        "secciones" => $secciones
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Error al obtener catalogos: " . $e->getMessage()
    ]);
}
?>