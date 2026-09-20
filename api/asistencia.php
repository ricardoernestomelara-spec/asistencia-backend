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
    // 1. Asegurar la tabla de asistencias
    $sqlCrearTabla = "CREATE TABLE IF NOT EXISTS asistencias (
        id INT AUTO_INCREMENT PRIMARY KEY,
        estudiante_id INT NOT NULL,
        fecha DATE NOT NULL,
        asignatura VARCHAR(100) NOT NULL,
        periodo VARCHAR(20) NOT NULL,
        estado VARCHAR(20) NOT NULL DEFAULT 'Asistió',
        inasistencia_por VARCHAR(100) DEFAULT NULL,
        observacion TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $pdo->exec($sqlCrearTabla); // cite: 1, 2

    // 2. Consulta con Subquery para obtener ÚNICAMENTE el registro de asistencia más reciente de cada alumno
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
            ORDER BY e.apellidos ASC";

    $stmt = $pdo->prepare($sql); // cite: 1, 2
    $stmt->execute([
        ':fecha' => $fecha, // cite: 1
        ':asignatura' => $asignatura, // cite: 1
        ':periodo' => $periodo // cite: 1
    ]);

    $estudiantes = $stmt->fetchAll(PDO::FETCH_ASSOC); // cite: 1

    echo json_encode($estudiantes); // cite: 1

} catch (PDOException $e) {
    echo json_encode(["error" => "Error en la consulta: " . $e->getMessage()]); // cite: 1
}
?>