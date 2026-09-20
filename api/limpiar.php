<?php
header("Content-Type: application/json; charset=UTF-8");

// Detectar automáticamente la ruta correcta de conexion.php
if (file_exists(__DIR__ . '/conexion.php')) {
    require_once __DIR__ . '/conexion.php';
} elseif (file_exists(__DIR__ . '/../conexion.php')) {
    require_once __DIR__ . '/../conexion.php';
} else {
    echo json_encode(["success" => false, "message" => "No se encontró el archivo conexion.php"]);
    exit;
}

try {
    if (!isset($pdo) && isset($conn)) {
        $pdo = $conn;
    }

    // 1. Eliminar el registro basura creado por el archivo .ods
    $stmt1 = $pdo->prepare("DELETE FROM estudiantes WHERE nie LIKE 'PK%' OR nie IS NULL OR nie = ''");
    $stmt1->execute();
    $filasBasura = $stmt1->rowCount();

    // 2. Vaciar completamente la sección '1° A Software'
    $stmt2 = $pdo->prepare("DELETE FROM estudiantes WHERE seccion = '1° A Software' OR seccion_id = 1");
    $stmt2->execute();
    $filasSeccion = $stmt2->rowCount();

    echo json_encode([
        "success" => true,
        "message" => "Limpieza completada con éxito.",
        "registros_corruptos_eliminados" => $filasBasura,
        "registros_seccion_eliminados" => $filasSeccion
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}
?>