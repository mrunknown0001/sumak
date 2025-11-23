<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class DocumentQuizBatchService
{
    public function eligibleSubtopicsForUser(Document $document, int $userId): Collection
    {
        $maxAttempts = (int) config('quiz.max_attempts', 3);
        
        Log::debug('Batch: Eligible topics loaded', [
            'document_id' => $document->id,
            'topics_raw' => $document->topics->map(function($t){
                return [
                    'id' => $t->id,
                    'name' => $t->name,
                    'items_count' => $t->items_count,
                    'user_attempts_count' => $t->user_attempts_count,
                ];
            }),
            'eligible_ids' => $document->topics
                ->filter(fn($t) => ($t->items_count ?? 0) > 0 && ($t->user_attempts_count ?? 0) < $maxAttempts)
                ->pluck('id'),
        ]);



        $document->loadMissing([
            'topics' => function ($query) use ($userId) {
                $query->withCount('items')
                    ->withCount([
                        // Count only completed attempts so "in-progress" doesn't block retries
                        'quizAttempts as user_attempts_count' => function ($attemptQuery) use ($userId) {
                            $attemptQuery->where('user_id', $userId)
                                        ->whereNotNull('completed_at');
                        },
                    ]);
            },
        ]);

        // Return actual Topic models that have items and are under max attempts
        return $document->topics
            ->filter(function ($topic) use ($maxAttempts) {
                return ($topic->items_count ?? 0) > 0 &&
                    ($topic->user_attempts_count ?? 0) < $maxAttempts;
            })
            ->values();
    }

    public function initialiseBatchSession(Document $document, Collection $topics): void
    {
        Log::debug('Batch: Initialising batch session', [
            'document_id' => $document->id,
            'topics_passed' => $topics->pluck('id'),
        ]);
        // ensure topics is a collection of models and grab their integer ids
        $queue = $topics->pluck('id')->filter()->map(fn($id) => (int) $id)->values()->all();

        // If nothing valid, don't initialise
        if (empty($queue)) {
            $this->clearBatch();
            return;
        }

        session()->put('quiz.batch', [
            'document_id' => (int) $document->id,
            'queue' => $queue,
            'total' => count($queue),
            'started_at' => now()->toIso8601String(),
            'timer_mode' => null,
            'timer_settings' => null,
        ]);

        Log::debug('Batch: Stored session', [
            'session_batch' => session('quiz.batch')
        ]);
    }

    public function currentBatch(): ?array
    {
        $batch = session('quiz.batch');

        Log::debug('Batch: currentBatch() raw session', [
            'raw_batch' => $batch,
        ]);

        if (! is_array($batch)) {
            return null;
        }

        // Defensive sanitation: ensure document_id exists and queue contains integer topic ids only
        $documentId = isset($batch['document_id']) ? (int) $batch['document_id'] : null;
        $queue = collect($batch['queue'] ?? [])
            ->filter(fn($v) => is_numeric($v) || (is_string($v) && ctype_digit($v)))
            ->map(fn($v) => (int) $v)
            ->values()
            ->all();

        if (empty($queue) || ! $documentId) {
            // corrupted/empty — clear and return null
            $this->clearBatch();
            return null;
        }

        $batch['document_id'] = $documentId;
        $batch['queue'] = $queue;
        // normalize total
        $batch['total'] = count($queue);

        // persist sanitized batch back to session in case it changed
        session()->put('quiz.batch', $batch);

        Log::debug('Batch: currentBatch() sanitized return', [
            'sanitized_batch' => $batch,
        ]);

        return $batch;
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