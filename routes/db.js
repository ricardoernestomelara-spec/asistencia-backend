const mysql = require('mysql2/promise');

const pool = mysql.createPool({
  host: 'localhost',
  user: 'root',
  password: '', // Si tienes contraseña, escríbela aquí
  database: 'asistencia', // <-- Nombre exacto según tu phpMyAdmin
  waitForConnections: true,
  connectionLimit: 10,
  queueLimit: 0
});

module.exports = pool;