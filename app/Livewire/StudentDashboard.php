<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\QuizAttempt;
use App\Models\ItemBank;

class StudentDashboard extends Component
{
    public $studentData;
    public $courses;
    public $recentQuizzes;
    public $consolidatedRecentQuizzes;
    public $aiFeedback;
    public $overallStats;
    public $selectedCourse;
    public $graphData = [];
    public $scoreData = [];

    protected $dashboardController;

    public function boot()
    {
        $this->dashboardController = app(\App\Http\Controllers\Student\StudentDashboardController::class);
    }

    public function mount()
    {
        $this->loadDashboardData();
        $this->selectedCourse = !empty($this->courses) ? $this->courses[0]['id'] : null;
        $this->loadGraphData();
        $this->loadScoreData();
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

        // Consolidate recent quizzes by course
        $this->consolidatedRecentQuizzes = collect($this->recentQuizzes)
            ->groupBy('course')
            ->map(function ($quizzes) {
                $sortedQuizzes = $quizzes->sortByDesc('date');
                $latestQuiz = $sortedQuizzes->first();
                $totalDuration = $sortedQuizzes->sum(function ($quiz) {
                    return abs($quiz['duration_seconds']);
                });
                $averagePercentage = $sortedQuizzes->avg(function ($quiz) {
                    return ($quiz['score'] / $quiz['total']) * 100;
                });
                return [
                    'course' => $latestQuiz['course'],
                    'score' => number_format($averagePercentage, 2) . '%',
                    'total_duration' => $this->formatStudyTime($totalDuration),
                    'date' => $latestQuiz['date'],
                    'quiz_id' => $latestQuiz['id'],
                ];
            })
            ->sortByDesc('date')
            ->values();
   }

   public function loadGraphData()
   {
       $this->graphData = [];
       if ($this->selectedCourse) {
           $attempts = QuizAttempt::whereHas('topic.document', function($q) {
               $q->where('course_id', $this->selectedCourse);
           })->where('user_id', auth()->id())->whereNotNull('completed_at')->with('responses')->get();

           // Group by attempt_number and calculate average difficulty
           $grouped = $attempts->groupBy('attempt_number');
           $this->graphData = collect([1, 2, 3])->map(function($attemptNumber) use ($grouped) {
               $attemptsForNumber = $grouped->get($attemptNumber, collect());
               if ($attemptsForNumber->isEmpty()) {
                   $difficulty = 0;
               } else {
                   $allItemIds = $attemptsForNumber->pluck('responses')->flatten()->pluck('item_id')->unique()->toArray();
                   $difficulty = ItemBank::whereIn('id', $allItemIds)->avg('difficulty_b') ?? 0;
               }
               return [
                   'attempt' => 'Attempt ' . $attemptNumber,
                   'difficulty' => round($difficulty, 2)
               ];
           })->toArray();
       }
       $this->js("window.dispatchEvent(new CustomEvent('updateChart', {detail: " . json_encode($this->graphData) . "}))");
   }

   public function loadScoreData()
   {
       $this->scoreData = [];
       if ($this->selectedCourse) {
           $attempts = QuizAttempt::whereHas('topic.document', function($q) {
               $q->where('course_id', $this->selectedCourse);
           })->where('user_id', auth()->id())->whereNotNull('completed_at')->get();

           // Group by attempt_number and calculate average score
           $grouped = $attempts->groupBy('attempt_number');
           $this->scoreData = collect([1, 2, 3])->map(function($attemptNumber) use ($grouped) {
               $attemptsForNumber = $grouped->get($attemptNumber, collect());
               if ($attemptsForNumber->isEmpty()) {
                   $score = 0;
               } else {
                   $score = $attemptsForNumber->avg('score_percentage');
               }
               return [
                   'attempt' => 'Attempt ' . $attemptNumber,
                   'score' => round($score, 2)
               ];
           })->values()->toArray();
       }
       $this->js("window.dispatchEvent(new CustomEvent('updateScoreChart', {detail: " . json_encode($this->scoreData) . "}))");
   }

   public function updatedSelectedCourse()
   {
       $this->loadGraphData();
       $this->loadScoreData();
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
            return redirect()->route('student.quiz.context', $quiz['quiz_id']);
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

    private function formatStudyTime($seconds)
    {
        if($seconds < 0) {
            $seconds = -($seconds);
        }
        $seconds = max(0, (int) $seconds);

        if ($seconds === 0) {
            return '0 mins';
        }

        if ($seconds < 60) {
            return $seconds === 1 ? '1 sec' : $seconds . ' secs';
        }

        if ($seconds < 3600) {
            $minutes = (int) max(1, round($seconds / 60));
            return $minutes === 1 ? '1 min' : $minutes . ' mins';
        }

        $hours = intdiv($seconds, 3600);
        $minutes = (int) floor(($seconds % 3600) / 60);

        $hourLabel = $hours === 1 ? '1 hr' : $hours . ' hrs';

        if ($minutes <= 0) {
            return $hourLabel;
        }

        $minuteLabel = $minutes === 1 ? '1 min' : $minutes . ' mins';

        return $hourLabel . ' ' . $minuteLabel;
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