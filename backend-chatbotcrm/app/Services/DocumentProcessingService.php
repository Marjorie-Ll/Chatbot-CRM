<?php
// app/Services/DocumentProcessingService.php

namespace App\Services;

use App\Models\Document;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\PdfToText\Pdf;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Intervention\Image\ImageManagerStatic as Image;
use OpenAI\Client;

class DocumentProcessingService
{
    protected $openai;

    public function __construct()
    {
        $this->openai = \OpenAI::client(config('services.openai.key'));
    }

    /**
     * Procesa un documento específico
     */
    public function processDocument(Document $document): bool
    {
        try {
            $filePath = Storage::path($document->file_path);
            
            if (!file_exists($filePath)) {
                Log::error("File not found: {$filePath}");
                return false;
            }

            $content = $this->extractContent($filePath, $document->type);
            
            if (empty($content)) {
                Log::warning("No content extracted from document: {$document->filename}");
                return false;
            }

            // Generar embeddings si el contenido es válido
            $embeddings = $this->generateEmbeddings($content);

            // Actualizar documento
            $document->update([
                'content' => $content,
                'embeddings' => $embeddings,
                'processed' => true,
                'processed_at' => now(),
            ]);

            Log::info("Document processed successfully: {$document->filename}");
            return true;

        } catch (\Exception $e) {
            Log::error("Error processing document {$document->filename}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Procesa todos los documentos pendientes
     */
    public function processAllDocuments(): array
    {
        $documents = Document::unprocessed()->get();
        $results = [
            'total' => $documents->count(),
            'processed' => 0,
            'failed' => 0,
            'errors' => []
        ];

        foreach ($documents as $document) {
            if ($this->processDocument($document)) {
                $results['processed']++;
            } else {
                $results['failed']++;
                $results['errors'][] = "Failed to process: {$document->filename}";
            }
        }

        return $results;
    }

    /**
     * Extrae contenido según el tipo de archivo
     */
    private function extractContent(string $filePath, string $type): string
    {
        switch ($type) {
            case 'pdf':
                return $this->extractFromPdf($filePath);
            case 'docx':
                return $this->extractFromDocx($filePath);
            case 'xlsx':
                return $this->extractFromExcel($filePath);
            case 'image':
                return $this->extractFromImage($filePath);
            case 'text':
                return file_get_contents($filePath);
            default:
                throw new \InvalidArgumentException("Unsupported file type: {$type}");
        }
    }

    /**
     * Extrae texto de PDF
     */
    private function extractFromPdf(string $filePath): string
    {
        try {
            return Pdf::getText($filePath);
        } catch (\Exception $e) {
            Log::error("PDF extraction error: " . $e->getMessage());
            return '';
        }
    }

    /**
     * Extrae texto de DOCX
     */
    private function extractFromDocx(string $filePath): string
    {
        try {
            $zip = new \ZipArchive();
            if ($zip->open($filePath) === true) {
                $xml = $zip->getFromName("word/document.xml");
                $zip->close();
                
                if ($xml) {
                    $xml = str_replace('</w:p>', "\n", $xml);
                    return strip_tags($xml);
                }
            }
            return '';
        } catch (\Exception $e) {
            Log::error("DOCX extraction error: " . $e->getMessage());
            return '';
        }
    }

    /**
     * Extrae texto de Excel
     */
    private function extractFromExcel(string $filePath): string
    {
        try {
            $spreadsheet = IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $content = '';
            
            foreach ($worksheet->getRowIterator() as $row) {
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);
                
                $rowData = [];
                foreach ($cellIterator as $cell) {
                    $rowData[] = $cell->getValue();
                }
                $content .= implode(' | ', $rowData) . "\n";
            }
            
            return $content;
        } catch (\Exception $e) {
            Log::error("Excel extraction error: " . $e->getMessage());
            return '';
        }
    }

    /**
     * Extrae texto de imagen usando OCR
     */
    private function extractFromImage(string $filePath): string
    {
        try {
            // Mejorar imagen para OCR
            $image = Image::make($filePath);
            $image->contrast(50);
            $image->brightness(10);
            
            $tempPath = storage_path('app/temp/ocr_' . uniqid() . '.png');
            $image->save($tempPath);
            
            // Aquí integrarías con un servicio OCR como Tesseract
            $text = $this->performOCR($tempPath);
            
            // Limpiar archivo temporal
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }
            
            return $text;
        } catch (\Exception $e) {
            Log::error("Image OCR error: " . $e->getMessage());
            return '';
        }
    }

    /**
     * Realiza OCR en la imagen (placeholder)
     */
    private function performOCR(string $imagePath): string
    {
        // Implementar integración con Tesseract OCR o servicio cloud
        return "Contenido extraído por OCR de la imagen";
    }

    /**
     * Genera embeddings usando OpenAI
     */
    public function generateEmbeddings(string $text): array
    {
        try {
            $cleanText = $this->cleanText($text);
            
            if (strlen($cleanText) < 10) {
                return [];
            }

            if (strlen($cleanText) > 8000) {
                $cleanText = substr($cleanText, 0, 8000);
            }

            $response = $this->openai->embeddings()->create([
                'model' => 'text-embedding-ada-002',
                'input' => $cleanText,
            ]);

            return $response->embeddings[0]->embedding;

        } catch (\Exception $e) {
            Log::error("Embeddings generation error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Limpia el texto para procesamiento
     */
    private function cleanText(string $text): string
    {
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        
        return $text;
    }

    /**
     * Calcula similaridad entre textos usando embeddings
     */
    public function calculateSimilarity(array $embedding1, array $embedding2): float
    {
        if (empty($embedding1) || empty($embedding2)) {
            return 0.0;
        }

        $dotProduct = 0;
        $magnitude1 = 0;
        $magnitude2 = 0;

        for ($i = 0; $i < count($embedding1); $i++) {
            $dotProduct += $embedding1[$i] * $embedding2[$i];
            $magnitude1 += $embedding1[$i] * $embedding1[$i];
            $magnitude2 += $embedding2[$i] * $embedding2[$i];
        }

        $magnitude1 = sqrt($magnitude1);
        $magnitude2 = sqrt($magnitude2);

        if ($magnitude1 == 0 || $magnitude2 == 0) {
            return 0.0;
        }

        return $dotProduct / ($magnitude1 * $magnitude2);
    }
}
