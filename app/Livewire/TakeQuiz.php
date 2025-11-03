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
use App\Services\DocumentQuizBatchService;
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
    protected DocumentQuizBatchService $documentQuizBatchService;

    public function boot(IrtService $irtService, DocumentQuizBatchService $documentQuizBatchService): void
    {
        $this->irtService = $irtService;
        $this->documentQuizBatchService = $documentQuizBatchService;
    }

    public function mount(Subtopic $subtopic)
    {
        $this->subtopic = $subtopic->load('topic.document.course');
        $this->maxAttemptsAllowed = (int) config('quiz.max_attempts', $this->maxAttemptsAllowed);

        $contextSubtopicId = (int) (session()->get('quiz.context.subtopic') ?? 0);

        $hasActiveAttempt = QuizAttempt::query()
            ->where('user_id', auth()->id())
            ->where('subtopic_id', $subtopic->id)
            ->whereNull('completed_at')
            ->exists();

        if (!$hasActiveAttempt && $contextSubtopicId !== $subtopic->id) {
            return redirect()->route('student.quiz.context', $subtopic->id);
        }

        if ($contextSubtopicId === $subtopic->id) {
            session()->forget('quiz.context.subtopic');
        }

        Log::debug('TakeQuiz mount accessed', [
            'subtopic_id' => $subtopic->id,
            'course_id' => optional($subtopic->topic->document)->course_id,
            'route_name' => optional(request()->route())->getName(),
            'referer' => request()->headers->get('referer'),
            'context_subtopic_id' => $contextSubtopicId,
            'has_active_attempt' => $hasActiveAttempt,
        ]);

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

        if ($this->timerMode) {
            $this->documentQuizBatchService->updateTimerMode($this->timerMode);
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

        Log::debug('TakeQuiz startQuiz timer initialized', [
            'user_id' => auth()->id(),
            'subtopic_id' => $this->subtopic->id,
            'attempt_id' => $this->attempt->id ?? null,
            'timer_mode' => $this->timerMode,
            'time_remaining' => $this->timeRemaining,
            'question_count' => $this->items->count(),
            'current_question_index' => $this->currentQuestionIndex,
        ]);

        $this->resetTimer();
        $this->dispatchTimerStream('start_quiz', [
            'question_count' => $this->items->count(),
            'current_question_index' => $this->currentQuestionIndex,
        ]);
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

    public function submitAnswer(?string $answerOverride = null, bool $forced = false): void
    {
        if (
            !$this->quizStarted ||
            !$this->attempt ||
            $this->items->isEmpty()
        ) {
            return;
        }

        $item = $this->items[$this->currentQuestionIndex] ?? null;

        if (!$item) {
            return;
        }

        if (!$forced && !$this->selectedAnswer) {
            return;
        }

        $answer = $forced
            ? ($answerOverride ?? $this->selectedAnswer)
            : $this->selectedAnswer;

        $answerValue = $answer ?? '';

        $alreadyResponded = $this->attempt->responses()
            ->where('user_id', auth()->id())
            ->where('item_id', $item['id'])
            ->exists();

        if ($alreadyResponded) {
            return;
        }

        $this->selectedAnswer = $answer;
        $this->isCorrect = $answer !== null && $answer !== '' && $item['correct_answer'] === $answer;
        $this->correctAnswer = $item['correct_answer'];

        Response::create([
            'quiz_attempt_id' => $this->attempt->id,
            'item_id' => $item['id'],
            'user_id' => auth()->id(),
            'user_answer' => $answerValue,
            'is_correct' => $this->isCorrect,
            'time_taken_seconds' => $this->calculateTimeTaken(),
            'response_at' => now(),
        ]);

        $this->showFeedback = true;

        $this->dispatchTimerStream('answer_submitted', [
            'forced_submission' => $forced,
            'was_correct' => $this->isCorrect,
            'selected_answer' => $this->selectedAnswer,
            'correct_answer' => $this->correctAnswer,
        ]);
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
        $this->dispatchTimerStream('next_question', [
            'current_question_index' => $this->currentQuestionIndex,
        ]);

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

        $nextSubtopicId = $this->documentQuizBatchService->advanceAfterCompletion($this->subtopic->id);

        if ($nextSubtopicId) {
            session()->put('quiz.context.subtopic', $nextSubtopicId);

            return redirect()->route('student.quiz.take', $nextSubtopicId);
        }

        session()->forget('quiz.context.subtopic');

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

    public function tickTimer(): void
    {
        if (!$this->shouldPollTimer()) {
            return;
        }

        if ($this->timeRemaining > 0) {
            $this->timeRemaining--;
            $this->dispatchTimerStream($this->isBreakTime ? 'tick_break' : 'tick');
            return;
        }

        if ($this->timerMode === 'standard') {
            $this->dispatchTimerStream('timeout');
            $this->submitAnswer(null, true);
            return;
        }

        if ($this->timerMode === 'pomodoro') {
            if ($this->isBreakTime) {
                $this->dispatchTimerStream('break_complete');
                $this->endBreak();
            } else {
                $this->dispatchTimerStream('session_complete');
                $this->startBreak();
            }
        }
    }

    public function resetTimer(): void
    {
        $previousTimeRemaining = $this->timeRemaining;

        if ($this->timerMode === 'pomodoro') {
            $this->timeRemaining = $this->isBreakTime
                ? $this->pomodoroBreakTime
                : $this->pomodoroSessionTime;
        } elseif ($this->timerMode === 'standard') {
            $this->timeRemaining = 60;
        } else {
            $this->timeRemaining = 0;
        }

        Log::debug('TakeQuiz resetTimer dispatched', [
            'user_id' => auth()->id(),
            'subtopic_id' => $this->subtopic->id,
            'attempt_id' => $this->attempt->id ?? null,
            'timer_mode' => $this->timerMode,
            'is_break_time' => $this->isBreakTime,
            'previous_time_remaining' => $previousTimeRemaining,
            'new_time_remaining' => $this->timeRemaining,
            'source' => 'resetTimer',
        ]);

        $this->dispatch('resetTimer');
        $this->dispatchTimerStream('reset');
    }

    public function startBreak(): void
    {
        $this->isBreakTime = true;
        $this->timeRemaining = $this->pomodoroBreakTime;

        Log::debug('TakeQuiz startBreak activated', [
            'user_id' => auth()->id(),
            'subtopic_id' => $this->subtopic->id,
            'attempt_id' => $this->attempt->id ?? null,
            'timer_mode' => $this->timerMode,
            'time_remaining' => $this->timeRemaining,
        ]);

        $this->dispatch('startBreak');
        $this->dispatchTimerStream('start_break');
    }

    public function endBreak(): void
    {
        $this->isBreakTime = false;

        Log::debug('TakeQuiz endBreak triggered', [
            'user_id' => auth()->id(),
            'subtopic_id' => $this->subtopic->id,
            'attempt_id' => $this->attempt->id ?? null,
            'timer_mode' => $this->timerMode,
        ]);

        $this->resetTimer();
        $this->dispatch('endBreak');
        $this->dispatchTimerStream('end_break');
    }

    public function timerMaxSeconds(): int
    {
        if ($this->timerMode === 'pomodoro') {
            return $this->isBreakTime
                ? $this->pomodoroBreakTime
                : $this->pomodoroSessionTime;
        }

        return $this->timerMode === 'standard' ? 60 : 0;
    }

    public function timerColorClass(): string
    {
        if ($this->timerMode === 'free') {
            return 'bg-slate-400 dark:bg-slate-600';
        }

        if ($this->timerMode === 'pomodoro') {
            return 'bg-purple-500 dark:bg-purple-400';
        }

        if ($this->timeRemaining > 30) {
            return 'bg-emerald-500 dark:bg-emerald-400';
        }

        if ($this->timeRemaining > 10) {
            return 'bg-amber-500 dark:bg-amber-400';
        }

        return 'bg-rose-500 dark:bg-rose-400';
    }

    public function formatSeconds(int $seconds): string
    {
        $total = max(0, $seconds);
        $minutes = intdiv($total, 60);
        $remainingSeconds = $total % 60;

        return sprintf('%d:%02d', $minutes, $remainingSeconds);
    }

    public function shouldPollTimer(): bool
    {
        if (!$this->timerMode || $this->timerMode === 'free') {
            return false;
        }

        if ($this->quizCompleted) {
            return false;
        }

        if ($this->showFeedback) {
            return false;
        }

        if ($this->isBreakTime && $this->timerMode === 'pomodoro') {
            return true;
        }

        return $this->quizStarted;
    }

    protected function dispatchTimerStream(string $event, array $context = []): void
    {
        $payload = array_merge([
            'event' => $event,
            'timeRemaining' => $this->timeRemaining,
            'timerMode' => $this->timerMode,
            'quizStarted' => $this->quizStarted,
            'isBreakTime' => $this->isBreakTime,
            'showFeedback' => $this->showFeedback,
        ], $context);

        Log::debug('TakeQuiz streamTimeRemaining dispatch', $payload);

        $this->dispatch('streamTimeRemaining', $payload);
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