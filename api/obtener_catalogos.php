<?php
// Limpiar cabeceras previas para evitar duplicados
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

    $docente_id = $_GET['docente_id'] ?? $_GET['id_docente'] ?? null;

    // 1. Obtener lista general de Docentes
    $stmtDocentes = $pdo->query("SELECT id, nombre, email FROM docentes ORDER BY nombre ASC");
    $docentes = $stmtDocentes->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($docente_id)) {
        // --- FILTRO DE CARGA REAL DEL DOCENTE ---

        // Secciones asignadas al docente
        $sqlSec = "SELECT DISTINCT s.id, s.nombre 
                   FROM secciones s 
                   INNER JOIN carga_academica ca ON ca.seccion_id = s.id
                   WHERE ca.docente_id = :docente_id
                   ORDER BY s.nombre ASC";
                   
        $stmtSec = $pdo->prepare($sqlSec);
        $stmtSec->execute([':docente_id' => $docente_id]);
        $secciones = $stmtSec->fetchAll(PDO::FETCH_ASSOC);

        // Asignaturas asignadas al docente
        $sqlAsig = "SELECT DISTINCT a.id, a.nombre, a.codigo 
                    FROM asignaturas a 
                    INNER JOIN carga_academica ca ON ca.asignatura_id = a.id
                    WHERE ca.docente_id = :docente_id
                    ORDER BY a.nombre ASC";

        $stmtAsig = $pdo->prepare($sqlAsig);
        $stmtAsig->execute([':docente_id' => $docente_id]);
        $asignaturas = $stmtAsig->fetchAll(PDO::FETCH_ASSOC);

    } else {
        // --- VISTA ADMINISTRADOR / CATALOGO GENERAL ---
        $stmtAsignaturas = $pdo->query("SELECT id, nombre, codigo FROM asignaturas ORDER BY nombre ASC");
        $asignaturas = $stmtAsignaturas->fetchAll(PDO::FETCH_ASSOC);

        $stmtSecciones = $pdo->query("SELECT id, nombre FROM secciones ORDER BY nombre ASC");
        $secciones = $stmtSecciones->fetchAll(PDO::FETCH_ASSOC);
    }

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
        "message" => "Error al obtener catálogos: " . $e->getMessage(),
        "docentes" => [],
        "asignaturas" => [],
        "secciones" => []
    ]);
}
?>