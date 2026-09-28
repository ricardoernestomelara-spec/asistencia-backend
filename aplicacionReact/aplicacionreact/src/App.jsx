import React, { useState } from 'react';
import Login from './components/Login';
import DashboardLayout from './components/DashboardLayout';
import TablaAsistencia from './components/TablaAsistencia';
import CargaAcademica from './components/CargaAcademica';
import GestionDocentes from './components/GestionDocentes';
import GestionSecciones from './components/GestionSecciones';
import GestionAsignaturas from './components/GestionAsignaturas';
import GestionCargaAdmin from './components/GestionCargaAdmin';

function App() {
  const [usuario, setUsuario] = useState(localStorage.getItem('usuario') || null);
  const [rol, setRol] = useState(localStorage.getItem('rol') || null);

  const getInitialTab = (rolUsuario) => {
    return rolUsuario === 'docente' ? 'asistencia' : 'secciones';
  };

  const [tabActiva, setTabActiva] = useState(() => {
    const savedRol = localStorage.getItem('rol');
    return getInitialTab(savedRol);
  });

  const handleLogin = (user, userRol) => {
    let nombreFinal = 'Usuario';
    let rolFinal = userRol || 'admin';

    if (typeof user === 'object' && user !== null) {
      // Extrae el nombre buscando en todas las propiedades comunes que suele enviar la API
      nombreFinal = user.nombre || user.usuario || user.user || user.name || user.email || 'Usuario';
      rolFinal = user.rol || userRol || 'admin';
    } else if (typeof user === 'string') {
      nombreFinal = user;
    }

    setUsuario(nombreFinal);
    setRol(rolFinal);
    setTabActiva(getInitialTab(rolFinal));

    localStorage.setItem('usuario', nombreFinal);
    localStorage.setItem('rol', rolFinal);
  };

  const handleLogout = () => {
    setUsuario(null);
    setRol(null);
    localStorage.removeItem('usuario');
    localStorage.removeItem('rol');
  };

  if (!usuario) {
    return <Login onLogin={handleLogin} login={handleLogin} setUsuario={handleLogin} />;
  }

  const esDocente = rol === 'docente';

  return (
    <DashboardLayout 
      usuario={usuario} 
      rol={rol} 
      tabActiva={tabActiva} 
      setTabActiva={setTabActiva} 
      onLogout={handleLogout}
    >
      {(tabActiva === 'asistencia' || esDocente) && <TablaAsistencia usuario={usuario} rol={rol} />}

      {!esDocente && (
        <>
          {tabActiva === 'asignar' && <CargaAcademica />}
          {tabActiva === 'docentes' && <GestionDocentes />}
          {tabActiva === 'secciones' && <GestionSecciones />}
          {tabActiva === 'asignaturas' && <GestionAsignaturas />}
          {tabActiva === 'todo' && <GestionCargaAdmin />}
        </>
      )}
    </DashboardLayout>
  );
}

export default App;