<?php
header_remove('Access-Control-Allow-Origin');
header_remove('Access-Control-Allow-Headers');
header_remove('Access-Control-Allow-Methods');

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

require_once __DIR__ . '/../conexion.php';

if (!isset($pdo) && isset($conn)) {
    $pdo = $conn;
}

$seccionInput = $_GET['seccion'] ?? $_GET['seccion_id'] ?? '';
$asignatura   = $_GET['asignatura'] ?? '';
$periodo      = $_GET['periodo'] ?? '';
$fecha        = $_GET['fecha'] ?? date('Y-m-d');

try {
    // 1. Crear tabla asistencias con el ÍNDICE ÚNICO que evita duplicados
    $sqlCrearTabla = "CREATE TABLE IF NOT EXISTS asistencias (
        id INT AUTO_INCREMENT PRIMARY KEY,
        estudiante_id INT NOT NULL,
        fecha DATE NOT NULL,
        asignatura VARCHAR(100) NOT NULL,
        periodo VARCHAR(20) NOT NULL,
        estado VARCHAR(20) NOT NULL DEFAULT 'Asistió',
        inasistencia_por VARCHAR(100) DEFAULT NULL,
        observacion TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_estudiante_asistencia (estudiante_id, fecha, asignatura, periodo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $pdo->exec($sqlCrearTabla);

    // Intentar agregar el índice único en caso de que la tabla ya existiera antes sin él
    try {
        $pdo->exec("ALTER TABLE asistencias ADD UNIQUE KEY uq_estudiante_asistencia (estudiante_id, fecha, asignatura, periodo)");
    } catch (Throwable $ignored) {
        // Si el índice ya existe, ignora el error de forma segura
    }

    // 2. Buscar el seccion_id correspondiente
    $seccion_id = null;
    if (is_numeric($seccionInput)) {
        $seccion_id = (int)$seccionInput;
    } elseif (!empty($seccionInput)) {
        $stmtSec = $pdo->prepare("SELECT id FROM secciones WHERE TRIM(nombre) = TRIM(:nombre) LIMIT 1");
        $stmtSec->execute([':nombre' => $seccionInput]);
        $sec = $stmtSec->fetch(PDO::FETCH_ASSOC);
        if ($sec) {
            $seccion_id = $sec['id'];
        }
    }

    if (!$seccion_id) {
        echo json_encode([]);
        exit();
    }

    // 3. Filtrar estudiantes ÚNICOS agrupando por estudiante id
    $sql = "SELECT 
                e.id AS estudiante_id,
                e.nie,
                e.apellidos,
                e.nombres,
                CONCAT(e.apellidos, ' ', e.nombres) AS nombre_completo,
                IFNULL(ult_asistencia.estado, 'Asistió') AS asistencia,
                IFNULL(ult_asistencia.estado, 'Asistió') AS estado,
                ult_asistencia.inasistencia_por,
                ult_asistencia.observacion
            FROM estudiantes e
            LEFT JOIN (
                SELECT a1.*
                FROM asistencias a1
                INNER JOIN (
                    SELECT estudiante_id, MAX(id) AS max_id
                    FROM asistencias
                    WHERE fecha = :fecha 
                      AND asignatura = :asignatura 
                      AND periodo = :periodo
                    GROUP BY estudiante_id
                ) a2 ON a1.id = a2.max_id
            ) ult_asistencia ON e.id = ult_asistencia.estudiante_id
            WHERE e.seccion_id = :seccion_id
            GROUP BY e.id
            ORDER BY e.apellidos ASC, e.nombres ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':fecha'      => $fecha,
        ':asignatura' => $asignatura,
        ':periodo'    => $periodo,
        ':seccion_id' => $seccion_id
    ]);

    $estudiantes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($estudiantes);

} catch (Throwable $e) {
    http_response_code(200);
    echo json_encode(["error" => "Error en la consulta: " . $e->getMessage()]);
}
?>