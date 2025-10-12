<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Course;
use App\Models\CourseEnrollment;

class StudentCourses extends Component
{
    public $enrolledCourses;
    public $availableCourses;
    public $activeTab = 'enrolled'; // 'enrolled' or 'available'

    public function mount()
    {
        $this->loadCourses();
        
        // If no enrolled courses, default to available tab
        if ($this->enrolledCourses->isEmpty()) {
            $this->activeTab = 'available';
        }
    }

    public function loadCourses()
    {
        $userId = auth()->id();

        // Get enrolled courses
        $this->enrolledCourses = Course::whereHas('enrollments', function($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->with(['obtlDocument', 'documents'])
            ->withCount('documents')
            ->get();

        // Get available courses (not enrolled yet)
        $enrolledIds = $this->enrolledCourses->pluck('id');
        $this->availableCourses = Course::whereNotIn('id', $enrolledIds)
            ->with(['obtlDocument', 'user'])
            ->withCount(['documents', 'enrollments'])
            ->get();
    }

    public function enroll($courseId)
    {
        $course = Course::findOrFail($courseId);
        
        // Check if already enrolled
        if ($course->isEnrolledBy(auth()->id())) {
            session()->flash('error', 'You are already enrolled in this course.');
            return;
        }

        // Create enrollment
        CourseEnrollment::create([
            'user_id' => auth()->id(),
            'course_id' => $courseId,
            'enrolled_at' => now(),
        ]);

        $this->loadCourses();
        session()->flash('message', 'Successfully enrolled in ' . $course->course_title . '!');
    }

    public function unenroll($courseId)
    {
        $enrollment = CourseEnrollment::where('user_id', auth()->id())
            ->where('course_id', $courseId)
            ->first();

        if ($enrollment) {
            $enrollment->delete();
            $this->loadCourses();
            session()->flash('message', 'Successfully unenrolled from course.');
        }
    }

    public function render()
    {
        return view('livewire.student-courses')
            ->layout('layouts.app', [
                'title' => 'SumakQuiz | Courses',
                'pageTitle' => 'Courses',
                'pageSubtitle' => $this->activeTab === 'enrolled'
                    ? 'Access and manage courses you are currently enrolled in.'
                    : 'Discover new courses and expand your learning journey.',
            ]);
    }
}