<?php

// database/seeders/DocumentSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Document;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class DocumentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('email', 'admin@chatbotcrm.com')->first();
        $demo = User::where('email', 'demo@chatbotcrm.com')->first();

        if (!$admin || !$demo) {
            $this->command->warn('⚠️  Usuarios no encontrados. Ejecuta UserSeeder primero.');
            return;
        }

        // Crear directorio de documentos si no existe
        if (!Storage::exists('documents')) {
            Storage::makeDirectory('documents');
        }

        $documents = [
            [
                'filename' => 'Manual de Usuario CRM.pdf',
                'type' => 'pdf',
                'content' => 'Este es el manual completo del sistema CRM. Incluye instrucciones detalladas sobre cómo gestionar leads, contactos y oportunidades de venta. El sistema permite automatizar procesos de seguimiento y generar reportes de ventas.',
                'processed' => true,
                'uploaded_by' => $admin->id,
            ],
            [
                'filename' => 'FAQ Soporte Técnico.docx',
                'type' => 'docx',
                'content' => 'Preguntas frecuentes sobre soporte técnico: 1. ¿Cómo resetear contraseña? 2. ¿Cómo contactar soporte? 3. ¿Horarios de atención? 4. ¿Cómo reportar bugs? 5. ¿Actualizaciones del sistema?',
                'processed' => true,
                'uploaded_by' => $admin->id,
            ],
            [
                'filename' => 'Políticas de Privacidad.txt',
                'type' => 'text',
                'content' => 'Políticas de Privacidad del Chatbot CRM: Recopilamos información para mejorar el servicio. Los datos están protegidos con encriptación. No compartimos información personal con terceros sin consentimiento.',
                'processed' => true,
                'uploaded_by' => $demo->id,
            ],
            [
                'filename' => 'Guía de Integración API.md',
                'type' => 'text',
                'content' => '# Guía de Integración API\n\n## Autenticación\nUsa tokens Bearer para autenticarte.\n\n## Endpoints principales\n- GET /api/documents - Listar documentos\n- POST /api/documents/upload - Subir documento\n- POST /api/chat/message - Enviar mensaje',
                'processed' => false,
                'uploaded_by' => $admin->id,
            ],
            [
                'filename' => 'Datos de Ventas Q4.csv',
                'type' => 'xlsx',
                'content' => 'Mes,Ventas,Leads,Conversión\nOctubre,125000,450,28%\nNoviembre,138000,520,26%\nDiciembre,165000,600,27%\nTotal Q4,428000,1570,27%',
                'processed' => false,
                'uploaded_by' => $demo->id,
            ],
        ];

        foreach ($documents as $index => $docData) {
            // Crear archivo físico ficticio
            $filePath = "documents/demo_document_{$index}." . ($docData['type'] === 'text' ? 'txt' : $docData['type']);
            Storage::put($filePath, $docData['content']);

            // Generar embeddings ficticios si está procesado
            $embeddings = null;
            if ($docData['processed']) {
                $embeddings = $this->generateMockEmbeddings();
            }

            Document::create([
                'filename' => $docData['filename'],
                'type' => $docData['type'],
                'file_path' => $filePath,
                'content' => $docData['content'],
                'embeddings' => $embeddings,
                'processed' => $docData['processed'],
                'processed_at' => $docData['processed'] ? now()->subDays(rand(1, 30)) : null,
                'uploaded_by' => $docData['uploaded_by'],
                'created_at' => now()->subDays(rand(1, 60)),
            ]);
        }

        //
        $this->command->info("✅ " . (count($documents)) . " documentos de prueba creados");
    }

    /**
     * Generar embeddings ficticios para demostración
     */
    private function generateMockEmbeddings(): array
    {
        // Generar array de 1536 elementos (tamaño de embeddings de OpenAI)
        $embeddings = [];
        for ($i = 0; $i < 1536; $i++) {
            $embeddings[] = (float) rand(-100, 100) / 100; // Valores entre -1 y 1
        }
        return $embeddings;
    }
}