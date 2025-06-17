import { useState } from 'react';
import axios from 'axios';

export const useApi = () => {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const apiCall = async (method, endpoint, data = null, token = null) => {
    setLoading(true);
    setError(null);

    try {
      // Definir baseURL de forma explícita según entorno
      const baseURL =
        process.env.NODE_ENV === 'production'
          ? 'https://chatbot-crm.zxt.cl'
          : 'http://localhost:8000';

      // Construir URL completa
      const url = endpoint.startsWith('/')
        ? `${baseURL}${endpoint}`
        : `${baseURL}/${endpoint}`;

      // Configuración del request
      const config = {
        method,
        url,
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
        },
      };

      if (token) {
        config.headers.Authorization = `Bearer ${token}`;
      }

      if (data) {
        config.data = data;
      }

      const response = await axios(config);
      return response.data;
    } catch (err) {
      setError(err.response?.data?.message || err.message || 'Error desconocido');
      throw err;
    } finally {
      setLoading(false);
    }
  };

  return { apiCall, loading, error };
};
