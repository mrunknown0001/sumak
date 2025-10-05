<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Student;
use App\Models\Course;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Services\IrtService;
use App\Services\AIFeedbackService;

class StudentDashboardController extends Controller
{
    protected $irtService;
    protected $aiFeedbackService;

    public function __construct(IrtService $irtService, AIFeedbackService $aiFeedbackService)
    {
        $this->irtService = $irtService;
        $this->aiFeedbackService = $aiFeedbackService;
    }

    /**
     * Get dashboard data for the authenticated student
     * This method would be called from the Livewire component
     */
    public function getDashboardData()
    {
        $student = Auth::user();

        return [
            'student_info' => $this->getStudentInfo($student),
            'courses' => $this->getActiveCourses($student),
            'recent_quizzes' => $this->getRecentQuizzes($student),
            'ai_feedback' => $this->getAIFeedback($student),
            'overall_stats' => $this->getOverallStats($student),
        ];
    }

    /**
     * Get basic student information
     */
    private function getStudentInfo($student)
    {
        return [
            'name' => $student->name,
            'student_id' => $student->student_id,
            'email' => $student->email,
        ];
    }

    /**
     * Get active courses with progress and ability levels
     */
    private function getActiveCourses($student)
    {
        return $student->courses()
            ->where('status', 'active')
            ->get()
            ->map(function ($course) use ($student) {
                $quizzesTaken = $this->getQuizzesTakenCount($student, $course);
                $totalQuizzes = $course->quizzes()->count();
                $avgScore = $this->getAverageScore($student, $course);
                $abilityLevel = $this->irtService->calculateStudentAbility($student, $course);

                return [
                    'id' => $course->id,
                    'name' => $course->name,
                    'code' => $course->code,
                    'progress' => $totalQuizzes > 0 ? round(($quizzesTaken / $totalQuizzes) * 100) : 0,
                    'quizzes_taken' => $quizzesTaken,
                    'total_quizzes' => $totalQuizzes,
                    'avg_score' => round($avgScore, 2),
                    'ability_level' => $abilityLevel,
                    'status' => $course->pivot->status ?? 'active',
                ];
            })
            ->toArray();
    }

    /**
     * Get recent quiz attempts with results
     */
    private function getRecentQuizzes($student, $limit = 10)
    {
        return QuizAttempt::where('student_id', $student->id)
            ->with(['quiz.course'])
            ->orderBy('completed_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($attempt) use ($student) {
                $attemptsCount = QuizAttempt::where('student_id', $student->id)
                    ->where('quiz_id', $attempt->quiz_id)
                    ->count();

                $abilityEstimate = $this->irtService->estimateAbilityFromAttempt($attempt);

                return [
                    'id' => $attempt->id,
                    'quiz_id' => $attempt->quiz_id,
                    'course' => $attempt->quiz->course->name,
                    'topic' => $attempt->quiz->topic,
                    'score' => $attempt->correct_answers,
                    'total' => $attempt->total_questions,
                    'date' => $attempt->completed_at->format('Y-m-d'),
                    'duration' => $this->formatDuration($attempt->duration_seconds),
                    'attempts_used' => $attemptsCount,
                    'attempts_remaining' => max(0, 3 - $attemptsCount),
                    'ability_estimate' => $abilityEstimate,
                ];
            })
            ->toArray();
    }

    /**
     * Get AI-generated feedback for recent quizzes
     */
    private function getAIFeedback($student, $limit = 5)
    {
        $recentAttempts = QuizAttempt::where('student_id', $student->id)
            ->with(['quiz.course'])
            ->orderBy('completed_at', 'desc')
            ->limit($limit)
            ->get();

        return $recentAttempts->map(function ($attempt) {
            // Generate AI feedback using ChatGPT API
            $feedback = $this->aiFeedbackService->generateFeedback($attempt);

            return [
                'course' => $attempt->quiz->course->name,
                'topic' => $attempt->quiz->topic,
                'feedback' => $feedback['main_feedback'],
                'recommendations' => $feedback['recommendations'],
                'strengths' => $feedback['strengths'],
                'areas_to_improve' => $feedback['areas_to_improve'],
            ];
        })->toArray();
    }

    /**
     * Get overall statistics for the student
     */
    private function getOverallStats($student)
    {
        $totalQuizzes = QuizAttempt::where('student_id', $student->id)->count();
        $avgAccuracy = QuizAttempt::where('student_id', $student->id)
            ->selectRaw('AVG(correct_answers * 100.0 / total_questions) as avg_accuracy')
            ->value('avg_accuracy');

        $totalStudyTime = QuizAttempt::where('student_id', $student->id)
            ->sum('duration_seconds');

        $overallAbility = $this->irtService->calculateOverallAbility($student);
        $masteryLevel = $this->determineMasteryLevel($overallAbility);

        return [
            'total_quizzes_taken' => $totalQuizzes,
            'avg_accuracy' => round($avgAccuracy, 2),
            'total_study_time' => $this->formatStudyTime($totalStudyTime),
            'mastery_level' => $masteryLevel,
            'overall_ability' => $overallAbility,
        ];
    }

    /**
     * Helper: Get count of unique quizzes taken by student in a course
     */
    private function getQuizzesTakenCount($student, $course)
    {
        return QuizAttempt::where('student_id', $student->id)
            ->whereHas('quiz', function ($query) use ($course) {
                $query->where('course_id', $course->id);
            })
            ->distinct('quiz_id')
            ->count('quiz_id');
    }

    /**
     * Helper: Calculate average score for a student in a course
     */
    private function getAverageScore($student, $course)
    {
        return QuizAttempt::where('student_id', $student->id)
            ->whereHas('quiz', function ($query) use ($course) {
                $query->where('course_id', $course->id);
            })
            ->selectRaw('AVG(correct_answers * 100.0 / total_questions) as avg_score')
            ->value('avg_score') ?? 0;
    }

    /**
     * Helper: Format duration from seconds to readable format
     */
    private function formatDuration($seconds)
    {
        $minutes = floor($seconds / 60);
        return $minutes . ' mins';
    }

    /**
     * Helper: Format total study time
     */
    private function formatStudyTime($seconds)
    {
        $hours = floor($seconds / 3600);
        return $hours . ' hours';
    }

    /**
     * Helper: Determine mastery level based on IRT ability
     */
    private function determineMasteryLevel($ability)
    {
        if ($ability >= 0.8) return 'Expert';
        if ($ability >= 0.6) return 'Advanced';
        if ($ability >= 0.4) return 'Intermediate';
        if ($ability >= 0.2) return 'Advanced Beginner';
        return 'Beginner';
    }
}