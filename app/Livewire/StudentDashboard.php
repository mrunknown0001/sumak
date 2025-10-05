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
        // In production, fetch from StudentDashboardController
        // Uncomment the line below to use real data:
        // $data = $this->dashboardController->getDashboardData();
        
        // Mock data for demonstration
        $student = Auth::user();
        $this->studentData = [
            'name' => $student->name ?? 'Maria Santos',
            'student_id' => $student->student_id ?? '2024-00123',
        ];

        $this->courses = [
            [
                'id' => 1,
                'name' => 'Data Structures & Algorithms',
                'code' => 'CS201',
                'progress' => 75,
                'quizzes_taken' => 8,
                'total_quizzes' => 12,
                'avg_score' => 85,
                'ability_level' => 0.65,
                'status' => 'active'
            ],
            [
                'id' => 2,
                'name' => 'Database Management Systems',
                'code' => 'CS301',
                'progress' => 60,
                'quizzes_taken' => 5,
                'total_quizzes' => 10,
                'avg_score' => 78,
                'ability_level' => 0.42,
                'status' => 'active'
            ],
            [
                'id' => 3,
                'name' => 'Web Development',
                'code' => 'CS205',
                'progress' => 90,
                'quizzes_taken' => 10,
                'total_quizzes' => 10,
                'avg_score' => 92,
                'ability_level' => 0.85,
                'status' => 'active'
            ]
        ];

        $this->recentQuizzes = [
            [
                'id' => 1,
                'course' => 'Data Structures & Algorithms',
                'topic' => 'Binary Search Trees',
                'score' => 18,
                'total' => 20,
                'date' => '2025-10-03',
                'duration' => '18 mins',
                'attempts_used' => 1,
                'attempts_remaining' => 2,
                'ability_estimate' => 0.72
            ],
            [
                'id' => 2,
                'course' => 'Database Management Systems',
                'topic' => 'Normalization',
                'score' => 15,
                'total' => 20,
                'date' => '2025-10-02',
                'duration' => '20 mins',
                'attempts_used' => 2,
                'attempts_remaining' => 1,
                'ability_estimate' => 0.45
            ],
            [
                'id' => 3,
                'course' => 'Web Development',
                'topic' => 'React Hooks',
                'score' => 19,
                'total' => 20,
                'date' => '2025-10-01',
                'duration' => '17 mins',
                'attempts_used' => 1,
                'attempts_remaining' => 2,
                'ability_estimate' => 0.88
            ]
        ];

        $this->aiFeedback = [
            [
                'course' => 'Data Structures & Algorithms',
                'topic' => 'Binary Search Trees',
                'feedback' => 'Excellent work on tree traversal concepts! You demonstrated strong understanding of in-order, pre-order, and post-order traversals. Consider reviewing balancing operations for even better performance.',
                'recommendations' => ['Practice AVL tree rotations', 'Study Red-Black tree properties'],
                'strengths' => ['Tree traversal algorithms', 'Recursive thinking'],
                'areas_to_improve' => ['Tree balancing', 'Time complexity analysis']
            ],
            [
                'course' => 'Database Management Systems',
                'topic' => 'Normalization',
                'feedback' => 'You\'re making good progress with normalization! Focus on identifying functional dependencies more accurately. Review 3NF and BCNF differences.',
                'recommendations' => ['Practice with more complex schemas', 'Review functional dependency rules'],
                'strengths' => ['1NF and 2NF identification'],
                'areas_to_improve' => ['3NF vs BCNF distinction', 'Decomposition techniques']
            ]
        ];

        $this->overallStats = [
            'total_quizzes_taken' => 23,
            'avg_accuracy' => 85,
            'total_study_time' => '42 hours',
            'mastery_level' => 'Advanced Beginner',
            'overall_ability' => 0.65
        ];
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
            return redirect()->route('student.quiz.retake', $quizId);
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
            ->layout('layouts.app');
    }
}