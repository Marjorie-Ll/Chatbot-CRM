import { useState, useEffect, useCallback } from 'react';
import { useApi } from './useApi';

export const useAuth = () => {
  const [user, setUser] = useState(null);
  const [token, setToken] = useState(localStorage.getItem('auth_token'));
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const { apiCall } = useApi();

  // Login
  //DEMO 
  const login = async (email, password) => {
  // Simular login exitoso
  const fakeUser = { name: "Usuario Demo", email: email };
  const fakeToken = "demo-token-123";
  
  setUser(fakeUser);
  setToken(fakeToken);
  localStorage.setItem("auth_token", fakeToken);
  localStorage.setItem("auth_user", JSON.stringify(fakeUser));
  
  return { success: true, user: fakeUser };
};


  /* const login = useCallback(async (email, password) => {
    setLoading(true);
    setError(null);
    
    try {
      const response = await apiCall('POST', '/auth/login', { email, password }, null);

      if (response.success) {
        setUser(response.user);
        setToken(response.token);
        localStorage.setItem('auth_token', response.token);
        localStorage.setItem('auth_user', JSON.stringify(response.user));
        return { success: true, user: response.user };
      } else {
        throw new Error(response.error || 'Error en el login');
      }
    } catch (error) {
      setError(error.message);
      return { success: false, error: error.message };
    } finally {
      setLoading(false);
    }
  }, [apiCall]); */

  // Register
  const register = useCallback(async (name, email, password, password_confirmation) => {
    setLoading(true);
    setError(null);
    
    try {
      const response = await apiCall('POST', '/auth/register', { 
        name, 
        email, 
        password, 
        password_confirmation 
      }, null);

      if (response.success) {
        setUser(response.user);
        setToken(response.token);
        localStorage.setItem('auth_token', response.token);
        localStorage.setItem('auth_user', JSON.stringify(response.user));
        return { success: true, user: response.user };
      } else {
        throw new Error(response.error || 'Error en el registro');
      }
    } catch (error) {
      setError(error.message);
      return { success: false, error: error.message };
    } finally {
      setLoading(false);
    }
  }, [apiCall]);

  // Logout
  const logout = useCallback(async () => {
    try {
      if (token) {
        await apiCall('POST', '/auth/logout', null, token);
      }
    } catch (error) {
      console.error('Error during logout:', error);
    } finally {
      setUser(null);
      setToken(null);
      localStorage.removeItem('auth_token');
      localStorage.removeItem('auth_user');
    }
  }, [apiCall, token]);

  // Verificar usuario al cargar
  useEffect(() => {
    const checkAuth = async () => {
      const savedToken = localStorage.getItem('auth_token');
      const savedUser = localStorage.getItem('auth_user');
      
      if (savedToken && savedUser) {
        try {
          setToken(savedToken);
          setUser(JSON.parse(savedUser));
          
          // Verificar que el token siga siendo válido
          const response = await apiCall('GET', '/auth/user', null, savedToken);
          if (response.success) {
            setUser(response.user);
          } else {
            // Token inválido, limpiar
            logout();
          }
        } catch (error) {
          console.error('Error checking auth:', error);
          logout();
        }
      }
    };

    checkAuth();
  }, []);

  const isAuthenticated = !!token && !!user;

  return {
    user,
    token,
    loading,
    error,
    isAuthenticated,
    login,
    register,
    logout,
    setError
  };
};
