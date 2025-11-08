<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseEnrollment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use App\Models\QuizAttempt;

class EnrollmentController extends Controller
{
    /**
     * Enroll student in a course
     */
    public function enroll(Course $course): RedirectResponse
    {
        $userId = auth()->id();
        
        // Check if already enrolled
        if ($course->isEnrolledBy($userId)) {
            return redirect()->back()->with('error', 'You are already enrolled in this course.');
        }

        // Create enrollment
        CourseEnrollment::create([
            'user_id' => $userId,
            'course_id' => $course->id,
            'enrolled_at' => now(),
        ]);

        return redirect()->route('student.course.show', $course->id)
            ->with('message', 'Successfully enrolled in ' . $course->course_title);
    }

    /**
     * Unenroll from course
     */
    public function unenroll(Course $course): RedirectResponse
    {
        $enrollment = CourseEnrollment::where('user_id', auth()->id())
            ->where('course_id', $course->id)
            ->first();
            
        if ($enrollment) {
            $enrollment->delete();
            return redirect()->route('student.courses')
                ->with('message', 'Unenrolled from ' . $course->course_title);
        }

        return redirect()->back()->with('error', 'You are not enrolled in this course.');
    }
}