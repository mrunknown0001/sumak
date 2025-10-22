<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\QuizAttempt;
use Illuminate\Support\Facades\Log;

class QuizResult extends Component
{
    public QuizAttempt $attempt;

    public function mount(QuizAttempt $attempt)
    {
        $this->authorize('view', $attempt);
        $this->attempt = $attempt->load([
            'responses.item',
            'feedback',
            'subtopic.topic.document.course'
        ]);

        Log::debug('QuizResult attempt timing snapshot', [
            'attempt_id' => $this->attempt->id,
            'started_at' => optional($this->attempt->started_at)->toIso8601String(),
            'completed_at' => optional($this->attempt->completed_at)->toIso8601String(),
            'time_spent_seconds' => $this->attempt->time_spent_seconds,
            'time_spent_minutes_accessor' => $this->attempt->time_spent_minutes,
        ]);
    }

    public function render()
    {
        return view('livewire.quiz-result')->layout('layouts.app', [
            'title' => 'SumakQuiz | Quiz Result',
            'pageTitle' => 'Quiz Results',
            'pageSubtitle' => $this->attempt->subtopic->name . ' • Review performance insights and feedback.',
        ]);
    }
}