import { useState, useEffect, useCallback } from 'react';
import api from '../config/api';

export const useDocuments = (token) => {
  const [documents, setDocuments] = useState([]);
  const [loading, setLoading] = useState(false);
  const [stats, setStats] = useState({
    total: 0,
    processed: 0,
    pending: 0,
    totalTokens: 0,
    totalChunks: 0
  });

  // Cargar documentos desde la API
  const fetchDocuments = useCallback(async () => {
    setLoading(true);
    try {
      const response = await api.get('/documents', {
        headers: { Authorization: `Bearer ${token}` }
      });
      
      setDocuments(response.data.documents || []);
      setStats(response.data.stats || {
        total: 0,
        processed: 0,
        pending: 0,
        totalTokens: 0,
        totalChunks: 0
      });
    } catch (error) {
      console.error('Error fetching documents:', error);
      // Si falla la API, usar datos de ejemplo
      setDocuments([
        {
          id: 1,
          name: 'Manual de producto.pdf',
          type: 'pdf',
          size: 2048000,
          processed: true,
          uploadedAt: '2024-06-10T10:30:00Z',
          status: 'completed',
          chunks: 45,
          tokens: 12500
        },
        {
          id: 2,
          name: 'FAQ corporativo.docx',
          type: 'docx',
          size: 1024000,
          processed: false,
          uploadedAt: '2024-06-11T14:22:00Z',
          status: 'pending',
          chunks: 0,
          tokens: 0
        }
      ]);
      setStats({
        total: 2,
        processed: 1,
        pending: 1,
        totalTokens: 12500,
        totalChunks: 45
      });
    } finally {
      setLoading(false);
    }
  }, [token]);

  // Cargar documentos al inicializar
  useEffect(() => {
    if (token) {
      fetchDocuments();
    }
  }, [token, fetchDocuments]);

  // Subir documento
  const uploadDocument = async (file) => {
    const formData = new FormData();
    formData.append('document', file);

    try {
      const response = await api.post('/documents/upload', formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
          Authorization: `Bearer ${token}`
        }
      });

      // Agregar nuevo documento a la lista local
      const newDoc = response.data.document;
      setDocuments(prev => [...prev, newDoc]);
      setStats(prev => ({ 
        ...prev, 
        total: prev.total + 1, 
        pending: prev.pending + 1 
      }));

      return response.data;
    } catch (error) {
      console.error('Error uploading document:', error);
      throw error;
    }
  };

  // Procesar todos los documentos
  const processAllDocuments = async () => {
    try {
      const response = await api.post('/documents/process-all', {}, {
        headers: { Authorization: `Bearer ${token}` }
      });

      // Actualizar documentos localmente
      setDocuments(prev => prev.map(doc => ({ 
        ...doc, 
        processed: true, 
        status: 'completed' 
      })));

      return response.data;
    } catch (error) {
      console.error('Error processing all documents:', error);
      throw error;
    }
  };

  // Procesar documento individual
  const processDocument = async (id) => {
    try {
      const response = await api.post(`/documents/${id}/process`, {}, {
        headers: { Authorization: `Bearer ${token}` }
      });

      // Actualizar documento local
      setDocuments(prev => prev.map(doc => 
        doc.id === id ? { 
          ...doc, 
          processed: true, 
          status: 'completed',
          chunks: response.data.chunks || 0,
          tokens: response.data.tokens || 0
        } : doc
      ));

      return response.data;
    } catch (error) {
      console.error('Error processing document:', error);
      throw error;
    }
  };

  // Eliminar documento
  const deleteDocument = async (id) => {
    try {
      await api.delete(`/documents/${id}`, {
        headers: { Authorization: `Bearer ${token}` }
      });

      // Eliminar documento de la lista local
      setDocuments(prev => prev.filter(doc => doc.id !== id));
      setStats(prev => ({ 
        ...prev, 
        total: prev.total - 1 
      }));
    } catch (error) {
      console.error('Error deleting document:', error);
      throw error;
    }
  };

  // Buscar documentos
  const searchDocuments = async (query) => {
    try {
      const response = await api.get(`/documents/search?q=${encodeURIComponent(query)}`, {
        headers: { Authorization: `Bearer ${token}` }
      });

      return response.data.results || [];
    } catch (error) {
      console.error('Error searching documents:', error);
      // Fallback a búsqueda local
      return documents.filter(doc => 
        doc.name.toLowerCase().includes(query.toLowerCase())
      );
    }
  };

  return {
    documents,
    loading,
    stats,
    uploadDocument,
    processAllDocuments,
    processDocument,
    deleteDocument,
    searchDocuments,
    refreshDocuments: fetchDocuments
  };
};
