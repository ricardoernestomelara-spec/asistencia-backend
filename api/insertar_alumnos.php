<?php
require_once __DIR__ . '/../conexion.php';

if (!isset($pdo) && isset($conn)) {
    $pdo = $conn;
}

try {
    // 1. Asegurar que la tabla exista
    $pdo->exec("CREATE TABLE IF NOT EXISTS estudiantes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nie VARCHAR(20) UNIQUE NOT NULL,
        apellidos VARCHAR(100) NOT NULL,
        nombres VARCHAR(100) NOT NULL,
        seccion_id INT NOT NULL,
        FOREIGN KEY (seccion_id) REFERENCES secciones(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 2. Obtener la primera sección registrada (ej. 1° A Software)
    $stmtSeccion = $pdo->query("SELECT id, nombre FROM secciones LIMIT 1");
    $seccion = $stmtSeccion->fetch(PDO::FETCH_ASSOC);

    if (!$seccion) {
        die("Error: No hay secciones creadas en la base de datos.");
    }

    $seccion_id = $seccion['id'];
    echo "Insertando alumnos en la sección: " . $seccion['nombre'] . " (ID: $seccion_id)...<br>";

    // 3. Alumnos de prueba a registrar
    $alumnosPrueba = [
        ['nie' => '10293841', 'apellidos' => 'Gómez Hernández', 'nombres' => 'Carlos Eduardo'],
        ['nie' => '10293842', 'apellidos' => 'Martínez López', 'nombres' => 'María José'],
        ['nie' => '10293843', 'apellidos' => 'Rivas Orellana', 'nombres' => 'Kevin Alexander'],
        ['nie' => '10293844', 'apellidos' => 'Torres Vásquez', 'nombres' => 'Andrea Beatriz']
    ];

    $sqlInsert = "INSERT INTO estudiantes (nie, apellidos, nombres, seccion_id) 
                  VALUES (:nie, :apellidos, :nombres, :seccion_id)
                  ON DUPLICATE KEY UPDATE apellidos=VALUES(apellidos), nombres=VALUES(nombres)";

    $stmtInsert = $pdo->prepare($sqlInsert);

    foreach ($alumnosPrueba as $alumno) {
        $stmtInsert->execute([
            ':nie' => $alumno['nie'],
            ':apellidos' => $alumno['apellidos'],
            ':nombres' => $alumno['nombres'],
            ':seccion_id' => $seccion_id
        ]);
    }

    echo "<b>¡Alumnos insertados con éxito en la base de datos de Aiven!</b>";

} catch (Exception $e) {
    echo "Error al insertar alumnos: " . $e->getMessage();
}
?>