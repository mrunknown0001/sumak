<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\QuizRegenerationController;
use App\Http\Controllers\EnrollmentController;
use App\Services\Examples\OpenAiServiceExamples;
use App\Services\OpenAiService;
use App\Livewire\StudentDashboard;
use App\Livewire\StudentCourses;
use App\Livewire\CourseDetail;
use App\Livewire\TakeQuiz;
use App\Livewire\QuizResult;

Route::get('/', function () {
    return view('home');
});

Route::post('/login', [LoginController::class, 'login'])->name('login.submit');

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');

Route::group(['middleware' => ['auth']], function () {
    Route::get('/dashboard', function () {
        if (auth()->user()->role === 'student') {
            return redirect()->route('student.dashboard');
        }
        else {
            return redirect('/admin');
        }
    })->name('dashboard');

    Route::get('/logout', [LoginController::class, 'logout'])->name('logout.get');
});


Route::middleware(['auth', 'student'])->prefix('student')->name('student.')->group(function () {
    
    // Dashboard
    Route::get('/dashboard', StudentDashboard::class)->name('dashboard');
    
    // Courses & Enrollment
    Route::get('/courses', StudentCourses::class)->name('courses');
    Route::post('/course/{course}/enroll', [EnrollmentController::class, 'enroll'])->name('course.enroll');
    Route::post('/course/{course}/unenroll', [EnrollmentController::class, 'unenroll'])->name('course.unenroll');
    Route::get('/course/{course}', CourseDetail::class)->name('course.show');
    
    // Quiz
    Route::get('/quiz/{subtopic}', TakeQuiz::class)->name('quiz.take');
    Route::get('/quiz/{attempt}/result', QuizResult::class)->name('quiz.result');
    Route::post('/quiz/{subtopic}/regenerate', [QuizRegenerationController::class, 'regenerate'])->name('quiz.regenerate');
});