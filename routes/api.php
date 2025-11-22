<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\StudentDashboardController;
use App\Http\Controllers\OpenAi\ContentAnalysisController;
use App\Http\Controllers\OpenAi\TosGenerationController;
use App\Http\Controllers\OpenAi\QuizGenerationController;
use App\Http\Controllers\OpenAi\QuestionRegenerationController;
use App\Http\Controllers\OpenAi\FeedbackGenerationController;
use App\Http\Controllers\OpenAi\ApiUsageController;

// Public routes
Route::post('/login', [\App\Http\Controllers\LoginController::class, 'login']);
// Route::post('/register', [\App\Http\Controllers\RegisterController::class, 'register']);

// Authenticated routes
Route::middleware('auth:sanctum')->group(function () {
    
    // User info
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    // Student Dashboard
    Route::prefix('dashboard')->group(function () {
        Route::get('/', [StudentDashboardController::class, 'index']);
        Route::get('/course/{course}/progress', [StudentDashboardController::class, 'courseProgress']);
        Route::get('/analytics', [StudentDashboardController::class, 'analytics']);
    });
    
    // Courses
    Route::prefix('courses')->group(function () {
        Route::get('/', [CourseController::class, 'index']);
        Route::post('/', [CourseController::class, 'store']);
        Route::get('/{course}', [CourseController::class, 'show']);
        Route::put('/{course}', [CourseController::class, 'update']);
        Route::delete('/{course}', [CourseController::class, 'destroy']);
        
        // Documents within courses
        Route::post('/{course}/documents', [DocumentController::class, 'store']);
    });
    
    // Documents
    Route::prefix('documents')->group(function () {
        Route::get('/{document}', [DocumentController::class, 'show']);
        Route::get('/{document}/status', [DocumentController::class, 'status']);
        Route::get('/{document}/download', [DocumentController::class, 'download']);
        Route::delete('/{document}', [DocumentController::class, 'destroy']);
    });
    
    // Quiz Management
    Route::prefix('quiz')->group(function () {
        // Start quiz for subtopic
        Route::post('/subtopic/{subtopic}/start', [QuizController::class, 'start']);
        
        // Submit answer
        Route::post('/attempt/{attempt}/answer', [QuizController::class, 'submitAnswer']);
        
        // Complete quiz
        Route::post('/attempt/{attempt}/complete', [QuizController::class, 'complete']);
        
        // Get results
        Route::get('/attempt/{attempt}/results', [QuizController::class, 'results']);
    });
    
    // OpenAI API Routes
    Route::prefix('openai')->group(function () {
        
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
});

// Webhook Routes (if needed for OpenAI callbacks)
Route::prefix('webhooks')->group(function () {
    Route::post('openai/status', function () {
        // Handle OpenAI status webhooks
        return response()->json(['received' => true]);
    });
});

Route::get('/learning-outcomes', function () {
    $lo = \App\Models\LearningOutcome::all();

    return $lo;
});

Route::get('/tos-items', function() {
    $tositems = \App\Models\TosItem::all();

    return $tositems;
});