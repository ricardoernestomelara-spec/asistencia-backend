import React, { useState, useEffect } from 'react';
import { API_BASE } from '../config'; // Importar API_BASE

export default function AsignarCarga() {
  const [docentes, setDocentes] = useState([]);
  const [secciones, setSecciones] = useState([]);
  const [asignaturas, setAsignaturas] = useState([]);
  
  const [docenteSel, setDocenteSel] = useState('');
  const [seccionSel, setSeccionSel] = useState('');
  const [asignaturaSel, setAsignaturaSel] = useState('');

  useEffect(() => {
    // Uso de API_BASE con %20
    fetch(`${API_BASE}/obtener_catalogos.php`)
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          setDocentes(data.docentes);
          setSecciones(data.secciones);
          setAsignaturas(data.asignaturas);
        }
      });
  }, []);

  const handleGuardar = async (e) => {
    e.preventDefault();
    if (!docenteSel || !seccionSel || !asignaturaSel) {
      alert('Por favor selecciona todos los campos');
      return;
    }

    // Uso de API_BASE con %20
    const res = await fetch(`${API_BASE}/guardar_carga.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        docente_id: docenteSel,
        seccion_id: seccionSel,
        asignatura_id: asignaturaSel
      })
    });

    const data = await res.json();
    if (data.success) {
      alert('Carga académica asignada correctamente');
    } else {
      alert('Error: ' + data.message);
    }
  };

  // ... Mantener el renderizado del JSX intacto ...