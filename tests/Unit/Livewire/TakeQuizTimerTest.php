<?php

namespace Tests\Unit\Livewire;

use App\Livewire\TakeQuiz;
use App\Models\Topic;
use App\Services\DocumentQuizBatchService;
use App\Services\IrtService;
use Illuminate\Support\Facades\Session;
use Mockery;
use Tests\TestCase;

class TakeQuizTimerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Session::forget('quiz.timer.custom_pomodoro');
    }

    protected function tearDown(): void
    {
        Session::forget('quiz.timer.custom_pomodoro');

        parent::tearDown();
    }

    public function test_custom_pomodoro_settings_are_applied_and_persisted(): void
    {
        $component = $this->makeComponent();
        $component->timerMode = 'custom_pomodoro';
        $component->isBreakTime = false;

        $component->exposeApplyPomodoroDurationsFromSeconds(1800, 600);
        $component->exposePersistCustomPomodoroToSession(1800, 600);

        $this->assertSame(1800, $component->pomodoroSessionTime);
        $this->assertSame(600, $component->pomodoroBreakTime);
        $this->assertSame(30, $component->customFocusMinutes);
        $this->assertSame(10, $component->customBreakMinutes);

        $settings = $component->exposeCurrentPomodoroSettings();
        $this->assertSame([
            'focus_seconds' => 1800,
            'break_seconds' => 600,
            'is_custom' => true,
        ], $settings);

        $stored = Session::get('quiz.timer.custom_pomodoro');
        $this->assertSame(1800, $stored['focus_seconds']);
        $this->assertSame(600, $stored['break_seconds']);
    }

    public function test_hydrate_timer_from_batch_uses_service_values(): void
    {
        $batchMock = Mockery::mock(DocumentQuizBatchService::class);
        $batchMock->shouldReceive('timerMode')->once()->andReturn('custom_pomodoro');
        $batchMock->shouldReceive('timerSettings')->once()->andReturn([
            'focus_seconds' => 2100,
            'break_seconds' => 420,
        ]);

        $irtMock = Mockery::mock(IrtService::class);

        $component = $this->makeComponent($irtMock, $batchMock);
        $component->exposeHydrateTimerFromBatch();

        $this->assertSame('custom_pomodoro', $component->timerMode);
        $this->assertSame(2100, $component->pomodoroSessionTime);
        $this->assertSame(420, $component->pomodoroBreakTime);
        $this->assertTrue($component->customPomodoroConfigured);

        $stored = Session::get('quiz.timer.custom_pomodoro');
        $this->assertSame(2100, $stored['focus_seconds']);
        $this->assertSame(420, $stored['break_seconds']);
    }

    protected function makeComponent(
        ?IrtService $irtService = null,
        ?DocumentQuizBatchService $batchService = null
    ): TestableTakeQuiz {
        $component = new TestableTakeQuiz();
        $component->items = collect();
        $component->topic = new Topic();
        $component->timerMode = null;
        $component->isBreakTime = false;
        $component->customPomodoroConfigured = false;

        $component->boot(
            $irtService ?? Mockery::mock(IrtService::class),
            $batchService ?? Mockery::mock(DocumentQuizBatchService::class)
        );

        return $component;
    }
}

class TestableTakeQuiz extends TakeQuiz
{
    public function exposeHydrateTimerFromBatch(): void
    {
        $this->hydrateTimerFromBatch();
    }

    public function exposeApplyPomodoroDurationsFromSeconds(int $focusSeconds, int $breakSeconds): void
    {
        $this->applyPomodoroDurationsFromSeconds($focusSeconds, $breakSeconds);
    }

    public function exposeCurrentPomodoroSettings(): ?array
    {
        return $this->currentPomodoroSettings();
    }

    public function exposePersistCustomPomodoroToSession(int $focusSeconds, int $breakSeconds): void
    {
        $this->persistCustomPomodoroToSession($focusSeconds, $breakSeconds);
    }
}