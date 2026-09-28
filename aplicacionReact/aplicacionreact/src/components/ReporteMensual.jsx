import React, { useState, useEffect } from 'react';
import { API_BASE } from '../config';

const MESES = [
  { id: '01', nombre: 'Enero' },
  { id: '02', nombre: 'Febrero' },
  { id: '03', nombre: 'Marzo' },
  { id: '04', nombre: 'Abril' },
  { id: '05', nombre: 'Mayo' },
  { id: '06', nombre: 'Junio' },
  { id: '07', nombre: 'Julio' },
  { id: '08', nombre: 'Agosto' },
  { id: '09', nombre: 'Septiembre' },
  { id: '10', nombre: 'Octubre' },
  { id: '11', nombre: 'Noviembre' },
  { id: '12', nombre: 'Diciembre' },
];

export const ReporteMensual = ({ docenteId, esAdmin = false }) => {
  const [anio, setAnio] = useState(new Date().getFullYear().toString());
  const [mes, setMes] = useState(String(new Date().getMonth() + 1).padStart(2, '0'));
  
  const [secciones, setSecciones] = useState([]);
  const [seccionId, setSeccionId] = useState('');

  const [reporte, setReporte] = useState([]);
  const [cargando, setCargando] = useState(false);

  // 1. Cargar las secciones habilitadas según el rol
  useEffect(() => {
    const obtenerSecciones = async () => {
      try {
        const endpoint = esAdmin
          ? `${API_BASE}/obtener_catalogos.php`
          : `${API_BASE}/carga_academica.php?docente_id=${docenteId}`;

        const res = await fetch(endpoint);
        const data = await res.json();

        if (data.success) {
          const listaSecs = esAdmin ? data.secciones || [] : data.carga || [];
          setSecciones(listaSecs);
          if (listaSecs.length > 0) {
            setSeccionId(listaSecs[0].seccion_id || listaSecs[0].id || '');
          }
        }
      } catch (err) {
        console.error('Error al cargar secciones:', err);
      }
    };

    obtenerSecciones();
  }, [docenteId, esAdmin]);

  // 2. Cargar reporte mensual enviando seccion_id, mes y anio
  useEffect(() => {
    if (!seccionId) return;

    const cargarReporte = async () => {
      setCargando(true);
      try {
        const res = await fetch(`${API_BASE}/reporte_mensual.php?seccion_id=${seccionId}&mes=${mes}&anio=${anio}`);
        const data = await res.json();

        if (data.success && Array.isArray(data.reporte)) {
          setReporte(data.reporte);
        } else {
          setReporte([]);
        }
      } catch (err) {
        console.error('Error al obtener reporte:', err);
        setReporte([]);
      } finally {
        setCargando(false);
      }
    };

    cargarReporte();
  }, [seccionId, mes, anio]);

  return (
    <div style={{ padding: '16px', background: '#fff', borderRadius: '12px', border: '1px solid #e2e8f0', fontFamily: 'system-ui, sans-serif' }}>
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '16px', flexWrap: 'wrap', gap: '12px' }}>
        <h3 style={{ margin: 0, fontSize: '18px', fontWeight: 'bold', color: '#1e293b' }}>
          📊 Reporte Mensual de Inasistencias
        </h3>
        
        <button
          onClick={() => window.print()}
          disabled={reporte.length === 0}
          style={{
            padding: '8px 16px',
            backgroundColor: reporte.length === 0 ? '#cbd5e1' : '#0284c7',
            color: '#fff',
            border: 'none',
            borderRadius: '6px',
            fontWeight: 'bold',
            cursor: reporte.length === 0 ? 'not-allowed' : 'pointer'
          }}
        >
          🖨️ Imprimir Reporte
        </button>
      </div>

      {/* Controles de Filtros */}
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(160px, 1fr))', gap: '12px', marginBottom: '20px' }}>
        <div>
          <label style={{ display: 'block', fontSize: '11px', fontWeight: 'bold', color: '#64748b', marginBottom: '4px' }}>AÑO</label>
          <select value={anio} onChange={(e) => setAnio(e.target.value)} style={{ width: '100%', padding: '8px', borderRadius: '6px', border: '1px solid #cbd5e1' }}>
            <option value="2026">2026</option>
            <option value="2025">2025</option>
          </select>
        </div>

        <div>
          <label style={{ display: 'block', fontSize: '11px', fontWeight: 'bold', color: '#64748b', marginBottom: '4px' }}>MES</label>
          <select value={mes} onChange={(e) => setMes(e.target.value)} style={{ width: '100%', padding: '8px', borderRadius: '6px', border: '1px solid #cbd5e1' }}>
            {MESES.map((m) => (
              <option key={m.id} value={m.id}>{m.nombre}</option>
            ))}
          </select>
        </div>

        <div>
          <label style={{ display: 'block', fontSize: '11px', fontWeight: 'bold', color: '#64748b', marginBottom: '4px' }}>SECCIÓN</label>
          <select value={seccionId} onChange={(e) => setSeccionId(e.target.value)} style={{ width: '100%', padding: '8px', borderRadius: '6px', border: '1px solid #cbd5e1' }}>
            {secciones.map((sec) => (
              <option key={sec.seccion_id || sec.id} value={sec.seccion_id || sec.id}>
                {sec.seccion || sec.nombre}
              </option>
            ))}
          </select>
        </div>
      </div>

      {/* Tabla de Resultados */}
      {cargando ? (
        <div style={{ textAlign: 'center', padding: '30px', color: '#64748b' }}>Cargando reporte...</div>
      ) : reporte.length === 0 ? (
        <div style={{ textAlign: 'center', padding: '24px', color: '#94a3b8', background: '#f8fafc', borderRadius: '8px' }}>
          No hay inasistencias registradas para esta sección en el mes seleccionado.
        </div>
      ) : (
        <div style={{ overflowX: 'auto' }}>
          <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: '13px', textAlign: 'left' }}>
            <thead>
              <tr style={{ background: '#f1f5f9', borderBottom: '2px solid #cbd5e1' }}>
                <th style={{ padding: '10px', textAlign: 'center', width: '40px' }}>#</th>
                <th style={{ padding: '10px' }}>NIE</th>
                <th style={{ padding: '10px' }}>ESTUDIANTE</th>
                <th style={{ padding: '10px', textAlign: 'center', color: '#16a34a' }}>ASISTIÓ</th>
                <th style={{ padding: '10px', textAlign: 'center', color: '#dc2626' }}>FALTAS</th>
                <th style={{ padding: '10px', textAlign: 'center', color: '#d97706' }}>PERMISOS</th>
                <th style={{ padding: '10px', textAlign: 'center' }}>% INASISTENCIA</th>
              </tr>
            </thead>
            <tbody>
              {reporte.map((item, idx) => {
                const totalClases = Number(item.asistencias) + Number(item.inasistencias) + Number(item.permisos);
                const pctFaltas = totalClases > 0 ? ((item.inasistencias / totalClases) * 100).toFixed(1) : 0;

                return (
                  <tr key={item.estudiante_id || idx} style={{ borderBottom: '1px solid #e2e8f0' }}>
                    <td style={{ padding: '10px', textAlign: 'center', color: '#64748b' }}>{idx + 1}</td>
                    <td style={{ padding: '10px', color: '#334155' }}>{item.nie || 'N/A'}</td>
                    <td style={{ padding: '10px', fontWeight: 'bold', color: '#0f172a' }}>{item.estudiante}</td>
                    <td style={{ padding: '10px', textAlign: 'center', fontWeight: 'bold', color: '#16a34a' }}>{item.asistencias}</td>
                    <td style={{ padding: '10px', textAlign: 'center', fontWeight: 'bold', color: '#dc2626' }}>{item.inasistencias}</td>
                    <td style={{ padding: '10px', textAlign: 'center', fontWeight: 'bold', color: '#d97706' }}>{item.permisos}</td>
                    <td style={{ padding: '10px', textAlign: 'center' }}>
                      <span style={{
                        padding: '3px 8px',
                        borderRadius: '12px',
                        fontWeight: 'bold',
                        fontSize: '11px',
                        backgroundColor: pctFaltas > 15 ? '#fee2e2' : '#e2e8f0',
                        color: pctFaltas > 15 ? '#991b1b' : '#334155'
                      }}>
                        {pctFaltas}%
                      </span>
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
};

export default ReporteMensual;