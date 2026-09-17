import React from 'react';

export default function Navbar({ moduloActual, setModuloActual, usuario, onCerrarSesion }) {
  const modulos = [
    { id: 'carga', etiqueta: 'Asignar Carga' },
    { id: 'docentes', etiqueta: 'Docentes' },
    { id: 'secciones', etiqueta: 'Secciones' },
    { id: 'asignaturas', etiqueta: 'Asignaturas' },
  ];

  return (
    <nav className="navbar navbar-expand-lg border-bottom mb-4 shadow-sm" style={{ backgroundColor: '#ffffff' }}>
      <div className="container-fluid app-main-container d-flex justify-content-between align-items-center">
        
        {/* Marca o Nombre del Sistema */}
        <span className="navbar-brand fw-bold fs-5" style={{ color: 'var(--color-primary-dark)' }}>
          Sistema Académico
        </span>

        {/* Pestañas de Navegación */}
        <div className="d-flex gap-2">
          {modulos.map((mod) => {
            const activo = moduloActual === mod.id;
            return (
              <button
                key={mod.id}
                onClick={() => setModuloActual(mod.id)}
                className={`btn btn-sm ${
                  activo ? 'btn-info text-white fw-bold' : 'btn-outline-secondary'
                }`}
                style={{
                  transition: 'all 0.2s ease',
                  borderWidth: '1.5px'
                }}
              >
                {mod.etiqueta}
              </button>
            );
          })}
        </div>

        {/* Usuario y Salida */}
        <div className="d-flex align-items-center gap-3">
          <span className="small fw-semibold text-secondary">
            {usuario || 'admin (Administrador)'}
          </span>
          <button 
            onClick={onCerrarSesion} 
            className="btn btn-sm btn-danger px-3 fw-bold"
          >
            Cerrar Sesión
          </button>
        </div>

      </div>
    </nav>
  );
}