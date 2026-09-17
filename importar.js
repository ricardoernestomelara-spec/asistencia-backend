const path = require('path');
const xlsx = require('xlsx');
const pool = require('./routes/db');

async function importarTodasLasSecciones() {
  console.log('🚀 Iniciando la importación completa desde estudiantes.xlsx...');

  const rutaExcel = path.join(__dirname, 'estudiantes.xlsx');

  try {
    // Cargar el libro de Excel con todas sus pestañas
    const workbook = xlsx.readFile(rutaExcel);
    let totalInsertados = 0;

    // Recorrer cada una de las pestañas (Sección A, B, C, D, E)
    for (const sheetName of workbook.SheetNames) {
      const sheet = workbook.Sheets[sheetName];
      const filas = xlsx.utils.sheet_to_json(sheet);

      for (const fila of filas) {
        const nie = fila['NIE'] || fila['nie'];
        const nombreCompleto = fila['Nombre Completo'] || fila['nombre_completo'];
        const seccion_id = fila['seccion_id'];

        if (nie && nombreCompleto && seccion_id) {
          let apellidos = '';
          let nombres = '';

          // Separar APELLIDOS, NOMBRES por la coma
          if (nombreCompleto.includes(',')) {
            const partes = nombreCompleto.split(',');
            apellidos = partes[0].trim();
            nombres = partes[1].trim();
          } else {
            apellidos = nombreCompleto.trim();
          }

          const idSeccion = parseInt(seccion_id, 10);

          if (!isNaN(idSeccion)) {
            const sql = `
              INSERT INTO estudiantes (nie, apellidos, nombres, seccion_id)
              VALUES (?, ?, ?, ?)
              ON DUPLICATE KEY UPDATE
                apellidos = VALUES(apellidos),
                nombres = VALUES(nombres),
                seccion_id = VALUES(seccion_id)
            `;

            await pool.query(sql, [nie, apellidos, nombres, idSeccion]);
            totalInsertados++;
          }
        }
      }
    }

    console.log(`✅ Importación completada: ${totalInsertados} estudiantes procesados de todas las secciones.`);
  } catch (err) {
    console.error('❌ Error durante la importación:', err.message);
  } finally {
    if (pool.end) await pool.end();
    process.exit(0);
  }
}

importarTodasLasSecciones();