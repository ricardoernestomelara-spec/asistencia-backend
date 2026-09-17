import React, { useState } from 'react';
import { useAuth } from '../context/AuthContext';
import { API_BASE } from '../config';

export default function Login() {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [cargandoEnvio, setCargandoEnvio] = useState(false);
  
  const { login } = useAuth();

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setCargandoEnvio(true);

    try {
      const respuesta = await fetch(`${API_BASE}/login.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, password }),
      });

      const datos = await respuesta.json();

      if (datos.success) {
        login(datos.usuario);
      } else {
        setError(datos.message || 'Credenciales inválidas.');
      }
    } catch (err) {
      console.error('Error al conectar con la API:', err);
      setError('No se pudo conectar con el servidor PHP. Verifica XAMPP.');
    } finally {
      setCargandoEnvio(false);
    }
  };

  return (
    <div style={styles.container}>
      <div style={styles.card}>
        <div style={styles.brandBadge}>
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#2563eb" strokeWidth="2">
            <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
            <path d="M6 12v5c3 3 9 3 12 0v-5"/>
          </svg>
        </div>
        
        <div style={styles.header}>
          <h2 style={styles.title}>Centro Educativo</h2>
          <p style={styles.subtitle}>Control de Asistencia Docente</p>
        </div>

        <form onSubmit={handleSubmit} style={styles.form}>
          {error && <div style={styles.error}>{error}</div>}

          <div>
            <label style={styles.label}>Correo Electrónico</label>
            <input
              type="email"
              required
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              placeholder="docente@escuela.edu"
              style={styles.input}
              disabled={cargandoEnvio}
            />
          </div>

          <div>
            <label style={styles.label}>Contraseña</label>
            <input
              type="password"
              required
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              placeholder="••••••••"
              style={styles.input}
              disabled={cargandoEnvio}
            />
          </div>

          <button 
            type="submit" 
            style={{
              ...styles.btnSubmit,
              backgroundColor: cargandoEnvio ? '#93c5fd' : '#2563eb',
              cursor: cargandoEnvio ? 'not-allowed' : 'pointer'
            }}
            disabled={cargandoEnvio}
          >
            {cargandoEnvio ? 'Verificando...' : 'Iniciar Sesión'}
          </button>
        </form>
      </div>
    </div>
  );
}

const styles = {
  container: {
    display: 'flex',
    justifyContent: 'center',
    alignItems: 'center',
    minHeight: '100vh',
    backgroundColor: '#f1f5f9',
    fontFamily: "'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif",
  },
  card: {
    width: '100%',
    maxWidth: '400px',
    backgroundColor: '#ffffff',
    borderRadius: '16px',
    boxShadow: '0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01)',
    padding: '36px',
    border: '1px solid #e2e8f0',
  },
  brandBadge: {
    width: '44px',
    height: '44px',
    backgroundColor: '#eff6ff',
    borderRadius: '12px',
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
    margin: '0 auto 16px auto',
  },
  header: { textAlign: 'center', marginBottom: '28px' },
  title: { margin: 0, color: '#0f172a', fontSize: '22px', fontWeight: '700', letterSpacing: '-0.02em' },
  subtitle: { margin: '4px 0 0 0', color: '#64748b', fontSize: '14px' },
  form: { display: 'flex', flexDirection: 'column', gap: '18px' },
  label: { display: 'block', fontSize: '13px', fontWeight: '600', color: '#334155', marginBottom: '6px' },
  input: {
    width: '100%',
    padding: '11px 14px',
    border: '1px solid #cbd5e1',
    borderRadius: '8px',
    fontSize: '14px',
    boxSizing: 'border-box',
    outline: 'none',
    transition: 'all 0.2s ease',
    backgroundColor: '#f8fafc',
  },
  btnSubmit: {
    color: '#ffffff',
    border: 'none',
    padding: '12px',
    borderRadius: '8px',
    fontWeight: '600',
    fontSize: '14px',
    marginTop: '6px',
    boxShadow: '0 2px 4px rgba(37, 99, 235, 0.2)',
  },
  error: {
    backgroundColor: '#fef2f2',
    color: '#b91c1c',
    border: '1px solid #fecaca',
    padding: '10px 14px',
    borderRadius: '8px',
    fontSize: '13px',
    textAlign: 'center',
  },
};