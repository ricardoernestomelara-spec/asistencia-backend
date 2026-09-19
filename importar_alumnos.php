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

    // 1. OBTENER O CREAR LA SECCIÓN "1° A Software"
    $seccionNombreOficial = '1° A Software';
    $stmtSec = $pdo->prepare("SELECT id FROM secciones WHERE TRIM(nombre) = TRIM(:nombre) LIMIT 1");
    $stmtSec->execute([':nombre' => $seccionNombreOficial]);
    $sec = $stmtSec->fetch(PDO::FETCH_ASSOC);

    if ($sec) {
        $seccionId = $sec['id'];
    } else {
        $stmtInsSec = $pdo->prepare("INSERT INTO secciones (nombre) VALUES (:nombre)");
        $stmtInsSec->execute([':nombre' => $seccionNombreOficial]);
        $seccionId = $pdo->lastInsertId();
    }

    // 2. LIMPIAR ASISTENCIAS Y ESTUDIANTES PREVIOS DE ESTA SECCIÓN (Para eliminar duplicados y TEMPs)
    $pdo->prepare("DELETE FROM asistencia WHERE estudiante_id IN (SELECT id FROM estudiantes WHERE seccion_id = :sec_id)")->execute([':sec_id' => $seccionId]);
    $pdo->prepare("DELETE FROM estudiantes WHERE seccion_id = :sec_id OR nie LIKE 'TEMP-%'")->execute([':sec_id' => $seccionId]);

    // 3. LEER CSV E INSERTAR LOS 36 ALUMNOS UNICOS
    $content = file_get_contents($filePath);
    $lines = explode("\n", str_replace("\r", "", $content));

    $stmtEst = $pdo->prepare("
        INSERT INTO estudiantes (nie, apellidos, nombres, seccion_id)
        VALUES (:nie, :apellidos, :nombres, :seccion_id)
    ");

    $insertados = 0;
    $nieProcesados = []; // Evitar procesar el mismo NIE si viene repetido en el CSV

    foreach ($lines as $line) {
        $line = trim($line, "\ufeff\" ");
        if (empty($line)) continue;

        $parts = strpos($line, "\t") !== false ? explode("\t", $line) : explode(";", $line);

        if (count($parts) < 5) continue;

        $nie = trim($parts[1]);
        $nombreCompleto = trim($parts[2]);

        // Ignorar cabeceras y NIEs repetidos
        if ($nie === 'NIE' || stristr($nie, 'No') || in_array($nie, $nieProcesados)) continue;

        if (!empty($nie) && !empty($nombreCompleto)) {
            if (strpos($nombreCompleto, ',') !== false) {
                list($apellidos, $nombres) = explode(',', $nombreCompleto, 2);
            } else {
                $apellidos = $nombreCompleto;
                $nombres = '';
            }

            $stmtEst->execute([
                ':nie' => $nie,
                ':apellidos' => trim($apellidos),
                ':nombres' => trim($nombres),
                ':seccion_id' => $seccionId
            ]);

            $nieProcesados[] = $nie;
            $insertados++;
        }
    }

    echo "<h3>¡Limpieza e Importación completada con éxito!</h3>";
    echo "Se han eliminado los temporales/duplicados y se registron exactamente <b>$insertados estudiantes reales</b> en la sección <b>$seccionNombreOficial</b>.";

} catch (Exception $e) {
    echo "Error en la limpieza e importación: " . $e->getMessage();
}
?>