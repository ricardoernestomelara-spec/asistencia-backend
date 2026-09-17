const express = require('express');
const router = express.Router();

// Ruta de prueba para verificar que el módulo funcione correctamente
router.get('/', (req, res) => {
  res.json({ message: "Ruta de asistencia Node.js activa" });
});

module.exports = router;