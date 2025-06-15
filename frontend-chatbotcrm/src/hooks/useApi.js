import { useState } from 'react';
import api from '../config/api';

export const useApi = () => {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const apiCall = async (method, url, data = null, token = null) => {
    setLoading(true);
    setError(null);
    
    try {
      // Determinar la URL base según el entorno
      const baseURL = process.env.NODE_ENV === 'production' 
        ? 'https://chatbot-crm.zxt.cl'
        : 'http://localhost:8000';
      
      // Construir la URL completa
      const fullUrl = url.startsWith('/') ? `${baseURL}${url}` : `${baseURL}/${url}`;
      
      const config = {
        method,
        url: fullUrl,
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        }
      };

      if (token) {
        config.headers.Authorization = `Bearer ${token}`;
      }

      if (data) {
        config.data = data;
      }

      const response = await api(config);
      return response.data;
    } catch (err) {
      setError(err.response?.data?.message || err.message);
      throw err;
    } finally {
      setLoading(false);
    }
  };

  return { apiCall, loading, error };
};

