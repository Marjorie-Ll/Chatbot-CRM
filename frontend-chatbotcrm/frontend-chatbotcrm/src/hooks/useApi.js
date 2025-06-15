import { useState } from 'react';
import api from '../config/api';

export const useApi = () => {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const apiCall = async (method, url, data = null, token = null) => {
    setLoading(true);
    setError(null);
    
    try {
      const config = {
        method,
        url,
        headers: {}
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