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
     * Get enrolled courses with progress and ability levels
     */
    private function getActiveCourses($student)
    {
        return Course::whereHas('enrollments', function($q) use ($student) {
                $q->where('user_id', $student->id);
            })
            ->with(['obtlDocument', 'documents'])
            ->get()
            ->map(function ($course) use ($student) {
                // Get total subtopics across all documents in this course
                $totalSubtopics = $course->documents()
                    ->with('topics.subtopics')
                    ->get()
                    ->flatMap(fn($doc) => $doc->topics)
                    ->flatMap(fn($topic) => $topic->subtopics)
                    ->count();

                // Display constraint: Only 20 quizzes are generated per course
                $maxQuizzesPerCourse = 20;
                $displayTotalQuizzes = min($totalSubtopics, $maxQuizzesPerCourse);

                // Get completed subtopics (where student has at least one attempt)
                $completedSubtopics = QuizAttempt::where('user_id', $student->id)
                    ->whereHas('subtopic.topic.document', function($q) use ($course) {
                        $q->where('course_id', $course->id);
                    })
                    ->distinct('subtopic_id')
                    ->count('subtopic_id');

                $displayQuizzesTaken = min($completedSubtopics, $displayTotalQuizzes);

                $avgScore = $this->getAverageScore($student, $course);
                $abilityLevel = $this->calculateAbilityForCourse($student, $course);

                return [
                    'id' => $course->id,
                    'name' => $course->course_title,
                    'code' => $course->course_code,
                    'progress' => $displayTotalQuizzes > 0 ? round(($displayQuizzesTaken / $displayTotalQuizzes) * 100) : 0,
                    'quizzes_taken' => $displayQuizzesTaken,
                    'total_quizzes' => $displayTotalQuizzes,
                    'avg_score' => round($avgScore, 2),
                    'ability_level' => $abilityLevel,
                    'status' => 'active',
                ];
            })
            ->toArray();
    }

    /**
     * Get recent quiz attempts with results
     */
    private function getRecentQuizzes($student, $limit = 10)
    {
        return QuizAttempt::where('user_id', $student->id)
            ->with(['subtopic.topic.document.course'])
            ->whereNotNull('completed_at')
            ->orderBy('completed_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($attempt) use ($student) {
                $attemptsCount = QuizAttempt::where('user_id', $student->id)
                    ->where('subtopic_id', $attempt->subtopic_id)
                    ->count();

                $abilityEstimate = $this->irtService->estimateAbilityFromAttempt($attempt);

                return [
                    'id' => $attempt->id,
                    'quiz_id' => $attempt->subtopic_id,
                    'course' => $attempt->subtopic->topic->document->course->course_title,
                    'topic' => $attempt->subtopic->name,
                    'score' => $attempt->correct_answers,
                    'total' => $attempt->total_questions,
                    'date' => $attempt->completed_at->format('Y-m-d'),
                    'duration' => $this->formatDuration($attempt->time_spent_seconds),
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
        $recentAttempts = QuizAttempt::where('user_id', $student->id)
            ->with(['feedback', 'subtopic.topic.document.course'])
            ->whereNotNull('completed_at')
            ->orderBy('completed_at', 'desc')
            ->limit($limit)
            ->get()
            ->filter(fn($attempt) => $attempt->feedback);

        return $recentAttempts->map(function ($attempt) {
            return [
                'course' => $attempt->subtopic->topic->document->course->course_title,
                'topic' => $attempt->subtopic->name,
                'feedback' => $attempt->feedback->feedback_text ?? 'Feedback is being generated...',
                'recommendations' => $this->normalizeFeedbackField($attempt->feedback->recommendations ?? null),
                'strengths' => $this->normalizeFeedbackField($attempt->feedback->strengths ?? null),
                'areas_to_improve' => $this->normalizeFeedbackField($attempt->feedback->areas_to_improve ?? null),
            ];
        })->toArray();
    }

    /**
     * Get overall statistics for the student
     */
    private function getOverallStats($student)
    {
        $totalQuizzes = QuizAttempt::where('user_id', $student->id)
            ->whereNotNull('completed_at')
            ->count();
        
        $avgAccuracy = QuizAttempt::where('user_id', $student->id)
            ->whereNotNull('completed_at')
            ->selectRaw('AVG(correct_answers * 100.0 / total_questions) as avg_accuracy')
            ->value('avg_accuracy') ?? 0;

        $totalStudyTime = QuizAttempt::where('user_id', $student->id)
            ->whereNotNull('completed_at')
            ->sum('time_spent_seconds');

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
     * Helper: Calculate average score for a student in a course
     */
    private function getAverageScore($student, $course)
    {
        return QuizAttempt::where('user_id', $student->id)
            ->whereHas('subtopic.topic.document', function ($query) use ($course) {
                $query->where('course_id', $course->id);
            })
            ->selectRaw('AVG(correct_answers * 100.0 / total_questions) as avg_score')
            ->value('avg_score') ?? 0;
    }

    /**
     * Helper: Calculate ability level for a course
     */
    private function calculateAbilityForCourse($student, $course)
    {
        $attempts = QuizAttempt::where('user_id', $student->id)
            ->whereHas('subtopic.topic.document', function ($query) use ($course) {
                $query->where('course_id', $course->id);
            })
            ->get();

        if ($attempts->isEmpty()) {
            return 0.5;
        }

        $totalCorrect = $attempts->sum('correct_answers');
        $totalQuestions = $attempts->sum('total_questions');

        return $totalQuestions > 0 ? $totalCorrect / $totalQuestions : 0.5;
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

    /**
     * Normalize stored feedback fields that may be JSON, arrays, or collections.
     */
    private function normalizeFeedbackField($value): array
    {
        if (is_null($value)) {
            return [];
        }

        if ($value instanceof \Illuminate\Contracts\Support\Arrayable) {
            $value = $value->toArray();
        } elseif ($value instanceof \Illuminate\Support\Collection) {
            $value = $value->all();
        } elseif ($value instanceof \JsonSerializable) {
            $value = $value->jsonSerialize();
        }

        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $value = $decoded;
            } else {
                $value = [$value];
            }
        }

        if (!is_array($value)) {
            $value = [$value];
        }

        $normalized = array_map(fn ($entry) => $this->stringifyFeedbackEntry($entry), $value);

        return array_values(array_filter($normalized, fn ($entry) => $entry !== ''));
    }

    /**
     * Ensure each feedback entry is rendered as a human-readable string.
     */
    private function stringifyFeedbackEntry($entry): string
    {
        if ($entry instanceof \Illuminate\Contracts\Support\Arrayable) {
            $entry = $entry->toArray();
        } elseif ($entry instanceof \Illuminate\Support\Collection) {
            $entry = $entry->all();
        } elseif ($entry instanceof \JsonSerializable) {
            $entry = $entry->jsonSerialize();
        }

        if (is_array($entry)) {
            $flattened = array_map(fn ($item) => $this->stringifyFeedbackEntry($item), $entry);
            $flattened = array_filter($flattened, fn ($item) => $item !== '');
            return implode(', ', $flattened);
        }

        if (is_string($entry)) {
            return trim($entry);
        }

        if (is_bool($entry)) {
            return $entry ? 'Yes' : 'No';
        }

        if (is_scalar($entry)) {
            return (string) $entry;
        }

        return '';
    }
}