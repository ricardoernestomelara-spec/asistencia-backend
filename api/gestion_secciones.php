<?php
// En tu archivo backend (p. ej. gestion_secciones.php o eliminar_estudiantes.php)
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/conexion.php';

$data = json_decode(file_get_contents("php_input"), true);
$action = $data['action'] ?? '';
$seccion_id = $data['seccion_id'] ?? null;

if ($action === 'vaciar_alumnos' && $seccion_id) {
    try {
        // 1. Eliminar asistencias asociadas a los alumnos de esta sección
        $stmtAsist = $pdo->prepare("
            DELETE a FROM asistencias a 
            INNER JOIN estudiantes e ON a.estudiante_id = e.id 
            WHERE e.seccion_id = ?
        ");
        $stmtAsist->execute([$seccion_id]);

        // 2. Eliminar todos los alumnos de esa sección (limpia registros normales y corruptos)
        $stmtEst = $pdo->prepare("DELETE FROM estudiantes WHERE seccion_id = ?");
        $stmtEst->execute([$seccion_id]);

        echo json_encode([
            "success" => true, 
            "message" => "Lista de alumnos vaciada correctamente. Ya puedes realizar una nueva carga masiva."
        ]);
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => "Error al vaciar la sección: " . $e->getMessage()]);
    }
    exit;
}
?>