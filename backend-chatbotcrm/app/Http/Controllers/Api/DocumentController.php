<?php
// app/Http/Controllers/Api/DocumentController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Services\DocumentProcessingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentController extends Controller
{
    protected $documentService;

    public function __construct(DocumentProcessingService $documentService)
    {
        $this->documentService = $documentService;
        
    }

    /**
 * Lista todos los documentos (para el frontend)
 */
    public function index(): JsonResponse
    {
        try {
            // Datos de ejemplo por ahora
            $documents = [
                [
                    'id' => 1,
                    'name' => 'Manual de producto.pdf',
                    'type' => 'pdf',
                    'size' => 2048000,
                    'processed' => true,
                    'uploadedAt' => '2024-06-10T10:30:00Z',
                    'status' => 'completed',
                    'chunks' => 45,
                    'tokens' => 12500
                ],
                [
                    'id' => 2,
                    'name' => 'FAQ corporativo.docx',
                    'type' => 'docx',
                    'size' => 1024000,
                    'processed' => false,
                    'uploadedAt' => '2024-06-11T14:22:00Z',
                    'status' => 'pending',
                    'chunks' => 0,
                    'tokens' => 0
                ]
            ];

            $stats = [
                'total' => 2,
                'processed' => 1,
                'pending' => 1,
                'totalTokens' => 12500,
                'totalChunks' => 45
            ];

            return response()->json([
                'success' => true,
                'documents' => $documents,
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error obteniendo documentos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sube un nuevo documento
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:10240|mimes:pdf,doc,docx,xls,xlsx,txt,png,jpg,jpeg',
        ]);

        try {
            $file = $request->file('file');
            $filename = Str::uuid() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('documents', $filename);

            // Detectar tipo de archivo
            $extension = $file->getClientOriginalExtension();
            $type = $this->getFileType($extension);

            $document = Document::create([
                'filename' => $file->getClientOriginalName(),
                'type' => $type,
                'file_path' => $filePath,
                'uploaded_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Documento subido exitosamente',
                'document' => $document->load('uploader:id,name')
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error subiendo documento: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lista todos los documentos
     */
    public function list(Request $request): JsonResponse
    {
        $query = Document::with('uploader:id,name');

        // Filtros opcionales
        if ($request->has('processed')) {
            $query->where('processed', $request->boolean('processed'));
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('filename', 'LIKE', "%{$search}%")
                  ->orWhere('content', 'LIKE', "%{$search}%");
            });
        }

        $documents = $query->orderBy('created_at', 'desc')->paginate(20);

        // Agregar información adicional
        $documents->getCollection()->transform(function ($document) {
            $document->size_formatted = $this->formatBytes($document->size);
            $document->status = $document->status;
            return $document;
        });

        return response()->json($documents);
    }

    /**
     * Elimina un documento
     */
    public function delete($id): JsonResponse
    {
        try {
            $document = Document::findOrFail($id);
            
            // Verificar permisos (solo el que subió o admin)
            if ($document->uploaded_by !== auth()->id() && !auth()->user()->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'error' => 'No tienes permisos para eliminar este documento'
                ], 403);
            }
            
            // Eliminar archivo físico
            if (Storage::exists($document->file_path)) {
                Storage::delete($document->file_path);
            }
            
            $document->delete();

            return response()->json([
                'success' => true,
                'message' => 'Documento eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error eliminando documento: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ⭐ ENDPOINT PRINCIPAL: Procesa todos los documentos pendientes
     */
    public function processAll(): JsonResponse
    {
        try {
            $results = $this->documentService->processAllDocuments();
            
            return response()->json([
                'success' => true,
                'message' => 'Procesamiento completado',
                'results' => $results
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error procesando documentos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Procesa un documento específico
     */
    public function process($id): JsonResponse
    {
        try {
            $document = Document::findOrFail($id);
            
            $processed = $this->documentService->processDocument($document);
            
            if ($processed) {
                return response()->json([
                    'success' => true,
                    'message' => 'Documento procesado exitosamente',
                    'document' => $document->fresh()->load('uploader:id,name')
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'Error procesando el documento'
                ], 422);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene el contenido de un documento
     */
    public function getContent($id): JsonResponse
    {
        try {
            $document = Document::findOrFail($id);
            
            if (!$document->processed) {
                return response()->json([
                    'success' => false,
                    'error' => 'Documento aún no procesado'
                ], 422);
            }

            return response()->json([
                'success' => true,
                'content' => $document->content,
                'document' => $document->load('uploader:id,name')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Busca documentos por contenido
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:3',
        ]);

        try {
            $query = $request->query;
            
            // Generar embedding de la consulta
            $queryEmbedding = $this->documentService->generateEmbeddings($query);
            
            if (empty($queryEmbedding)) {
                return response()->json([
                    'success' => false,
                    'error' => 'No se pudo procesar la consulta'
                ], 422);
            }

            // Buscar documentos similares
            $documents = Document::processed()
                ->whereNotNull('embeddings')
                ->get();

            $results = [];
            foreach ($documents as $document) {
                if (!empty($document->embeddings)) {
                    $similarity = $this->documentService->calculateSimilarity(
                        $queryEmbedding,
                        $document->embeddings
                    );
                    
                    if ($similarity > 0.7) {
                        $results[] = [
                            'document' => $document->load('uploader:id,name'),
                            'similarity' => $similarity,
                            'excerpt' => $this->getRelevantExcerpt($document->content, $query)
                        ];
                    }
                }
            }

            // Ordenar por similaridad descendente
            usort($results, function($a, $b) {
                return $b['similarity'] <=> $a['similarity'];
            });

            return response()->json([
                'success' => true,
                'results' => array_slice($results, 0, 10)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error en búsqueda: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene estadísticas de documentos
     */
    public function stats(): JsonResponse
    {
        $stats = [
            'total' => Document::count(),
            'processed' => Document::where('processed', true)->count(),
            'pending' => Document::where('processed', false)->count(),
            'failed' => Document::where('processed', false)
                ->where('created_at', '<', now()->subMinutes(10))
                ->count(),
            'by_type' => Document::selectRaw('type, count(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type'),
            'recent' => Document::with('uploader:id,name')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
        ];

        return response()->json([
            'success' => true,
            'stats' => $stats
        ]);
    }

    // Métodos auxiliares
    private function getFileType(string $extension): string
    {
        $typeMap = [
            'pdf' => 'pdf',
            'doc' => 'docx',
            'docx' => 'docx',
            'xls' => 'xlsx',
            'xlsx' => 'xlsx',
            'txt' => 'text',
            'png' => 'image',
            'jpg' => 'image',
            'jpeg' => 'image'
        ];

        return $typeMap[strtolower($extension)] ?? 'text';
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }

    private function getRelevantExcerpt(string $content, string $query): string
    {
        $queryWords = explode(' ', strtolower($query));
        $sentences = explode('.', $content);
        
        $bestSentence = '';
        $maxMatches = 0;
        
        foreach ($sentences as $sentence) {
            $matches = 0;
            $lowerSentence = strtolower($sentence);
            
            foreach ($queryWords as $word) {
                if (strlen($word) > 2 && strpos($lowerSentence, $word) !== false) {
                    $matches++;
                }
            }
            
            if ($matches > $maxMatches) {
                $maxMatches = $matches;
                $bestSentence = trim($sentence);
            }
        }
        
        return strlen($bestSentence) > 200 ? 
            substr($bestSentence, 0, 200) . '...' : 
            $bestSentence;
    }
}
