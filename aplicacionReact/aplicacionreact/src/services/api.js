const API_URL = import.meta.env.VITE_API_URL || 'https://asistencia-backend-qgim.onrender.com/api';

export const getAsistencia = async (claseId, seccionId) => {
  try {
    const response = await fetch(`${API_URL}/${claseId}?seccionId=${encodeURIComponent(seccionId)}`);
    if (!response.ok) {
      throw new Error('Error al obtener los datos de asistencia');
    }
    return await response.json();
  } catch (error) {
    console.error('Error en getAsistencia:', error);
    throw error;
  }
};

export const postAsistencia = async (datos) => {
  try {
    const response = await fetch(API_URL, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(datos),
    });
    if (!response.ok) {
      throw new Error('Error al registrar la asistencia');
    }
    return await response.json();
  } catch (error) {
    console.error('Error en postAsistencia:', error);
    throw error;
  }
};

export const deleteAsistenciaPorFecha = async (claseId, fecha) => {
  try {
    const response = await fetch(`${API_URL}/${claseId}/${fecha}`, {
      method: 'DELETE',
    });
    if (!response.ok) {
      throw new Error('Error al eliminar la asistencia');
    }
    return await response.json();
  } catch (error) {
    console.error('Error en deleteAsistenciaPorFecha:', error);
    throw error;
  }
};