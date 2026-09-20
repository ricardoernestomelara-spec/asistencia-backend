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
    // 1. Crear la tabla de asistencias con índice único para que reemplace registros anteriores
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
        UNIQUE KEY uq_estudiante_fecha_materia (estudiante_id, fecha, asignatura, periodo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $pdo->exec($sqlCrearTabla); // cite: 1, 2

    // 2. Traer la lista de estudiantes con su asistencia real guardada
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
            ORDER BY e.apellidos ASC"; // cite: 1

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