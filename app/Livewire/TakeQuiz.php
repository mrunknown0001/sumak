<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Subtopic;
use App\Models\QuizAttempt;
use App\Models\Response;
use App\Models\ItemBank;
use App\Models\StudentAbility;
use App\Services\IrtService;
use App\Jobs\GenerateFeedbackJob;
use Illuminate\Support\Facades\DB;

class TakeQuiz extends Component
{
    public Subtopic $subtopic;
    public $attempt;
    public $questions;
    public $currentQuestionIndex = 0;
    public $selectedAnswer = null;
    public $timeRemaining = 60;
    public $quizStarted = false;
    public $quizCompleted = false;
    public $showFeedback = false;
    public $isCorrect = false;
    public $correctAnswer = null;
    public $timerMode = null; // 'standard', 'pomodoro', 'free'
    public $pomodoroSessionTime = 1500; // 25 minutes in seconds
    public $pomodoroBreakTime = 300; // 5 minutes in seconds
    public $isBreakTime = false;

    protected $irtService;

    public function boot(IrtService $irtService)
    {
        $this->irtService = $irtService;
    }

    public function mount(Subtopic $subtopic)
    {
        $this->subtopic = $subtopic->load('topic.document.course');
    }

    public function selectTimerMode($mode)
    {
        $this->timerMode = $mode;
        
        // Set initial time based on mode
        if ($mode === 'pomodoro') {
            $this->timeRemaining = $this->pomodoroSessionTime;
        } elseif ($mode === 'standard') {
            $this->timeRemaining = 60;
        } else { // free time
            $this->timeRemaining = 0;
        }
    }

    public function startQuiz()
    {
        // Ensure timer mode is selected
        if (!$this->timerMode) {
            session()->flash('error', 'Please select a timer mode first.');
            return;
        }

        // Check if adaptive quiz should be generated
        $isAdaptive = $this->subtopic->hasCompletedAllInitialQuizzes(auth()->id());
        
        // Get questions
        if ($isAdaptive) {
            $studentAbility = StudentAbility::firstOrCreate(
                ['user_id' => auth()->id(), 'subtopic_id' => $this->subtopic->id],
                ['theta' => 0, 'attempts_count' => 0]
            );
            
            $availableItems = $this->subtopic->items->map(fn($item) => [
                'id' => $item->id,
                'difficulty' => $item->difficulty_b,
            ])->toArray();
            
            $selectedIds = $this->irtService->selectAdaptiveItems(
                $studentAbility->theta, 
                $availableItems, 
                20
            );
            
            $this->questions = ItemBank::whereIn('id', $selectedIds)->get();
        } else {
            $this->questions = $this->subtopic->items()
                ->inRandomOrder()
                ->limit(20)
                ->get();
        }

        // Create attempt
        $attemptNumber = QuizAttempt::where('user_id', auth()->id())
            ->where('subtopic_id', $this->subtopic->id)
            ->max('attempt_number') + 1;

        $this->attempt = QuizAttempt::create([
            'user_id' => auth()->id(),
            'subtopic_id' => $this->subtopic->id,
            'attempt_number' => $attemptNumber,
            'is_adaptive' => $isAdaptive,
            'total_questions' => $this->questions->count(),
            'started_at' => now(),
        ]);

        $this->quizStarted = true;
        $this->resetTimer();
    }

    public function submitAnswer()
    {
        if (!$this->selectedAnswer) return;

        $question = $this->questions[$this->currentQuestionIndex];
        $this->isCorrect = $question->correct_answer === $this->selectedAnswer;
        $this->correctAnswer = $question->correct_answer;

        // Save response
        Response::create([
            'quiz_attempt_id' => $this->attempt->id,
            'item_id' => $question->id,
            'user_id' => auth()->id(),
            'user_answer' => $this->selectedAnswer,
            'is_correct' => $this->isCorrect,
            'time_taken_seconds' => 60 - $this->timeRemaining,
            'response_at' => now(),
        ]);

        $this->showFeedback = true;
    }

    public function nextQuestion()
    {
        $this->currentQuestionIndex++;
        $this->selectedAnswer = null;
        $this->showFeedback = false;
        $this->isCorrect = false;
        $this->correctAnswer = null;
        
        if ($this->currentQuestionIndex >= $this->questions->count()) {
            $this->completeQuiz();
        } else {
            $this->resetTimer();
        }
    }

    public function completeQuiz()
    {
        $correctAnswers = $this->attempt->responses()->where('is_correct', true)->count();
        $scorePercentage = ($correctAnswers / $this->attempt->total_questions) * 100;

        $this->attempt->update([
            'correct_answers' => $correctAnswers,
            'score_percentage' => round($scorePercentage, 2),
            'completed_at' => now(),
            'time_spent_seconds' => now()->diffInSeconds($this->attempt->started_at),
        ]);

        // Update student ability using IRT
        $studentAbility = StudentAbility::firstOrCreate(
            ['user_id' => auth()->id(), 'subtopic_id' => $this->subtopic->id],
            ['theta' => 0, 'attempts_count' => 0]
        );

        $responses = $this->attempt->responses()
            ->with('item')
            ->get()
            ->map(fn($r) => [
                'difficulty' => $r->item->difficulty_b,
                'correct' => $r->is_correct,
            ]);

        $newTheta = $this->irtService->estimateAbility(
            $studentAbility->theta,
            $responses->toArray()
        );

        $studentAbility->increment('attempts_count');
        $studentAbility->update(['theta' => $newTheta]);

        // Dispatch feedback generation
        GenerateFeedbackJob::dispatch($this->attempt->id);

        $this->quizCompleted = true;
    }

    public function resetTimer()
    {
        if ($this->timerMode === 'pomodoro') {
            if ($this->isBreakTime) {
                $this->timeRemaining = $this->pomodoroBreakTime;
            } else {
                $this->timeRemaining = $this->pomodoroSessionTime;
            }
        } elseif ($this->timerMode === 'standard') {
            $this->timeRemaining = 60;
        } else {
            $this->timeRemaining = 0; // Free time mode
        }
        
        $this->dispatch('resetTimer');
    }

    public function startBreak()
    {
        $this->isBreakTime = true;
        $this->timeRemaining = $this->pomodoroBreakTime;
        $this->dispatch('startBreak');
    }

    public function endBreak()
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