import React, { useState, useEffect } from 'react';
import { API_BASE } from '../config';

export default function GestionCargaAdmin() {
  const [docentes, setDocentes] = useState([]);
  const [secciones, setSecciones] = useState([]);
  const [asignaturas, setAsignaturas] = useState([]);
  
  const [docenteSeleccionado, setDocenteSeleccionado] = useState('');
  const [seccionSeleccionada, setSeccionSeleccionada] = useState('');
  const [asignaturaSeleccionada, setAsignaturaSeleccionada] = useState('');
  
  const [cargaDocente, setCargaDocente] = useState([]);
  const [mensaje, setMensaje] = useState({ tipo: '', texto: '' });

  const cargarCatalogos = async () => {
    try {
      const res = await fetch(`${API_BASE}/obtener_catalogos.php`);
      const data = await res.json();
      if (data.success) {
        setDocentes(data.docentes || []);
        setSecciones(data.secciones || []);
        setAsignaturas(data.asignaturas || []);
      } else {
        setMensaje({ tipo: 'danger', texto: data.message || 'Error al obtener catálogos' });
      }
    } catch (error) {
      console.error('Error al cargar catálogos:', error);
      setMensaje({ tipo: 'danger', texto: 'No se pudo conectar con el servidor para obtener los catálogos.' });
    }
  };

  const cargarCargaDocente = async (docenteId) => {
    if (!docenteId) {
      setCargaDocente([]);
      return;
    }
    try {
      const res = await fetch(`${API_BASE}/carga_academica.php?docente_id=${docenteId}`);
      const data = await res.json();
      if (data.success) {
        setCargaDocente(data.carga || []);
      } else {
        setCargaDocente([]);
      }
    } catch (error) {
      console.error('Error al cargar asignaciones:', error);
    }
  };

  useEffect(() => {
    cargarCatalogos();
  }, []);

  const handleDocenteChange = (e) => {
    const id = e.target.value;
    setDocenteSeleccionado(id);
    cargarCargaDocente(id);
  };

  const handleAsignar = async (e) => {
    e.preventDefault();
    if (!docenteSeleccionado || !seccionSeleccionada || !asignaturaSeleccionada) {
      setMensaje({ tipo: 'warning', texto: 'Debes seleccionar docente, asignatura y sección.' });
      return;
    }

    // Obtenemos los objetos seleccionados para validar por nombre y por ID
    const asigObj = asignaturas.find(a => String(a.id) === String(asignaturaSeleccionada));
    const secObj = secciones.find(s => String(s.id) === String(seccionSeleccionada));

    // VALIDACIÓN LOCAL ANTI-DUPLICADOS
    const yaExiste = cargaDocente.some((item) => {
      const mismoId = String(item.asignatura_id) === String(asignaturaSeleccionada) && 
                      String(item.seccion_id) === String(seccionSeleccionada);
      const mismoNombre = asigObj && secObj && 
                          item.asignatura === asigObj.nombre && 
                          item.seccion === secObj.nombre;
      return mismoId || mismoNombre;
    });

    if (yaExiste) {
      setMensaje({ 
        tipo: 'warning', 
        texto: `La asignatura "${asigObj?.nombre || ''}" ya está asignada a la sección "${secObj?.nombre || ''}" para este docente.` 
      });
      return;
    }

    try {
      const res = await fetch(`${API_BASE}/guardar_carga.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          docente_id: parseInt(docenteSeleccionado),
          seccion_id: parseInt(seccionSeleccionada),
          asignatura_id: parseInt(asignaturaSeleccionada)
        })
      });

      const data = await res.json();
      if (data.success) {
        setMensaje({ tipo: 'success', texto: data.message });
        setSeccionSeleccionada('');
        setAsignaturaSeleccionada('');
        cargarCargaDocente(docenteSeleccionado);
      } else {
        setMensaje({ tipo: 'danger', texto: data.message });
      }
    } catch (error) {
      console.error('Error al asignar carga:', error);
      setMensaje({ tipo: 'danger', texto: 'Error de red al intentar asignar la carga.' });
    }
  };

  const handleEliminarCarga = async (cargaId) => {
    if (!window.confirm('¿Deseas eliminar esta asignación de materia?')) return;

    try {
      const res = await fetch(`${API_BASE}/eliminar_carga.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: cargaId })
      });

      const data = await res.json();
      if (data.success) {
        setMensaje({ tipo: 'success', texto: data.message });
        cargarCargaDocente(docenteSeleccionado);
      } else {
        setMensaje({ tipo: 'danger', texto: data.message });
      }
    } catch (error) {
      console.error('Error al eliminar asignación:', error);
      setMensaje({ tipo: 'danger', texto: 'Error al comunicarse con el servidor.' });
    }
  };

  return (
    <div className="app-main-container">
      <div className="panel-card">
        <h5 className="fw-bold text-secondary mb-3">Gestión de Carga Académica (Administrador)</h5>
        
        {mensaje.texto && (
          <div className={`alert alert-${mensaje.tipo} alert-dismissible fade show`} role="alert">
            {mensaje.texto}
            <button type="button" className="btn-close" onClick={() => setMensaje({ tipo: '', texto: '' })}></button>
          </div>
        )}

        <form onSubmit={handleAsignar} className="row g-3 align-items-end mb-4">
          <div className="col-md-4">
            <label className="form-label fw-bold small text-muted">Docente:</label>
            <select className="form-select" value={docenteSeleccionado} onChange={handleDocenteChange}>
              <option value="">-- Seleccionar Docente --</option>
              {docentes.map((doc) => (
                <option key={doc.id} value={doc.id}>{doc.nombre}</option>
              ))}
            </select>
          </div>

          <div className="col-md-3">
            <label className="form-label fw-bold small text-muted">Asignatura / Módulo:</label>
            <select className="form-select" value={asignaturaSeleccionada} onChange={(e) => setAsignaturaSeleccionada(e.target.value)}>
              <option value="">-- Seleccionar Asignatura --</option>
              {asignaturas.map((asig) => (
                <option key={asig.id} value={asig.id}>{asig.nombre}</option>
              ))}
            </select>
          </div>

          <div className="col-md-3">
            <label className="form-label fw-bold small text-muted">Sección:</label>
            <select className="form-select" value={seccionSeleccionada} onChange={(e) => setSeccionSeleccionada(e.target.value)}>
              <option value="">-- Seleccionar Sección --</option>
              {secciones.map((sec) => (
                <option key={sec.id} value={sec.id}>{sec.nombre}</option>
              ))}
            </select>
          </div>

          <div className="col-md-2">
            <button type="submit" className="btn btn-info text-white w-100 fw-bold">
              Asignar Carga
            </button>
          </div>
        </form>

        <h6 className="fw-bold text-secondary border-bottom pb-2 mb-3">Carga Asignada Actualmente</h6>
        <div className="table-responsive">
          <table className="table table-bordered align-middle text-center">
            <thead className="table-dark">
              <tr>
                <th style={{ width: '50%' }}>ASIGNATURA</th>
                <th style={{ width: '30%' }}>SECCIÓN</th>
                <th style={{ width: '20%' }}>ACCIÓN</th>
              </tr>
            </thead>
            <tbody>
              {!docenteSeleccionado ? (
                <tr>
                  <td colSpan="3" className="text-muted">Selecciona un docente para ver su carga asignada</td>
                </tr>
              ) : cargaDocente.length === 0 ? (
                <tr>
                  <td colSpan="3" className="text-muted">El docente seleccionado no tiene carga académica registrada.</td>
                </tr>
              ) : (
                cargaDocente.map((item) => (
                  <tr key={item.id}>
                    <td className="text-start ps-3">{item.asignatura}</td>
                    <td>{item.seccion}</td>
                    <td>
                      <button className="btn btn-danger btn-sm" onClick={() => handleEliminarCarga(item.id)}>
                        Eliminar
                      </button>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}