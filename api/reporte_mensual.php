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

export const ReporteMensual = ({ docenteId, usuario = 'Docente', esAdmin = false }) => {
  const [anio, setAnio] = useState('2026');
  const [mes, setMes] = useState(String(new Date().getMonth() + 1).padStart(2, '0'));
  
  const [secciones, setSecciones] = useState([]);
  const [seccionId, setSeccionId] = useState('');

  const [asignaturas, setAsignaturas] = useState([]);
  const [asignaturaId, setAsignaturaId] = useState('');

  const [reporte, setReporte] = useState([]);
  const [cargando, setCargando] = useState(false);

  // 1. Obtener catálogos filtrados por el docente actual
  useEffect(() => {
    const obtenerCatalogos = async () => {
      try {
        // Si no es admin, enviamos docente_id para traer solo sus secciones asignadas
        const url = (esAdmin || !docenteId)
          ? `${API_BASE}/obtener_catalogos.php` 
          : `${API_BASE}/obtener_catalogos.php?docente_id=${docenteId}`;

        const res = await fetch(url);
        const data = await res.json();

        if (data.success) {
          let listSecciones = data.secciones || [];
          let listAsignaturas = data.asignaturas || [];

          // Si la API responde con la carga específica del docente
          if (!esAdmin && data.docente_carga) {
            listSecciones = data.docente_carga.secciones || listSecciones;
            listAsignaturas = data.docente_carga.asignaturas || listAsignaturas;
          }

          setSecciones(listSecciones);
          if (listSecciones.length > 0) {
            setSeccionId(String(listSecciones[0].id));
          }

          setAsignaturas(listAsignaturas);
          if (listAsignaturas.length > 0) {
            setAsignaturaId(String(listAsignaturas[0].id));
          }
        }
      } catch (err) {
        console.error('Error cargando catálogos:', err);
      }
    };

    obtenerCatalogos();
  }, [docenteId, esAdmin]);

  // 2. Cargar reporte según la sección y asignatura seleccionadas
  useEffect(() => {
    if (!seccionId || !asignaturaId) return;

    const cargarReporte = async () => {
      setCargando(true);
      try {
        const queryParams = new URLSearchParams({
          seccion_id: seccionId,
          id_seccion: seccionId,
          asignatura_id: asignaturaId,
          id_asignatura: asignaturaId,
          mes: mes,
          anio: anio
        });

        const res = await fetch(`${API_BASE}/reporte_mensual.php?${queryParams.toString()}`);
        const data = await res.json();

        if (data.success && Array.isArray(data.reporte)) {
          setReporte(data.reporte);
        } else if (Array.isArray(data)) {
          setReporte(data);
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
  }, [seccionId, asignaturaId, mes, anio]);

  const nombreMes = MESES.find(m => m.id === mes)?.nombre || mes;
  const objSeccion = secciones.find(s => String(s.id) === String(seccionId));
  const nombreSeccion = objSeccion ? objSeccion.nombre : seccionId;
  const objAsignatura = asignaturas.find(a => String(a.id) === String(asignaturaId));
  const nombreAsignatura = objAsignatura ? objAsignatura.nombre : 'Asignatura';

  return (
    <div className="reporte-container" style={{ padding: '20px', background: '#fff', borderRadius: '12px', border: '1px solid #e2e8f0', fontFamily: 'system-ui, sans-serif' }}>
      
      {/* Estilos para PDF / Imprimir */}
      <style>{`
        @media print {
          body * { visibility: hidden !important; }
          .reporte-container, .reporte-container * { visibility: visible !important; }
          .reporte-container { position: absolute !important; left: 0 !important; top: 0 !important; width: 100% !important; border: none !important; padding: 0 !important; margin: 0 !important; }
          .no-print { display: none !important; }
          .print-header { display: block !important; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; }
          table { width: 100% !important; border-collapse: collapse !important; font-size: 11pt !important; }
          th, td { border: 1px solid #000 !important; padding: 6px 8px !important; }
        }
      `}</style>

      {/* ENCABEZADO IMPRESIÓN */}
      <div className="print-header" style={{ display: 'none' }}>
        <h2 style={{ margin: '0 0 4px 0', textAlign: 'center', fontSize: '18px', textTransform: 'uppercase' }}>
          CENTRO EDUCATIVO - REGISTRO DE ASISTENCIA
        </h2>
        <h3 style={{ margin: '0 0 12px 0', textAlign: 'center', fontSize: '14px', color: '#333' }}>
          REPORTE MENSUAL DE ASISTENCIA E INASISTENCIAS
        </h3>
        <table style={{ width: '100%', marginBottom: '15px', border: 'none', fontSize: '12px' }}>
          <tbody>
            <tr>
              <td style={{ border: 'none', padding: '3px' }}><strong>Sección:</strong> {nombreSeccion}</td>
              <td style={{ border: 'none', padding: '3px' }}><strong>Asignatura/Módulo:</strong> {nombreAsignatura}</td>
            </tr>
            <tr>
              <td style={{ border: 'none', padding: '3px' }}><strong>Período:</strong> {nombreMes} - {anio}</td>
              <td style={{ border: 'none', padding: '3px' }}><strong>Docente:</strong> {usuario}</td>
            </tr>
          </tbody>
        </table>
      </div>

      {/* ENCABEZADO PANTALLA */}
      <div className="no-print" style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '20px', flexWrap: 'wrap', gap: '12px' }}>
        <h3 style={{ margin: 0, fontSize: '18px', fontWeight: 'bold', color: '#1e293b' }}>
          📊 Reporte Mensual de Inasistencias
        </h3>
        
        <button
          onClick={() => window.print()}
          style={{
            padding: '9px 18px',
            backgroundColor: '#0284c7',
            color: '#fff',
            border: 'none',
            borderRadius: '6px',
            fontWeight: 'bold',
            cursor: 'pointer'
          }}
        >
          🖨️ Imprimir Reporte (PDF)
        </button>
      </div>

      {/* FILTROS */}
      <div className="no-print" style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(160px, 1fr))', gap: '16px', marginBottom: '24px' }}>
        <div>
          <label style={{ display: 'block', fontSize: '11px', fontWeight: 'bold', color: '#64748b', marginBottom: '6px' }}>AÑO</label>
          <select value={anio} onChange={(e) => setAnio(e.target.value)} style={{ width: '100%', padding: '9px', borderRadius: '6px', border: '1px solid #cbd5e1' }}>
            <option value="2026">2026</option>
            <option value="2025">2025</option>
          </select>
        </div>

        <div>
          <label style={{ display: 'block', fontSize: '11px', fontWeight: 'bold', color: '#64748b', marginBottom: '6px' }}>MES</label>
          <select value={mes} onChange={(e) => setMes(e.target.value)} style={{ width: '100%', padding: '9px', borderRadius: '6px', border: '1px solid #cbd5e1' }}>
            {MESES.map((m) => (
              <option key={m.id} value={m.id}>{m.nombre}</option>
            ))}
          </select>
        </div>

        <div>
          <label style={{ display: 'block', fontSize: '11px', fontWeight: 'bold', color: '#64748b', marginBottom: '6px' }}>SECCIÓN</label>
          <select value={seccionId} onChange={(e) => setSeccionId(e.target.value)} style={{ width: '100%', padding: '9px', borderRadius: '6px', border: '1px solid #cbd5e1' }}>
            {secciones.map((sec) => (
              <option key={sec.id} value={sec.id}>
                {sec.nombre}
              </option>
            ))}
          </select>
        </div>

        <div>
          <label style={{ display: 'block', fontSize: '11px', fontWeight: 'bold', color: '#64748b', marginBottom: '6px' }}>ASIGNATURA / MÓDULO</label>
          <select value={asignaturaId} onChange={(e) => setAsignaturaId(e.target.value)} style={{ width: '100%', padding: '9px', borderRadius: '6px', border: '1px solid #cbd5e1' }}>
            {asignaturas.map((asig) => (
              <option key={asig.id} value={asig.id}>
                {asig.nombre}
              </option>
            ))}
          </select>
        </div>
      </div>

      {/* TABLA DE RESULTADOS */}
      {cargando ? (
        <div style={{ textAlign: 'center', padding: '30px', color: '#64748b' }}>Cargando información del reporte...</div>
      ) : reporte.length === 0 ? (
        <div style={{ textAlign: 'center', padding: '24px', color: '#94a3b8', background: '#f8fafc', borderRadius: '8px' }}>
          No existen registros o estudiantes asignados a esta combinación en el sistema.
        </div>
      ) : (
        <div style={{ overflowX: 'auto' }}>
          <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: '13px', textAlign: 'left' }}>
            <thead>
              <tr style={{ background: '#f1f5f9', borderBottom: '2px solid #cbd5e1' }}>
                <th style={{ padding: '10px', textAlign: 'center', width: '40px' }}>N°</th>
                <th style={{ padding: '10px' }}>NIE</th>
                <th style={{ padding: '10px' }}>ESTUDIANTE</th>
                <th style={{ padding: '10px', textAlign: 'center', color: '#16a34a' }}>ASISTENCIAS</th>
                <th style={{ padding: '10px', textAlign: 'center', color: '#dc2626' }}>INASISTENCIAS</th>
                <th style={{ padding: '10px', textAlign: 'center', color: '#d97706' }}>PERMISOS</th>
                <th style={{ padding: '10px', textAlign: 'center' }}>% INASISTENCIAS</th>
              </tr>
            </thead>
            <tbody>
              {reporte.map((item, idx) => {
                const asist = Number(item.asistencias || 0);
                const inasist = Number(item.inasistencias || 0);
                const perm = Number(item.permisos || 0);
                const totalClases = asist + inasist + perm;
                const pctFaltas = totalClases > 0 ? ((inasist / totalClases) * 100).toFixed(1) : '0.0';

                return (
                  <tr key={item.estudiante_id || idx} style={{ borderBottom: '1px solid #e2e8f0' }}>
                    <td style={{ padding: '10px', textAlign: 'center', color: '#64748b' }}>{idx + 1}</td>
                    <td style={{ padding: '10px', color: '#334155' }}>{item.nie || 'N/A'}</td>
                    <td style={{ padding: '10px', fontWeight: 'bold', color: '#0f172a' }}>
                      {item.estudiante || `${item.apellidos || ''} ${item.nombres || ''}`}
                    </td>
                    <td style={{ padding: '10px', textAlign: 'center', fontWeight: 'bold', color: '#16a34a' }}>{asist}</td>
                    <td style={{ padding: '10px', textAlign: 'center', fontWeight: 'bold', color: '#dc2626' }}>{inasist}</td>
                    <td style={{ padding: '10px', textAlign: 'center', fontWeight: 'bold', color: '#d97706' }}>{perm}</td>
                    <td style={{ padding: '10px', textAlign: 'center' }}>
                      <span style={{
                        padding: '3px 8px',
                        borderRadius: '12px',
                        fontWeight: 'bold',
                        fontSize: '11px',
                        backgroundColor: Number(pctFaltas) > 15 ? '#fee2e2' : '#e2e8f0',
                        color: Number(pctFaltas) > 15 ? '#991b1b' : '#334155'
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