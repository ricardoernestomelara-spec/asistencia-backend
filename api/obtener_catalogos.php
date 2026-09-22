<?php
// Limpiar cabeceras previas para evitar duplicados
//este es un ejemplo
header_remove('Access-Control-Allow-Origin');
header_remove('Access-Control-Allow-Headers');
header_remove('Access-Control-Allow-Methods');

// Cabeceras CORS
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

    // Asegurar compatibilidad de variables de conexión
    if (!isset($pdo) && isset($conn)) {
        $pdo = $conn;
    }

    if (!isset($pdo) || !$pdo) {
        throw new Exception("Error interno: No hay conexión activa a la BD.");
    }

    // 1. Obtener Docentes (Consulta corregida a la tabla 'docentes')
    $stmtDocentes = $pdo->query("SELECT id, nombre, email FROM docentes ORDER BY nombre ASC");
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

} catch (Throwable $e) {
    http_response_code(200);
    echo json_encode([
        "success" => false,
        "message" => "Error al obtener catálogos: " . $e->getMessage()
    ]);
}
?>