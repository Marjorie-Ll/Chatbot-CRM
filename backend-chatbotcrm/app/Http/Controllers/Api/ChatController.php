<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\AIService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ChatController extends Controller
{
    protected $aiService;

    public function __construct(AIService $aiService)
    {
        $this->aiService = $aiService;
    }

    public function sendMessage(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:2000',
            'conversation_id' => 'nullable|exists:conversations,id',
            'channel' => 'required|in:web,whatsapp,api',
            'contact_phone' => 'nullable|string',
            'contact_name' => 'nullable|string',
        ]);

        try {
            // Crear o encontrar conversación
            $conversation = $this->findOrCreateConversation($request);
            
            // Guardar mensaje del usuario
            $userMessage = Message::create([
                'conversation_id' => $conversation->id,
                'content' => $request->message,
                'sender' => 'user',
                'type' => 'text',
            ]);

            // Generar respuesta de IA
            $aiResponse = $this->aiService->generateResponse($request->message, $conversation);
            
            // Guardar respuesta de IA
            $aiMessage = Message::create([
                'conversation_id' => $conversation->id,
                'content' => $aiResponse['response'],
                'sender' => 'ai',
                'type' => 'text',
                'confidence_score' => $aiResponse['confidence'] ?? null,
                'metadata' => $aiResponse['metadata'] ?? null,
            ]);

            // Actualizar última actividad de la conversación
            $conversation->update(['last_message_at' => now()]);

            return response()->json([
                'success' => true,
                'conversation_id' => $conversation->id,
                'user_message' => $userMessage,
                'ai_response' => $aiMessage,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error procesando mensaje: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getConversations(Request $request): JsonResponse
    {
        $conversations = Conversation::with(['latestMessage'])
            ->orderBy('last_message_at', 'desc')
            ->paginate(20);

        return response()->json($conversations);
    }

    public function getConversationHistory($id): JsonResponse
    {
        $conversation = Conversation::with(['messages' => function($query) {
            $query->orderBy('created_at', 'asc');
        }])->findOrFail($id);

        return response()->json($conversation);
    }

    private function findOrCreateConversation(Request $request): Conversation
    {
        if ($request->conversation_id) {
            return Conversation::findOrFail($request->conversation_id);
        }

        $data = [
            'channel' => $request->channel,
            'status' => 'active',
            'last_message_at' => now(),
        ];

        if ($request->contact_phone) {
            $data['contact_phone'] = $request->contact_phone;
        }
        if ($request->contact_name) {
            $data['contact_name'] = $request->contact_name;
        }

        return Conversation::create($data);
    }
}
