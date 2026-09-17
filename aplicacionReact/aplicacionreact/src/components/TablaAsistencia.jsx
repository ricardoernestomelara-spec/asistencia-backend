import React, { useState, useEffect } from 'react';
import ModalAsistencia from './ModalAsistencia';

const API_BASE = 'http://localhost/proyecto%20venta/api';

const TablaAsistencia = ({ docenteId = 1 }) => {
  const [alumnos, setAlumnos] = useState([]);
  const [cargas, setCargas] = useState([]);
  
  // Listas filtradas para los desplegables
  const [seccionesDisponibles, setSeccionesDisponibles] = useState([]);
  const [asignaturasDisponibles, setAsignaturasDisponibles] = useState([]);

  const [seccionSeleccionada, setSeccionSeleccionada] = useState('');
  const [asignaturaSeleccionada, setAsignaturaSeleccionada] = useState('');
  const [periodo, setPeriodo] = useState('1');
  
  const [vistaReporte, setVistaReporte] = useState(false);
  const [mostrarModalPasarAsistencia, setMostrarModalPasarAsistencia] = useState(false);
  
  // Encabezado dinámico de fechas
  const [fechasHeader, setFechasHeader] = useState([]);
  const [asistenciasGuardadas, setAsistenciasGuardadas] = useState({});

  // 1. Cargar carga académica del docente
  useEffect(() => {
    if (!docenteId) return;

    fetch(`${API_BASE}/carga_academica.php?docente_id=${docenteId}`)
      .then((res) => res.json())
      .then((data) => {
        if (data.success && data.carga && data.carga.length > 0) {
          setCargas(data.carga);

          // Extraer secciones únicas
          const secs = [...new Set(data.carga.map((item) => item.seccion))];
          setSeccionesDisponibles(secs);

          const primeraSeccion = secs[0];
          setSeccionSeleccionada(primeraSeccion);

          // Filtrar asignaturas pertenecientes a la primera sección
          const asigs = data.carga
            .filter((item) => item.seccion === primeraSeccion)
            .map((item) => item.asignatura);

          setAsignaturasDisponibles(asigs);
          setAsignaturaSeleccionada(asigs[0] || '');
        } else {
          setCargas([]);
          setSeccionesDisponibles([]);
          setAsignaturasDisponibles([]);
        }
      })
      .catch((err) => console.error("Error al cargar la carga académica:", err));
  }, [docenteId]);

  // 2. Manejar cambio de sección para actualizar asignaturas
  const handleCambioSeccion = (e) => {
    const nuevaSeccion = e.target.value;
    setSeccionSeleccionada(nuevaSeccion);

    const asigs = cargas
      .filter((item) => item.seccion === nuevaSeccion)
      .map((item) => item.asignatura);

    setAsignaturasDisponibles(asigs);
    setAsignaturaSeleccionada(asigs[0] || '');
  };

  // 3. Cargar lista de alumnos y registros de asistencia desde PHP
  const cargarDatos = () => {
    if (!seccionSeleccionada || !asignaturaSeleccionada) return;

    const secParam = encodeURIComponent(seccionSeleccionada.trim());
    const asigParam = encodeURIComponent(asignaturaSeleccionada.trim());
    const periodoParam = encodeURIComponent(periodo);

    const url = vistaReporte 
      ? `${API_BASE}/reporte_mensual.php?seccion=${secParam}&asignatura=${asigParam}&periodo=${periodoParam}`
      : `${API_BASE}/asistencia.php?seccion=${secParam}&asignatura=${asigParam}&periodo=${periodoParam}`;

    fetch(url)
      .then((res) => {
        if (!res.ok) throw new Error("Error en la respuesta del servidor");
        return res.json();
      })
      .then((data) => {
        if (data.success) {
          if (vistaReporte) {
            setAlumnos(data.reporte || []);
          } else {
            setAlumnos(data.alumnos || []);
            // Filtrar cualquier fecha inválida como '00/00' o nula
            const fechasValidas = (data.fechas || []).filter(f => f && f !== '00/00');
            setFechasHeader(fechasValidas);
            setAsistenciasGuardadas(data.asistencias || {});
          }
        }
      })
      .catch((err) => console.error("Error al obtener datos:", err));
  };

  useEffect(() => {
    cargarDatos();
  }, [seccionSeleccionada, asignaturaSeleccionada, periodo, vistaReporte]);

  // 4. Guardar asistencia masiva desde el modal
  const guardarAsistenciaModal = async (datosModal) => {
    const payload = {
      fecha: datosModal.fecha,
      seccion: seccionSeleccionada,
      asignatura: asignaturaSeleccionada,
      periodo: periodo,
      detalles: datosModal.detalles
    };

    try {
      const res = await fetch(`${API_BASE}/guardar_asistencia.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      
      const data = await res.json();
      
      if (data.success) {
        setMostrarModalPasarAsistencia(false);
        cargarDatos();
      } else {
        alert("Error al guardar: " + (data.message || "Error desconocido"));
      }
    } catch (err) {
      console.error("Error al guardar asistencia:", err);
      alert("Ocurrió un error de red al guardar los datos.");
    }
  };

  // 5. Actualizar celda individual dinámicamente con validación de fecha
  const actualizarAsistenciaIndividual = (alumnoId, fechaCorta, nuevoEstado) => {
    if (!fechaCorta || fechaCorta === '00/00') return;

    const clave = `${alumnoId}-${fechaCorta}`;
    
    // Actualización inmediata en UI
    setAsistenciasGuardadas(prev => ({ ...prev, [clave]: nuevoEstado }));

    const anoActual = new Date().getFullYear();
    const partes = fechaCorta.split('/');
    
    // Validar que la fecha corta tenga formato MM/DD o DD/MM correcto
    if (partes.length !== 2) return;
    
    const [mm, dd] = partes;
    const fechaCompleta = `${anoActual}-${mm.padStart(2, '0')}-${dd.padStart(2, '0')}`;

    const payload = {
      fecha: fechaCompleta,
      seccion: seccionSeleccionada,
      asignatura: asignaturaSeleccionada,
      periodo: periodo,
      detalles: [{ estudiante_id: alumnoId, estado: nuevoEstado }]
    };

    fetch(`${API_BASE}/guardar_asistencia.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
      if (!data.success) {
        console.error("Error al guardar celda individual:", data.message);
      }
    })
    .catch(err => console.error("Error al actualizar la celda:", err));
  };

  // Selector visual con colores distintivos por estado
  const renderBadgeSelector = (alumnoId, fechaCorta, estadoActual) => {
    let style = { bg: '#dcfce7', color: '#15803d', border: '#bbf7d0' }; // Asistió (Verde)

    if (estadoActual === 'Faltó' || estadoActual === 'A') {
      style = { bg: '#fee2e2', color: '#b91c1c', border: '#fca5a5' }; // Faltó (Rojo)
    } else if (estadoActual === 'Permiso' || estadoActual === 'J') {
      style = { bg: '#fef3c7', color: '#b45309', border: '#fde68a' }; // Permiso (Naranja/Amarillo)
    } else if (estadoActual === 'Incapacidad') {
      style = { bg: '#f3e8ff', color: '#6b21a8', border: '#e9d5ff' }; // Incapacidad (Morado)
    } else if (estadoActual === 'Tardía' || estadoActual === 'L') {
      style = { bg: '#e0f2fe', color: '#0369a1', border: '#bae6fd' }; // Tardía (Azul Claro)
    } else if (estadoActual === 'Retirado') {
      style = { bg: '#e2e8f0', color: '#334155', border: '#cbd5e1' }; // Retirado (Gris)
    }

    return (
      <select
        value={estadoActual || 'Asistió'}
        onChange={(e) => actualizarAsistenciaIndividual(alumnoId, fechaCorta, e.target.value)}
        style={{
          backgroundColor: style.bg,
          color: style.color,
          border: `1px solid ${style.border}`,
          padding: '4px 8px',
          borderRadius: '20px',
          fontWeight: '700',
          fontSize: '12px',
          cursor: 'pointer',
          outline: 'none',
          boxShadow: '0 1px 2px rgba(0,0,0,0.05)'
        }}
      >
        <option value="Asistió" style={{ backgroundColor: '#fff', color: '#15803d' }}>Asistió</option>
        <option value="Faltó" style={{ backgroundColor: '#fff', color: '#b91c1c' }}>Faltó</option>
        <option value="Permiso" style={{ backgroundColor: '#fff', color: '#b45309' }}>Permiso</option>
        <option value="Incapacidad" style={{ backgroundColor: '#fff', color: '#6b21a8' }}>Incapacidad</option>
        <option value="Tardía" style={{ backgroundColor: '#fff', color: '#0369a1' }}>Tardía</option>
        <option value="Retirado" style={{ backgroundColor: '#fff', color: '#334155' }}>Retirado</option>
      </select>
    );
  };

  return (
    <div style={{ padding: '24px', backgroundColor: '#f8fafc', minHeight: '100vh', fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif' }}>
      <div style={{ backgroundColor: '#ffffff', borderRadius: '12px', boxShadow: '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)', padding: '24px', border: '1px solid #e2e8f0' }}>
        
        <div style={{ display: 'flex', flexWrap: 'wrap', justifyContent: 'space-between', alignItems: 'center', gap: '16px', marginBottom: '24px', borderBottom: '1px solid #f1f5f9', paddingBottom: '16px' }}>
          <div style={{ display: 'flex', gap: '12px' }}>
            <button 
              onClick={() => setMostrarModalPasarAsistencia(true)}
              style={{ display: 'flex', alignItems: 'center', gap: '8px', backgroundColor: '#2563eb', color: '#ffffff', border: 'none', padding: '10px 18px', borderRadius: '8px', cursor: 'pointer', fontWeight: '600', fontSize: '14px', boxShadow: '0 2px 4px rgba(37,99,235,0.2)' }}
            >
              <span>+</span> Tomar Asistencia
            </button>
            
            <button 
              onClick={() => setVistaReporte(!vistaReporte)}
              style={{ backgroundColor: vistaReporte ? '#f1f5f9' : '#ffffff', color: '#334155', border: '1px solid #cbd5e1', padding: '10px 18px', borderRadius: '8px', cursor: 'pointer', fontWeight: '600', fontSize: '14px' }}
            >
              {vistaReporte ? '📊 Ver Vista Diaria' : '📈 Reporte Mensual'}
            </button>
          </div>

          <div style={{ display: 'flex', gap: '16px', alignItems: 'center' }}>
            
            {/* Selector de Período */}
            <div>
              <label style={{ fontSize: '12px', fontWeight: '700', color: '#64748b', textTransform: 'uppercase', display: 'block', marginBottom: '4px' }}>Período</label>
              <select 
                value={periodo} 
                onChange={(e) => setPeriodo(e.target.value)} 
                style={{ padding: '8px 12px', borderRadius: '6px', border: '1px solid #cbd5e1', fontSize: '14px', backgroundColor: '#fff', color: '#1e293b' }}
              >
                <option value="1">1° Período</option>
                <option value="2">2° Período</option>
                <option value="3">3° Período</option>
                <option value="4">4° Período</option>
              </select>
            </div>

            {/* Selector de Sección */}
            <div>
              <label style={{ fontSize: '12px', fontWeight: '700', color: '#64748b', textTransform: 'uppercase', display: 'block', marginBottom: '4px' }}>Sección</label>
              <select 
                value={seccionSeleccionada} 
                onChange={handleCambioSeccion} 
                style={{ padding: '8px 12px', borderRadius: '6px', border: '1px solid #cbd5e1', fontSize: '14px', backgroundColor: '#fff', color: '#1e293b' }}
              >
                {seccionesDisponibles.map((sec, i) => (
                  <option key={i} value={sec}>{sec}</option>
                ))}
              </select>
            </div>

            {/* Selector de Asignatura */}
            <div>
              <label style={{ fontSize: '12px', fontWeight: '700', color: '#64748b', textTransform: 'uppercase', display: 'block', marginBottom: '4px' }}>Asignatura</label>
              <select 
                value={asignaturaSeleccionada} 
                onChange={(e) => setAsignaturaSeleccionada(e.target.value)} 
                style={{ padding: '8px 12px', borderRadius: '6px', border: '1px solid #cbd5e1', fontSize: '14px', backgroundColor: '#fff', color: '#1e293b', minWidth: '180px' }}
              >
                {asignaturasDisponibles.map((asig, i) => (
                  <option key={i} value={asig}>{asig}</option>
                ))}
              </select>
            </div>
          </div>
        </div>

        <div style={{ overflowX: 'auto', borderRadius: '8px', border: '1px solid #e2e8f0' }}>
          {!vistaReporte ? (
            <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: '14px' }}>
              <thead>
                <tr style={{ backgroundColor: '#f8fafc', borderBottom: '2px solid #e2e8f0', color: '#475569', textTransform: 'uppercase', fontSize: '12px', letterSpacing: '0.05em' }}>
                  <th style={{ padding: '14px 16px', textAlign: 'left', width: '50px' }}>#</th>
                  <th style={{ padding: '14px 16px', textAlign: 'left' }}>NIE</th>
                  <th style={{ padding: '14px 16px', textAlign: 'left' }}>Apellidos</th>
                  <th style={{ padding: '14px 16px', textAlign: 'left' }}>Nombres</th>
                  {fechasHeader.length === 0 ? (
                    <th style={{ padding: '14px 16px', textAlign: 'center', color: '#94a3b8' }}>Sin Registros</th>
                  ) : (
                    fechasHeader.map((f, i) => (
                      <th key={i} style={{ padding: '14px 16px', textAlign: 'center', backgroundColor: '#f1f5f9', borderLeft: '1px solid #e2e8f0' }}>{f}</th>
                    ))
                  )}
                </tr>
              </thead>
              <tbody>
                {alumnos.length === 0 ? (
                  <tr>
                    <td colSpan={4 + (fechasHeader.length || 1)} style={{ padding: '20px', textAlign: 'center', color: '#64748b' }}>
                      No hay alumnos o datos cargados para este filtro.
                    </td>
                  </tr>
                ) : (
                  alumnos.map((alumno, index) => (
                    <tr key={alumno.id || index} style={{ borderBottom: '1px solid #f1f5f9', backgroundColor: index % 2 === 0 ? '#ffffff' : '#fafafa' }}>
                      <td style={{ padding: '12px 16px', color: '#94a3b8', fontWeight: '600' }}>{index + 1}</td>
                      <td style={{ padding: '12px 16px', color: '#64748b', fontFamily: 'monospace' }}>{alumno.nie}</td>
                      <td style={{ padding: '12px 16px', fontWeight: '600', color: '#0f172a' }}>{alumno.apellidos}</td>
                      <td style={{ padding: '12px 16px', color: '#334155' }}>{alumno.nombres}</td>
                      {fechasHeader.length === 0 ? (
                        <td style={{ padding: '10px 12px', textAlign: 'center', color: '#94a3b8' }}>--</td>
                      ) : (
                        fechasHeader.map((fecha, i) => {
                          const estadoRegistrado = asistenciasGuardadas[`${alumno.id}-${fecha}`] || 'Asistió';
                          return (
                            <td key={i} style={{ padding: '10px 12px', textAlign: 'center', borderLeft: '1px solid #f1f5f9' }}>
                              {renderBadgeSelector(alumno.id, fecha, estadoRegistrado)}
                            </td>
                          );
                        })
                      )}
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          ) : (
            <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: '14px' }}>
              <thead>
                <tr style={{ backgroundColor: '#f8fafc', borderBottom: '2px solid #e2e8f0', color: '#475569', textTransform: 'uppercase', fontSize: '12px' }}>
                  <th style={{ padding: '14px 16px', textAlign: 'left' }}>#</th>
                  <th style={{ padding: '14px 16px', textAlign: 'left' }}>NIE</th>
                  <th style={{ padding: '14px 16px', textAlign: 'left' }}>Apellidos y Nombres</th>
                  <th style={{ padding: '14px 16px', textAlign: 'center', color: '#16a34a' }}>Presentes</th>
                  <th style={{ padding: '14px 16px', textAlign: 'center', color: '#dc2626' }}>Ausentes</th>
                  <th style={{ padding: '14px 16px', textAlign: 'center', color: '#d97706' }}>Justificadas</th>
                </tr>
              </thead>
              <tbody>
                {alumnos.map((alumno, index) => (
                  <tr key={alumno.id || index} style={{ borderBottom: '1px solid #f1f5f9' }}>
                    <td style={{ padding: '12px 16px' }}>{index + 1}</td>
                    <td style={{ padding: '12px 16px', fontFamily: 'monospace' }}>{alumno.nie}</td>
                    <td style={{ padding: '12px 16px', fontWeight: '600' }}>{alumno.apellidos}, {alumno.nombres}</td>
                    <td style={{ padding: '12px 16px', textAlign: 'center', fontWeight: '700', color: '#16a34a' }}>{alumno.presentes || 0}</td>
                    <td style={{ padding: '12px 16px', textAlign: 'center', fontWeight: '700', color: '#dc2626' }}>{alumno.ausentes || 0}</td>
                    <td style={{ padding: '12px 16px', textAlign: 'center', fontWeight: '700', color: '#d97706' }}>{alumno.justificadas || 0}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
        </div>
      </div>

      {mostrarModalPasarAsistencia && (
        <ModalAsistencia
          estudiantes={alumnos}
          onClose={() => setMostrarModalPasarAsistencia(false)}
          onGuardar={guardarAsistenciaModal}
        />
      )}
    </div>
  );
};

export default TablaAsistencia;