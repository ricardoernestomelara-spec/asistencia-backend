import React, { useState } from 'react';
import { API_BASE } from '../config';

export default function CargaMasivaModal({ secciones = [], onClose, onSuccess }) {
  const [seccionSel, setSeccionSel] = useState('');
  const [archivo, setArchivo] = useState(null);
  const [cargando, setCargando] = useState(false);
  const [mensaje, setMensaje] = useState({ tipo: '', texto: '' });

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!seccionSel || !archivo) {
      setMensaje({ tipo: 'warning', texto: 'Por favor selecciona la sección y el archivo CSV.' });
      return;
    }

    const formData = new FormData();
    formData.append('seccion', seccionSel);
    formData.append('archivo', archivo);

    setCargando(true);
    setMensaje({ tipo: '', texto: '' });

    try {
      const res = await fetch(`${API_BASE}/insertar_alumnos.php`, {
        method: 'POST',
        body: formData,
      });

      const data = await res.json();
      if (data.success) {
        setMensaje({ tipo: 'success', texto: data.message });
        if (onSuccess) onSuccess();
      } else {
        setMensaje({ tipo: 'danger', texto: data.message || 'Error al procesar el archivo.' });
      }
    } catch (err) {
      setMensaje({ tipo: 'danger', texto: 'Error de red al conectar con el servidor.' });
    } finally {
      setCargando(false);
    }
  };

  return (
    <div className="modal show d-block tab-modal" style={{ backgroundColor: 'rgba(0,0,0,0.5)' }}>
      <div className="modal-dialog modal-dialog-centered">
        <div className="modal-content">
          <div className="modal-header">
            <h5 className="modal-title fw-bold text-secondary">Carga Masiva de Alumnos (CSV)</h5>
            <button type="button" className="btn-close" onClick={onClose}></button>
          </div>
          <form onSubmit={handleSubmit}>
            <div className="modal-body">
              {mensaje.texto && (
                <div className={`alert alert-${mensaje.tipo} alert-dismissible fade show`} role="alert">
                  {mensaje.texto}
                  <button type="button" className="btn-close" onClick={() => setMensaje({ tipo: '', texto: '' })}></button>
                </div>
              )}

              <div className="mb-3">
                <label className="form-label fw-bold small text-muted">Sección Destino:</label>
                <select 
                  className="form-select" 
                  value={seccionSel} 
                  onChange={(e) => setSeccionSel(e.target.value)}
                  required
                >
                  <option value="">-- Seleccionar Sección --</option>
                  {secciones.map((sec) => (
                    <option key={sec.id || sec.nombre} value={sec.nombre}>{sec.nombre}</option>
                  ))}
                </select>
              </div>

              <div className="mb-3">
                <label className="form-label fw-bold small text-muted">Archivo Formateado (.csv):</label>
                <input 
                  type="file" 
                  className="form-control" 
                  accept=".csv" 
                  onChange={(e) => setArchivo(e.target.files[0])}
                  required 
                />
                <div className="form-text mt-2">
                  Estructura esperada del CSV: <code>NIE ; APELLIDOS ; NOMBRES</code>
                </div>
              </div>
            </div>
            <div className="modal-footer">
              <button type="button" className="btn btn-secondary" onClick={onClose}>Cancelar</button>
              <button type="submit" className="btn btn-info text-white fw-bold" disabled={cargando}>
                {cargando ? 'Procesando...' : 'Cargar Estudiantes'}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  );
}