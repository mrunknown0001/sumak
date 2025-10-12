<?php

namespace App\Livewire;

use App\Jobs\GenerateFeedbackJob;
use App\Models\ItemBank;
use App\Models\QuizAttempt;
use App\Models\Response;
use App\Models\StudentAbility;
use App\Models\Subtopic;
use App\Services\IrtService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Component;

class TakeQuiz extends Component
{
    public Subtopic $subtopic;
    public ?QuizAttempt $attempt = null;
    public Collection $items;
    public int $currentQuestionIndex = 0;
    public ?string $selectedAnswer = null;
    public int $timeRemaining = 60;
    public bool $quizStarted = false;
    public bool $quizCompleted = false;
    public bool $showFeedback = false;
    public bool $isCorrect = false;
    public ?string $correctAnswer = null;
    public ?string $timerMode = null;
    public int $pomodoroSessionTime = 1500;
    public int $pomodoroBreakTime = 300;
    public bool $isBreakTime = false;

    protected IrtService $irtService;

    public function boot(IrtService $irtService): void
    {
        $this->irtService = $irtService;
    }

    public function mount(Subtopic $subtopic): void
    {
        $this->subtopic = $subtopic->load('topic.document.course');
        $this->items = collect();
    }

    public function selectTimerMode(string $mode): void
    {
        $this->timerMode = $mode;

        if ($mode === 'pomodoro') {
            $this->timeRemaining = $this->pomodoroSessionTime;
        } elseif ($mode === 'standard') {
            $this->timeRemaining = 60;
        } else {
            $this->timeRemaining = 0;
        }
    }

    public function startQuiz(): void
    {
        if (!$this->timerMode) {
            session()->flash('error', 'Please select a timer mode first.');
            return;
        }

        $existingAttempt = $this->getActiveAttempt();
        $isAdaptive = $existingAttempt?->is_adaptive ?? $this->shouldUseAdaptiveMode();

        $items = $this->loadItemsForAttempt($existingAttempt);

        if ($items->isEmpty()) {
            $selectedItemModels = $this->selectItemModelsForSubtopic($isAdaptive, 20);
            $items = $this->transformItems($selectedItemModels);
        }

        if ($items->isEmpty()) {
            session()->flash('error', 'No questions available for this quiz yet. Please try again later.');
            return;
        }

        if (!$existingAttempt) {
            $attemptNumber = QuizAttempt::where('user_id', auth()->id())
                ->where('subtopic_id', $this->subtopic->id)
                ->max('attempt_number') + 1;

            $this->attempt = QuizAttempt::create([
                'user_id' => auth()->id(),
                'subtopic_id' => $this->subtopic->id,
                'attempt_number' => $attemptNumber,
                'is_adaptive' => $isAdaptive,
                'total_questions' => $items->count(),
                'started_at' => now(),
            ]);
        } else {
            $this->attempt = $existingAttempt;

            if ((int) $this->attempt->total_questions === 0) {
                $this->attempt->update(['total_questions' => $items->count()]);
            }
        }

        $answeredCount = $this->attempt
            ? $this->attempt->responses()
                ->where('user_id', auth()->id())
                ->count()
            : 0;

        if ($answeredCount >= $items->count()) {
            $answeredCount = max(0, $items->count() - 1);
        }

        $this->items = $items->values();
        $this->currentQuestionIndex = max(0, $answeredCount);
        $this->selectedAnswer = null;
        $this->showFeedback = false;
        $this->isCorrect = false;
        $this->correctAnswer = null;
        $this->quizCompleted = false;
        $this->quizStarted = true;
        $this->isBreakTime = false;

        $this->resetTimer();
    }

    protected function shouldUseAdaptiveMode(): bool
    {
        return $this->subtopic->hasCompletedAllInitialQuizzes(auth()->id());
    }

