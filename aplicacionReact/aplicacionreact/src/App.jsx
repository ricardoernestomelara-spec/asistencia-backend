import React, { useState } from 'react';
import { API_BASE } from './config';
import DashboardLayout from './components/DashboardLayout';
import TablaAsistencia from './components/TablaAsistencia';
import ReporteMensual from './components/ReporteMensual';
import GestionSecciones from './components/GestionSecciones';
import GestionDocentes from './components/GestionDocentes';
import GestionAsignaturas from './components/GestionAsignaturas';
import GestionCargaAdmin from './components/GestionCargaAdmin';

export default function App() {
  const [usuario, setUsuario] = useState(() => localStorage.getItem('usuario') || null);
  const [rol, setRol] = useState(() => localStorage.getItem('rol') || 'docente');
  const [docenteId, setDocenteId] = useState(() => localStorage.getItem('docente_id') || localStorage.getItem('id_docente') || null);

  const [emailInput, setEmailInput] = useState('');
  const [passInput, setPassInput] = useState('');
  const [errorLogin, setErrorLogin] = useState('');
  const [cargando, setCargando] = useState(false);

  const [tabActiva, setTabActiva] = useState('asistencia');

  const iniciarSesion = async (e) => {
    e.preventDefault();
    setErrorLogin('');

    if (!emailInput.trim() || !passInput.trim()) {
      setErrorLogin('Por favor completa todos los campos.');
      return;
    }

    setCargando(true);

    try {
      const res = await fetch(`${API_BASE}/login.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ usuario: emailInput, email: emailInput, password: passInput })
      });

      const data = await res.json();

      if (data.success || data.usuario) {
        const userObj = data.usuario || data;
        const userNombre = typeof userObj === 'string' ? userObj : (userObj.nombre || userObj.email || emailInput);
        const userRol = data.rol || userObj.rol || (emailInput.toLowerCase().includes('admin') ? 'admin' : 'docente');
        
        // Obtener el ID dinámico del docente devuelto por el backend
        const userId = userObj.id || userObj.docente_id || userObj.id_docente || null;

        localStorage.setItem('usuario', userNombre);
        localStorage.setItem('rol', userRol);
        if (userId) {
          localStorage.setItem('docente_id', userId);
          setDocenteId(userId);
        }

        setUsuario(userNombre);
        setRol(userRol);

        if (userRol === 'admin') {
          setTabActiva('secciones');
        } else {
          setTabActiva('asistencia');
        }
      } else {
        setErrorLogin(data.message || 'Usuario o contraseña incorrectos');
      }
    } catch (err) {
      console.error("Error al conectar con la API de login:", err);
      setErrorLogin('Error de conexión con el servidor backend.');
    } finally {
      setCargando(false);
    }
  };

  const cerrarSesion = () => {
    localStorage.clear();
    sessionStorage.clear();
    setUsuario(null);
    setRol('docente');
    setDocenteId(null);
  };

  if (!usuario) {
    return (
      <div style={{
        display: 'flex',
        height: '100vh',
        width: '100vw',
        alignItems: 'center',
        justifyContent: 'center',
        backgroundColor: '#0f172a',
        fontFamily: 'system-ui, sans-serif'
      }}>
        <form onSubmit={iniciarSesion} style={{
          backgroundColor: '#ffffff',
          padding: '32px',
          borderRadius: '12px',
          width: '100%',
          maxWidth: '360px',
          boxShadow: '0 10px 25px rgba(0,0,0,0.3)'
        }}>
          <div style={{ textAlign: 'center', marginBottom: '24px' }}>
            <div style={{
              width: '48px',
              height: '48px',
              backgroundColor: '#2563eb',
              color: '#fff',
              borderRadius: '8px',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              fontWeight: 'bold',
              fontSize: '20px',
              margin: '0 auto 12px auto'
            }}>
              SA
            </div>
            <h2 style={{ margin: 0, fontSize: '20px', color: '#1e293b' }}>Sistema Académico</h2>
            <p style={{ margin: '4px 0 0 0', fontSize: '13px', color: '#64748b' }}>Ingresa tus credenciales para continuar</p>
          </div>

          {errorLogin && (
            <div style={{ backgroundColor: '#fee2e2', color: '#991b1b', padding: '8px 12px', borderRadius: '6px', fontSize: '12px', marginBottom: '16px' }}>
              {errorLogin}
            </div>
          )}

          <div style={{ marginBottom: '16px' }}>
            <label style={{ display: 'block', fontSize: '12px', fontWeight: 'bold', color: '#475569', marginBottom: '6px' }}>
              USUARIO / CORREO
            </label>
            <input
              type="text"
              value={emailInput}
              onChange={(e) => setEmailInput(e.target.value)}
              placeholder="docente@escuela.edu"
              style={{ width: '100%', padding: '10px', borderRadius: '6px', border: '1px solid #cbd5e1', boxSizing: 'border-box' }}
            />
          </div>

          <div style={{ marginBottom: '20px' }}>
            <label style={{ display: 'block', fontSize: '12px', fontWeight: 'bold', color: '#475569', marginBottom: '6px' }}>
              CONTRASEÑA
            </label>
            <input
              type="password"
              value={passInput}
              onChange={(e) => setPassInput(e.target.value)}
              placeholder="••••••••"
              style={{ width: '100%', padding: '10px', borderRadius: '6px', border: '1px solid #cbd5e1', boxSizing: 'border-box' }}
            />
          </div>

          <button
            type="submit"
            disabled={cargando}
            style={{
              width: '100%',
              padding: '11px',
              backgroundColor: '#2563eb',
              color: '#ffffff',
              border: 'none',
              borderRadius: '8px',
              fontWeight: 'bold',
              cursor: 'pointer',
              fontSize: '14px'
            }}
          >
            {cargando ? 'Iniciando sesión...' : 'Iniciar Sesión'}
          </button>
        </form>
      </div>
    );
  }

  const esAdmin = rol === 'admin';

  const renderContenido = () => {
    switch (tabActiva) {
      case 'secciones':
        return <GestionSecciones />;
      case 'docentes':
        return <GestionDocentes />;
      case 'asignaturas':
        return <GestionAsignaturas />;
      case 'asignar':
        return <GestionCargaAdmin />;
      case 'todo':
        return (
          <div className="d-flex flex-column gap-4">
            <GestionSecciones />
            <GestionDocentes />
            <GestionAsignaturas />
            <GestionCargaAdmin />
          </div>
        );
      case 'reporte':
        return <ReporteMensual docenteId={docenteId} usuario={usuario} esAdmin={esAdmin} />;
      case 'asistencia':
      default:
        return <TablaAsistencia docenteId={docenteId} usuario={usuario} esAdmin={esAdmin} />;
    }
  };

  return (
    <DashboardLayout
      usuario={usuario}
      rol={rol}
      tabActiva={tabActiva}
      setTabActiva={setTabActiva}
      onLogout={cerrarSesion}
    >
      {!esAdmin && (
        <div className="d-flex gap-2 mb-4 border-bottom pb-2">
          <button
            onClick={() => setTabActiva('asistencia')}
            className={`btn fw-bold ${tabActiva === 'asistencia' ? 'btn-primary' : 'btn-outline-secondary'}`}
          >
            📋 Tomar / Modificar Asistencia
          </button>
          <button
            onClick={() => setTabActiva('reporte')}
            className={`btn fw-bold ${tabActiva === 'reporte' ? 'btn-primary' : 'btn-outline-secondary'}`}
          >
            📊 Reporte Mensual
          </button>
        </div>
      )}

      {renderContenido()}
    </DashboardLayout>
  );
}