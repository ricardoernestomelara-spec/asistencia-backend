import React, { useState, useEffect } from 'react';
import { API_BASE } from '../config';

const OPCIONES_ESTADO = [
  'Asistió',
  'Faltó',
  'Permiso',
  'Incapacidad',
  'Tardía',
  'Retirado'
];

const OPCIONES_MOTIVO = [
  'Aislamiento social',
  'Bajo rendimiento',
  'Competencia Deportiva',
  'Cuarentena',
  'Cuido de familiar',
  'Desinterés de los padres por la educación',
  'Dificultad del transporte',
  'Dificultades de aprendizajes',
  'Discapacidad del estudiante',
  'Docente no asistió a clases',
  'Embarazo precoz',
  'Enfermedad',
  'Falta de motivación',
  'Inseguridad en el camino a la escuela',
  'Integrante de grupos que lo aleja de la escuela',
  'Muerte de un pariente',
  'Motivo Personal',
  'Nacimiento de hermano(a)',
  'No desea presentarse a exámenes',
  'No desea presentar tarea',
  'Noviazgo a temprana edad',
  'Otro, justificado',
  'Otro, No justificado',
  'Presencia de alcohólicos en el hogar',
  'Presencia de la menstruación',
  'Problemas relacionados con los compañeros',
  'Retención en la casa',
  'Separación de los padres',
  'Se retiró del país',
  'Socieeconómico',
  'Trabajo'
];

