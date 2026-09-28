import React, { useState, useEffect } from 'react';
import { API_BASE } from '../config';

const CargaAcademica = () => {
  const [docentes, setDocentes] = useState([]);
  const [secciones, setSecciones] = useState([]);
  const [asignaturas, setAsignaturas] = useState([]);

  const [docenteId, setDocenteId] = useState('');
  const [seccionId, setSeccionId] = useState('');
  const [asignaturaId, setAsignaturaId] = useState('');

  const [mensaje, setMensaje] = useState({ texto: '', tipo: '' });
  const [cargando, setCargando] = useState(false);

  // Cargar catálogo de docentes, secciones y asignaturas
  useEffect(() => {
    fetch(`${API_BASE}/obtener_catalogos.php`)
      .then((res) => res.json())
      .then((data) => {
        if (data.success) {
          setDocentes(data.docentes || []);
          setSecciones(data.secciones || []);
          setAsignaturas(data.asignaturas || []);
        } else {
          setMensaje({ texto: 'No se pudieron cargar los datos de los catálogos.', tipo: 'danger' });
        }
      })
      .catch((err) => {
        console.error('Error al cargar catálogos:', err);
        setMensaje({ texto: 'Error de conexión al obtener los catálogos.', tipo: 'danger' });
      });
  }, []);

  const handleAsignar = (e) => {
    e.preventDefault();
    if (!docenteId || !seccionId || !asignaturaId) {
      setMensaje({ texto: 'Por favor completa todos los campos del formulario.', tipo: 'warning' });
      return;
    }

    setCargando(true);
    setMensaje({ texto: '', tipo: '' });

    // Corrección de endpoint: 'guardar_carga.php' + conversión a entero (parseInt)
    fetch(`${API_BASE}/guardar_carga.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        docente_id: parseInt(docenteId, 10),
        seccion_id: parseInt(seccionId, 10),
        asignatura_id: parseInt(asignaturaId, 10),
      }),
    })
      .then((res) => res.json())
      .then((data) => {
        if (data.success) {
          setMensaje({ texto: data.message || 'Carga académica asignada exitosamente.', tipo: 'success' });
          setDocenteId('');
          setSeccionId('');
          setAsignaturaId('');
        } else {
          setMensaje({ texto: data.message || 'No se pudo asignar la carga académica.', tipo: 'danger' });
        }
      })
      .catch((err) => {
        console.error('Error al asignar carga:', err);
        setMensaje({ texto: 'Error de conexión con el servidor al asignar.', tipo: 'danger' });
      })
      .finally(() => setCargando(false));
  };

  return (
    <div className="panel-card bg-white p-4 rounded shadow-sm">
      <h4 className="fw-bold text-primary mb-2">📝 Asignar Carga Académica</h4>
      <p className="text-muted small mb-4">
        Asocia un docente con una asignatura y una sección específica.
      </p>

      {mensaje.texto && (
        <div className={`alert alert-${mensaje.tipo} alert-dismissible fade show`} role="alert">
          {mensaje.texto}
          <button type="button" className="btn-close" onClick={() => setMensaje({ texto: '', tipo: '' })}></button>
        </div>
      )}

      <form onSubmit={handleAsignar} className="row g-3">
        {/* Selección de Docente */}
        <div className="col-md-4">
          <label className="form-label fw-bold small text-secondary">DOCENTE</label>
          <select
            className="form-select"
            value={docenteId}
            onChange={(e) => setDocenteId(e.target.value)}
            required
          >
            <option value="">-- Seleccionar Docente --</option>
            {docentes.map((doc) => (
              <option key={doc.id} value={doc.id}>
                {doc.nombre || doc.nombre_completo}
              </option>
            ))}
          </select>
        </div>

        {/* Selección de Sección */}
        <div className="col-md-4">
          <label className="form-label fw-bold small text-secondary">SECCIÓN</label>
          <select
            className="form-select"
            value={seccionId}
            onChange={(e) => setSeccionId(e.target.value)}
            required
          >
            <option value="">-- Seleccionar Sección --</option>
            {secciones.map((sec) => (
              <option key={sec.id} value={sec.id}>
                {sec.nombre}
              </option>
            ))}
          </select>
        </div>

        {/* Selección de Asignatura */}
        <div className="col-md-4">
          <label className="form-label fw-bold small text-secondary">ASIGNATURA</label>
          <select
            className="form-select"
            value={asignaturaId}
            onChange={(e) => setAsignaturaId(e.target.value)}
            required
          >
            <option value="">-- Seleccionar Asignatura --</option>
            {asignaturas.map((asig) => (
              <option key={asig.id} value={asig.id}>
                {asig.nombre}
              </option>
            ))}
          </select>
        </div>

        <div className="col-12 text-end mt-4">
          <button type="submit" disabled={cargando} className="btn btn-primary px-4 fw-bold">
            {cargando ? 'Guardando...' : '➕ Asignar Carga'}
          </button>
        </div>
      </form>
    </div>
  );
};

export default CargaAcademica;