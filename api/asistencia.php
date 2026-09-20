<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

require_once '../conexion.php'; // cite: 1, 2

$seccion = $_GET['seccion'] ?? '';
$asignatura = $_GET['asignatura'] ?? '';
$periodo = $_GET['periodo'] ?? '';
$fecha = $_GET['fecha'] ?? date('Y-m-d');

try {
    // 1. Crear automáticamente la tabla de asistencias si no existe
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
        INDEX idx_estudiante_fecha (estudiante_id, fecha)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $pdo->exec($sqlCrearTabla); // cite: 1, 2

    // 2. Verificar los nombres reales de las columnas en la tabla 'estudiantes'
    $columnasStmt = $pdo->query("SHOW COLUMNS FROM estudiantes");
    $columnas = $columnasStmt->fetchAll(PDO::FETCH_COLUMN);

    // Identificar si existe alguna columna asociada a la sección/grado
    $columnaSeccion = null;
    foreach (['seccion', 'seccion_id', 'grado', 'curso', 'grupo'] as $col) {
        if (in_array($col, $columnas)) {
            $columnaSeccion = $col;
            break;
        }
    }

    // 3. Construir la consulta dinámicamente según las columnas existentes
    $whereClause = "";
    $params = [
        ':fecha' => $fecha, // cite: 1
        ':asignatura' => $asignatura, // cite: 1
        ':periodo' => $periodo // cite: 1
    ];

    if ($columnaSeccion && !empty($seccion)) {
        $seccionBusqueda = '%' . str_replace('Software', '%', $seccion) . '%';
        $whereClause = "WHERE e.{$columnaSeccion} LIKE :seccion";
        $params[':seccion'] = $seccionBusqueda;
    }

    $sql = "SELECT 
                e.id AS estudiante_id,
                e.nie,
                e.apellidos,
                e.nombres,
                CONCAT(e.apellidos, ' ', e.nombres) AS nombre_completo,
                IFNULL(a.estado, 'Asistió') AS asistencia,
                IFNULL(a.estado, 'Asistió') AS estado,
                a.inasistencia_por,
                a.observacion
            FROM estudiantes e
            LEFT JOIN asistencias a ON e.id = a.estudiante_id 
                AND a.fecha = :fecha 
                AND a.asignatura = :asignatura 
                AND a.periodo = :periodo
            {$whereClause}
            ORDER BY e.apellidos ASC"; // cite: 1

    $stmt = $pdo->prepare($sql); // cite: 1, 2
    $stmt->execute($params);

    $estudiantes = $stmt->fetchAll(PDO::FETCH_ASSOC); // cite: 1

    echo json_encode($estudiantes); // cite: 1

} catch (PDOException $e) {
    echo json_encode(["error" => "Error en la consulta: " . $e->getMessage()]); // cite: 1
}
?>