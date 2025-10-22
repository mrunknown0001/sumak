# SumakQuiz Implementation Plan
## Step-by-Step Guide to Complete Missing Modules

**Based on:** Gap Analysis Report  
**Timeline:** 4 weeks (160 hours)  
**Priority:** Critical modules first

---

## Week 1: Core Student Experience

### Day 1-2: Student Dashboard Real Data Integration

**Objective:** Replace mock data with real database queries

**Files to Modify:**
1. [`app/Livewire/StudentDashboard.php`](app/Livewire/StudentDashboard.php:28)

**Steps:**
```php
// BEFORE (Line 25-141): Mock data
$this->studentData = [
    'name' => $student->name ?? 'Maria Santos',
    // ... mock data
];

// AFTER: Real data
public function loadDashboardData()
{
    $data = $this->dashboardController->getDashboardData();
    
    $this->studentData = $data['student_data'];
    $this->courses = $data['courses'];
    $this->recentQuizzes = $data['recent_quizzes'];
    $this->aiFeedback = $data['ai_feedback'];
    $this->overallStats = $data['overall_stats'];
}
```

**Testing:**
- Verify course data displays correctly
- Check quiz attempts show real scores
- Confirm AI feedback displays (if generated)

**Estimated Time:** 4 hours

---

### Day 2-3: Course Management UI

**Objective:** Students can create and view courses

**Files to Create:**

#### 1. `app/Livewire/StudentCourses.php`
```php
<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Course;
use App\Models\ObtlDocument;

class StudentCourses extends Component
{
    use WithFileUploads;

    public $courses;
    public $showCreateModal = false;
    
    // Form fields
    public $course_code;
    public $course_title;
    public $description;
    public $obtl_file;

    public function mount()
    {
        $this->loadCourses();
    }

    public function loadCourses()
    {
        $this->courses = Course::where('user_id', auth()->id())
            ->with(['obtlDocument', 'documents'])
            ->withCount('documents')
            ->latest()
            ->get();
    }

    public function createCourse()
    {
        $this->validate([
            'course_code' => 'required|string|max:50',
            'course_title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'obtl_file' => 'nullable|file|mimes:pdf|max:10240',
        ]);

        $course = Course::create([
            'user_id' => auth()->id(),
            'course_code' => $this->course_code,
            'course_title' => $this->course_title,
            'description' => $this->description,
        ]);

        if ($this->obtl_file) {
            $path = $this->obtl_file->store('obtl_documents', 'private');
            
            ObtlDocument::create([
                'course_id' => $course->id,
                'user_id' => auth()->id(),
                'title' => $this->obtl_file->getClientOriginalName(),
                'file_path' => $path,
                'file_type' => $this->obtl_file->getClientOriginalExtension(),
                'file_size' => $this->obtl_file->getSize(),
                'uploaded_at' => now(),
            ]);
        }

        $this->reset(['course_code', 'course_title', 'description', 'obtl_file']);
        $this->showCreateModal = false;
        $this->loadCourses();
        
        session()->flash('message', 'Course created successfully!');
    }

    public function deleteCourse($courseId)
    {
        $course = Course::findOrFail($courseId);
        $this->authorize('delete', $course);
        
        $course->delete();
        $this->loadCourses();
        
        session()->flash('message', 'Course deleted successfully!');
    }

    public function render()
    {
        return view('livewire.student-courses')
            ->layout('layouts.app');
    }
}
```

