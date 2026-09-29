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

    // 1. Obtener Docentes
    $stmtDocentes = $pdo->query("SELECT id, nombre, email FROM docentes ORDER BY nombre ASC");
    $docentes = $stmtDocentes->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($docente_id)) {
        // Intentar obtener las secciones asignadas mediante las distintas tablas posibles de carga académica
        $sqlSec = "SELECT DISTINCT s.id, s.nombre 
                   FROM secciones s 
                   WHERE s.id IN (
                       SELECT seccion_id FROM carga_academica WHERE docente_id = :d1
                       UNION SELECT id_seccion FROM carga_academica WHERE id_docente = :d1
                       UNION SELECT seccion_id FROM docente_carga WHERE docente_id = :d1
                       UNION SELECT id_seccion FROM docente_carga WHERE id_docente = :d1
                       UNION SELECT seccion_id FROM asignaciones WHERE docente_id = :d1
                   ) ORDER BY s.nombre ASC";
                   
        $stmtSec = $pdo->prepare($sqlSec);
        $stmtSec->execute([':d1' => $docente_id]);
        $secciones = $stmtSec->fetchAll(PDO::FETCH_ASSOC);

        // Intentar obtener las asignaturas asignadas
        $sqlAsig = "SELECT DISTINCT a.id, a.nombre, a.codigo 
                    FROM asignaturas a 
                    WHERE a.id IN (
                        SELECT asignatura_id FROM carga_academica WHERE docente_id = :d1
                        UNION SELECT id_asignatura FROM carga_academica WHERE id_docente = :d1
                        UNION SELECT asignatura_id FROM docente_carga WHERE docente_id = :d1
                        UNION SELECT id_asignatura FROM docente_carga WHERE id_docente = :d1
                        UNION SELECT asignatura_id FROM asignaciones WHERE docente_id = :d1
                    ) ORDER BY a.nombre ASC";

        $stmtAsig = $pdo->prepare($sqlAsig);
        $stmtAsig->execute([':d1' => $docente_id]);
        $asignaturas = $stmtAsig->fetchAll(PDO::FETCH_ASSOC);

        // Si por alguna razón la subconsulta filtrada viene vacía, retornamos los catalogos globales para evitar pantallas en blanco
        if (empty($secciones)) {
            $stmtSecciones = $pdo->query("SELECT id, nombre FROM secciones ORDER BY nombre ASC");
            $secciones = $stmtSecciones->fetchAll(PDO::FETCH_ASSOC);
        }

        if (empty($asignaturas)) {
            $stmtAsignaturas = $pdo->query("SELECT id, nombre, codigo FROM asignaturas ORDER BY nombre ASC");
            $asignaturas = $stmtAsignaturas->fetchAll(PDO::FETCH_ASSOC);
        }

    } else {
        // Modo por defecto / Administrador
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
        "message" => "Error al obtener catálogos: " . $e->getMessage()
    ]);
}
?>