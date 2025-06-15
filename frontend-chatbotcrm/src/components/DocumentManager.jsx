import React, { useState, useCallback } from 'react';
import {
  Upload,
  FileText,
  Trash2,
  Eye,
  Play,
  CheckCircle,
  Clock,
  AlertCircle,
  Search,
  //Filter,
  //Download,
  Zap,
  BarChart3,
  X,
  RefreshCw,
  MessageSquare,
  Brain
} from 'lucide-react';
import { useDocuments } from '../hooks/useDocuments';

const DocumentManager = ({ token }) => {
  const {
    documents,
    loading,
    stats,
    uploadDocument,
    processAllDocuments,
    processDocument,
    deleteDocument,
    searchDocuments
  } = useDocuments(token);

  // Estados
  const [dragOver, setDragOver] = useState(false);
  const [uploading, setUploading] = useState(false);
  const [processing, setProcessing] = useState(false);
  const [processingDoc, setProcessingDoc] = useState(null);
  const [filters, setFilters] = useState({
    processed: '',
    type: '',
    search: ''
  });
  const [searchResults, setSearchResults] = useState([]);
  const [showSearch, setShowSearch] = useState(false);
  const [notification, setNotification] = useState(null);

  // Notificaciones
  const showNotification = useCallback((message, type = 'info') => {
    setNotification({ message, type });
    setTimeout(() => setNotification(null), 5000);
  }, []);

  // Validar archivos
  const validateFile = (file) => {
    const maxSize = 10 * 1024 * 1024; // 10MB
    const allowedTypes = ['pdf', 'docx', 'txt', 'md', 'csv'];
    const fileExtension = file.name.split('.').pop().toLowerCase();

    if (file.size > maxSize) {
      return `El archivo ${file.name} excede el tamaño máximo de 10MB`;
    }

    if (!allowedTypes.includes(fileExtension)) {
      return `Tipo de archivo no permitido: ${fileExtension}. Tipos permitidos: ${allowedTypes.join(', ')}`;
    }

    return null;
  };

  // Manejar subida de archivos
  const handleFileUpload = async (files) => {
    if (uploading) return;

    setUploading(true);
    const fileArray = Array.from(files);
    let uploadedCount = 0;
    let errorCount = 0;

    try {
      for (let file of fileArray) {
        const validationError = validateFile(file);
        if (validationError) {
          showNotification(validationError, 'error');
          errorCount++;
          continue;
        }

        try {
          await uploadDocument(file);
          uploadedCount++;
        } catch (error) {
          console.error(`Error uploading ${file.name}:`, error);
          showNotification(`Error al subir ${file.name}`, 'error');
          // errorCount++;
        }
      }

      if (uploadedCount > 0) {
        showNotification(
          `${uploadedCount} archivo${uploadedCount > 1 ? 's' : ''} subido${uploadedCount > 1 ? 's' : ''} correctamente`,
          'success'
        );
      }
    } finally {
      setUploading(false);
    }
  };

  // Drag & Drop handlers
  const handleDragOver = useCallback((e) => {
    e.preventDefault();
    e.stopPropagation();
    setDragOver(true);
  }, []);

  const handleDragLeave = useCallback((e) => {
    e.preventDefault();
    e.stopPropagation();
    if (!e.currentTarget.contains(e.relatedTarget)) {
      setDragOver(false);
    }
  }, []);

  const handleDrop = useCallback((e) => {
    e.preventDefault();
    e.stopPropagation();
    setDragOver(false);

    const files = e.dataTransfer.files;
    if (files && files.length > 0) {
      handleFileUpload(files);
    }
  }, []);

  // Procesar todos los documentos
  const handleProcessAll = async () => {
    if (processing) return;

    const pendingDocs = documents.filter(doc => !doc.processed);
    if (pendingDocs.length === 0) {
      showNotification('No hay documentos pendientes por procesar', 'info');
      return;
    }

    try {
      setProcessing(true);
      const results = await processAllDocuments();

      if (results && typeof results === 'object') {
        showNotification(
          `Procesamiento completado: ${results.processed} exitosos, ${results.failed} fallidos`,
          results.failed > 0 ? 'warning' : 'success'
        );
      }
    } catch (error) {
      console.error('Error en procesamiento:', error);
      showNotification(`Error en el procesamiento: ${error.message}`, 'error');
    } finally {
      setProcessing(false);
    }
  };

  // Procesar documento individual
  const handleProcessDocument = async (docId) => {
    if (processingDoc === docId) return;

    try {
      setProcessingDoc(docId);
      await processDocument(docId);
      showNotification('Documento procesado correctamente', 'success');
    } catch (error) {
      console.error('Error procesando documento:', error);
      showNotification('Error al procesar el documento', 'error');
    } finally {
      setProcessingDoc(null);
    }
  };

  // Eliminar documento
  const handleDeleteDocument = async (docId, docName) => {
    if (window.confirm(`¿Estás seguro de que quieres eliminar "${docName}"?`)) {
      try {
        await deleteDocument(docId);
        showNotification('Documento eliminado correctamente', 'success');
      } catch (error) {
        console.error('Error eliminando documento:', error);
        showNotification('Error al eliminar el documento', 'error');
      }
    }
  };

  // Buscar documentos
  const handleSearch = async (query) => {
    if (!query.trim()) {
      setSearchResults([]);
      setShowSearch(false);
      return;
    }

    try {
      const results = await searchDocuments(query);
      setSearchResults(results);
      setShowSearch(true);
    } catch (error) {
      console.error('Error en búsqueda:', error);
      showNotification('Error al buscar documentos', 'error');
    }
  };

  // Filtrar documentos
  const filteredDocuments = documents.filter(doc => {
    if (filters.processed !== '' && doc.processed !== (filters.processed === 'true')) {
      return false;
    }
    if (filters.type && doc.type !== filters.type) {
      return false;
    }
    if (filters.search && !doc.name.toLowerCase().includes(filters.search.toLowerCase())) {
      return false;
    }
    return true;
  });

  // Formatear tamaño de archivo
  const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
  };

  // Obtener icono por tipo de archivo
  const getFileIcon = (type) => {
    const icons = {
      pdf: '📄',
      docx: '📝',
      txt: '📋',
      md: '📋',
      csv: '📊'
    };
    return icons[type] || '📄';
  };

  // Obtener estado del documento
  const getDocumentStatus = (doc) => {
    if (processingDoc === doc.id) return 'processing';
    return doc.status || (doc.processed ? 'completed' : 'pending');
  };

  return (
    <div className="max-w-7xl mx-auto p-6 space-y-6">
      {/* Notificaciones */}
      {notification && (
        <div className={`fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-md ${
          notification.type === 'success' ? 'bg-green-500 text-white' :
          notification.type === 'error' ? 'bg-red-500 text-white' :
          notification.type === 'warning' ? 'bg-yellow-500 text-white' :
          'bg-blue-500 text-white'
        }`}>
          <div className="flex items-center justify-between">
            <span>{notification.message}</span>
            <button
              onClick={() => setNotification(null)}
              className="ml-4 text-white hover:text-gray-200"
            >
              <X size={16} />
            </button>
          </div>
        </div>
      )}

      {/* Header */}
      <div className="bg-white rounded-lg shadow-sm border p-6">
        <div className="flex items-center justify-between mb-4">
          <div className="flex items-center space-x-3">
            <Brain className="h-8 w-8 text-blue-600" />
            <div>
              <h1 className="text-2xl font-bold text-gray-900">Gestor de Documentos</h1>
              <p className="text-gray-600">Entrena tu chatbot con documentos de conocimiento</p>
            </div>
          </div>
          <div className="flex items-center space-x-4">
            <MessageSquare className="h-6 w-6 text-gray-400" />
            <span className="text-sm text-gray-500">Chatbot CRM v1.0</span>
          </div>
        </div>

        {/* Estadísticas */}
        <div className="grid grid-cols-2 md:grid-cols-5 gap-4">
          <div className="bg-blue-50 p-4 rounded-lg">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-blue-600 font-medium">Total</p>
                <p className="text-2xl font-bold text-blue-900">{stats.total}</p>
              </div>
              <FileText className="h-8 w-8 text-blue-500" />
            </div>
          </div>
          <div className="bg-green-50 p-4 rounded-lg">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-green-600 font-medium">Procesados</p>
                <p className="text-2xl font-bold text-green-900">{stats.processed}</p>
              </div>
              <CheckCircle className="h-8 w-8 text-green-500" />
            </div>
          </div>
          <div className="bg-yellow-50 p-4 rounded-lg">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-yellow-600 font-medium">Pendientes</p>
                <p className="text-2xl font-bold text-yellow-900">{stats.pending}</p>
              </div>
              <Clock className="h-8 w-8 text-yellow-500" />
            </div>
          </div>
          <div className="bg-purple-50 p-4 rounded-lg">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-purple-600 font-medium">Tokens</p>
                <p className="text-2xl font-bold text-purple-900">{stats.totalTokens?.toLocaleString()}</p>
              </div>
              <BarChart3 className="h-8 w-8 text-purple-500" />
            </div>
          </div>
          <div className="bg-indigo-50 p-4 rounded-lg">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-indigo-600 font-medium">Chunks</p>
                <p className="text-2xl font-bold text-indigo-900">{stats.totalChunks}</p>
              </div>
              <Zap className="h-8 w-8 text-indigo-500" />
            </div>
          </div>
        </div>
      </div>

      {/* Área de carga */}
      <div className="bg-white rounded-lg shadow-sm border">
        <div className="p-6 border-b">
          <h2 className="text-xl font-semibold text-gray-900 mb-2">Cargar Documentos</h2>
          <p className="text-gray-600 text-sm">
            Sube documentos PDF, DOCX, TXT, MD o CSV para entrenar tu chatbot (máx. 10MB por archivo)
          </p>
        </div>
        
        <div
          className={`m-6 border-2 border-dashed rounded-lg p-8 text-center transition-colors ${
            dragOver
              ? 'border-blue-500 bg-blue-50'
              : 'border-gray-300 hover:border-gray-400'
          }`}
          onDragOver={handleDragOver}
          onDragLeave={handleDragLeave}
          onDrop={handleDrop}
        >
          {uploading ? (
            <div className="flex items-center justify-center space-x-2">
              <RefreshCw className="h-6 w-6 text-blue-500 animate-spin" />
              <span className="text-blue-600 font-medium">Subiendo archivos...</span>
            </div>
          ) : (
            <>
              <Upload className="h-12 w-12 text-gray-400 mx-auto mb-4" />
              <p className="text-lg font-medium text-gray-700 mb-2">
                Arrastra archivos aquí o haz clic para seleccionar
              </p>
              <p className="text-sm text-gray-500 mb-4">
                Formatos soportados: PDF, DOCX, TXT, MD, CSV
              </p>
              <input
                type="file"
                multiple
                accept=".pdf,.docx,.txt,.md,.csv"
                onChange={(e) => handleFileUpload(e.target.files)}
                className="hidden"
                id="file-upload"
              />
              <label
                htmlFor="file-upload"
                className="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 cursor-pointer transition-colors"
              >
                <Upload className="h-4 w-4 mr-2" />
                Seleccionar archivos
              </label>
            </>
          )}
        </div>
      </div>

      {/* Controles */}
      <div className="bg-white rounded-lg shadow-sm border p-6">
        <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center space-y-4 sm:space-y-0">
          <div className="flex items-center space-x-4">
            <button
              onClick={handleProcessAll}
              disabled={processing || documents.filter(d => !d.processed).length === 0}
              className="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              {processing ? (
                <RefreshCw className="h-4 w-4 mr-2 animate-spin" />
              ) : (
                <Play className="h-4 w-4 mr-2" />
              )}
              Procesar todos
            </button>
          </div>

          <div className="flex items-center space-x-4">
            {/* Búsqueda */}
            <div className="relative">
              <Search className="h-4 w-4 absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
              <input
                type="text"
                placeholder="Buscar documentos..."
                value={filters.search}
                onChange={(e) => {
                  setFilters(prev => ({ ...prev, search: e.target.value }));
                  handleSearch(e.target.value);
                }}
                className="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
            </div>

            {/* Filtros */}
            <select
              value={filters.processed}
              onChange={(e) => setFilters(prev => ({ ...prev, processed: e.target.value }))}
              className="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option value="">Todos los estados</option>
              <option value="true">Procesados</option>
              <option value="false">Pendientes</option>
            </select>

            <select
              value={filters.type}
              onChange={(e) => setFilters(prev => ({ ...prev, type: e.target.value }))}
              className="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option value="">Todos los tipos</option>
              <option value="pdf">PDF</option>
              <option value="docx">DOCX</option>
              <option value="txt">TXT</option>
              <option value="md">Markdown</option>
              <option value="csv">CSV</option>
            </select>
          </div>
        </div>
      </div>

      {/* Lista de documentos */}
      <div className="bg-white rounded-lg shadow-sm border">
        <div className="p-6 border-b">
          <h2 className="text-xl font-semibold text-gray-900">
            Documentos ({filteredDocuments.length})
          </h2>
        </div>

        {loading ? (
          <div className="p-8 text-center">
            <RefreshCw className="h-8 w-8 text-blue-500 animate-spin mx-auto mb-4" />
            <p className="text-gray-600">Cargando documentos...</p>
          </div>
        ) : filteredDocuments.length === 0 ? (
          <div className="p-8 text-center">
            <FileText className="h-12 w-12 text-gray-400 mx-auto mb-4" />
            <p className="text-gray-600 text-lg">No hay documentos</p>
            <p className="text-gray-500 text-sm">Sube algunos documentos para comenzar</p>
          </div>
        ) : (
          <div className="divide-y divide-gray-200">
            {filteredDocuments.map((doc) => {
              const status = getDocumentStatus(doc);
              return (
                <div key={doc.id} className="p-6 hover:bg-gray-50 transition-colors">
                  <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-4 flex-1">
                      <div className="text-2xl">{getFileIcon(doc.type)}</div>
                      <div className="flex-1 min-w-0">
                        <h3 className="text-lg font-medium text-gray-900 truncate">
                          {doc.name}
                        </h3>
                        <div className="flex items-center space-x-4 mt-1 text-sm text-gray-500">
                          <span>{formatFileSize(doc.size)}</span>
                          <span>•</span>
                          <span className="uppercase">{doc.type}</span>
                          <span>•</span>
                          <span>{new Date(doc.uploadedAt).toLocaleDateString()}</span>
                          {doc.processed && (
                            <>
                              <span>•</span>
                              <span>{doc.chunks} chunks</span>
                              <span>•</span>
                              <span>{doc.tokens?.toLocaleString()} tokens</span>
                            </>
                          )}
                        </div>
                      </div>
                    </div>

                    <div className="flex items-center space-x-4">
                      {/* Estado */}
                      <div className="flex items-center space-x-2">
                        {status === 'processing' ? (
                          <>
                            <RefreshCw className="h-4 w-4 text-blue-500 animate-spin" />
                            <span className="text-sm text-blue-600 font-medium">Procesando...</span>
                          </>
                        ) : status === 'completed' ? (
                          <>
                            <CheckCircle className="h-4 w-4 text-green-500" />
                            <span className="text-sm text-green-600 font-medium">Completado</span>
                          </>
                        ) : status === 'error' ? (
                          <>
                            <AlertCircle className="h-4 w-4 text-red-500" />
                            <span className="text-sm text-red-600 font-medium">Error</span>
                          </>
                        ) : (
                          <>
                            <Clock className="h-4 w-4 text-yellow-500" />
                            <span className="text-sm text-yellow-600 font-medium">Pendiente</span>
                          </>
                        )}
                      </div>

                      {/* Acciones */}
                      <div className="flex items-center space-x-2">
                        {!doc.processed && (
                          <button
                            onClick={() => handleProcessDocument(doc.id)}
                            disabled={processingDoc === doc.id}
                            className="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                            title="Procesar documento"
                          >
                            <Play className="h-4 w-4" />
                          </button>
                        )}
                        <button
                          onClick={() => {/* Implementar vista previa */}}
                          className="p-2 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors"
                          title="Vista previa"
                        >
                          <Eye className="h-4 w-4" />
                        </button>
                        <button
                          onClick={() => handleDeleteDocument(doc.id, doc.name)}
                          className="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                          title="Eliminar documento"
                        >
                          <Trash2 className="h-4 w-4" />
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              );
            })}
          </div>
        )}
      </div>
    </div>
  );
};

export default DocumentManager;