#### 2. `resources/views/livewire/student-courses.blade.php`
```blade
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-900">My Courses</h1>
        <button wire:click="$set('showCreateModal', true)" 
                class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700">
            + Add Course
        </button>
    </div>

    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('message') }}
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($courses as $course)
            <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h3 class="text-xl font-semibold text-gray-900">{{ $course->course_code }}</h3>
                        <p class="text-gray-600">{{ $course->course_title }}</p>
                    </div>
                    <button wire:click="deleteCourse({{ $course->id }})" 
                            wire:confirm="Are you sure you want to delete this course?"
                            class="text-red-500 hover:text-red-700">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                </div>

                <div class="space-y-2 mb-4">
                    @if($course->obtlDocument)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            OBTL Uploaded
                        </span>
                    @endif
                    <p class="text-sm text-gray-500">{{ $course->documents_count }} lectures</p>
                </div>

                <a href="{{ route('student.course.show', $course->id) }}" 
                   class="block w-full text-center bg-indigo-600 text-white py-2 rounded-lg hover:bg-indigo-700">
                    View Course
                </a>
            </div>
        @empty
            <div class="col-span-full text-center py-12">
                <p class="text-gray-500">No courses yet. Create your first course to get started!</p>
            </div>
        @endforelse
    </div>

    <!-- Create Course Modal -->
    @if($showCreateModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium">Create New Course</h3>
                    <button wire:click="$set('showCreateModal', false)" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                </div>

                <form wire:submit.prevent="createCourse" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Course Code*</label>
                        <input type="text" wire:model="course_code" 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @error('course_code') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Course Title*</label>
                        <input type="text" wire:model="course_title" 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @error('course_title') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea wire:model="description" rows="3"
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">OBTL Document (Optional)</label>
                        <input type="file" wire:model="obtl_file" accept=".pdf"
                               class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                        @error('obtl_file') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex justify-end space-x-3">
                        <button type="button" wire:click="$set('showCreateModal', false)" 
                                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                            Create Course
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
```

#### 3. Add Route
```php
// routes/web.php
Route::middleware(['auth', 'student'])->prefix('student')->group(function () {
    Route::get('/dashboard', StudentDashboard::class)->name('student.dashboard');
    Route::get('/courses', StudentCourses::class)->name('student.courses'); // NEW
});
```

**Testing:**
- Create a course with OBTL
- Create a course without OBTL
- View course list
- Delete a course

**Estimated Time:** 8 hours

---

### Day 4-5: Course Detail & Lecture Upload

**Objective:** View course details and upload lecture documents

**Files to Create:**

#### 1. `app/Livewire/CourseDetail.php`
```php
<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Course;
use App\Models\Document;
use App\Jobs\ProcessDocumentJob;

class CourseDetail extends Component
{
    use WithFileUploads;

    public Course $course;
    public $showUploadModal = false;
    
    // Upload form
    public $lecture_title;
    public $lecture_number;
    public $hours_taught;
    public $lecture_file;
    
    public function mount(Course $course)
    {
        $this->authorize('view', $course);
        $this->course = $course;
    }

    public function uploadLecture()
    {
        $this->validate([
            'lecture_title' => 'required|string|max:255',
            'lecture_file' => 'required|file|mimes:pdf|max:20480',
            'lecture_number' => 'nullable|string|max:50',
            'hours_taught' => $this->course->hasObtl() ? 'nullable' : 'required|numeric|min:0',
        ]);

        $path = $this->lecture_file->store('documents', 'private');

        $document = Document::create([
            'course_id' => $this->course->id,
            'user_id' => auth()->id(),
            'title' => $this->lecture_title,
            'file_path' => $path,
            'file_type' => $this->lecture_file->getClientOriginalExtension(),
            'file_size' => $this->lecture_file->getSize(),
            'uploaded_at' => now(),
        ]);

        // Dispatch processing job
        ProcessDocumentJob::dispatch($document->id, [
            'lecture_number' => $this->lecture_number,
            'hours_taught' => $this->hours_taught,
            'has_obtl' => $this->course->hasObtl(),
        ]);

        $this->reset(['lecture_title', 'lecture_number', 'hours_taught', 'lecture_file']);
        $this->showUploadModal = false;
        $this->course->refresh();
        
        session()->flash('message', 'Lecture uploaded! Processing will begin shortly.');
    }

    public function render()
    {
        return view('livewire.course-detail', [
            'documents' => $this->course->documents()
                ->with(['topics.subtopics', 'tableOfSpecification'])
                ->latest()
                ->get()
        ])->layout('layouts.app');
    }
}
```

