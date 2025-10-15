<?php

namespace App\Livewire;

use App\Facades\OpenAI;
use App\Jobs\GenerateFeedbackJob;
use App\Models\ItemBank;
use App\Models\QuizAttempt;
use App\Models\QuizRegeneration;
use App\Models\Response;
use App\Models\StudentAbility;
use App\Models\Subtopic;
use App\Services\IrtService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\Features\SupportRedirects\Redirector;

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
    public int $maxAttemptsAllowed = 3;
    public int $completedAttemptsCount = 0;
    public bool $hasReachedAttemptLimit = false;

    protected IrtService $irtService;

    public function boot(IrtService $irtService): void
    {
        $this->irtService = $irtService;
    }

    public function mount(Subtopic $subtopic): void
    {
        $this->subtopic = $subtopic->load('topic.document.course');
        $this->items = collect();
        $this->refreshAttemptLimitState();
    }

    public function selectTimerMode(string $mode): void
    {
        if ($this->hasReachedAttemptLimit && !$this->quizStarted) {
            session()->flash('error', 'You have reached the maximum number of quiz attempts allowed for this subtopic.');
            return;
        }

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

        $nextAttemptNumber = $existingAttempt
            ? $existingAttempt->attempt_number
            : ((QuizAttempt::where('user_id', auth()->id())
                ->where('subtopic_id', $this->subtopic->id)
                ->max('attempt_number') ?? 0) + 1);

        if (!$existingAttempt) {
            $this->refreshAttemptLimitState();

            if ($this->hasReachedAttemptLimit || $nextAttemptNumber > $this->maxAttemptsAllowed) {
                session()->flash('error', 'You have reached the maximum number of quiz attempts allowed for this subtopic.');
                return;
            }
        }

        $isAdaptive = $existingAttempt?->is_adaptive ?? $this->shouldUseAdaptiveMode();

        $assignedItemModels = $this->resolveAttemptItems($existingAttempt, $isAdaptive, $nextAttemptNumber);

        if ($assignedItemModels->isEmpty()) {
            session()->flash('error', 'No questions available for this quiz yet. Please try again later.');
            return;
        }

        if (!$existingAttempt) {
            $this->attempt = QuizAttempt::create([
                'user_id' => auth()->id(),
                'subtopic_id' => $this->subtopic->id,
                'attempt_number' => $nextAttemptNumber,
                'is_adaptive' => $isAdaptive,
                'total_questions' => $assignedItemModels->count(),
                'question_item_ids' => $assignedItemModels->pluck('id')->toArray(),
                'started_at' => now(),
            ]);
        } else {
            $existingAttempt->refresh();

            if ((int) $existingAttempt->total_questions === 0) {
                $existingAttempt->update([
                    'total_questions' => $assignedItemModels->count(),
                ]);
            }

            if (empty($existingAttempt->question_item_ids)) {
                $existingAttempt->update([
                    'question_item_ids' => $assignedItemModels->pluck('id')->toArray(),
                ]);
            }

            $this->attempt = $existingAttempt;
        }

        $items = $this->transformItems($assignedItemModels);

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

    protected function resolveAttemptItems(?QuizAttempt $attempt, bool $isAdaptive, int $attemptNumber): Collection
    {
        $questionTarget = $attempt && $attempt->total_questions
            ? max(1, (int) $attempt->total_questions)
            : 20;

        if ($attempt) {
            $assignedIds = collect($attempt->question_item_ids ?? []);

            if ($assignedIds->isEmpty()) {
                $assignedIds = $attempt->responses()
                    ->orderBy('response_at')
                    ->pluck('item_id');
            }

            if ($assignedIds->isNotEmpty()) {
                $items = $this->fetchItemsByIds($assignedIds)->values();

                $missing = max(0, $questionTarget - $items->count());

                if ($missing > 0) {
                    $exclude = $items->pluck('id')->filter()->all();
                    $additionalModels = $this->selectItemModelsForSubtopic($isAdaptive, $missing, $exclude);
                    $additionalModels = $this->prepareItemsForAttempt($additionalModels, $attemptNumber);
                    $items = $items->concat($additionalModels)->values();

                    $attempt->update([
                        'question_item_ids' => $items->pluck('id')->toArray(),
                        'total_questions' => $items->count(),
                    ]);
                }

                return $items;
            }
        }

        $initialModels = $this->selectItemModelsForSubtopic($isAdaptive, $questionTarget);
        $initialModels = $this->prepareItemsForAttempt($initialModels, $attemptNumber)->values();

        if ($attempt) {
            $attempt->update([
                'question_item_ids' => $initialModels->pluck('id')->toArray(),
                'total_questions' => $initialModels->count(),
            ]);
        }

        return $initialModels;
    }

    protected function fetchItemsByIds(Collection $ids): Collection
    {
        if ($ids->isEmpty()) {
            return collect();
        }

        $records = ItemBank::query()
            ->whereIn('id', $ids)
            ->with($this->itemRelations())
            ->get();

        return $ids->map(fn ($id) => $records->firstWhere('id', $id))
            ->filter()
            ->values();
    }

    protected function prepareItemsForAttempt(Collection $itemModels, int $attemptNumber): Collection
    {
        if ($itemModels->isEmpty()) {
            return $itemModels;
        }

        if ($attemptNumber <= 1) {
            return $itemModels->values();
        }

        return $itemModels->map(fn (ItemBank $item) => $this->createRewordedItemForRetake($item))->values();
    }

    protected function createRewordedItemForRetake(ItemBank $originalItem): ItemBank
    {
        $regenerationCount = QuizRegeneration::where('original_item_id', $originalItem->id)->count();
        $withinLimit = $regenerationCount < 3;
        $nextCount = $withinLimit ? $regenerationCount + 1 : $regenerationCount;

        if ($withinLimit) {
            try {
                $reworded = OpenAI::rewordQuestion(
                    $originalItem->question,
                    $originalItem->options ?? [],
                    $nextCount
                );

                $payload = $reworded['reworded_question'] ?? null;

                if (!$payload) {
                    throw new \RuntimeException('Reworded question payload missing.');
                }

                $optionSet = $this->buildOptionSet($payload['options'] ?? [], $originalItem->correct_answer);

                $questionText = $payload['question_text'] ?? $originalItem->question;
                $explanation = $payload['explanation'] ?? $originalItem->explanation;
                $maintainsEquivalence = $payload['maintains_equivalence'] ?? true;

                return $this->persistRetakeItem(
                    $originalItem,
                    $questionText,
                    $optionSet['options'],
                    $optionSet['correct_answer'],
                    $explanation,
                    true,
                    $nextCount,
                    $maintainsEquivalence
                );
            } catch (\Throwable $e) {
                Log::warning('Failed to reword quiz question for retake', [
                    'item_id' => $originalItem->id,
                    'error' => $e->getMessage(),
                ]);

                $optionSet = $this->buildOptionSet($originalItem->options ?? [], $originalItem->correct_answer);

                return $this->persistRetakeItem(
                    $originalItem,
                    $originalItem->question,
                    $optionSet['options'],
                    $optionSet['correct_answer'],
                    $originalItem->explanation,
                    true,
                    $nextCount,
                    true
                );
            }
        }

        $optionSet = $this->buildOptionSet($originalItem->options ?? [], $originalItem->correct_answer);

        return $this->persistRetakeItem(
            $originalItem,
            $originalItem->question,
            $optionSet['options'],
            $optionSet['correct_answer'],
            $originalItem->explanation,
            false,
            null,
            true
        );
    }

    protected function persistRetakeItem(
        ItemBank $originalItem,
        string $questionText,
        array $options,
        string $correctLetter,
        ?string $explanation,
        bool $logRegeneration,
        ?int $regenerationCount,
        bool $maintainsEquivalence = true
    ): ItemBank {
        $newItem = ItemBank::create([
            'tos_item_id' => $originalItem->tos_item_id,
            'subtopic_id' => $originalItem->subtopic_id,
            'learning_outcome_id' => $originalItem->learning_outcome_id,
            'question' => $questionText,
            'options' => $options,
            'correct_answer' => $correctLetter,
            'explanation' => $explanation,
            'cognitive_level' => $originalItem->cognitive_level,
            'difficulty_b' => $originalItem->difficulty_b,
            'time_estimate_seconds' => $originalItem->time_estimate_seconds,
            'created_at' => now(),
        ]);

        if ($logRegeneration && $regenerationCount !== null && $regenerationCount <= 3) {
            QuizRegeneration::create([
                'original_item_id' => $originalItem->id,
                'regenerated_item_id' => $newItem->id,
                'subtopic_id' => $originalItem->subtopic_id,
                'regeneration_count' => $regenerationCount,
                'maintains_equivalence' => $maintainsEquivalence,
                'regenerated_at' => now(),
            ]);
        }

        return $newItem->load($this->itemRelations());
    }

    protected function buildOptionSet(array $options, ?string $originalCorrectLetter = null): array
    {
        $normalized = collect($options)
            ->map(function ($option) {
                return [
                    'option_letter' => $option['option_letter'] ?? null,
                    'option_text' => $option['option_text'] ?? '',
                    'is_correct' => $option['is_correct'] ?? false,
                ];
            })
            ->filter(fn ($option) => ($option['option_text'] ?? '') !== '');

        if ($normalized->isEmpty()) {
            throw new \RuntimeException('No options available for retake question generation.');
        }

        $letters = range('A', 'Z');

        $shuffled = $normalized->shuffle()->values();

        $prepared = [];
        $correctLetter = null;

        foreach ($shuffled as $index => $option) {
            $letter = $letters[$index] ?? chr(ord('A') + $index);
            $isCorrect = (bool) $option['is_correct'];

            if (!$isCorrect && $originalCorrectLetter && $option['option_letter'] === $originalCorrectLetter) {
                $isCorrect = true;
            }

            $prepared[] = [
                'option_letter' => $letter,
                'option_text' => $option['option_text'],
                'is_correct' => $isCorrect,
            ];

            if ($isCorrect && !$correctLetter) {
                $correctLetter = $letter;
            }
        }

        if (!$correctLetter && !empty($prepared)) {
            $prepared[0]['is_correct'] = true;
            $correctLetter = $prepared[0]['option_letter'];
        }

        return [
            'options' => collect($prepared)->map(fn ($option) => [
                'option_letter' => $option['option_letter'],
                'option_text' => $option['option_text'],
            ])->toArray(),
            'correct_answer' => $correctLetter,
        ];
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

    public function nextQuestion(): RedirectResponse|Redirector|null
    {
        $this->currentQuestionIndex++;
        $this->selectedAnswer = null;
        $this->showFeedback = false;
        $this->isCorrect = false;
        $this->correctAnswer = null;

        if ($this->currentQuestionIndex >= $this->items->count()) {
            return $this->completeQuiz();
        }

        $this->resetTimer();

        return null;
    }

    public function completeQuiz(): RedirectResponse|Redirector|null
    {
        if (!$this->attempt) {
            return null;
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

            $studentAbility->updateTheta($newTheta);
        }

        GenerateFeedbackJob::dispatch($this->attempt->id);

        $this->quizCompleted = true;
        $this->quizStarted = false;
        $this->items = collect();

        $this->refreshAttemptLimitState();

        return redirect()->route('student.course.show', $this->subtopic->topic->document->course_id);
    }

    protected function refreshAttemptLimitState(): void
    {
        $this->completedAttemptsCount = QuizAttempt::query()
            ->where('user_id', auth()->id())
            ->where('subtopic_id', $this->subtopic->id)
            ->whereNotNull('completed_at')
            ->count();

        $this->hasReachedAttemptLimit = $this->completedAttemptsCount >= $this->maxAttemptsAllowed;
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