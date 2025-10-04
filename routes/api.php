<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OpenAi\ContentAnalysisController;
use App\Http\Controllers\OpenAi\TosGenerationController;
use App\Http\Controllers\OpenAi\QuizGenerationController;
use App\Http\Controllers\OpenAi\QuestionRegenerationController;
use App\Http\Controllers\OpenAi\FeedbackGenerationController;
use App\Http\Controllers\OpenAi\ApiUsageController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// OpenAI API Routes
Route::prefix('openai')->middleware(['auth:sanctum'])->group(function () {
    
    // Content Analysis
    Route::prefix('content')->group(function () {
        Route::post('analyze', [ContentAnalysisController::class, 'analyze'])
            ->middleware(['openai.access', 'openai.rate-limit', 'openai.validate']);
        
        Route::get('analysis/{material}', [ContentAnalysisController::class, 'show']);
    });

    // Table of Specification
    Route::prefix('tos')->group(function () {
        Route::post('generate', [TosGenerationController::class, 'generate'])
            ->middleware(['openai.access', 'openai.rate-limit']);
        
        Route::get('{tos}', [TosGenerationController::class, 'show']);
        
        Route::get('material/{material}', [TosGenerationController::class, 'getByMaterial']);
    });

    // Quiz Generation
    Route::prefix('quiz')->group(function () {
        Route::post('generate', [QuizGenerationController::class, 'generate'])
            ->middleware(['openai.access', 'openai.rate-limit']);
        
        Route::get('{quiz}', [QuizGenerationController::class, 'show']);
        
        Route::post('{quiz}/publish', [QuizGenerationController::class, 'publish']);
    });

    // Question Regeneration
    Route::prefix('questions')->group(function () {
        Route::post('{question}/regenerate', [QuestionRegenerationController::class, 'regenerate'])
            ->middleware(['openai.access', 'openai.rate-limit']);
        
        Route::get('{question}/regenerations', [QuestionRegenerationController::class, 'getRegenerations']);
        
        Route::get('{question}/can-regenerate', [QuestionRegenerationController::class, 'canRegenerate']);
    });

    // Feedback Generation
    Route::prefix('feedback')->group(function () {
        Route::post('generate', [FeedbackGenerationController::class, 'generate'])
            ->middleware(['openai.access', 'openai.rate-limit']);
        
        Route::get('attempt/{quizAttempt}', [FeedbackGenerationController::class, 'getByAttempt']);
        
        Route::get('{feedback}', [FeedbackGenerationController::class, 'show']);
    });

    // API Usage & Statistics
    Route::prefix('usage')->group(function () {
        Route::get('stats', [ApiUsageController::class, 'stats']);
        
        Route::get('logs', [ApiUsageController::class, 'logs']);
        
        Route::get('cost-breakdown', [ApiUsageController::class, 'costBreakdown']);
        
        Route::get('export', [ApiUsageController::class, 'export']);
    });

    // Health Check
    Route::get('health', function () {
        try {
            $apiKey = config('services.openai.api_key');
            return response()->json([
                'status' => 'healthy',
                'api_configured' => !empty($apiKey),
                'model' => config('services.openai.model'),
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ], 500);
        }
    });
});

// Webhook Routes (if needed for OpenAI callbacks)
Route::prefix('webhooks')->group(function () {
    Route::post('openai/status', function () {
        // Handle OpenAI status webhooks
        return response()->json(['received' => true]);
    });
});