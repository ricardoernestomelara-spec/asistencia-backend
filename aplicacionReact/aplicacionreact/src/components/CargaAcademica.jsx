import React, { useState, useEffect } from 'react';

const API_BASE = 'http://localhost/proyecto%20venta/api';

const CargaAcademica = ({ docenteId = 1 }) => {
  const [cargaRegular, setCargaRegular] = useState([]);
  const [seccionOrientacion, setSeccionOrientacion] = useState(null);

  useEffect(() => {
    if (!docenteId) return;

    fetch(`${API_BASE}/carga_academica.php?docente_id=${docenteId}`)
      .then((res) => res.json())
      .then((data) => {
        if (data.success && data.carga) {
          // 1. Filtrar las materias que NO son "Orientación" para la tabla superior
          const regulares = data.carga.filter(
            (item) => item.asignatura.trim().toLowerCase() !== 'orientación' &&
                      item.asignatura.trim().toLowerCase() !== 'orientacion'
          );

          // 2. Buscar si tiene asignada la materia "Orientación" para la tabla inferior
          const orientacionEncontrada = data.carga.find(
            (item) => item.asignatura.trim().toLowerCase() === 'orientación' ||
                      item.asignatura.trim().toLowerCase() === 'orientacion'
          );

          setCargaRegular(regulares);
          
          if (orientacionEncontrada) {
            setSeccionOrientacion(orientacionEncontrada.seccion);
          } else {
            setSeccionOrientacion(null);
          }
        } else {
          setCargaRegular([]);
          setSeccionOrientacion(null);
        }
      })
      .catch((err) => console.error("Error al obtener la carga:", err));
  }, [docenteId]);

  return (
    <div style={{ padding: '20px', maxWidth: '1000px', margin: '0 auto', fontFamily: 'sans-serif' }}>
      <div style={{ backgroundColor: '#ffffff', borderRadius: '8px', overflow: 'hidden', boxShadow: '0 2px 8px rgba(0,0,0,0.1)' }}>
        
        {/* TABLA SUPERIOR: ASIGNATURAS REGULARES */}
        <div style={{ backgroundColor: '#007b8f', color: '#ffffff', padding: '12px 16px', fontWeight: '700', fontSize: '14px', letterSpacing: '0.5px' }}>
          CARGA ACADÉMICA 2026
        </div>
        
        <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: '14px' }}>
          <thead>
            <tr style={{ backgroundColor: '#e0f2f1', color: '#004d40', borderBottom: '1px solid #b2dfdb', textTransform: 'uppercase', fontSize: '12px' }}>
              <th style={{ padding: '12px 16px', textAlign: 'left', width: '50%' }}>ASIGNATURA(S)</th>
              <th style={{ padding: '12px 16px', textAlign: 'left', width: '50%' }}>SECCIÓN(ES)</th>
            </tr>
          </thead>
          <tbody>
            {cargaRegular.length === 0 ? (
              <tr>
                <td colSpan={2} style={{ padding: '14px 16px', color: '#666', textAlign: 'center' }}>
                  Sin asignaturas regulares asignadas
                </td>
              </tr>
            ) : (
              cargaRegular.map((item, index) => (
                <tr key={item.id || index} style={{ borderBottom: '1px solid #eee' }}>
                  <td style={{ padding: '12px 16px', color: '#333' }}>{item.asignatura}</td>
                  <td style={{ padding: '12px 16px', color: '#333' }}>{item.seccion}</td>
                </tr>
              ))
            )}
          </tbody>
        </table>

        {/* TABLA INFERIOR: SECCIÓN DE ORIENTACIÓN */}
        <div style={{ backgroundColor: '#2d3748', color: '#ffffff', padding: '12px 16px', fontWeight: '700', fontSize: '14px', letterSpacing: '0.5px', marginTop: '4px' }}>
          SECCIÓN DE ORIENTACIÓN
        </div>

        <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: '14px' }}>
          <thead>
            <tr style={{ backgroundColor: '#edf2f7', color: '#2d3748', borderBottom: '1px solid #cbd5e0', textTransform: 'uppercase', fontSize: '12px' }}>
              <th style={{ padding: '12px 16px', textAlign: 'left', width: '50%' }}>SECCIÓN DE ORIENTACIÓN</th>
              <th style={{ padding: '12px 16px', textAlign: 'left', width: '50%' }}>AULA ASIGNADA</th>
            </tr>
          </thead>
          <tbody>
            <tr style={{ borderBottom: '1px solid #eee' }}>
              <td style={{ padding: '12px 16px', fontWeight: seccionOrientacion ? '600' : 'normal', color: '#333' }}>
                {seccionOrientacion ? seccionOrientacion : 'Ninguna'}
              </td>
              <td style={{ padding: '12px 16px', color: '#333' }}>
                {seccionOrientacion ? 'Aula Asignada' : 'N/A'}
              </td>
            </tr>
          </tbody>
        </table>

      </div>
    </div>
  );
};

export default CargaAcademica;