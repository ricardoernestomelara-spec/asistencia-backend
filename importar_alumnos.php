<?php
require_once __DIR__ . '/conexion.php';

try {
    if (!isset($pdo) && isset($conn)) {
        $pdo = $conn;
    }

    $filePath = __DIR__ . '/estudiantes_2.csv';
    if (!file_exists($filePath)) {
        die("Error: El archivo estudiantes_2.csv no se encuentra en la raíz.");
    }

    $content = file_get_contents($filePath);
    $lines = explode("\n", str_replace("\r", "", $content));

    $stmtSec = $pdo->prepare("SELECT id FROM secciones WHERE TRIM(nombre) = TRIM(:nombre) LIMIT 1");
    $stmtInsSec = $pdo->prepare("INSERT INTO secciones (nombre) VALUES (:nombre)");
    $stmtEst = $pdo->prepare("
        INSERT INTO estudiantes (nie, apellidos, nombres, seccion_id)
        VALUES (:nie, :apellidos, :nombres, :seccion_id)
        ON DUPLICATE KEY UPDATE 
            apellidos = VALUES(apellidos),
            nombres = VALUES(nombres),
            seccion_id = VALUES(seccion_id)
    ");

    $insertados = 0;

    foreach ($lines as $line) {
        $line = trim($line, "\ufeff\" ");
        if (empty($line)) continue;

        // Detectar separador por tabulación o punto y coma
        $parts = strpos($line, "\t") !== false ? explode("\t", $line) : explode(";", $line);

        if (count($parts) < 5) continue;

        $nie = trim($parts[1]);
        $nombreCompleto = trim($parts[2]);
        $seccionNombre = trim($parts[4]);

        // Omitir cabeceras
        if ($nie === 'NIE' || stristr($nie, 'No')) continue;

        if (!empty($nie) && !empty($nombreCompleto) && !empty($seccionNombre)) {
            // Separa "APELLIDOS, NOMBRES"
            if (strpos($nombreCompleto, ',') !== false) {
                list($apellidos, $nombres) = explode(',', $nombreCompleto, 2);
            } else {
                $apellidos = $nombreCompleto;
                $nombres = '';
            }

            $apellidos = trim($apellidos);
            $nombres = trim($nombres);

            // Obtener o crear ID de la sección
            $stmtSec->execute([':nombre' => $seccionNombre]);
            $sec = $stmtSec->fetch(PDO::FETCH_ASSOC);

            if ($sec) {
                $seccionId = $sec['id'];
            } else {
                $stmtInsSec->execute([':nombre' => $seccionNombre]);
                $seccionId = $pdo->lastInsertId();
            }

            // Insertar o actualizar estudiante
            $stmtEst->execute([
                ':nie' => $nie,
                ':apellidos' => $apellidos,
                ':nombres' => $nombres,
                ':seccion_id' => $seccionId
            ]);

            $insertados++;
        }
    }

    echo "<h3>¡Importación exitosa!</h3>";
    echo "Se registraron/actualizaron <b>$insertados estudiantes</b> en la base de datos.";

} catch (Exception $e) {
    echo "Error al importar: " . $e->getMessage();
}
?>