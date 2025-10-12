<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Course;

class CourseDetail extends Component
{
    public Course $course;
    public $isEnrolled = false;
    
    public function mount(Course $course)
    {
        $this->course = $course;
        $this->isEnrolled = $course->isEnrolledBy(auth()->id());
        
        // Redirect if not enrolled
        if (!$this->isEnrolled) {
            return redirect()->route('student.courses')
                ->with('error', 'You must enroll in this course first.');
        }
    }

    public function render()
    {
        return view('livewire.course-detail', [
            'documents' => $this->course->documents()
                ->with(['topics.subtopics.items'])
                ->latest()
                ->get()
        ])->layout('layouts.app', [
            'title' => 'SumakQuiz | ' . $this->course->course_code,
            'pageTitle' => $this->course->course_title,
            'pageSubtitle' => $this->course->course_code . ' • Lecture materials and quiz readiness status.',
        ]);
    }
}