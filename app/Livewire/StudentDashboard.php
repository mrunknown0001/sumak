<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class StudentDashboard extends Component
{
    public $studentData;
    public $courses;
    public $recentQuizzes;
    public $aiFeedback;
    public $overallStats;

    protected $dashboardController;

    public function boot()
    {
        $this->dashboardController = app(\App\Http\Controllers\Student\StudentDashboardController::class);
    }

    public function mount()
    {
        $this->loadDashboardData();
    }

    public function loadDashboardData()
    {
        // Fetch real data from StudentDashboardController
        $data = $this->dashboardController->getDashboardData();
        
        $this->studentData = $data['student_info'];
        $this->courses = $data['courses'];
        $this->recentQuizzes = $data['recent_quizzes'];
        $this->aiFeedback = $data['ai_feedback'];
        $this->overallStats = $data['overall_stats'];
    }

    public function viewCourse($courseId)
    {
        return redirect()->route('student.course.show', $courseId);
    }

    public function viewQuiz($quizId)
    {
        return redirect()->route('student.quiz.result', $quizId);
    }

    public function retakeQuiz($quizId)
    {
        // Check if attempts remaining
        $quiz = collect($this->recentQuizzes)->firstWhere('id', $quizId);

        if ($quiz && $quiz['attempts_remaining'] > 0) {
            return redirect()->route('student.quiz.take', $quiz['quiz_id']);
        }

        session()->flash('error', 'No attempts remaining for this quiz.');
    }

    public function getAbilityLabel($ability)
    {
        if ($ability >= 0.8) {
            return [
                'label' => 'Mastery',
                'color' => 'text-green-600',
                'bg' => 'bg-green-100'
            ];
        }
        if ($ability >= 0.6) {
            return [
                'label' => 'Proficient',
                'color' => 'text-blue-600',
                'bg' => 'bg-blue-100'
            ];
        }
        if ($ability >= 0.4) {
            return [
                'label' => 'Developing',
                'color' => 'text-yellow-600',
                'bg' => 'bg-yellow-100'
            ];
        }
        return [
            'label' => 'Needs Practice',
            'color' => 'text-orange-600',
            'bg' => 'bg-orange-100'
        ];
    }

    public function getProgressColor($progress)
    {
        if ($progress >= 80) return 'bg-green-500';
        if ($progress >= 60) return 'bg-blue-500';
        if ($progress >= 40) return 'bg-yellow-500';
        return 'bg-orange-500';
    }

    public function calculateCompletionRate()
    {
        $totalTaken = collect($this->courses)->sum('quizzes_taken');
        $totalQuizzes = collect($this->courses)->sum('total_quizzes');
        
        return $totalQuizzes > 0 ? round(($totalTaken / $totalQuizzes) * 100) : 0;
    }

    public function render()
    {
        return view('livewire.student-dashboard')
            ->layout('layouts.app', [
                'title' => 'SumakQuiz | Student Dashboard',
                'pageTitle' => 'Dashboard',
                'pageSubtitle' => 'Monitor your mastery, courses, and recent quiz performance.',
            ]);
    }
}