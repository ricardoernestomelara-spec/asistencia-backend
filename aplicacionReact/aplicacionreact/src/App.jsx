import React, { useState } from 'react';
import { AuthProvider, useAuth } from './context/AuthContext';
import Login from './components/Login';
import TablaAsistencia from './components/TablaAsistencia';
import CargaAcademica from './components/CargaAcademica';
import GestionCargaAdmin from './components/GestionCargaAdmin';
import GestionAsignaturas from './components/GestionAsignaturas';
import GestionDocentes from './components/GestionDocentes';
import GestionSecciones from './components/GestionSecciones';

function ContentApp() {
  const { usuario, logout } = useAuth();
  const [vistaAdmin, setVistaAdmin] = useState('carga'); // 'carga', 'docentes', 'secciones', 'asignaturas', 'todas'
  const [vistaDocente, setVistaDocente] = useState('asistencia');

  if (!usuario) {
    return <Login />;
  }

  return (
    <div className="bg-light min-vh-100">
      {/* Header Superior Limpio con Sombreado */}
      <header className="bg-white border-bottom shadow-sm mb-4">
        <div className="app-main-container d-flex justify-content-between align-items-center py-3">
          <div className="d-flex align-items-center gap-2">
            <span className="fw-bold fs-5" style={{ color: 'var(--color-primary-dark)' }}>
              Sistema Académico
            </span>
            <span className="badge rounded-pill text-bg-light border text-muted px-2 py-1">
              {usuario.rol === 'admin' ? 'Administración' : 'Docente'}
            </span>
          </div>

          <div className="d-flex align-items-center gap-3">
            <span className="fw-semibold small text-secondary">
              {usuario.nombre || 'Usuario'}
            </span>
            <button 
              onClick={logout} 
              className="btn btn-outline-danger btn-sm fw-bold px-3 rounded-pill"
            >
              Cerrar Sesión
            </button>
          </div>
        </div>

        {/* Pestañas de Navegación de Administrador */}
        {usuario.rol === 'admin' && (
          <div className="border-top bg-light">
            <div className="app-main-container d-flex gap-1 overflow-auto pt-2 px-3">
              {[
                { id: 'carga', label: 'Asignar Carga' },
                { id: 'docentes', label: 'Gestionar Docentes' },
                { id: 'secciones', label: 'Gestionar Secciones' },
                { id: 'asignaturas', label: 'Gestionar Asignaturas' },
                { id: 'todas', label: 'Vista General (Todo)' },
              ].map((tab) => {
                const activo = vistaAdmin === tab.id;
                return (
                  <button
                    key={tab.id}
                    onClick={() => setVistaAdmin(tab.id)}
                    className="btn btn-sm border-0 pb-2 px-3 fw-bold text-nowrap rounded-top"
                    style={{
                      color: activo ? 'var(--color-primary-blue)' : '#6c757d',
                      borderBottom: activo ? '3px solid var(--color-primary-blue)' : '3px solid transparent',
                      backgroundColor: activo ? '#ffffff' : 'transparent',
                      transition: 'all 0.2s ease',
                    }}
                  >
                    {tab.label}
                  </button>
                );
              })}
            </div>
          </div>
        )}
      </header>

      {/* Contenido Principal */}
      <main className="pb-5">
        {usuario.rol === 'admin' ? (
          <div>
            {vistaAdmin === 'carga' && <GestionCargaAdmin />}
            {vistaAdmin === 'docentes' && <GestionDocentes />}
            {vistaAdmin === 'secciones' && <GestionSecciones />}
            {vistaAdmin === 'asignaturas' && <GestionAsignaturas />}
            {vistaAdmin === 'todas' && (
              <div className="d-flex flex-column gap-4">
                <GestionCargaAdmin />
                <GestionDocentes />
                <GestionSecciones />
                <GestionAsignaturas />
              </div>
            )}
          </div>
        ) : (
          /* VISTA DOCENTE */
          <div className="app-main-container">
            <div className="d-flex gap-2 mb-3">
              <button 
                onClick={() => setVistaDocente('asistencia')}
                className={`btn fw-bold rounded-pill px-4 ${
                  vistaDocente === 'asistencia' ? 'btn-info text-white' : 'btn-light border'
                }`}
              >
                Pasar Asistencia
              </button>
              <button 
                onClick={() => setVistaDocente('carga')}
                className={`btn fw-bold rounded-pill px-4 ${
                  vistaDocente === 'carga' ? 'btn-info text-white' : 'btn-light border'
                }`}
              >
                Carga Académica
              </button>
            </div>

            {vistaDocente === 'asistencia' && <TablaAsistencia docenteId={usuario.id} />}
            {vistaDocente === 'carga' && <CargaAcademica docenteId={usuario.id} />}
          </div>
        )}
      </main>
    </div>
  );
}

export default function App() {
  return (
    <AuthProvider>
      <ContentApp />
    </AuthProvider>
  );
}