    protected function getActiveAttempt(): ?QuizAttempt
    {
        return QuizAttempt::query()
            ->with([
                'responses' => fn ($query) => $query
                    ->where('user_id', auth()->id())
                    ->with('item'),
            ])
            ->where('user_id', auth()->id())
            ->where('subtopic_id', $this->subtopic->id)
            ->whereNull('completed_at')
            ->latest('started_at')
            ->first();
    }

    protected function loadItemsForAttempt(?QuizAttempt $attempt): Collection
    {
        if (!$attempt) {
            return collect();
        }

        $responses = $attempt->responses
            ->sortBy('response_at')
            ->filter(fn (Response $response) => $response->item !== null);

        if ($responses->isEmpty() && $attempt->total_questions === 0) {
            return collect();
        }

        $respondedItems = $responses->pluck('item')->filter();
        $items = $this->transformItems($respondedItems);

        $remaining = max(0, $attempt->total_questions - $items->count());

        if ($remaining > 0) {
            $exclude = $respondedItems->pluck('id')->all();
            $additionalModels = $this->selectItemModelsForSubtopic($attempt->is_adaptive, $remaining, $exclude);
            $items = $items->concat($this->transformItems($additionalModels));
        }

        return $items->values();
    }

    protected function selectItemModelsForSubtopic(bool $isAdaptive, int $limit, array $exclude = []): Collection
    {
        if ($limit <= 0) {
            return collect();
        }

        $baseQuery = ItemBank::query()
            ->where('subtopic_id', $this->subtopic->id)
            ->when(!empty($exclude), fn (Builder $query) => $query->whereNotIn('id', $exclude));

        if ($isAdaptive) {
            $studentAbility = StudentAbility::firstOrCreate(
                ['user_id' => auth()->id(), 'subtopic_id' => $this->subtopic->id],
                ['theta' => 0, 'attempts_count' => 0]
            );

            $availableItems = (clone $baseQuery)
                ->get(['id', 'difficulty_b'])
                ->map(fn (ItemBank $item) => [
                    'id' => $item->id,
                    'difficulty' => $item->difficulty_b,
                ])
                ->toArray();

            if (empty($availableItems)) {
                return collect();
            }

            $selectedIds = $this->irtService->selectAdaptiveItems(
                $studentAbility->theta,
                $availableItems,
                $limit
            );

            if (empty($selectedIds)) {
                return collect();
            }

            $orderedIds = implode(',', $selectedIds);

            return ItemBank::query()
                ->whereIn('id', $selectedIds)
                ->with($this->itemRelations())
                ->orderByRaw("FIELD(id, {$orderedIds})")
                ->get();
        }

        return (clone $baseQuery)
            ->with($this->itemRelations())
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }

    protected function transformItems(Collection $itemModels): Collection
    {
        return $itemModels->map(function (ItemBank $item) {
            $options = collect($item->options ?? [])
                ->map(function ($option) {
                    $letter = $option['option_letter'] ?? null;
                    $text = $option['option_text'] ?? null;

                    if (!$letter || !$text) {
                        return null;
                    }

                    return [
                        'option_letter' => $letter,
                        'option_text' => $text,
                    ];
                })
                ->filter()
                ->values()
                ->toArray();

            return [
                'id' => $item->id,
                'question' => $item->question,
                'options' => $options,
                'correct_answer' => $item->correct_answer,
                'explanation' => $item->explanation,
                'cognitive_level' => $item->cognitive_level,
                'difficulty_b' => $item->difficulty_b,
                'time_estimate_seconds' => $item->time_estimate_seconds,
                'tos_item_id' => $item->tos_item_id,
                'learning_outcome_id' => $item->learning_outcome_id,
            ];
        });
    }

    protected function itemRelations(): array
    {
        return [
            'tosItem.learningOutcome',
        ];
    }