#### 2. `resources/views/livewire/course-detail.blade.php`
```blade
<div class="container mx-auto px-4 py-8">
    <div class="mb-6">
        <a href="{{ route('student.courses') }}" class="text-indigo-600 hover:text-indigo-800">
            ← Back to Courses
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">{{ $course->course_code }}</h1>
                <p class="text-xl text-gray-600 mt-2">{{ $course->course_title }}</p>
                @if($course->description)
                    <p class="text-gray-500 mt-2">{{ $course->description }}</p>
                @endif
            </div>
            <button wire:click="$set('showUploadModal', true)" 
                    class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700">
                + Upload Lecture
            </button>
        </div>

        <div class="mt-4 flex items-center space-x-4">
            @if($course->obtlDocument)
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                    ✓ OBTL Uploaded
                </span>
            @else
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                    Manual Hours Required
                </span>
            @endif
            <span class="text-gray-500">{{ $course->documents->count() }} lectures</span>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('message') }}
        </div>
    @endif

    <div class="space-y-4">
        @forelse($documents as $document)
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <h3 class="text-xl font-semibold text-gray-900">{{ $document->title }}</h3>
                        <p class="text-sm text-gray-500 mt-1">
                            Uploaded {{ $document->uploaded_at->diffForHumans() }} • {{ $document->formatted_file_size }}
                        </p>
                    </div>
                    
                    @if($document->hasTos())
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            ✓ Processed
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                            Processing...
                        </span>
                    @endif
                </div>

                @if($document->hasTos() && $document->topics->isNotEmpty())
                    <div class="mt-4">
                        <h4 class="font-medium text-gray-700 mb-2">Subtopics:</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                            @foreach($document->topics as $topic)
                                @foreach($topic->subtopics as $subtopic)
                                    <a href="{{ route('student.quiz.take', $subtopic->id) }}" 
                                       class="flex justify-between items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100">
                                        <span class="text-sm text-gray-700">{{ $subtopic->name }}</span>
                                        <span class="text-xs text-gray-500">{{ $subtopic->items()->count() }} questions</span>
                                    </a>
                                @endforeach
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @empty
            <div class="text-center py-12 bg-white rounded-lg shadow">
                <p class="text-gray-500">No lectures uploaded yet. Upload your first lecture to generate quizzes!</p>
            </div>
        @endforelse
    </div>

    <!-- Upload Modal -->
    @if($showUploadModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium">Upload Lecture</h3>
                    <button wire:click="$set('showUploadModal', false)" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                </div>

                <form wire:submit.prevent="uploadLecture" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Lecture Title*</label>
                        <input type="text" wire:model="lecture_title" 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        @error('lecture_title') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Lecture Number</label>
                        <input type="text" wire:model="lecture_number" 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>

                    @unless($course->hasObtl())
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Hours Taught*</label>
                            <input type="number" step="0.5" wire:model="hours_taught" 
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            @error('hours_taught') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                    @endunless

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Lecture File (PDF)*</label>
                        <input type="file" wire:model="lecture_file" accept=".pdf"
                               class="mt-1 block w-full text-sm text-gray-500">
                        @error('lecture_file') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex justify-end space-x-3">
                        <button type="button" wire:click="$set('showUploadModal', false)" 
                                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                            Upload
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
```

#### 3. Add Route
```php
// routes/web.php
Route::get('/course/{course}', CourseDetail::class)->name('student.course.show');
```

**Testing:**
- Upload lecture with OBTL course
- Upload lecture without OBTL (requires hours)
- Verify processing job dispatches
- Check subtopics appear after processing

**Estimated Time:** 8 hours

---

## Week 2: Quiz Taking Interface (CRITICAL)

### Day 1-3: Quiz Taking Component

**Objective:** Build the core quiz taking interface with timer

**Files to Create:**

#### 1. `app/Livewire/TakeQuiz.php`
```php
<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Subtopic;
use App\Models\QuizAttempt;
use App\Models\Response;
use App\Models\ItemBank;
use App\Services\IrtService;
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

    protected $irtService;

    public function boot(IrtService $irtService)
    {
        $this->irtService = $irtService;
    }

    public function mount(Subtopic $subtopic)
    {
        $this->subtopic = $subtopic->load('topic.document.course');
    }

    public function startQuiz()
    {
        // Check if adaptive quiz should be generated
        $isAdaptive = $this->subtopic->hasCompletedAllInitialQuizzes(auth()->id());
        
        // Get questions
        if ($isAdaptive) {
            $studentAbility = \App\Models\StudentAbility::firstOrCreate(
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
            'total_questions' => $this->questions->count(),
            'started_at' => now(),
            'is_adaptive' => $isAdaptive,
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
        $studentAbility = \App\Models\StudentAbility::firstOrCreate(
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

        $studentAbility->updateTheta($newTheta);

        // Dispatch feedback generation
        \App\Jobs\GenerateFeedbackJob::dispatch($this->attempt->id);

        $this->quizCompleted = true;
    }

    public function resetTimer()
    {
        $this->timeRemaining = 60;
        $this->dispatch('resetTimer');
    }

    public function render()
    {
        return view('livewire.take-quiz')->layout('layouts.app');
    }
}
```

