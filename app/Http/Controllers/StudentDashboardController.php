<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\QuizAttempt;
use App\Models\StudentAbility;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class StudentDashboardController extends Controller
{
    /**
     * Get student dashboard overview
     */
    public function index(): JsonResponse
    {
        $userId = auth()->id();

        // Get all courses
        $courses = Course::where('user_id', $userId)
            ->with([
                'documents.topics',
                'documents.tableOfSpecification'
            ])
            ->withCount('documents')
            ->get();

        // Get recent quiz attempts
        $recentAttempts = QuizAttempt::where('user_id', $userId)
            ->with(['topic.document.course'])
            ->latest('completed_at')
            ->limit(10)
            ->get();

        // Get overall statistics
        $totalAttempts = QuizAttempt::where('user_id', $userId)->count();
        $completedAttempts = QuizAttempt::where('user_id', $userId)
            ->whereNotNull('completed_at')
            ->count();
        
        $averageScore = QuizAttempt::where('user_id', $userId)
            ->whereNotNull('completed_at')
            ->avg('score_percentage');

        $passedAttempts = QuizAttempt::where('user_id', $userId)
            ->where('score_percentage', '>=', 70)
            ->count();

        // Get mastery levels by topic
        $masteryLevels = StudentAbility::where('user_id', $userId)
            ->with(['topic.document.course'])
            ->get()
            ->groupBy(function($ability) {
                return $ability->topic->topic->document->course_id;
            })
            ->map(function($abilities, $courseId) use ($courses) {
                $course = $courses->firstWhere('id', $courseId);
                
                return [
                    'course' => [
                        'id' => $course->id,
                        'code' => $course->course_code,
                        'title' => $course->course_title,
                    ],
                    'topics' => $abilities->map(fn($a) => [
                        'topic' => $a->topic->name,
                        'theta' => round($a->theta, 2),
                        'proficiency_level' => $a->proficiency_level,
                        'attempts_count' => $a->attempts_count,
                        'last_updated' => $a->last_updated,
                    ])->values(),
                ];
            })
            ->values();

        return response()->json([
            'data' => [
                'overview' => [
                    'total_courses' => $courses->count(),
                    'total_documents' => $courses->sum('documents_count'),
                    'total_attempts' => $totalAttempts,
                    'completed_attempts' => $completedAttempts,
                    'average_score' => round($averageScore ?? 0, 2),
                    'pass_rate' => $completedAttempts > 0 
                        ? round(($passedAttempts / $completedAttempts) * 100, 2) 
                        : 0,
                ],
                'courses' => $courses->map(fn($c) => [
                    'id' => $c->id,
                    'code' => $c->course_code,
                    'title' => $c->course_title,
                    'documents_count' => $c->documents_count,
                    'has_obtl' => $c->hasObtl(),
                ]),
                'recent_attempts' => $recentAttempts->map(fn($a) => [
                    'id' => $a->id,
                    'course' => $a->topic->topic->document->course->course_title,
                    'topic' => $a->topic->name,
                    'score' => $a->score_percentage,
                    'passed' => $a->isPassed(),
                    'completed_at' => $a->completed_at,
                ]),
                'mastery_levels' => $masteryLevels,
            ],
        ]);
    }

    /**
     * Get course progress details
     */
    public function courseProgress(Course $course): JsonResponse
    {
        $this->authorize('view', $course);

        $userId = auth()->id();

        $course->load([
            'documents.topics.topics',
            'documents.tableOfSpecification.tosItems'
        ]);

        $progressData = $course->documents->map(function($document) use ($userId) {
            return [
                'document' => [
                    'id' => $document->id,
                    'title' => $document->title,
                ],
                'topics' => $document->topics->map(function($topic) use ($userId) {
                    return [
                        'topic' => [
                            'id' => $topic->id,
                            'name' => $topic->name,
                        ],
                        'topics' => $topic->topics->map(function($topic) use ($userId) {
                            $attempts = QuizAttempt::where('user_id', $userId)
                                ->where('topic_id', $topic->id)
                                ->get();

                            $ability = StudentAbility::where('user_id', $userId)
                                ->where('topic_id', $topic->id)
                                ->first();

                            $lastAttempt = $attempts->sortByDesc('completed_at')->first();

                            return [
                                'id' => $topic->id,
                                'name' => $topic->name,
                                'attempts_count' => $attempts->count(),
                                'best_score' => $attempts->max('score_percentage') ?? 0,
                                'average_score' => round($attempts->avg('score_percentage') ?? 0, 2),
                                'last_attempt' => $lastAttempt ? [
                                    'id' => $lastAttempt->id,
                                    'score' => $lastAttempt->score_percentage,
                                    'passed' => $lastAttempt->isPassed(),
                                    'completed_at' => $lastAttempt->completed_at,
                                ] : null,
                                'mastery' => $ability ? [
                                    'theta' => round($ability->theta, 2),
                                    'level' => $ability->proficiency_level,
                                ] : null,
                                'can_take_quiz' => true,
                                'can_regenerate' => $attempts->count() > 0 && $attempts->count() < 3,
                            ];
                        })->values(),
                    ];
                })->values(),
            ];
        });

        return response()->json([
            'data' => [
                'course' => [
                    'id' => $course->id,
                    'code' => $course->course_code,
                    'title' => $course->course_title,
                ],
                'progress' => $progressData,
            ],
        ]);
    }

    /**
     * Get performance analytics
     */
    public function analytics(): JsonResponse
    {
        $userId = auth()->id();

        // Time-based performance
        $last30Days = QuizAttempt::where('user_id', $userId)
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(completed_at) as date, AVG(score_percentage) as avg_score, COUNT(*) as attempts')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Performance by cognitive level
        $cognitivePerformance = DB::table('responses')
            ->join('item_bank', 'responses.item_id', '=', 'item_bank.id')
            ->where('responses.user_id', $userId)
            ->selectRaw('item_bank.cognitive_level, COUNT(*) as total, SUM(CASE WHEN responses.is_correct THEN 1 ELSE 0 END) as correct')
            ->groupBy('item_bank.cognitive_level')
            ->get()
            ->map(fn($r) => [
                'cognitive_level' => $r->cognitive_level,
                'accuracy' => round(($r->correct / $r->total) * 100, 2),
                'total_questions' => $r->total,
            ]);

        // Difficulty distribution
        $difficultyPerformance = DB::table('responses')
            ->join('item_bank', 'responses.item_id', '=', 'item_bank.id')
            ->where('responses.user_id', $userId)
            ->selectRaw('
                CASE 
                    WHEN item_bank.difficulty_b < -1 THEN "Very Easy"
                    WHEN item_bank.difficulty_b < 0 THEN "Easy"
                    WHEN item_bank.difficulty_b < 1 THEN "Medium"
                    WHEN item_bank.difficulty_b < 2 THEN "Hard"
                    ELSE "Very Hard"
                END as difficulty_level,
                COUNT(*) as total,
                SUM(CASE WHEN responses.is_correct THEN 1 ELSE 0 END) as correct
            ')
            ->groupBy('difficulty_level')
            ->get()
            ->map(fn($r) => [
                'difficulty' => $r->difficulty_level,
                'accuracy' => round(($r->correct / $r->total) * 100, 2),
                'total_questions' => $r->total,
            ]);

        // Study patterns
        $studyPatterns = QuizAttempt::where('user_id', $userId)
            ->whereNotNull('completed_at')
            ->selectRaw('HOUR(completed_at) as hour, COUNT(*) as attempts')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->mapWithKeys(fn($r) => [$r->hour => $r->attempts]);

        return response()->json([
            'data' => [
                'time_series' => $last30Days,
                'cognitive_performance' => $cognitivePerformance,
                'difficulty_performance' => $difficultyPerformance,
                'study_patterns' => $studyPatterns,
            ],
        ]);
    }
}