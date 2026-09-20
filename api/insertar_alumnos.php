<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../conexion.php';

try {
    if (!isset($pdo) && isset($conn)) {
        $pdo = $conn;
    }

    $seccionNombre = $_POST['seccion'] ?? '';
    if (empty($seccionNombre) || !isset($_FILES['archivo'])) {
        echo json_encode(['success' => false, 'message' => 'Falta seleccionar la sección o subir el archivo CSV.']);
        exit;
    }

    // 1. Obtener el ID de la sección objetivo
    $stmtSec = $pdo->prepare("SELECT id FROM secciones WHERE TRIM(nombre) = TRIM(:nombre) LIMIT 1");
    $stmtSec->execute([':nombre' => $seccionNombre]);
    $sec = $stmtSec->fetch(PDO::FETCH_ASSOC);

    if ($sec) {
        $seccionId = $sec['id'];
    } else {
        $stmtInsSec = $pdo->prepare("INSERT INTO secciones (nombre) VALUES (:nombre)");
        $stmtInsSec->execute([':nombre' => $seccionNombre]);
        $seccionId = $pdo->lastInsertId();
    }

    // 2. Procesar líneas del archivo subido
    $fileTmpPath = $_FILES['archivo']['tmp_name'];
    $fileHandle = fopen($fileTmpPath, 'r');

    $stmtEst = $pdo->prepare("
        INSERT INTO estudiantes (nie, apellidos, nombres, seccion_id)
        VALUES (:nie, :apellidos, :nombres, :seccion_id)
        ON DUPLICATE KEY UPDATE 
            apellidos = VALUES(apellidos),
            nombres = VALUES(nombres),
            seccion_id = VALUES(seccion_id)
    ");

    $procesados = 0;
    while (($data = fgetcsv($fileHandle, 1000, ";")) !== FALSE) {
        // Detectar si la separación es por coma en lugar de punto y coma
        if (count($data) < 2) {
            $data = explode(",", $data[0]);
        }

        $nie = trim($data[0] ?? '');
        $apellidos = trim($data[1] ?? '');
        $nombres = trim($data[2] ?? '');

        // Ignorar encabezados de columna o registros temporales
        if (empty($nie) || stristr($nie, 'NIE') || stristr($nie, 'TEMP-')) continue;

        $stmtEst->execute([
            ':nie' => $nie,
            ':apellidos' => $apellidos,
            ':nombres' => $nombres,
            ':seccion_id' => $seccionId
        ]);
        $procesados++;
    }

    fclose($fileHandle);

    echo json_encode([
        'success' => true,
        'message' => "Se importaron correctamente $procesados alumnos en la sección '$seccionNombre'."
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>