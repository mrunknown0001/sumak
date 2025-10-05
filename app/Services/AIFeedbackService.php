<?php

namespace App\Services;

use App\Models\QuizAttempt;
use Illuminate\Support\Facades\Http;

class AIFeedbackService
{
    protected $apiKey;
    protected $apiUrl;

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');
        $this->apiUrl = 'https://api.openai.com/v1/chat/completions';
    }

    /**
     * Generate AI-powered feedback using ChatGPT API
     */
    public function generateFeedback(QuizAttempt $attempt)
    {
        $prompt = $this->buildPrompt($attempt);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post($this->apiUrl, [
            'model' => 'gpt-4',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are an educational assistant providing personalized, formative feedback to students.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.7,
        ]);

        return $this->parseFeedbackResponse($response->json());
    }

    private function buildPrompt(QuizAttempt $attempt)
    {
        return "Generate personalized feedback for a student who scored {$attempt->correct_answers} out of {$attempt->total_questions} on a quiz about {$attempt->quiz->topic}. Include: 1) Main feedback, 2) Strengths, 3) Areas to improve, 4) Specific recommendations.";
    }

    private function parseFeedbackResponse($response)
    {
        $content = $response['choices'][0]['message']['content'] ?? '';
        
        // Parse the response into structured feedback
        // This is a simplified example
        return [
            'main_feedback' => $content,
            'recommendations' => ['Review key concepts', 'Practice more problems'],
            'strengths' => ['Good understanding of basics'],
            'areas_to_improve' => ['Advanced applications'],
        ];
    }
}