<?php

namespace App\Livewire;

use App\Models\QuizAttempt;
use App\Models\Subtopic;
use App\Models\TosItem;
use Illuminate\Support\Collection;
use Livewire\Component;

class QuizLearningContext extends Component
{
    public Subtopic $subtopic;
    public $course;
    public $document;
    public int $maxAttemptsAllowed = 3;
    public int $completedAttemptsCount = 0;
    public bool $hasReachedAttemptLimit = false;

    public function mount(Subtopic $subtopic): void
    {
        $subtopic->load([
            'topic.document.course',
            'topic.document.tableOfSpecification',
        ]);

        $this->subtopic = $subtopic;
        $this->document = $subtopic->topic->document;
        $this->course = $this->document->course;

        if (!$this->course->isEnrolledBy(auth()->id())) {
            redirect()
                ->route('student.courses')
                ->with('error', 'You must enroll in this course first.');

            return;
        }

        $this->maxAttemptsAllowed = (int) config('quiz.max_attempts', 3);
        $this->refreshAttemptStats();
    }

    public function startQuiz()
    {
        $this->refreshAttemptStats();

        if ($this->hasReachedAttemptLimit) {
            session()->flash('error', 'You have reached the maximum number of quiz attempts allowed for this subtopic.');

            return null;
        }

        session()->put('quiz.context.subtopic', $this->subtopic->id);

        return redirect()->route('student.quiz.take', $this->subtopic->id);
    }

    protected function refreshAttemptStats(): void
    {
        $this->completedAttemptsCount = QuizAttempt::query()
            ->where('user_id', auth()->id())
            ->where('subtopic_id', $this->subtopic->id)
            ->whereNotNull('completed_at')
            ->count();

        $this->hasReachedAttemptLimit = $this->completedAttemptsCount >= $this->maxAttemptsAllowed;
    }

    protected function buildLearningOutcomeSummaries(Collection $tosItems): Collection
    {
        return $tosItems
            ->filter(fn (TosItem $item) => $item->learningOutcome !== null)
            ->groupBy(fn (TosItem $item) => $item->learningOutcome->id)
            ->map(function (Collection $items) {
                $outcome = $items->first()->learningOutcome;

                return [
                    'outcome' => $outcome,
                    'cognitive_levels' => $items->pluck('cognitive_level')->filter()->unique()->values(),
                    'sample_indicators' => $items
                        ->flatMap(fn (TosItem $item) => collect($item->sample_indicators ?? []))
                        ->filter()
                        ->unique()
                        ->values(),
                    'planned_items' => $items->sum('num_items'),
                    'generated_items' => $items->sum('items_count'),
                ];
            })
            ->values();
    }

    public function render()
    {
        $tosItems = $this->subtopic->tosItems()
            ->with(['learningOutcome'])
            ->withCount('items')
            ->orderBy('learning_outcome_id')
            ->get();

        $learningOutcomeSummaries = $this->buildLearningOutcomeSummaries($tosItems);
        $tableOfSpecification = $this->document->tableOfSpecification;

        return view('livewire.quiz-learning-context', [
            'course' => $this->course,
            'document' => $this->document,
            'tosItems' => $tosItems,
            'learningOutcomeSummaries' => $learningOutcomeSummaries,
            'tableOfSpecification' => $tableOfSpecification,
        ])->layout('layouts.app', [
            'title' => 'SumakQuiz | Learning Context',
            'pageTitle' => $this->subtopic->name,
            'pageSubtitle' => $this->subtopic->topic->name . ' • Review learning outcomes and expectations before starting the quiz.',
        ]);
    }
}