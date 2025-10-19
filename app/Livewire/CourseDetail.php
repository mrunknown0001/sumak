<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Course;
use Illuminate\Support\Facades\Log;

class CourseDetail extends Component
{
    public Course $course;
    public $isEnrolled = false;
    
    public function mount(Course $course)
    {
        $this->course = $course;
        $this->isEnrolled = $course->isEnrolledBy(auth()->id());
        
        // Redirect if not enrolled
        if (!$this->isEnrolled) {
            return redirect()->route('student.courses')
                ->with('error', 'You must enroll in this course first.');
        }
    }

    public function render()
    {
        $userId = auth()->id();

        $documents = $this->course->documents()
            ->whereHas('tableOfSpecification')
            ->whereHas('topics.subtopics.items')
            ->with([
                'tableOfSpecification',
                'topics' => function ($topicQuery) use ($userId) {
                    $topicQuery->whereHas('subtopics.items')
                        ->with([
                            'subtopics' => function ($subtopicQuery) use ($userId) {
                                $subtopicQuery->whereHas('items')
                                    ->withCount('items')
                                    ->withCount([
                                        'quizAttempts as user_attempts_count' => function ($attemptQuery) use ($userId) {
                                            $attemptQuery->where('user_id', $userId);
                                        },
                                    ]);
                            },
                        ]);
                },
            ])
            ->latest()
            ->get();
        Log::debug('CourseDetail render document stats', [
            'course_id' => $this->course->id,
            'documents_count' => $documents->count(),
            'document_topic_breakdown' => $documents->map(function ($doc) {
                return [
                    'document_id' => $doc->id,
                    'topics' => $doc->topics->count(),
                    'subtopics' => $doc->topics->sum(function ($topic) {
                        return $topic->subtopics->count();
                    }),
                ];
            })->toArray(),
        ]);

        return view('livewire.course-detail', [
            'documents' => $documents,
        ])->layout('layouts.app', [
            'title' => 'SumakQuiz | ' . $this->course->course_code,
            'pageTitle' => $this->course->course_title,
            'pageSubtitle' => $this->course->course_code . ' • Lecture materials and quiz readiness status.',
        ]);
    }
}