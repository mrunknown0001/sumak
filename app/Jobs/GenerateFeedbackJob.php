<?php

namespace App\Jobs;

use App\Models\QuizAttempt;
use App\Models\Feedback;
use App\Models\StudentAbility;
use App\Services\OpenAiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateFeedbackJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes

    protected int $attemptId;

    public function __construct(int $attemptId)
    {
        $this->attemptId = $attemptId;
    }

    public function handle(OpenAiService $openAiService): void
    {
        try {
            $attempt = QuizAttempt::with([
                'responses.item',
                'subtopic',
                'user'
            ])->findOrFail($this->attemptId);
            
            // Check if feedback already exists
            if ($attempt->feedback) {
                Log::info("Feedback already exists", ['attempt_id' => $attempt->id]);
                return;
            }
            
            // Prepare quiz attempt data
            $quizAttemptData = [
                'subtopic' => $attempt->subtopic->name,
                'attempt_number' => $attempt->attempt_number,
                'score_percentage' => $attempt->score_percentage,
                'total_questions' => $attempt->total_questions,
                'correct_answers' => $attempt->correct_answers,
                'time_spent_minutes' => $attempt->time_spent_minutes,
                'responses' => $attempt->responses->map(fn($r) => [
                    'question' => $r->item->question,
                    'cognitive_level' => $r->item->cognitive_level,
                    'difficulty' => $r->item->difficulty_b,
                    'is_correct' => $r->is_correct,
                    'time_taken' => $r->time_taken_seconds,
                ])->toArray(),
            ];
            
            // Get student mastery data
            $studentAbility = StudentAbility::where('user_id', $attempt->user_id)
                ->where('subtopic_id', $attempt->subtopic_id)
                ->first();
                
            $userMasteryData = [
                'current_theta' => $studentAbility ? $studentAbility->theta : 0,
                'proficiency_level' => $studentAbility ? $studentAbility->proficiency_level : 'Beginner',
                'attempts_count' => $studentAbility ? $studentAbility->attempts_count : 1,
                'performance_trend' => $this->getPerformanceTrend($attempt),
            ];
            
            // Generate feedback using AI
            $feedbackData = $openAiService->generateFeedback($quizAttemptData, $userMasteryData);

            $weaknesses = $feedbackData['areas_for_improvement'] ?? [];
            $recommendations = $feedbackData['specific_recommendations'] ?? [];

            if ((float) $attempt->score_percentage >= 100) {
                $weaknesses = [];
                $recommendations = [];
            }
            
            // Save feedback
            Feedback::create([
                'quiz_attempt_id' => $attempt->id,
                'subtopic_id' => $attempt->subtopic_id,
                'user_id' => $attempt->user_id,
                'feedback_text' => $feedbackData['overall_feedback'],
                'strengths' => $feedbackData['strengths'] ?? [],
                'weaknesses' => $weaknesses,
                'recommendations' => $recommendations,
                'next_steps' => $feedbackData['next_steps'] ?? [],
                'motivational_message' => $feedbackData['motivational_message'] ?? null,
                'generated_at' => now(),
            ]);
            
            Log::info("Feedback generated successfully", ['attempt_id' => $attempt->id]);
            
        } catch (\Exception $e) {
            Log::error("Failed to generate feedback", [
                'attempt_id' => $this->attemptId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    protected function getPerformanceTrend(QuizAttempt $currentAttempt): string
    {
        $previousAttempts = QuizAttempt::where('user_id', $currentAttempt->user_id)
            ->where('subtopic_id', $currentAttempt->subtopic_id)
            ->where('id', '<', $currentAttempt->id)
            ->whereNotNull('completed_at')
            ->orderBy('completed_at', 'desc')
            ->limit(3)
            ->pluck('score_percentage')
            ->toArray();
            
        if (empty($previousAttempts)) {
            return 'first_attempt';
        }
        
        $currentScore = $currentAttempt->score_percentage;
        $avgPreviousScore = array_sum($previousAttempts) / count($previousAttempts);
        
        if ($currentScore > $avgPreviousScore + 10) {
            return 'improving';
        } elseif ($currentScore < $avgPreviousScore - 10) {
            return 'declining';
        } else {
            return 'stable';
        }
    }
}