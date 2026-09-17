import React, { useState, useEffect } from 'react';
import { API_BASE } from '../config';

export default function GestionDocentes() {
  const [docentes, setDocentes] = useState([]);
  const [id, setId] = useState(0);
  const [nombre, setNombre] = useState('');
  const [usuario, setUsuario] = useState('');
  const [password, setPassword] = useState('');
  const [mensaje, setMensaje] = useState({ tipo: '', texto: '' });

  const cargarDocentes = async () => {
    try {
      const res = await fetch(`${API_BASE}/obtener_catalogos.php`);
      const data = await res.json();
      if (data.success) {
        setDocentes(data.docentes || []);
      }
    } catch (error) {
      console.error('Error al cargar docentes:', error);
    }
  };

  useEffect(() => {
    cargarDocentes();
  }, []);

  const handleGuardar = async (e) => {
    e.preventDefault();
    if (!nombre || !usuario) {
      setMensaje({ tipo: 'warning', texto: 'Nombre y Correo/Usuario son requeridos.' });
      return;
    }

    try {
      const res = await fetch(`${API_BASE}/gestion_docentes.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          action: 'guardar',
          id,
          nombre,
          usuario,
          password
        })
      });

      const data = await res.json();
      if (data.success) {
        setMensaje({ tipo: 'success', texto: data.message });
        limpiarFormulario();
        cargarDocentes();
      } else {
        setMensaje({ tipo: 'danger', texto: data.message });
      }
    } catch (error) {
      console.error('Error al guardar docente:', error);
      setMensaje({ tipo: 'danger', texto: 'Error de red al procesar la solicitud.' });
    }
  };

  const handleEditar = (docente) => {
    setId(docente.id);
    setNombre(docente.nombre);
    setUsuario(docente.email || docente.usuario || '');
    setPassword('');
  };

  const handleEliminar = async (docenteId) => {
    if (!window.confirm('¿Seguro que deseas eliminar este docente?')) return;

    try {
      const res = await fetch(`${API_BASE}/gestion_docentes.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          action: 'eliminar',
          id: docenteId
        })
      });

      const data = await res.json();
      if (data.success) {
        setMensaje({ tipo: 'success', texto: data.message });
        cargarDocentes();
      } else {
        setMensaje({ tipo: 'danger', texto: data.message });
      }
    } catch (error) {
      console.error('Error al eliminar docente:', error);
      setMensaje({ tipo: 'danger', texto: 'No se pudo eliminar el docente.' });
    }
  };

  const limpiarFormulario = () => {
    setId(0);
    setNombre('');
    setUsuario('');
    setPassword('');
  };

  return (
    <div className="app-main-container">
      <div className="panel-card">
        <h5 className="fw-bold text-secondary mb-3">Gestión de Docentes</h5>

        {mensaje.texto && (
          <div className={`alert alert-${mensaje.tipo} alert-dismissible fade show`} role="alert">
            {mensaje.texto}
            <button type="button" className="btn-close" onClick={() => setMensaje({ tipo: '', texto: '' })}></button>
          </div>
        )}

        <form onSubmit={handleGuardar} className="row g-2 mb-4">
          <div className="col-md-4">
            <input
              type="text"
              className="form-control"
              placeholder="Nombre del Docente"
              value={nombre}
              onChange={(e) => setNombre(e.target.value)}
            />
          </div>
          <div className="col-md-3">
            <input
              type="email"
              className="form-control"
              placeholder="admin@escuela.edu"
              value={usuario}
              onChange={(e) => setUsuario(e.target.value)}
            />
          </div>
          <div className="col-md-3">
            <input
              type="password"
              className="form-control"
              placeholder={id > 0 ? '•••••• (Opcional)' : 'Contraseña'}
              value={password}
              onChange={(e) => setPassword(e.target.value)}
            />
          </div>
          <div className="col-md-2 d-flex gap-1">
            <button type="submit" className="btn btn-info text-white fw-bold w-100">
              {id > 0 ? 'Actualizar' : 'Agregar'}
            </button>
            {id > 0 && (
              <button type="button" className="btn btn-secondary" onClick={limpiarFormulario}>
                Cancel
              </button>
            )}
          </div>
        </form>

        <div className="table-responsive">
          <table className="table table-bordered align-middle text-center">
            <thead className="table-dark">
              <tr>
                <th style={{ width: '10%' }}>ID</th>
                <th style={{ width: '40%' }}>NOMBRE</th>
                <th style={{ width: '30%' }}>USUARIO</th>
                <th style={{ width: '20%' }}>ACCIONES</th>
              </tr>
            </thead>
            <tbody>
              {docentes.length === 0 ? (
                <tr>
                  <td colSpan="4" className="text-muted">No hay docentes registrados.</td>
                </tr>
              ) : (
                docentes.map((doc) => (
                  <tr key={doc.id}>
                    <td>{doc.id}</td>
                    <td className="text-start ps-3">{doc.nombre}</td>
                    <td className="text-start ps-3">{doc.email || doc.usuario}</td>
                    <td>
                      <button className="btn btn-outline-secondary btn-sm me-2" onClick={() => handleEditar(doc)}>
                        Editar
                      </button>
                      <button className="btn btn-danger btn-sm" onClick={() => handleEliminar(doc.id)}>
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