#### 2. `resources/views/livewire/take-quiz.blade.php`
```blade
<div class="container mx-auto px-4 py-8" x-data="{ 
    timeRemaining: @entangle('timeRemaining'),
    timerInterval: null,
    startTimer() {
        this.timerInterval = setInterval(() => {
            if (this.timeRemaining > 0) {
                this.timeRemaining--;
            } else {
                clearInterval(this.timerInterval);
                @this.call('submitAnswer');
            }
        }, 1000);
    },
    stopTimer() {
        clearInterval(this.timerInterval);
    },
    getTimerColor() {
        if (this.timeRemaining > 30) return 'bg-green-500';
        if (this.timeRemaining > 10) return 'bg-yellow-500';
        return 'bg-red-500';
    }
}" x-init="$watch('timeRemaining', value => {
    if (value === 60 && $wire.quizStarted && !$wire.showFeedback) {
        stopTimer();
        startTimer();
    }
})">

    @if(!$quizStarted)
        <!-- Quiz Start Screen -->
        <div class="max-w-2xl mx-auto">
            <div class="bg-white rounded-lg shadow-lg p-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-4">{{ $subtopic->name }}</h1>
                <p class="text-gray-600 mb-6">{{ $subtopic->topic->name }}</p>
                
                <div class="bg-indigo-50 rounded-lg p-6 mb-6">
                    <h2 class="font-semibold text-indigo-900 mb-3">Quiz Information:</h2>
                    <ul class="space-y-2 text-indigo-800">
                        <li>• 20 multiple-choice questions</li>
                        <li>• 60 seconds per question</li>
                        <li>• Timer will change color: Green → Yellow → Red</li>
                        <li>• Immediate feedback after each answer</li>
                    </ul>
                </div>

                <button wire:click="startQuiz" 
                        class="w-full bg-indigo-600 text-white py-3 rounded-lg text-lg font-semibold hover:bg-indigo-700">
                    Start Quiz
                </button>
            </div>
        </div>

    @elseif($quizCompleted)
        <!-- Quiz Completed Screen -->
        <div class="max-w-2xl mx-auto">
            <div class="bg-white rounded-lg shadow-lg p-8 text-center">
                <div class="mb-6">
                    @if($attempt->score_percentage >= 70)
                        <svg class="w-20 h-20 mx-auto text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <h2 class="text-2xl font-bold text-green-600 mt-4">Great Job!</h2>
                    @else
                        <svg class="w-20 h-20 mx-auto text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        <h2 class="text-2xl font-bold text-yellow-600 mt-4">Keep Practicing!</h2>
                    @endif
                </div>

                <div class="text-center mb-8">
                    <p class="text-5xl font-bold text-gray-900">{{ $attempt->score_percentage }}%</p>
                    <p class="text-gray-600 mt-2">{{ $attempt->correct_answers }} out of {{ $attempt->total_questions }} correct</p>
                </div>

                <div class="flex justify-center space-x-4">
                    <a href="{{ route('student.course.show', $subtopic->topic->document->course_id) }}" 
                       class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                        Back to Course
                    </a>
                    <a href="{{ route('student.quiz.result', $attempt->id) }}" 
                       class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                        View Detailed Results
                    </a>
                </div>
            </div>
        </div>

    @else
        <!-- Quiz Question Screen -->
        <div class="max-w-3xl mx-auto">
            <!-- Timer Bar -->
            <div class="mb-4">
                <div class="flex justify-between text-sm text-gray-600 mb-2">
                    <span>Question {{ $currentQuestionIndex + 1 }} of {{ $questions->count() }}</span>
                    <span x-text="timeRemaining + 's'"></span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="h-2 rounded-full transition-all duration-1000" 
                         :class="getTimerColor()"
                         :style="`width: ${(timeRemaining / 60) * 100}%`"></div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-lg p-8">
                @if($questions->count() > 0)
                    @php $question = $questions[$currentQuestionIndex]; @endphp
                    
                    <h2 class="text-xl font-semibold text-gray-900 mb-6">{{ $question->question }}</h2>

                    @if(!$showFeedback)
                        <div class="space-y-3">
                            @foreach(json_decode($question->options, true) as $option)
                                <button wire:click="$set('selectedAnswer', '{{ $option['option_letter'] }}')" 
                                        class="w-full text-left p-4 rounded-lg border-2 transition
                                               {{ $selectedAnswer === $option['option_letter'] ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-gray-300' }}">
                                    <span class="font-semibold">{{ $option['option_letter'] }}.</span>
                                    {{ $option['option_text'] }}
                                </button>
                            @endforeach
                        </div>

                        <button wire:click="submitAnswer" 
                                :disabled="!$wire.selectedAnswer"
                                class="mt-6 w-full bg-indigo-600 text-white py-3 rounded-lg font-semibold hover:bg-indigo-700 disabled:bg-gray-300 disabled:cursor-not-allowed">
                            Submit Answer
                        </button>
                    @else
                        <!-- Feedback -->
                        <div class="mb-6 p-4 rounded-lg {{ $isCorrect ? 'bg-green-50 border-2 border-green-200' : 'bg-red-50 border-2 border-red-200' }}">
                            <p class="font-semibold {{ $isCorrect ? 'text-green-800' : 'text-red-800' }}">
                                {{ $isCorrect ? '✓ Correct!' : '✗ Incorrect' }}
                            </p>
                            @if(!$isCorrect)
                                <p class="text-red-700 mt-2">The correct answer is: <strong>{{ $correctAnswer }}</strong></p>
                            @endif
                            @if($question->explanation)
                                <p class="text-gray-700 mt-3"><strong>Explanation:</strong> {{ $question->explanation }}</p>
                            @endif
                        </div>

                        <button wire:click="nextQuestion" x-on:click="stopTimer()"
                                class="w-full bg-indigo-600 text-white py-3 rounded-lg font-semibold hover:bg-indigo-700">
                            {{ $currentQuestionIndex + 1 < $questions->count() ? 'Next Question' : 'Complete Quiz' }}
                        </button>
                    @endif
                @endif
            </div>
        </div>
    @endif
</div>
```

