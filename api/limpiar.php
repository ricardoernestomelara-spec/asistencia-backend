<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/conexion.php';

try {
    if (!isset($pdo) && isset($conn)) {
        $pdo = $conn;
    }

    // 1. Eliminar el registro basura con NIE que empieza por 'PK' o caracteres raros
    $stmt1 = $pdo->prepare("DELETE FROM estudiantes WHERE nie LIKE 'PK%' OR nie IS NULL OR nie = ''");
    $stmt1->execute();
    $filasBorradasBasura = $stmt1->rowCount();

    // 2. (Opcional) Si quieres limpiar toda la sección 1° A Software para empezar desde cero:
    // $stmt2 = $pdo->prepare("DELETE FROM estudiantes WHERE seccion = '1° A Software'");
    // $stmt2->execute();

    echo json_encode([
        "success" => true,
        "message" => "Se limpiaron {$filasBorradasBasura} registros corruptos con éxito."
    ]);
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}
?>