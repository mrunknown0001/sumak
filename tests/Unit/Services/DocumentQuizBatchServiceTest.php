<?php

namespace Tests\Unit\Services;

use App\Services\DocumentQuizBatchService;
use Tests\TestCase;

class DocumentQuizBatchServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        session()->forget('quiz.batch');

        session()->put('quiz.batch', [
            'document_id' => 1,
            'queue' => [101, 202],
            'total' => 2,
            'started_at' => now()->toIso8601String(),
            'timer_mode' => null,
            'timer_settings' => null,
        ]);
    }

    protected function tearDown(): void
    {
        session()->forget('quiz.batch');

        parent::tearDown();
    }

    public function test_it_persists_timer_mode_with_custom_settings(): void
    {
        $service = new DocumentQuizBatchService();

        $service->updateTimerMode('custom_pomodoro', [
            'focus_seconds' => 1800,
            'break_seconds' => 600,
        ]);

        $this->assertSame('custom_pomodoro', $service->timerMode());
        $this->assertSame([
            'focus_seconds' => 1800,
            'break_seconds' => 600,
        ], $service->timerSettings());
    }

    public function test_it_clears_timer_settings_when_not_provided(): void
    {
        $service = new DocumentQuizBatchService();

        $service->updateTimerMode('custom_pomodoro', [
            'focus_seconds' => 1500,
            'break_seconds' => 300,
        ]);
        $this->assertNotNull($service->timerSettings());

        $service->updateTimerMode('no_time_limit');

        $this->assertSame('no_time_limit', $service->timerMode());
        $this->assertNull($service->timerSettings());
    }
}