#### 3. Add Route and Helper Method
```php
// routes/web.php
Route::get('/quiz/{subtopic}', TakeQuiz::class)->name('student.quiz.take');

// Add to Subtopic model (app/Models/Subtopic.php)
public function hasCompletedAllInitialQuizzes(int $userId): bool
{
    $completedCount = QuizAttempt::where('user_id', $userId)
        ->where('subtopic_id', $this->id)
        ->where('is_adaptive', false)
        ->whereNotNull('completed_at')
        ->count();
    
    return $completedCount > 0;
}
```

**Testing:**
- Start quiz and verify timer counts down
- Answer questions and check immediate feedback
- Verify timer color changes (green→yellow→red)
- Complete quiz and check score calculation
- Verify ability estimation runs

**Estimated Time:** 16 hours

---

### Day 4-5: Quiz Results & Feedback Display

**Files to Create:**

#### `app/Livewire/QuizResult.php`
```php
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
        return view('livewire.quiz-result')->layout('layouts.app');
    }
}
```

**Estimated Time:** 8 hours

---

## Week 3: Advanced Features

### Day 1-2: Quiz Regeneration

**Files to Create:**

#### `app/Http/Controllers/QuizRegenerationController.php`
```php
<?php

namespace App\Http\Controllers;

use App\Models\Subtopic;
use App\Models\QuizRegeneration;
use App\Services\OpenAiService;
use Illuminate\Http\JsonResponse;

class QuizRegenerationController extends Controller
{
    protected OpenAiService $openAiService;

    public function __construct(OpenAiService $openAiService)
    {
        $this->openAiService = $openAiService;
    }

    public function regenerate(Subtopic $subtopic): JsonResponse
    {
        $userId = auth()->id();
        
        // Check if initial quizzes completed
        if (!$subtopic->hasCompletedAllInitialQuizzes($userId)) {
            return response()->json([
                'message' => 'Complete initial quizzes first'
            ], 400);
        }

        // Check regeneration count
        $regenCount = QuizRegeneration::where('subtopic_id', $subtopic->id)
            ->where('user_id', $userId)
            ->max('regeneration_count') ?? 0;

        if ($regenCount >= 3) {
            return response()->json([
                'message' => 'Maximum regenerations reached'
            ], 400);
        }

        // Regenerate questions
        $originalItems = $subtopic->items;
        $regeneratedItems = [];

        foreach ($originalItems as $item) {
            $reworded = $this->openAiService->rewordQuestion(
                $item->question,
                json_decode($item->options, true),
                $regenCount + 1
            );

            $regeneratedItem = $item->replicate();
            $regeneratedItem->question = $reworded['question'];
            $regeneratedItem->options = json_encode($reworded['options']);
            $regeneratedItem->save();

            QuizRegeneration::create([
                'original_item_id' => $item->id,
                'regenerated_item_id' => $regeneratedItem->id,
                'subtopic_id' => $subtopic->id,
                'user_id' => $userId,
                'regeneration_count' => $regenCount + 1,
                'regenerated_at' => now(),
            ]);

            $regeneratedItems[] = $regeneratedItem;
        }

        return response()->json([
            'message' => 'Quiz regenerated successfully',
            'regeneration_count' => $regenCount + 1,
            'items_count' => count($regeneratedItems)
        ], 201);
    }
}
```