export const TablaAsistencia = ({ docenteId, usuario }) => {
  const obtenerFechaLocal = (fechaObj = new Date()) => {
    const year = fechaObj.getFullYear();
    const month = String(fechaObj.getMonth() + 1).padStart(2, '0');
    const day = String(fechaObj.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
  };

  const [cargaAcademica, setCargaAcademica] = useState([]);
  const [seccionesDisponibles, setSeccionesDisponibles] = useState([]);
  const [asignaturasDisponibles, setAsignaturasDisponibles] = useState([]);

  const [periodo, setPeriodo] = useState('1');
  const [seccion, setSeccion] = useState('');
  const [asignatura, setAsignatura] = useState('');
  const [fecha, setFecha] = useState(obtenerFechaLocal());

  const [alumnos, setAlumnos] = useState([]);
  const [cargando, setCargando] = useState(false);
  const [guardando, setGuardando] = useState(false);
  const [modalAbierto, setModalAbierto] = useState(false);
  const [alumnosModal, setAlumnosModal] = useState([]);

  // Saber si ya se tomó asistencia al menos a un alumno para esta fecha/materia
  const yaExisteAsistencia = alumnos.some(
    (est) => est.asistencia && est.asistencia !== 'Pendiente'
  );

  // Cargar Carga Académica soportando tanto docenteId numérico como nombre de usuario
  useEffect(() => {
    const identificarDocenteYObtenerCarga = async () => {
      let idFinal = docenteId;

      if (!idFinal && usuario) {
        try {
          const resCat = await fetch(`${API_BASE}/obtener_catalogos.php`);
          const dataCat = await resCat.json();
          if (dataCat.success && Array.isArray(dataCat.docentes)) {
            const encontrado = dataCat.docentes.find(
              (d) =>
                (d.nombre && d.nombre.toLowerCase() === usuario.toLowerCase()) ||
                (d.usuario && d.usuario.toLowerCase() === usuario.toLowerCase()) ||
                (d.email && d.email.toLowerCase() === usuario.toLowerCase())
            );
            if (encontrado) idFinal = encontrado.id;
          }
        } catch (err) {
          console.error("Error identificando al docente por catálogo:", err);
        }
      }

      if (!idFinal) {
        setCargaAcademica([]);
        setSeccionesDisponibles([]);
        setAsignaturasDisponibles([]);
        setSeccion('');
        setAsignatura('');
        return;
      }

      fetch(`${API_BASE}/carga_academica.php?docente_id=${idFinal}`)
        .then((res) => res.json())
        .then((data) => {
          if (data.success && Array.isArray(data.carga) && data.carga.length > 0) {
            setCargaAcademica(data.carga);

            const seccionesUnicas = [...new Set(data.carga.map((item) => item.seccion))];
            setSeccionesDisponibles(seccionesUnicas);

            const primeraSeccion = seccionesUnicas[0];
            setSeccion(primeraSeccion);

            const materiasPrimeraSec = data.carga
              .filter((item) => item.seccion === primeraSeccion)
              .map((item) => item.asignatura);

            setAsignaturasDisponibles(materiasPrimeraSec);
            if (materiasPrimeraSec.length > 0) {
              setAsignatura(materiasPrimeraSec[0]);
            }
          } else {
            setCargaAcademica([]);
            setSeccionesDisponibles([]);
            setAsignaturasDisponibles([]);
            setSeccion('');
            setAsignatura('');
          }
        })
        .catch((err) => console.error('Error al cargar la carga académica:', err));
    };

    identificarDocenteYObtenerCarga();
  }, [docenteId, usuario]);

  const handleSeccionChange = (nuevaSeccion) => {
    setSeccion(nuevaSeccion);
    const materiasDeSeccion = cargaAcademica
      .filter((item) => item.seccion === nuevaSeccion)
      .map((item) => item.asignatura);

    setAsignaturasDisponibles(materiasDeSeccion);
    if (materiasDeSeccion.length > 0) {
      setAsignatura(materiasDeSeccion[0]);
    } else {
      setAsignatura('');
    }
  };

  const cargarAsistencia = async () => {
    if (!seccion || !asignatura) {
      setAlumnos([]);
      return;
    }
    setCargando(true);
    try {
      const queryParams = new URLSearchParams({
        seccion: seccion,
        asignatura: asignatura,
        periodo: periodo,
        fecha: fecha
      });

      const res = await fetch(`${API_BASE}/asistencia.php?${queryParams.toString()}`);
      const data = await res.json();

      let lista = [];
      if (Array.isArray(data)) {
        lista = data;
      } else if (data && (data.alumnos || data.estudiantes || data.data)) {
        lista = data.alumnos || data.estudiantes || data.data || [];
      }

      setAlumnos(lista);
    } catch (error) {
      console.error('Error al consultar asistencia:', error);
      setAlumnos([]);
    } finally {
      setCargando(false);
    }
  };

  useEffect(() => {
    cargarAsistencia();
  }, [seccion, asignatura, periodo, fecha]);

  const handleAbrirModal = () => {
    const copiaInicial = alumnos.map((est) => ({
      estudiante_id: est.estudiante_id || est.id,
      nie: est.nie,
      apellidos: est.apellidos,
      nombres: est.nombres,
      asistencia: est.asistencia || est.estado || 'Asistió',
      inasistencia_por: est.inasistencia_por || '',
      observacion: est.observacion || ''
    }));
    setAlumnosModal(copiaInicial);
    setModalAbierto(true);
  };

  const handleCambioModal = (index, campo, valor) => {
    const listaActualizada = [...alumnosModal];
    listaActualizada[index][campo] = valor;

    if (campo === 'asistencia' && valor === 'Asistió') {
      listaActualizada[index].inasistencia_por = '';
      listaActualizada[index].observacion = '';
    }
    setAlumnosModal(listaActualizada);
  };

  const handleGuardarAsistencia = async () => {
    setGuardando(true);
    try {
      const payload = {
        fecha: fecha,
        seccion: seccion,
        asignatura: asignatura,
        periodo: periodo,
        asistencias: alumnosModal.map((est) => ({
          estudiante_id: est.estudiante_id,
          asistencia: est.asistencia,
          inasistencia_por: est.inasistencia_por,
          observacion: est.observacion
        }))
      };

      const res = await fetch(`${API_BASE}/guardar_asistencia.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });

      await res.json();
      setModalAbierto(false);
      await cargarAsistencia();
    } catch (error) {
      console.error('Error al guardar asistencia:', error);
      alert('Error al guardar la asistencia.');
    } finally {
      setGuardando(false);
    }
  };

  const obtenerEstilosBadge = (estado) => {
    switch (estado) {
      case 'Faltó':
        return { backgroundColor: '#fde8e8', color: '#9b1c1c' };
      case 'Permiso':
        return { backgroundColor: '#fef3c7', color: '#92400e' };
      case 'Incapacidad':
        return { backgroundColor: '#e0e7ff', color: '#3730a3' };
      case 'Tardía':
        return { backgroundColor: '#ffedd5', color: '#9a3412' };
      case 'Retirado':
        return { backgroundColor: '#f3f4f6', color: '#374151' };
      case 'Asistió':
      default:
        return { backgroundColor: '#d1fae5', color: '#065f46' };
    }
  };

  return (
    <div style={{ padding: '12px', maxWidth: '1200px', margin: '0 auto', fontFamily: 'system-ui, sans-serif' }}>
      {/* Panel de Filtros */}
      <div style={{ background: '#fff', border: '2px solid #00a8e8', borderRadius: '12px', padding: '16px', marginBottom: '20px', boxShadow: '0 4px 6px -1px rgba(0, 0, 0, 0.05)' }}>
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '16px', flexWrap: 'wrap', gap: '12px' }}>
          {/* BOTÓN ADAPTATIVO: Muestra 'Modificar' si ya hay registros */}
          <button
            onClick={handleAbrirModal}
            disabled={alumnos.length === 0}
            style={{
              backgroundColor: alumnos.length === 0 ? '#cccccc' : yaExisteAsistencia ? '#f59e0b' : '#00a8e8',
              color: '#ffffff',
              border: 'none',
              padding: '12px 24px',
              borderRadius: '25px',
              fontWeight: 'bold',
              fontSize: '15px',
              width: '100%',
              maxWidth: '280px',
              cursor: alumnos.length === 0 ? 'not-allowed' : 'pointer',
              transition: 'all 0.2s ease'
            }}
          >
            {yaExisteAsistencia ? '✏️ Modificar Asistencia' : '+ Tomar Asistencia'}
          </button>

          <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
            <label style={{ fontSize: '14px', fontWeight: 'bold', color: '#333' }}>Fecha:</label>
            <input
              type="date"
              value={fecha}
              onChange={(e) => setFecha(e.target.value)}
              style={{ border: '1px solid #ccc', borderRadius: '6px', padding: '8px 12px', fontSize: '14px' }}
            />
          </div>
        </div>

        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(180px, 1fr))', gap: '12px' }}>
          <div>
            <label style={{ display: 'block', fontSize: '11px', fontWeight: 'bold', color: '#555', marginBottom: '4px' }}>
              PERÍODO
            </label>
            <select
              value={periodo}
              onChange={(e) => setPeriodo(e.target.value)}
              style={{ width: '100%', border: '1px solid #ccc', borderRadius: '6px', padding: '8px', fontSize: '14px' }}
            >
              <option value="1">1° Período</option>
              <option value="2">2° Período</option>
              <option value="3">3° Período</option>
              <option value="4">4° Período</option>
            </select>
          </div>

          <div>
            <label style={{ display: 'block', fontSize: '11px', fontWeight: 'bold', color: '#555', marginBottom: '4px' }}>
              SECCIÓN
            </label>
            <select
              value={seccion}
              onChange={(e) => handleSeccionChange(e.target.value)}
              style={{ width: '100%', border: '1px solid #ccc', borderRadius: '6px', padding: '8px', fontSize: '14px' }}
            >
              {seccionesDisponibles.length === 0 ? (
                <option value="">Sin secciones asignadas</option>
              ) : (
                seccionesDisponibles.map((sec) => (
                  <option key={sec} value={sec}>{sec}</option>
                ))
              )}
            </select>
          </div>

          <div>
            <label style={{ display: 'block', fontSize: '11px', fontWeight: 'bold', color: '#555', marginBottom: '4px' }}>
              ASIGNATURA
            </label>
            <select
              value={asignatura}
              onChange={(e) => setAsignatura(e.target.value)}
              style={{ width: '100%', border: '1px solid #ccc', borderRadius: '6px', padding: '8px', fontSize: '14px' }}
            >
              {asignaturasDisponibles.length === 0 ? (
                <option value="">Sin asignaturas</option>
              ) : (
                asignaturasDisponibles.map((asig) => (
                  <option key={asig} value={asig}>{asig}</option>
                ))
              )}
            </select>
          </div>
        </div>
      </div>

      {/* Lista Principal de Alumnos */}
      {cargando ? (
        <div style={{ padding: '30px', textAlign: 'center', color: '#666', background: '#fff', borderRadius: '8px' }}>
          Cargando registros...
        </div>
      ) : alumnos.length === 0 ? (
        <div style={{ padding: '24px', textAlign: 'center', color: '#999', background: '#fff', borderRadius: '8px' }}>
          No hay registros disponibles para la selección actual.
        </div>
      ) : (
        <div style={{ background: '#fff', border: '1px solid #e0e0e0', borderRadius: '8px', overflow: 'hidden' }}>
          {/* VISTA MÓVIL */}
          <div className="d-block d-md-none">
            {alumnos.map((est, idx) => {
              const estadoActual = est.asistencia || est.estado || 'Asistió';
              const estiloBadge = obtenerEstilosBadge(estadoActual);
              return (
                <div
                  key={est.estudiante_id || est.id || idx}
                  style={{
                    padding: '14px',
                    borderBottom: '1px solid #eee',
                    display: 'flex',
                    justifyContent: 'space-between',
                    alignItems: 'center'
                  }}
                >
                  <div>
                    <div style={{ fontSize: '11px', color: '#777', marginBottom: '2px' }}>
                      #{idx + 1} | NIE: {est.nie}
                    </div>
                    <div style={{ fontWeight: 'bold', fontSize: '14px', color: '#111' }}>
                      {est.apellidos}
                    </div>
                    <div style={{ fontSize: '13px', color: '#444' }}>
                      {est.nombres}
                    </div>
                  </div>
                  <div>
                    <span
                      style={{
                        padding: '6px 14px',
                        borderRadius: '16px',
                        fontSize: '12px',
                        fontWeight: 'bold',
                        display: 'inline-block',
                        ...estiloBadge
                      }}
                    >
                      {estadoActual}
                    </span>
                  </div>
                </div>
              );
            })}
          </div>

          {/* VISTA ESCRITORIO */}
          <div className="d-none d-md-block" style={{ overflowX: 'auto' }}>
            <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: '14px', textAlign: 'left' }}>
              <thead>
                <tr style={{ borderBottom: '2px solid #e0e0e0', background: '#fafafa', color: '#333' }}>
                  <th style={{ padding: '12px', textAlign: 'center', width: '50px' }}>#</th>
                  <th style={{ padding: '12px', width: '120px' }}>NIE</th>
                  <th style={{ padding: '12px' }}>APELLIDOS</th>
                  <th style={{ padding: '12px' }}>NOMBRES</th>
                  <th style={{ padding: '12px', textAlign: 'center', width: '120px' }}>
                    {fecha.split('-').reverse().slice(0, 2).join('/')}
                  </th>
                </tr>
              </thead>
              <tbody>
                {alumnos.map((est, idx) => {
                  const estadoActual = est.asistencia || est.estado || 'Asistió';
                  const estiloBadge = obtenerEstilosBadge(estadoActual);
                  return (
                    <tr key={est.estudiante_id || est.id || idx} style={{ borderBottom: '1px solid #eee' }}>
                      <td style={{ padding: '12px', textAlign: 'center', color: '#888' }}>{idx + 1}</td>
                      <td style={{ padding: '12px', color: '#444' }}>{est.nie}</td>
                      <td style={{ padding: '12px', fontWeight: 'bold', color: '#111' }}>{est.apellidos}</td>
                      <td style={{ padding: '12px', color: '#333' }}>{est.nombres}</td>
                      <td style={{ padding: '12px', textAlign: 'center' }}>
                        <span
                          style={{
                            padding: '4px 12px',
                            borderRadius: '12px',
                            fontSize: '12px',
                            fontWeight: 'bold',
                            ...estiloBadge
                          }}
                        >
                          {estadoActual}
                        </span>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* Modal para Tomar / Modificar Asistencia */}
      {modalAbierto && (
        <div
          style={{
            position: 'fixed',
            top: 0,
            left: 0,
            width: '100vw',
            height: '100vh',
            backgroundColor: 'rgba(0, 0, 0, 0.5)',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            zIndex: 9999,
            padding: '10px'
          }}
        >
          <div
            style={{
              background: '#fff',
              borderRadius: '12px',
              width: '100%',
              maxWidth: '950px',
              maxHeight: '90vh',
              display: 'flex',
              flexDirection: 'column',
              boxShadow: '0 10px 25px rgba(0,0,0,0.2)',
              overflow: 'hidden'
            }}
          >
            <div style={{ padding: '16px 20px', borderBottom: '1px solid #eee', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
              <h3 style={{ margin: 0, fontSize: '18px', fontWeight: 'bold' }}>
                {yaExisteAsistencia ? '✏️ Modificar Registro de Asistencia' : '📝 Tomar Asistencia Diaria'}
              </h3>
              <button
                onClick={() => setModalAbierto(false)}
                style={{ background: 'none', border: 'none', fontSize: '24px', cursor: 'pointer', color: '#888' }}
              >
                &times;
              </button>
            </div>

            <div style={{ padding: '12px 20px', background: '#fafafa', borderBottom: '1px solid #eee', display: 'flex', alignItems: 'center', gap: '8px' }}>
              <span style={{ fontSize: '14px', fontWeight: 'bold' }}>Fecha seleccionada:</span>
              <input
                type="date"
                value={fecha}
                onChange={(e) => setFecha(e.target.value)}
                style={{ border: '1px solid #ccc', borderRadius: '4px', padding: '6px 10px' }}
              />
            </div>

            <div style={{ padding: '12px', overflowY: 'auto', flex: 1 }}>
              {/* Tarjetas dentro del Modal para Móviles */}
              <div className="d-block d-md-none">
                {alumnosModal.map((est, idx) => (
                  <div
                    key={est.estudiante_id || idx}
                    style={{
                      background: '#f9fafb',
                      border: '1px solid #e5e7eb',
                      borderRadius: '8px',
                      padding: '12px',
                      marginBottom: '10px'
                    }}
                  >
                    <div style={{ fontWeight: 'bold', fontSize: '14px', color: '#111' }}>
                      #{idx + 1} - {est.apellidos}, {est.nombres}
                    </div>
                    <div style={{ fontSize: '11px', color: '#666', marginBottom: '8px' }}>
                      NIE: {est.nie}
                    </div>

                    <div style={{ display: 'flex', flexDirection: 'column', gap: '8px' }}>
                      <div>
                        <label style={{ fontSize: '11px', fontWeight: 'bold', display: 'block', marginBottom: '2px' }}>
                          ESTADO:
                        </label>
                        <select
                          value={est.asistencia}
                          onChange={(e) => handleCambioModal(idx, 'asistencia', e.target.value)}
                          style={{ width: '100%', padding: '6px', border: '1px solid #ccc', borderRadius: '4px' }}
                        >
                          {OPCIONES_ESTADO.map((opt) => (
                            <option key={opt} value={opt}>{opt}</option>
                          ))}
                        </select>
                      </div>

                      {est.asistencia !== 'Asistió' && (
                        <>
                          <div>
                            <label style={{ fontSize: '11px', fontWeight: 'bold', display: 'block', marginBottom: '2px' }}>
                              MOTIVO:
                            </label>
                            <select
                              value={est.inasistencia_por}
                              onChange={(e) => handleCambioModal(idx, 'inasistencia_por', e.target.value)}
                              style={{ width: '100%', padding: '6px', border: '1px solid #ccc', borderRadius: '4px' }}
                            >
                              <option value="">-- Seleccionar --</option>
                              {OPCIONES_MOTIVO.map((opt) => (
                                <option key={opt} value={opt}>{opt}</option>
                              ))}
                            </select>
                          </div>

                          <div>
                            <label style={{ fontSize: '11px', fontWeight: 'bold', display: 'block', marginBottom: '2px' }}>
                              OBSERVACIÓN:
                            </label>
                            <input
                              type="text"
                              placeholder="Escribe una observación"
                              value={est.observacion}
                              onChange={(e) => handleCambioModal(idx, 'observacion', e.target.value)}
                              style={{ width: '100%', padding: '6px', border: '1px solid #ccc', borderRadius: '4px' }}
                            />
                          </div>
                        </>
                      )}
                    </div>
                  </div>
                ))}
              </div>

              {/* Tabla dentro del Modal para PC */}
              <div className="d-none d-md-block">
                <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: '13px', textAlign: 'left' }}>
                  <thead>
                    <tr style={{ background: '#1d4ed8', color: '#fff' }}>
                      <th style={{ padding: '10px', textAlign: 'center', width: '30px' }}>#</th>
                      <th style={{ padding: '10px' }}>NIE</th>
                      <th style={{ padding: '10px' }}>APELLIDOS</th>
                      <th style={{ padding: '10px' }}>NOMBRES</th>
                      <th style={{ padding: '10px' }}>ESTADO</th>
                      <th style={{ padding: '10px' }}>INASISTENCIA POR</th>
                      <th style={{ padding: '10px' }}>OBSERVACIÓN</th>
                    </tr>
                  </thead>
                  <tbody>
                    {alumnosModal.map((est, idx) => (
                      <tr key={est.estudiante_id || idx} style={{ borderBottom: '1px solid #eee' }}>
                        <td style={{ padding: '8px', textAlign: 'center', color: '#777' }}>{idx + 1}</td>
                        <td style={{ padding: '8px', color: '#555' }}>{est.nie}</td>
                        <td style={{ padding: '8px', fontWeight: 'bold', color: '#111' }}>{est.apellidos}</td>
                        <td style={{ padding: '8px', color: '#333' }}>{est.nombres}</td>
                        <td style={{ padding: '8px' }}>
                          <select
                            value={est.asistencia}
                            onChange={(e) => handleCambioModal(idx, 'asistencia', e.target.value)}
                            style={{ padding: '4px 6px', border: '1px solid #ccc', borderRadius: '4px' }}
                          >
                            {OPCIONES_ESTADO.map((opt) => (
                              <option key={opt} value={opt}>{opt}</option>
                            ))}
                          </select>
                        </td>
                        <td style={{ padding: '8px' }}>
                          <select
                            disabled={est.asistencia === 'Asistió'}
                            value={est.inasistencia_por}
                            onChange={(e) => handleCambioModal(idx, 'inasistencia_por', e.target.value)}
                            style={{ padding: '4px 6px', border: '1px solid #ccc', borderRadius: '4px' }}
                          >
                            <option value="">-- Seleccionar --</option>
                            {OPCIONES_MOTIVO.map((opt) => (
                              <option key={opt} value={opt}>{opt}</option>
                            ))}
                          </select>
                        </td>
                        <td style={{ padding: '8px' }}>
                          <input
                            type="text"
                            disabled={est.asistencia === 'Asistió'}
                            placeholder="Escribe una observación"
                            value={est.observacion}
                            onChange={(e) => handleCambioModal(idx, 'observacion', e.target.value)}
                            style={{ padding: '4px 6px', border: '1px solid #ccc', borderRadius: '4px', width: '100%' }}
                          />
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>

            <div style={{ padding: '16px 20px', borderTop: '1px solid #eee', display: 'flex', justify: 'flex-end', gap: '10px', background: '#fafafa' }}>
              <button
                onClick={() => setModalAbierto(false)}
                style={{ padding: '10px 18px', border: '1px solid #ccc', background: '#fff', borderRadius: '6px', cursor: 'pointer' }}
              >
                Cancelar
              </button>
              <button
                onClick={handleGuardarAsistencia}
                disabled={guardando}
                style={{ padding: '10px 22px', border: 'none', background: '#1d4ed8', color: '#fff', borderRadius: '6px', fontWeight: 'bold', cursor: 'pointer' }}
              >
                {guardando ? 'Guardando...' : 'Actualizar Asistencia'}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default TablaAsistencia;