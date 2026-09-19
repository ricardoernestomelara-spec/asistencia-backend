<?php
// Configuración de cabeceras CORS
header_remove('Access-Control-Allow-Origin');
header_remove('Access-Control-Allow-Headers');
header_remove('Access-Control-Allow-Methods');

header("Access-Control-Allow-Origin: *", true);
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Origin, Accept", true);
header("Access-Control-Allow-Methods: POST, OPTIONS", true);

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

header("Content-Type: application/json; charset=UTF-8");

error_reporting(0);
ini_set('display_errors', 0);

try {
    require_once __DIR__ . '/../conexion.php';

    if (!isset($pdo) && isset($conn)) {
        $pdo = $conn;
    }

    if (!isset($pdo) || !$pdo) {
        throw new Exception("Sin conexión a la base de datos.");
    }

    // 1. Crear tablas si no existen
    $pdo->exec("CREATE TABLE IF NOT EXISTS secciones (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(100) UNIQUE NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $pdo->exec("CREATE TABLE IF NOT EXISTS estudiantes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nie VARCHAR(50) NOT NULL,
        apellidos VARCHAR(100) NOT NULL,
        nombres VARCHAR(100) NOT NULL,
        seccion_id INT NOT NULL,
        FOREIGN KEY (seccion_id) REFERENCES secciones(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $pdo->exec("CREATE TABLE IF NOT EXISTS asistencia (
        id INT AUTO_INCREMENT PRIMARY KEY,
        estudiante_id INT NOT NULL,
        fecha DATE NOT NULL,
        estado VARCHAR(50) NOT NULL,
        observacion VARCHAR(255) NULL,
        FOREIGN KEY (estudiante_id) REFERENCES estudiantes(id) ON DELETE CASCADE,
        UNIQUE KEY unique_asistencia (estudiante_id, fecha)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Intento silencioso de agregar columna por si falta en asistencia
    try { $pdo->exec("ALTER TABLE asistencia ADD COLUMN observacion VARCHAR(255) NULL;"); } catch (Throwable $t) {}

    $rawInput = file_get_contents("php://input");
    $data = json_decode($rawInput, true);

    if (!$data) {
        echo json_encode(["success" => false, "message" => "No se recibieron datos JSON válidos."]);
        exit();
    }

    $fecha = $data['fecha'] ?? date('Y-m-d');
    $seccion_nombre = $data['seccion'] ?? $data['seccion_nombre'] ?? '1° A Software';
    $items = $data['asistencias'] ?? $data['alumnos'] ?? $data['estudiantes'] ?? $data['datos'] ?? $data;

    if (!is_array($items) || empty($items)) {
        echo json_encode(["success" => false, "message" => "No hay datos de asistencia para guardar"]);
        exit();
    }

    // Obtener o crear ID de sección por defecto
    $stmtSec = $pdo->prepare("SELECT id FROM secciones WHERE nombre = :nombre LIMIT 1");
    $stmtSec->execute([':nombre' => $seccion_nombre]);
    $sec = $stmtSec->fetch(PDO::FETCH_ASSOC);

    if ($sec) {
        $seccion_id = $sec['id'];
    } else {
        $stmtInsSec = $pdo->prepare("INSERT INTO secciones (nombre) VALUES (:nombre)");
        $stmtInsSec->execute([':nombre' => $seccion_nombre]);
        $seccion_id = $pdo->lastInsertId();
    }

    $pdo->beginTransaction();

    $stmtFindEst = $pdo->prepare("SELECT id FROM estudiantes WHERE id = :val OR nie = :val LIMIT 1");
    
    // Inserción directa garantizando que nie nunca vaya vacío
    $stmtAutoCreateEst = $pdo->prepare("
        INSERT INTO estudiantes (nie, apellidos, nombres, seccion_id) 
        VALUES (:nie, :apellidos, :nombres, :seccion_id)
    ");

    $stmtInsertAsis = $pdo->prepare("
        INSERT INTO asistencia (estudiante_id, fecha, estado, observacion) 
        VALUES (:estudiante_id, :fecha, :estado, :observacion)
        ON DUPLICATE KEY UPDATE 
            estado = VALUES(estado),
            observacion = VALUES(observacion)
    ");

    $insertados = 0;

    foreach ($items as $val) {
        if (!is_array($val)) continue;

        // Extraer NIE o ID desde cualquier parámetro posible del JSON
        $nieVal = null;
        foreach (['nie', 'NIE', 'estudiante_id', 'id_estudiante', 'id'] as $key) {
            if (!empty($val[$key])) {
                $nieVal = (string)$val[$key];
                break;
            }
        }

        // Si después de buscar sigue sin haber un valor, generar un NIE provisional válido de 8 dígitos
        if (empty($nieVal)) {
            $nieVal = (string)rand(10000000, 99999999);
        }

        $apellidos = !empty($val['apellidos']) ? $val['apellidos'] : (!empty($val['APELLIDOS']) ? $val['APELLIDOS'] : 'Apellido');
        $nombres = !empty($val['nombres']) ? $val['nombres'] : (!empty($val['NOMBRES']) ? $val['NOMBRES'] : 'Nombre');
        $estado = $val['estado'] ?? $val['ESTADO'] ?? 'Asistió';
        $observacion = $val['observacion'] ?? $val['inasistencia_por'] ?? $val['OBSERVACION'] ?? null;

        $realStudentId = null;

        // 1. Intentar encontrar al estudiante en la BD por su ID o NIE
        $stmtFindEst->execute([':val' => $nieVal]);
        $est = $stmtFindEst->fetch(PDO::FETCH_ASSOC);
        if ($est) {
            $realStudentId = $est['id'];
        }

        // 2. Si no existe, crearlo obligatoriamente pasando :nie relleno
        if (!$realStudentId) {
            $stmtAutoCreateEst->execute([
                ':nie'        => $nieVal,
                ':apellidos'  => $apellidos,
                ':nombres'    => $nombres,
                ':seccion_id' => $seccion_id
            ]);
            $realStudentId = $pdo->lastInsertId();
        }

        // 3. Registrar asistencia
        if ($realStudentId) {
            $stmtInsertAsis->execute([
                ':estudiante_id' => $realStudentId,
                ':fecha'         => $fecha,
                ':estado'        => $estado,
                ':observacion'   => $observacion
            ]);
            $insertados++;
        }
    }

    $pdo->commit();

    echo json_encode([
        "success" => true, 
        "message" => "Asistencia guardada correctamente ($insertados registros procesados)."
    ]);

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(200);
    echo json_encode(["success" => false, "message" => "Error al guardar asistencia: " . $e->getMessage()]);
}
?>