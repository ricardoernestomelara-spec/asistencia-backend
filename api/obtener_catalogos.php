<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

require_once 'conexion.php'; // Ajusta la ruta de tu conexión PDO

$docente_id = $_GET['docente_id'] ?? $_GET['id_docente'] ?? null;

try {
    // Si viene un ID de docente (y no es admin sin ID), filtramos solo su carga
    if (!empty($docente_id)) {
        // Obtener solo las Secciones asignadas a este docente
        $sqlSecciones = "SELECT DISTINCT s.id, s.nombre 
                         FROM secciones s
                         INNER JOIN docente_carga dc ON dc.seccion_id = s.id OR dc.id_seccion = s.id
                         WHERE dc.docente_id = :docente_id OR dc.id_docente = :docente_id
                         ORDER BY s.nombre ASC";
        
        $stmtS = $pdo->prepare($sqlSecciones);
        $stmtS->execute([':docente_id' => $docente_id]);
        $secciones = $stmtS->fetchAll(PDO::FETCH_ASSOC);

        // Obtener solo las Asignaturas/Módulos asignados a este docente
        $sqlAsignaturas = "SELECT DISTINCT a.id, a.nombre 
                           FROM asignaturas a
                           INNER JOIN docente_carga dc ON dc.asignatura_id = a.id OR dc.id_asignatura = a.id
                           WHERE dc.docente_id = :docente_id OR dc.id_docente = :docente_id
                           ORDER BY a.nombre ASC";
                           
        $stmtA = $pdo->prepare($sqlAsignaturas);
        $stmtA->execute([':docente_id' => $docente_id]);
        $asignaturas = $stmtA->fetchAll(PDO::FETCH_ASSOC);

    } else {
        // Si no hay docente_id (Vista de Administrador), traemos todo el catálogo completo
        $stmtS = $pdo->query("SELECT id, nombre FROM secciones ORDER BY nombre ASC");
        $secciones = $stmtS->fetchAll(PDO::FETCH_ASSOC);

        $stmtA = $pdo->query("SELECT id, nombre FROM asignaturas ORDER BY nombre ASC");
        $asignaturas = $stmtA->fetchAll(PDO::FETCH_ASSOC);
    }

    echo json_encode([
        'success' => true,
        'secciones' => $secciones,
        'asignaturas' => $asignaturas
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'secciones' => [],
        'asignaturas' => []
    ]);
}
?>