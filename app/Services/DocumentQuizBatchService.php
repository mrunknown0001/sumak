<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Support\Collection;

class DocumentQuizBatchService
{
    public function eligibleSubtopicsForUser(Document $document, int $userId): Collection
    {
        $maxAttempts = (int) config('quiz.max_attempts', 3);

        $document->loadMissing([
            'topics' => function ($query) use ($userId) {
                $query->withCount('items')
                    ->withCount([
                        'quizAttempts as user_attempts_count' => function ($attemptQuery) use ($userId) {
                            $attemptQuery->where('user_id', $userId);
                        },
                    ]);
            },
        ]);

        return $document->topics
            ->flatMap(fn ($topic) => ($topic->items_count ?? 0) > 0   && (($topic->user_attempts_count ?? 0) < $maxAttempts))
            ->values();
    }

    public function initialiseBatchSession(Document $document, Collection $topics): void
    {
        session()->put('quiz.batch', [
            'document_id' => $document->id,
            'queue' => $topics->pluck('id')->values()->all(),
            'total' => $topics->count(),
            'started_at' => now()->toIso8601String(),
            'timer_mode' => null,
            'timer_settings' => null,
        ]);
    }

    public function currentBatch(): ?array
    {
        $batch = session('quiz.batch');

        return is_array($batch) ? $batch : null;
    }

    public function updateTimerMode(string $timerMode, ?array $timerSettings = null): void
    {
        $batch = $this->currentBatch();

        if (! $batch) {
            return;
        }

        $batch['timer_mode'] = $timerMode;

        if ($timerSettings !== null) {
            $batch['timer_settings'] = $timerSettings;
        } elseif (array_key_exists('timer_settings', $batch)) {
            unset($batch['timer_settings']);
        }

        session()->put('quiz.batch', $batch);
    }

    public function timerMode(): ?string
    {
        $batch = $this->currentBatch();

        return $batch['timer_mode'] ?? null;
    }

    public function timerSettings(): ?array
    {
        $batch = $this->currentBatch();

        $settings = $batch['timer_settings'] ?? null;

        return is_array($settings) ? $settings : null;
    }

    public function clearBatch(): void
    {
        session()->forget('quiz.batch');
    }

    public function advanceAfterCompletion(int $completedTopicId): ?int
    {
        $batch = $this->currentBatch();

        if (! $batch || empty($batch['queue'])) {
            $this->clearBatch();

            return null;
        }

        $queue = array_values(array_filter(
            $batch['queue'],
            static fn ($topicId) => (int) $topicId !== (int) $completedTopicId,
            ARRAY_FILTER_USE_BOTH
        ));

        if (empty($queue)) {
            $this->clearBatch();

            return null;
        }

        $batch['queue'] = $queue;

        session()->put('quiz.batch', $batch);

        return (int) $queue[0];
    }
}