**Add to routes/web.php:**
```php
Route::post('/quiz/{subtopic}/regenerate', [QuizRegenerationController::class, 'regenerate'])
    ->name('student.quiz.regenerate');
```

**Estimated Time:** 8 hours

---

### Day 3-5: Testing & Bug Fixes

**Tasks:**
- End-to-end testing of complete flow
- Fix any timer issues
- Verify IRT calculations
- Test regeneration logic
- Polish UI/UX

**Estimated Time:** 16 hours

---

## Week 4: Polish & Deployment

### Tasks:
1. Add ToS viewer component
2. Implement quiz history
3. Mobile responsive testing
4. Performance optimization
5. Security audit
6. User acceptance testing
7. Bug fixes
8. Documentation

**Estimated Time:** 40 hours

---

## Testing Checklist

### User Flow Testing:
- [ ] Student can register and login
- [ ] Student can create course with/without OBTL
- [ ] Student can upload lecture PDF
- [ ] Document processing completes successfully
- [ ] ToS generates correctly
- [ ] Questions are created per subtopic
- [ ] Student can start quiz
- [ ] Timer functions correctly (60s per question)
- [ ] Timer color changes appropriately
- [ ] Answers can be submitted
- [ ] Immediate feedback displays
- [ ] Quiz completes and shows score
- [ ] Ability (θ) is calculated
- [ ] Feedback generates (async)
- [ ] Dashboard shows real data
- [ ] Regeneration works (max 3x)
- [ ] Adaptive quiz triggers after all subtopics done

---

## Critical Success Factors

1. **Timer Reliability** - Must count down accurately, change colors, auto-submit
2. **IRT Accuracy** - Ability estimation must work correctly
3. **AI Integration** - Question generation, reword, feedback must be reliable
4. **Performance** - Quiz interface must be responsive
5. **Data Integrity** - Scores and progress must be accurate

---

## Next Steps

1. **Start with Week 1, Day 1** - Fix dashboard mock data
2. **Create GitHub Issues** for each major component
3. **Set up Development Environment** with proper testing
4. **Follow this plan sequentially** - Don't skip ahead

---

## Support Resources

- [Livewire Documentation](https://livewire.laravel.com/docs)
- [Alpine.js for Timer](https://alpinejs.dev/)
- [Tailwind CSS](https://tailwindcss.com/)
- Laravel Queue for background jobs
- Existing backend controllers and services

---

**Remember:** The backend is 90% complete. Focus on connecting it through student-facing UI components.