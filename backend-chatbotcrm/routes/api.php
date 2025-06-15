<?php

// routes/api.php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\{
    AuthController,
    DocumentController,
    // Otros controladores que agregues en módulos posteriores
};

// Rutas públicas de autenticación
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);

// Rutas protegidas que requieren autenticación
Route::middleware('auth:sanctum')->group(function () {
    
    // Autenticación
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/user', [AuthController::class, 'user']);
    
    //  MÓDULO 1: DOCUMENTOS (Motor IA - Parte 1)
    Route::prefix('documents')->group(function () {
        Route::get('/', [DocumentController::class, 'index']);
        Route::post('/upload', [DocumentController::class, 'upload']);
      
        Route::get('/list', [DocumentController::class, 'list']);
        Route::delete('/{id}', [DocumentController::class, 'delete']);
        Route::post('/process', [DocumentController::class, 'processAll']);
        Route::post('/process/{id}', [DocumentController::class, 'process']);
        Route::get('/content/{id}', [DocumentController::class, 'getContent']);
        Route::post('/search', [DocumentController::class, 'search']);
        Route::get('/stats', [DocumentController::class, 'stats']);
    });
    
    // MÓDULO 2: Chat IA (se agregará después)
    // MÓDULO 3: CRM (se agregará después)
    
}); // Fin del grupo de rutas protegidas

// Rutas públicas adicionales
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok', 
        'module' => 1,
        'message' => 'Chatbot CRM API funcionando correctamente',
        'timestamp' => now()
    ]);
});