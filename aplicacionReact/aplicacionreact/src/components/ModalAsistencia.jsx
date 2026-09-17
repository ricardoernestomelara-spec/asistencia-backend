import React, { useState, useEffect } from 'react';

const OPCIONES_ESTADO = [
  'Asistió',
  'Faltó',
  'Permiso',
  'Incapacidad',
  'Tardía',
  'Retirado'
];

const OPCIONES_MOTIVO = [
  'Asebo',
  'Aislamiento social',
  'Bajo rendimiento',
  'Competencia Deportiva',
  'Cuarentena',
  'Cuido de familiar',
  'Dificultad del transporte',
  'Dificultades de aprendizajes',
  'Discapacidad del estudiante',
  'Enfermedad',
  'Falta de motivación',
  'Inseguridad en el camino a la escuela',
  'No desea presentarse a exámenes',
  'No desea presentar tarea'
];

function ModalAsistencia({ estudiantes = [], onClose, onGuardar }) {
  const [fecha, setFecha] = useState(new Date().toISOString().split('T')[0]);
  const [asistencia, setAsistencia] = useState([]);

  useEffect(() => {
    if (estudiantes && estudiantes.length > 0) {
      setAsistencia(
        estudiantes.map((e) => ({
          estudiante_id: e.id,
          nie: e.nie,
          apellidos: e.apellidos,
          nombres: e.nombres,
          estado: 'Asistió',
          inasistencia_por: '',
          observacion: ''
        }))
      );
    }
  }, [estudiantes]);

  const handleChange = (index, field, value) => {
    const copia = [...asistencia];
    copia[index][field] = value;
    setAsistencia(copia);
  };

  const handleGuardar = () => {
    const partesFecha = fecha.split('-');
    const fechaCorta = partesFecha.length === 3 ? `${partesFecha[1]}/${partesFecha[2]}` : fecha;

    onGuardar({
      fecha,
      fechaCorta,
      detalles: asistencia
    });
  };

  return (
    <div style={styles.overlay}>
      <div style={styles.modal}>
        <div style={styles.header}>
          <h3 style={{ margin: 0, fontSize: '18px', color: '#1e293b' }}>Asistencia diaria</h3>
          <button style={styles.closeBtn} onClick={onClose}>&times;</button>
        </div>

        <div style={styles.body}>
          <div style={{ marginBottom: '15px', display: 'flex', alignItems: 'center', gap: '10px' }}>
            <label style={{ fontSize: '13px', fontWeight: 'bold', color: '#475569' }}>Fecha:</label>
            <input
              type="date"
              value={fecha}
              onChange={(e) => setFecha(e.target.value)}
              style={styles.dateInput}
            />
          </div>

          <div style={styles.tableContainer}>
            <table style={styles.table}>
              <thead>
                <tr>
                  <th style={styles.th}>#</th>
                  <th style={styles.th}>NIE</th>
                  <th style={styles.th}>APELLIDOS</th>
                  <th style={styles.th}>NOMBRES</th>
                  <th style={styles.th}>ESTADO</th>
                  <th style={styles.th}>INASISTENCIA POR</th>
                  <th style={styles.th}>OBSERVACIÓN</th>
                </tr>
              </thead>
              <tbody>
                {asistencia.map((item, idx) => (
                  <tr key={item.estudiante_id || idx} style={idx % 2 === 0 ? styles.trEven : styles.trOdd}>
                    <td style={styles.td}>{idx + 1}</td>
                    <td style={{ ...styles.td, fontFamily: 'monospace' }}>{item.nie}</td>
                    <td style={{ ...styles.td, fontWeight: '600' }}>{item.apellidos}</td>
                    <td style={styles.td}>{item.nombres}</td>
                    <td style={styles.td}>
                      <select
                        value={item.estado}
                        onChange={(e) => handleChange(idx, 'estado', e.target.value)}
                        style={styles.select}
                      >
                        {OPCIONES_ESTADO.map((opt) => (
                          <option key={opt} value={opt}>{opt}</option>
                        ))}
                      </select>
                    </td>
                    <td style={styles.td}>
                      <select
                        value={item.inasistencia_por}
                        onChange={(e) => handleChange(idx, 'inasistencia_por', e.target.value)}
                        style={styles.select}
                      >
                        <option value="">-- Seleccionar --</option>
                        {OPCIONES_MOTIVO.map((opt) => (
                          <option key={opt} value={opt}>{opt}</option>
                        ))}
                      </select>
                    </td>
                    <td style={styles.td}>
                      <input
                        type="text"
                        value={item.observacion}
                        onChange={(e) => handleChange(idx, 'observacion', e.target.value)}
                        style={styles.input}
                        placeholder="Escribe una observación..."
                      />
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>

        <div style={styles.footer}>
          <button style={styles.btnCancel} onClick={onClose}>Cancelar</button>
          <button style={styles.btnSave} onClick={handleGuardar}>Guardar</button>
        </div>
      </div>
    </div>
  );
}

const styles = {
  overlay: {
    position: 'fixed',
    top: 0,
    left: 0,
    width: '100vw',
    height: '100vh',
    backgroundColor: 'rgba(0, 0, 0, 0.5)',
    display: 'flex',
    justifyContent: 'center',
    alignItems: 'center',
    zIndex: 1000
  },
  modal: {
    backgroundColor: '#fff',
    width: '90%',
    maxWidth: '1100px',
    maxHeight: '90vh',
    borderRadius: '8px',
    display: 'flex',
    flexDirection: 'column',
    boxShadow: '0 10px 25px rgba(0,0,0,0.2)',
    overflow: 'hidden'
  },
  header: {
    padding: '16px 20px',
    borderBottom: '1px solid #e2e8f0',
    display: 'flex',
    justifyContent: 'space-between',
    alignItems: 'center',
    backgroundColor: '#f8fafc'
  },
  closeBtn: {
    background: 'none',
    border: 'none',
    fontSize: '22px',
    cursor: 'pointer',
    color: '#64748b'
  },
  body: {
    padding: '20px',
    overflowY: 'auto'
  },
  dateInput: {
    padding: '6px 12px',
    border: '1px solid #cbd5e1',
    borderRadius: '4px',
    fontSize: '14px',
    outline: 'none'
  },
  tableContainer: {
    maxHeight: '420px',
    overflowY: 'auto',
    border: '1px solid #e2e8f0',
    borderRadius: '6px'
  },
  table: {
    width: '100%',
    borderCollapse: 'collapse',
    fontSize: '13px'
  },
  th: {
    backgroundColor: '#0ea5e9',
    color: '#ffffff',
    padding: '10px 12px',
    textAlign: 'left',
    position: 'sticky',
    top: 0,
    fontSize: '11px',
    letterSpacing: '0.05em'
  },
  td: {
    padding: '8px 12px',
    borderBottom: '1px solid #f1f5f9',
    color: '#334155'
  },
  trEven: { backgroundColor: '#ffffff' },
  trOdd: { backgroundColor: '#f8fafc' },
  select: {
    width: '100%',
    padding: '6px 8px',
    border: '1px solid #cbd5e1',
    borderRadius: '4px',
    fontSize: '12px',
    backgroundColor: '#ffffff',
    outline: 'none'
  },
  input: {
    width: '100%',
    padding: '6px 8px',
    border: '1px solid #cbd5e1',
    borderRadius: '4px',
    fontSize: '12px',
    boxSizing: 'border-box',
    outline: 'none'
  },
  footer: {
    padding: '14px 20px',
    borderTop: '1px solid #e2e8f0',
    display: 'flex',
    justifyContent: 'flex-end',
    gap: '12px',
    backgroundColor: '#f8fafc'
  },
  btnCancel: {
    backgroundColor: '#ffffff',
    border: '1px solid #cbd5e1',
    color: '#475569',
    padding: '8px 18px',
    borderRadius: '6px',
    cursor: 'pointer',
    fontWeight: '600'
  },
  btnSave: {
    backgroundColor: '#2563eb',
    color: '#ffffff',
    border: 'none',
    padding: '8px 20px',
    borderRadius: '6px',
    cursor: 'pointer',
    fontWeight: '600'
  }
};

export default ModalAsistencia;