    public function submitAnswer(): void
    {
        if (
            !$this->quizStarted ||
            !$this->attempt ||
            $this->items->isEmpty() ||
            !$this->selectedAnswer
        ) {
            return;
        }

        $item = $this->items[$this->currentQuestionIndex] ?? null;

        if (!$item) {
            return;
        }

        $alreadyResponded = $this->attempt->responses()
            ->where('user_id', auth()->id())
            ->where('item_id', $item['id'])
            ->exists();

        if ($alreadyResponded) {
            return;
        }

        $this->isCorrect = $item['correct_answer'] === $this->selectedAnswer;
        $this->correctAnswer = $item['correct_answer'];

        Response::create([
            'quiz_attempt_id' => $this->attempt->id,
            'item_id' => $item['id'],
            'user_id' => auth()->id(),
            'user_answer' => $this->selectedAnswer,
            'is_correct' => $this->isCorrect,
            'time_taken_seconds' => $this->calculateTimeTaken(),
            'response_at' => now(),
        ]);

        $this->showFeedback = true;
    }

    protected function calculateTimeTaken(): int
    {
        return match ($this->timerMode) {
            'standard' => max(0, 60 - $this->timeRemaining),
            'pomodoro' => $this->isBreakTime ? 0 : max(0, $this->pomodoroSessionTime - $this->timeRemaining),
            default => 0,
        };
    }

    public function nextQuestion(): void
    {
        $this->currentQuestionIndex++;
        $this->selectedAnswer = null;
        $this->showFeedback = false;
        $this->isCorrect = false;
        $this->correctAnswer = null;

        if ($this->currentQuestionIndex >= $this->items->count()) {
            $this->completeQuiz();
            return;
        }

        $this->resetTimer();
    }

    public function completeQuiz(): void
    {
        if (!$this->attempt) {
            return;
        }

        $correctAnswers = $this->attempt->responses()->where('is_correct', true)->count();
        $totalQuestions = max(1, $this->attempt->total_questions);

        $scorePercentage = ($correctAnswers / $totalQuestions) * 100;

        $this->attempt->update([
            'correct_answers' => $correctAnswers,
            'score_percentage' => round($scorePercentage, 2),
            'completed_at' => now(),
            'time_spent_seconds' => now()->diffInSeconds($this->attempt->started_at),
        ]);

        $studentAbility = StudentAbility::firstOrCreate(
            ['user_id' => auth()->id(), 'subtopic_id' => $this->subtopic->id],
            ['theta' => 0, 'attempts_count' => 0]
        );

        $responses = $this->attempt->responses()
            ->with('item')
            ->get()
            ->filter(fn (Response $response) => $response->item !== null)
            ->map(fn (Response $response) => [
                'difficulty' => $response->item->difficulty_b,
                'correct' => $response->is_correct,
            ]);

        if ($responses->isNotEmpty()) {
            $newTheta = $this->irtService->estimateAbility(
                $studentAbility->theta,
                $responses->toArray()
            );

            $studentAbility->increment('attempts_count');
            $studentAbility->update(['theta' => $newTheta]);
        }

        GenerateFeedbackJob::dispatch($this->attempt->id);

        $this->quizCompleted = true;
        $this->quizStarted = false;
        $this->items = collect();
    }

    public function resetTimer(): void
    {
        if ($this->timerMode === 'pomodoro') {
            $this->timeRemaining = $this->isBreakTime
                ? $this->pomodoroBreakTime
                : $this->pomodoroSessionTime;
        } elseif ($this->timerMode === 'standard') {
            $this->timeRemaining = 60;
        } else {
            $this->timeRemaining = 0;
        }

        $this->dispatch('resetTimer');
    }

    public function startBreak(): void
    {
        $this->isBreakTime = true;
        $this->timeRemaining = $this->pomodoroBreakTime;
        $this->dispatch('startBreak');
    }

    public function endBreak(): void
    {
        $this->isBreakTime = false;
        $this->resetTimer();
        $this->dispatch('endBreak');
    }

    public function render()
    {
        return view('livewire.take-quiz')->layout('layouts.app', [
            'title' => 'SumakQuiz | Take Quiz',
            'pageTitle' => $this->subtopic->name,
            'pageSubtitle' => $this->subtopic->topic->name . ' • Select a timer mode and track your progress question by question.',
        ]);
    }
}