import React from 'react';

const DashboardLayout = ({ usuario, rol, tabActiva, setTabActiva, onLogout, children }) => {
  // Pestañas exclusivas para el Administrador
  const menuItems = [
    {
      id: 'secciones',
      label: 'Gestionar Secciones',
      icon: (
        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m0 0h4m-4 0V11m0 0h4m-4 0H9m4 0V5m0 0H9" />
        </svg>
      )
    },
    {
      id: 'docentes',
      label: 'Gestionar Docentes',
      icon: (
        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
        </svg>
      )
    },
    {
      id: 'asignaturas',
      label: 'Gestionar Asignaturas',
      icon: (
        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
        </svg>
      )
    },
    {
      id: 'asignar',
      label: 'Asignar Carga',
      icon: (
        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
        </svg>
      )
    },
    {
      id: 'todo',
      label: 'Vista General',
      icon: (
        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
        </svg>
      )
    }
  ];

  const nombreMostrar = usuario || 'Usuario';
  const inicialMostrar = nombreMostrar.charAt(0).toUpperCase();

  return (
    <div className="d-flex min-vh-100" style={{ backgroundColor: '#f8fafc', fontFamily: 'Inter, system-ui, sans-serif' }}>
      {/* Sidebar Lateral Izquierdo */}
      <aside 
        className="d-flex flex-column justify-content-between p-3 border-end"
        style={{ width: '260px', minWidth: '260px', backgroundColor: '#0f172a', color: '#f8fafc' }}
      >
        <div>
          {/* Header Brand */}
          <div className="d-flex align-items-center gap-3 px-2 py-3 mb-4 border-bottom border-secondary border-opacity-25">
            <div 
              className="d-flex align-items-center justify-content-center rounded-3 text-white fw-bold"
              style={{ width: '38px', height: '38px', fontSize: '1.1rem', background: 'linear-gradient(135deg, #2563eb, #1d4ed8)' }}
            >
              SA
            </div>
            <div>
              <h6 className="fw-bold mb-0 text-white" style={{ letterSpacing: '-0.3px' }}>Sistema Académico</h6>
              <span className="badge bg-secondary bg-opacity-25 text-info text-uppercase" style={{ fontSize: '10px' }}>
                {rol || 'Usuario'}
              </span>
            </div>
          </div>

          {/* Menú de Navegación Vertical (Únicamente pestañas de Admin) */}
          <nav className="nav flex-column gap-1">
            {rol === 'admin' && menuItems.map((item) => (
              <button
                key={item.id}
                type="button"
                onClick={() => setTabActiva(item.id)}
                className="btn d-flex align-items-center gap-3 px-3 py-2 text-start rounded-3 border-0"
                style={{
                  backgroundColor: tabActiva === item.id ? '#2563eb' : 'transparent',
                  color: tabActiva === item.id ? '#ffffff' : '#94a3b8',
                  fontSize: '0.9rem',
                  fontWeight: tabActiva === item.id ? '600' : '400',
                  transition: 'all 0.2s ease-in-out'
                }}
              >
                {item.icon}
                <span>{item.label}</span>
              </button>
            ))}
          </nav>
        </div>

        {/* Footer Sidebar */}
        <div className="sidebar-footer pt-3 mt-auto border-top border-secondary border-opacity-25" style={{ backgroundColor: 'transparent' }}>
          <div className="d-flex align-items-center gap-2 mb-3 px-1">
            <span 
              className="d-flex align-items-center justify-content-center fw-bold flex-shrink-0"
              style={{ 
                width: '36px', 
                height: '36px', 
                borderRadius: '50%',
                backgroundColor: '#2563eb', 
                color: '#ffffff',
                fontSize: '0.95rem' 
              }}
            >
              {inicialMostrar}
            </span>

            <div className="text-truncate fw-semibold" style={{ color: '#ffffff', fontSize: '0.9rem' }}>
              {nombreMostrar}
            </div>
          </div>

          <button 
            type="button"
            onClick={onLogout} 
            className="btn w-100 d-flex align-items-center justify-content-center gap-2 rounded-3 fw-semibold border-0"
            style={{ 
              backgroundColor: '#dc2626', 
              color: '#ffffff',
              fontSize: '0.85rem',
              padding: '9px 12px'
            }}
          >
            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
            <span>Cerrar Sesión</span>
          </button>
        </div>
      </aside>

      {/* Contenido Principal */}
      <main className="flex-grow-1 p-4 overflow-auto" style={{ maxHeight: '100vh' }}>
        {children}
      </main>
    </div>
  );
};

export default DashboardLayout;