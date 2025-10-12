<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\QuizAttempt;

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