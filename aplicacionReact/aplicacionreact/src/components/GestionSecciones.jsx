import React, { useState, useEffect } from 'react';
import { API_BASE } from '../config';

export default function GestionSecciones() {
  const [secciones, setSecciones] = useState([]);
  const [id, setId] = useState(null);
  const [nombre, setNombre] = useState('');
  const [mensaje, setMensaje] = useState({ texto: '', tipo: '' });
  const [cargando, setCargando] = useState(false);

  const cargarSecciones = () => {
    fetch(`${API_BASE}/obtener_catalogos.php`)
      .then((res) => res.json())
      .then((data) => {
        if (data.success) {
          setSecciones(data.secciones || []);
        }
      })
      .catch((err) => console.error("Error al cargar secciones:", err));
  };

  useEffect(() => {
    cargarSecciones();
  }, []);

  const handleSubmit = (e) => {
    e.preventDefault();
    if (!nombre.trim()) return;

    setCargando(true);
    setMensaje({ texto: '', tipo: '' });

    fetch(`${API_BASE}/gestion_secciones.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'guardar', id, nombre }),
    })
      .then((res) => res.json())
      .then((data) => {
        setCargando(false);
        if (data.success) {
          setMensaje({ texto: data.message, tipo: 'exito' });
          limpiarFormulario();
          cargarSecciones();
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
    setNombre(item.nombre || '');
    setMensaje({ texto: '', tipo: '' });
  };

  const eliminarSeccion = (idEliminar) => {
    if (!window.confirm("¿Seguro que deseas eliminar esta sección?")) return;

    fetch(`${API_BASE}/gestion_secciones.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'eliminar', id: idEliminar }),
    })
      .then((res) => res.json())
      .then((data) => {
        if (data.success) {
          setMensaje({ texto: data.message, tipo: 'exito' });
          cargarSecciones();
        } else {
          setMensaje({ texto: data.message, tipo: 'error' });
        }
      });
  };

  const limpiarFormulario = () => {
    setId(null);
    setNombre('');
  };

  return (
    <div className="app-main-container">
      <div className="panel-card">
        
        <h5 className="fw-bold text-secondary mb-3">
          Gestión de Secciones
        </h5>

        {mensaje.texto && (
          <div className={`alert ${mensaje.tipo === 'exito' ? 'alert-success' : 'alert-danger'} alert-dismissible fade show`} role="alert">
            {mensaje.texto}
            <button type="button" className="btn-close" onClick={() => setMensaje({ texto: '', tipo: '' })}></button>
          </div>
        )}

        <form onSubmit={handleSubmit} className="row g-2 mb-4">
          <div className="col-md-10">
            <input
              type="text"
              className="form-control"
              placeholder="Nombre de la Sección (Ej: 1° A Software)"
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
          <table className="table table-bordered table-hover align-middle text-center">
            <thead className="table-dark">
              <tr>
                <th style={{ width: '15%' }}>ID</th>
                <th style={{ width: '65%' }}>NOMBRE DE LA SECCIÓN</th>
                <th style={{ width: '20%' }}>ACCIONES</th>
              </tr>
            </thead>
            <tbody>
              {secciones.length === 0 ? (
                <tr>
                  <td colSpan={3} className="text-muted py-3">
                    No hay secciones registradas.
                  </td>
                </tr>
              ) : (
                secciones.map((item) => (
                  <tr key={item.id}>
                    <td>{item.id}</td>
                    <td className="text-start ps-3">{item.nombre}</td>
                    <td>
                      <button
                        onClick={() => seleccionarEditar(item)}
                        className="btn btn-sm btn-outline-secondary me-2"
                      >
                        Editar
                      </button>
                      <button
                        onClick={() => eliminarSeccion(item.id)}
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
}