import React, { useState, useEffect } from 'react';
import { API_BASE } from '../config';

const GestionSecciones = () => {
  const [secciones, setSecciones] = useState([]);
  const [nuevaSeccion, setNuevaSeccion] = useState('');
  const [archivo, setArchivo] = useState(null);
  const [seccionSeleccionada, setSeccionSeleccionada] = useState('');
  const [procesando, setProcesando] = useState(false);
  const [mensaje, setMensaje] = useState({ texto: '', tipo: '' });

  const cargarSecciones = () => {
    fetch(`${API_BASE}/obtener_catalogos.php`)
      .then((res) => res.json())
      .then((data) => {
        if (data.success) {
          setSecciones(data.secciones || []);
        } else {
          setMensaje({ texto: 'No se pudieron cargar las secciones.', tipo: 'danger' });
        }
      })
      .catch((err) => {
        console.error("Error al cargar secciones:", err);
        setMensaje({ texto: 'Error de conexión al obtener secciones.', tipo: 'danger' });
      });
  };

  useEffect(() => {
    cargarSecciones();
  }, []);

  const handleCrearSeccion = (e) => {
    e.preventDefault();
    if (!nuevaSeccion.trim()) {
      setMensaje({ texto: 'Escribe el nombre de la sección.', tipo: 'warning' });
      return;
    }

    setProcesando(true);
    setMensaje({ texto: '', tipo: '' });

    fetch(`${API_BASE}/gestion_secciones.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'crear_seccion', nombre: nuevaSeccion.trim() }),
    })
      .then((res) => res.json())
      .then((data) => {
        if (data.success) {
          setMensaje({ texto: data.message || 'Sección creada con éxito.', tipo: 'success' });
          setNuevaSeccion('');
          cargarSecciones();
        } else {
          setMensaje({ texto: data.message || 'No se pudo crear la sección.', tipo: 'danger' });
        }
      })
      .catch((err) => {
        console.error("Error al crear sección:", err);
        setMensaje({ texto: 'Error de red al crear la sección.', tipo: 'danger' });
      })
      .finally(() => setProcesando(false));
  };

  const handleSubirCSV = (e) => {
    e.preventDefault();
    if (!archivo) {
      setMensaje({ texto: 'Por favor selecciona un archivo CSV.', tipo: 'warning' });
      return;
    }
    if (!seccionSeleccionada) {
      setMensaje({ texto: 'Por favor selecciona la sección destino.', tipo: 'warning' });
      return;
    }

    setProcesando(true);
    setMensaje({ texto: '', tipo: '' });

    const formData = new FormData();
    formData.append('archivo', archivo);
    formData.append('seccion', seccionSeleccionada);

    fetch(`${API_BASE}/insertar_alumnos.php`, {
      method: 'POST',
      body: formData,
    })
      .then((res) => res.json())
      .then((data) => {
        if (data.success) {
          setMensaje({ texto: data.message || 'Carga de estudiantes completada.', tipo: 'success' });
          setArchivo(null);
          setSeccionSeleccionada('');
          const input = document.getElementById('input-file-csv');
          if (input) input.value = '';
          cargarSecciones();
        } else {
          setMensaje({ texto: data.message || 'No se pudo procesar el archivo CSV.', tipo: 'danger' });
        }
      })
      .catch((err) => {
        console.error("Error al subir CSV:", err);
        setMensaje({ texto: 'Error de conexión al subir el archivo.', tipo: 'danger' });
      })
      .finally(() => setProcesando(false));
  };

  const vaciarAlumnos = (seccionId, nombreSeccion) => {
    if (!window.confirm(`¿Seguro que deseas vaciar los alumnos de '${nombreSeccion}'?`)) return;

    fetch(`${API_BASE}/gestion_secciones.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'vaciar_alumnos', seccion_id: seccionId }),
    })
      .then((res) => res.json())
      .then((data) => {
        if (data.success) {
          setMensaje({ texto: data.message || 'Alumnos eliminados de la sección.', tipo: 'success' });
          cargarSecciones();
        } else {
          setMensaje({ texto: data.message || 'No se pudo vaciar la sección.', tipo: 'danger' });
        }
      })
      .catch((err) => {
        console.error("Error al vaciar alumnos:", err);
        setMensaje({ texto: 'Error de red al vaciar la sección.', tipo: 'danger' });
      });
  };

  return (
    <div className="container-fluid max-w-6xl p-0">
      {/* Encabezado del módulo */}
      <div className="mb-4">
        <h4 className="fw-bold text-dark mb-1" style={{ letterSpacing: '-0.5px' }}>Gestión de Secciones</h4>
        <p className="text-secondary small mb-0">Administra las secciones del ciclo e importa las nóminas de alumnos vía CSV.</p>
      </div>

      {mensaje.texto && (
        <div className={`alert alert-${mensaje.tipo === 'success' ? 'success' : 'danger'} alert-dismissible border-0 shadow-sm rounded-3 fade show small`} role="alert">
          {mensaje.texto}
          <button type="button" className="btn-close" onClick={() => setMensaje({ texto: '', tipo: '' })}></button>
        </div>
      )}

      {/* Grid de Formulario Modulares */}
      <div className="row g-4 mb-4">
        {/* Formulario Crear Sección */}
        <div className="col-lg-5">
          <div className="card border-0 shadow-sm rounded-4 p-4 h-100 bg-white">
            <div className="d-flex align-items-center gap-2 mb-3">
              <div className="p-2 bg-primary bg-opacity-10 text-primary rounded-3">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 4v16m8-8H4" />
                </svg>
              </div>
              <h6 className="fw-bold mb-0 text-dark">Nueva Sección</h6>
            </div>

            <form onSubmit={handleCrearSeccion} className="d-flex flex-column justify-content-between flex-grow-1">
              <div className="mb-3">
                <label className="form-label text-uppercase text-secondary fw-semibold" style={{ fontSize: '11px', letterSpacing: '0.5px' }}>
                  Nombre de la sección
                </label>
                <input
                  type="text"
                  className="form-control rounded-3 py-2 px-3 border-secondary border-opacity-25"
                  placeholder="Ej. 1° C Software"
                  value={nuevaSeccion}
                  onChange={(e) => setNuevaSeccion(e.target.value)}
                  style={{ fontSize: '0.9rem' }}
                  required
                />
              </div>
              <button
                type="submit"
                disabled={procesando}
                className="btn btn-primary rounded-3 py-2 fw-medium border-0 w-100 shadow-sm"
                style={{ backgroundColor: '#2563eb', fontSize: '0.9rem' }}
              >
                {procesando ? 'Guardando...' : 'Crear Sección'}
              </button>
            </form>
          </div>
        </div>

        {/* Formulario Cargar CSV */}
        <div className="col-lg-7">
          <div className="card border-0 shadow-sm rounded-4 p-4 h-100 bg-white">
            <div className="d-flex align-items-center gap-2 mb-3">
              <div className="p-2 bg-info bg-opacity-10 text-info rounded-3">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                </svg>
              </div>
              <h6 className="fw-bold mb-0 text-dark">Cargar Nómina de Estudiantes (CSV)</h6>
            </div>

            <form onSubmit={handleSubirCSV} className="row g-3 align-items-end">
              <div className="col-md-6">
                <label className="form-label text-uppercase text-secondary fw-semibold" style={{ fontSize: '11px', letterSpacing: '0.5px' }}>
                  Sección Destino
                </label>
                <select
                  className="form-select rounded-3 py-2 border-secondary border-opacity-25"
                  value={seccionSeleccionada}
                  onChange={(e) => setSeccionSeleccionada(e.target.value)}
                  style={{ fontSize: '0.9rem' }}
                  required
                >
                  <option value="">-- Seleccionar --</option>
                  {secciones.map((sec) => (
                    <option key={sec.id} value={sec.nombre}>{sec.nombre}</option>
                  ))}
                </select>
              </div>

              <div className="col-md-6">
                <label className="form-label text-uppercase text-secondary fw-semibold" style={{ fontSize: '11px', letterSpacing: '0.5px' }}>
                  Archivo .CSV
                </label>
                <input
                  id="input-file-csv"
                  type="file"
                  accept=".csv"
                  className="form-control rounded-3 py-2 border-secondary border-opacity-25"
                  onChange={(e) => setArchivo(e.target.files[0])}
                  style={{ fontSize: '0.85rem' }}
                  required
                />
              </div>

              <div className="col-12 text-end mt-3">
                <button
                  type="submit"
                  disabled={procesando}
                  className="btn btn-dark rounded-3 px-4 py-2 fw-medium border-0 shadow-sm"
                  style={{ backgroundColor: '#0f172a', fontSize: '0.9rem' }}
                >
                  {procesando ? 'Procesando...' : 'Importar Alumnos'}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

      {/* Tabla Estilizada */}
      <div className="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
        <div className="px-4 py-3 border-bottom border-light d-flex align-items-center justify-content-between">
          <h6 className="fw-bold mb-0 text-dark">Secciones Registradas</h6>
          <span className="badge bg-light text-secondary border fw-normal rounded-pill px-3 py-1">
            {secciones.length} Total
          </span>
        </div>

        <div className="table-responsive">
          <table className="table table-hover align-middle mb-0">
            <thead style={{ backgroundColor: '#f8fafc', fontSize: '11px', color: '#64748b' }}>
              <tr>
                <th className="ps-4 py-3 text-uppercase fw-bold" style={{ width: '80px' }}>ID</th>
                <th className="py-3 text-uppercase fw-bold">Nombre de Sección</th>
                <th className="pe-4 py-3 text-uppercase fw-bold text-end" style={{ width: '180px' }}>Acciones</th>
              </tr>
            </thead>
            <tbody style={{ fontSize: '0.9rem' }}>
              {secciones.length === 0 ? (
                <tr>
                  <td colSpan="3" className="text-center text-secondary py-4">
                    No hay secciones registradas.
                  </td>
                </tr>
              ) : (
                secciones.map((sec, index) => (
                  <tr key={sec.id || index}>
                    <td className="ps-4 fw-medium text-secondary">{sec.id || index + 1}</td>
                    <td className="fw-semibold text-dark">{sec.nombre}</td>
                    <td className="pe-4 text-end">
                      <button 
                        onClick={() => vaciarAlumnos(sec.id, sec.nombre)} 
                        className="btn btn-outline-danger btn-sm rounded-2 px-3 fw-medium border-opacity-50"
                        style={{ fontSize: '0.8rem' }}
                      >
                        Vaciar Lista
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
};

export default GestionSecciones;