<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Course;
use App\Models\QuizAttempt;
use App\Models\CourseEnrollment;
use App\Models\ObtlDocument;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Jobs\ExtractObtlDocumentJob;

class StudentCourses extends Component
{
    use WithFileUploads;

    public $enrolledCourses;
    public $availableCourses;
    public $activeTab = 'enrolled';
    public $showCreateCourseModal = false;

    public $newCourse = [
        'course_code' => '',
        'course_title' => '',
        'description' => '',
    ];

    public $newCourseObtl;
    public bool $creatingCourse = false;

    public function mount(): void
    {
        $this->loadCourses();
        $this->resetNewCourseForm();

        if ($this->enrolledCourses->isEmpty()) {
            $this->activeTab = 'available';
        }
    }

    public function loadCourses(): void
    {
        $userId = auth()->id();

        $this->enrolledCourses = Course::whereHas('enrollments', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->with(['obtlDocument', 'documents'])
            ->withCount('documents')
            ->orderByDesc('created_at')
            ->get();

        $enrolledIds = $this->enrolledCourses->pluck('id');

        $this->availableCourses = Course::whereNotIn('id', $enrolledIds)
            ->where('user_id' , auth()->id())
            ->with(['obtlDocument', 'user'])
            ->withCount(['documents', 'enrollments'])
            ->orderByDesc('created_at')
            ->get();
    }

    public function openCreateCourseModal(): void
    {
        $this->resetValidation();
        $this->showCreateCourseModal = true;
    }

    public function closeCreateCourseModal(): void
    {
        $this->resetValidation();
        $this->resetNewCourseForm();
        $this->showCreateCourseModal = false;
    }

    public function createCourse()
    {
        if ($this->creatingCourse) {
            return;
        }

        $this->creatingCourse = true;

        $this->validate(
            $this->courseValidationRules(),
            $this->courseValidationMessages()
        );

        $course = null;
        $obtlDocument = null;
        $storedRelativePath = null;

        DB::beginTransaction();

        try {
            $course = Course::create([
                'course_code' => $this->newCourse['course_code'],
                'course_title' => $this->newCourse['course_title'],
                'description' => $this->newCourse['description'] ?: null,
                'workflow_stage' => Course::WORKFLOW_STAGE_DRAFT,
            ]);

            CourseEnrollment::updateOrCreate(
                [
                    'user_id' => auth()->id(),
                    'course_id' => $course->id,
                ],
                [
                    'enrolled_at' => now(),
                ]
            );

            if ($this->newCourseObtl) {
                $storedRelativePath = $this->newCourseObtl->store('obtl_documents', 'private');
                $absolutePath = Storage::disk('private')->path($storedRelativePath);

                $obtlDocument = ObtlDocument::create([
                    'course_id' => $course->id,
                    'user_id' => auth()->id(),
                    'title' => $this->newCourseObtl->getClientOriginalName(),
                    'file_path' => $absolutePath,
                    'file_type' => $this->newCourseObtl->getClientOriginalExtension() ?: 'pdf',
                    'file_size' => $this->newCourseObtl->getSize(),
                    'uploaded_at' => now(),
                    'processing_status' => ObtlDocument::PROCESSING_PENDING,
                ]);

                $course->update([
                    'workflow_stage' => Course::WORKFLOW_STAGE_OBTL_UPLOADED,
                    'obtl_uploaded_at' => now(),
                ]);
            }

            DB::commit();
        } catch (\Throwable $exception) {
            DB::rollBack();

            if ($storedRelativePath) {
                Storage::disk('private')->delete($storedRelativePath);
            }

            report($exception);

            $this->addError('createCourse', 'Unable to create the course right now. Please try again.');
            $this->creatingCourse = false;

            return;
        }

        if ($obtlDocument) {
            ExtractObtlDocumentJob::dispatch($obtlDocument->id);
        }

        $this->creatingCourse = false;

        session()->flash('message', 'Course created successfully.');

        $this->closeCreateCourseModal();
        $this->loadCourses();
        $this->activeTab = 'enrolled';

        return redirect()->route('student.course.show', $course->id);
    }

    public function enroll(int $courseId): void
    {
        $course = Course::findOrFail($courseId);

        if ($course->isEnrolledBy(auth()->id())) {
            session()->flash('error', 'You are already enrolled in this course.');
            return;
        }

        CourseEnrollment::create([
            'user_id' => auth()->id(),
            'course_id' => $courseId,
            'enrolled_at' => now(),
        ]);

        $this->loadCourses();
        session()->flash('message', 'Successfully enrolled in ' . $course->course_title . '!');
    }

    public function unenroll(int $courseId): void
    {
        DB::beginTransaction();
        $enrollment = CourseEnrollment::where('user_id', auth()->id())
            ->where('course_id', $courseId)
            ->first();
        QuizAttempt::where('user_id', auth()->id())
            ->whereHas('topic.document', function ($query) use ($courseId) {
                $query->where('course_id', $courseId);
            })
            ->delete();
        if ($enrollment) {
            $enrollment->delete();
            $this->loadCourses();
            DB::commit();
            session()->flash('message', 'Successfully unenrolled from course.');
        }
        DB::rollBack();
    }


    public function deleteCourse(int $courseId)
    {
        DB::beginTransaction();
        
        try {
            $course = Course::findOrFail($courseId);
            $course->delete();
            
            DB::commit();
            
            $this->dispatch('delete-course', message: "Course deleted successfully");
            $this->loadCourses();
            
            session()->flash('message', 'Course deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            
            session()->flash('error', 'Unable to delete the course. Please try again.');
            report($e);
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

    protected function resetNewCourseForm(): void
    {
        $this->newCourse = [
            'course_code' => '',
            'course_title' => '',
            'description' => '',
        ];

        $this->newCourseObtl = null;
    }

    protected function courseValidationRules(): array
    {
        return [
            'newCourse.course_code' => 'required|string|max:50',
            'newCourse.course_title' => 'required|string|max:255',
            'newCourse.description' => 'nullable|string|max:1000',
            'newCourseObtl' => 'nullable|file|mimes:pdf|max:10240',
        ];
    }

    protected function courseValidationMessages(): array
    {
        return [
            'newCourse.course_code.required' => 'Course code is required.',
            'newCourse.course_title.required' => 'Course title is required.',
            'newCourseObtl.mimes' => 'The OBTL document must be a PDF file.',
            'newCourseObtl.max' => 'The OBTL document must not exceed 10MB.',
        ];
    }
}