import React, { useState, useEffect } from 'react';
import { API_BASE } from '../config';

const GestionAsignaturas = () => {
  const [asignaturas, setAsignaturas] = useState([]);
  const [id, setId] = useState(null);
  const [codigo, setCodigo] = useState('');
  const [nombre, setNombre] = useState('');
  const [mensaje, setMensaje] = useState({ texto: '', tipo: '' });
  const [cargando, setCargando] = useState(false);

  const cargarAsignaturas = () => {
    fetch(`${API_BASE}/obtener_catalogos.php`)
      .then((res) => res.json())
      .then((data) => {
        if (data.success) {
          setAsignaturas(data.asignaturas || []);
        }
      })
      .catch((err) => console.error("Error al cargar asignaturas:", err));
  };

  useEffect(() => {
    cargarAsignaturas();
  }, []);

  const handleSubmit = (e) => {
    e.preventDefault();
    if (!codigo.trim() || !nombre.trim()) return;

    setCargando(true);
    setMensaje({ texto: '', tipo: '' });

    fetch(`${API_BASE}/gestion_asignaturas.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'guardar', id, codigo, nombre }),
    })
      .then((res) => res.json())
      .then((data) => {
        setCargando(false);
        if (data.success) {
          setMensaje({ texto: data.message, tipo: 'exito' });
          limpiarFormulario();
          cargarAsignaturas();
        } else {
          setMensaje({ texto: data.message, tipo: 'error' });
        }
      })
      .catch(() => {
        setCargando(false);
        setMensaje({ texto: 'Error de conexión con el servidor', tipo: 'error' });
      });
  };

  const seleccionarEditar = (item) => {
    setId(item.id);
    setCodigo(item.codigo || '');
    setNombre(item.nombre || '');
    setMensaje({ texto: '', tipo: '' });
  };

  const eliminarAsignatura = (idEliminar) => {
    if (!window.confirm("¿Seguro que deseas eliminar esta asignatura?")) return;

    fetch(`${API_BASE}/gestion_asignaturas.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'eliminar', id: idEliminar }),
    })
      .then((res) => res.json())
      .then((data) => {
        if (data.success) {
          setMensaje({ texto: data.message, tipo: 'exito' });
          cargarAsignaturas();
        } else {
          setMensaje({ texto: data.message, tipo: 'error' });
        }
      });
  };

  const limpiarFormulario = () => {
    setId(null);
    setCodigo('');
    setNombre('');
  };

  return (
    <div className="app-main-container">
      <div className="panel-card">
        
        <h3 className="text-primary fw-bold mb-4">
          Gestión de Módulos y Asignaturas
        </h3>

        {mensaje.texto && (
          <div className={`alert ${mensaje.tipo === 'exito' ? 'alert-success' : 'alert-danger'} alert-dismissible fade show`} role="alert">
            {mensaje.texto}
            <button type="button" className="btn-close" onClick={() => setMensaje({ texto: '', tipo: '' })}></button>
          </div>
        )}

        <form onSubmit={handleSubmit} className="row g-2 mb-4">
          <div className="col-md-4">
            <input
              type="text"
              className="form-control"
              placeholder="Código (Ej: MOD 2.9)"
              value={codigo}
              onChange={(e) => setCodigo(e.target.value)}
              required
            />
          </div>
          <div className="col-md-6">
            <input
              type="text"
              className="form-control"
              placeholder="Nombre Completo (Ej: Mod 2.9 DS)"
              value={nombre}
              onChange={(e) => setNombre(e.target.value)}
              required
            />
          </div>
          <div className="col-md-2 d-flex gap-1">
            <button
              type="submit"
              disabled={cargando}
              className="btn btn-info text-white w-100 fw-bold"
            >
              {cargando ? 'Guardando...' : id ? 'Actualizar' : 'Agregar'}
            </button>
            
            {id && (
              <button
                type="button"
                onClick={limpiarFormulario}
                className="btn btn-secondary"
              >
                Cancelar
              </button>
            )}
          </div>
        </form>

        <div className="table-responsive">
          <table className="table table-bordered table-hover align-middle">
            <thead className="table-dark">
              <tr>
                <th style={{ width: '10%' }}>ID</th>
                <th style={{ width: '20%' }}>CÓDIGO</th>
                <th style={{ width: '50%' }}>NOMBRE DE ASIGNATURA</th>
                <th style={{ width: '20%', textAlign: 'center' }}>ACCIONES</th>
              </tr>
            </thead>
            <tbody>
              {asignaturas.length === 0 ? (
                <tr>
                  <td colSpan={4} className="text-center text-muted py-3">
                    No hay asignaturas registradas.
                  </td>
                </tr>
              ) : (
                asignaturas.map((item) => (
                  <tr key={item.id}>
                    <td>{item.id}</td>
                    <td className="fw-bold">{item.codigo || '-'}</td>
                    <td>{item.nombre}</td>
                    <td className="text-center">
                      <button
                        onClick={() => seleccionarEditar(item)}
                        className="btn btn-sm btn-outline-secondary me-2"
                      >
                        Editar
                      </button>
                      <button
                        onClick={() => eliminarAsignatura(item.id)}
                        className="btn btn-sm btn-danger"
                      >
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
};

export default GestionAsignaturas;