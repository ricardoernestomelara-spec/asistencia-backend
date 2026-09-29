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

    // Capturar si la petición pide filtrar por un docente en específico
    $docente_id = $_GET['docente_id'] ?? $_GET['id_docente'] ?? null;

    // 1. Obtener Docentes
    $stmtDocentes = $pdo->query("SELECT id, nombre, email FROM docentes ORDER BY nombre ASC");
    $docentes = $stmtDocentes->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($docente_id)) {
        // --- FILTRADO PARA DOCENTE ESPECÍFICO (Preza) ---
        
        // 2. Obtener solo sus Asignaturas asignadas
        $sqlAsig = "SELECT DISTINCT a.id, a.nombre, a.codigo 
                    FROM asignaturas a
                    INNER JOIN docente_carga dc ON (dc.asignatura_id = a.id OR dc.id_asignatura = a.id)
                    WHERE (dc.docente_id = :d1 OR dc.id_docente = :d2)
                    ORDER BY a.nombre ASC";
        $stmtAsig = $pdo->prepare($sqlAsig);
        $stmtAsig->execute([':d1' => $docente_id, ':d2' => $docente_id]);
        $asignaturas = $stmtAsig->fetchAll(PDO::FETCH_ASSOC);

        // 3. Obtener solo sus Secciones asignadas
        $sqlSec = "SELECT DISTINCT s.id, s.nombre 
                   FROM secciones s
                   INNER JOIN docente_carga dc ON (dc.seccion_id = s.id OR dc.id_seccion = s.id)
                   WHERE (dc.docente_id = :d1 OR dc.id_docente = :d2)
                   ORDER BY s.nombre ASC";
        $stmtSec = $pdo->prepare($sqlSec);
        $stmtSec->execute([':d1' => $docente_id, ':d2' => $docente_id]);
        $secciones = $stmtSec->fetchAll(PDO::FETCH_ASSOC);

    } else {
        // --- CONSULTA GLOBAL HISTÓRICA (Sin cambios, para no romper el resto del sistema) ---

        // 2. Obtener Asignaturas / Módulos
        $stmtAsignaturas = $pdo->query("SELECT id, nombre, codigo FROM asignaturas ORDER BY nombre ASC");
        $asignaturas = $stmtAsignaturas->fetchAll(PDO::FETCH_ASSOC);

        // 3. Obtener Secciones
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
        "message" => "Error al obtener catálogos: " . $e->getMessage()
    ]);
}
?>