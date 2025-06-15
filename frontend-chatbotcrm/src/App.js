import React from 'react';
import { useAuth } from './hooks/useAuth';

import DocumentManager from './components/DocumentManager';
import LoginForm from './components/Auth/LoginForm';

import './App.css';

function App() {
  const { user, token, isAuthenticated, login, logout, loading, error, setError } = useAuth();

  // Si no está autenticado, mostrar login
  if (!isAuthenticated) {
    return (
      <LoginForm 
        onLogin={login} 
        loading={loading} 
        error={error}
        onClearError={() => setError(null)}
      />
    );
  }

  // Si está autenticado, mostrar la aplicación principal
  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header con info del usuario */}
      <header className="bg-white shadow-sm border-b">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between items-center h-16">
            <div className="flex items-center">
              <h1 className="text-xl font-semibold text-gray-900">Chatbot CRM</h1>
            </div>
            <div className="flex items-center space-x-4">
              <span className="text-sm text-gray-700">Hola, {user?.name}</span>
              <button
                onClick={logout}
                className="text-sm text-gray-500 hover:text-gray-700"
              >
                Cerrar Sesión
              </button>
            </div>
          </div>
        </div>
      </header>

      {/* Contenido principal */}
      <main>
        <DocumentManager token={token} />
      </main>
    </div>
  );
}

export default App;
