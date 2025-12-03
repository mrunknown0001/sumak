<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\QuizRegenerationController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\DocumentController;
use App\Services\Examples\OpenAiServiceExamples;
use App\Services\OpenAiService;
use App\Livewire\StudentDashboard;
use App\Livewire\StudentCourses;
use App\Livewire\CourseDetail;
use App\Livewire\QuizLearningContext;
use App\Livewire\TakeQuiz;
use App\Livewire\QuizResult;
use Illuminate\Support\Facades\Password;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;

Route::get('/', function () {
    return view('home');
});

Route::get('/terms-and-conditions', function () {
    return view('terms');
})->name('terms');

Route::get('/privacy-policy', function () {
    return view('privacy-policy');
})->name('privacy.policy');

Route::post('/login', [LoginController::class, 'login'])->name('login.submit');

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');

Route::group(['middleware' => ['auth','verified']], function () {
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


Route::middleware(['auth', 'student', 'verified'])->prefix('student')->name('student.')->group(function () {
    
    // Dashboard
    Route::get('/dashboard', StudentDashboard::class)->name('dashboard');
    
    // Courses & Enrollment
    Route::get('/courses', StudentCourses::class)->name('courses');
    Route::post('/course/{course}/enroll', [EnrollmentController::class, 'enroll'])->name('course.enroll');
    Route::post('/course/{course}/unenroll', [EnrollmentController::class, 'unenroll'])->name('course.unenroll');
    Route::get('/course/{course}', CourseDetail::class)->name('course.show');
    
    // Learning materials
    Route::get('/document/{document}/download', [DocumentController::class, 'download'])
        ->name('document.download');
    Route::get('/document/{document}/preview', [DocumentController::class, 'preview'])
        ->name('document.preview');

    // Quiz
    Route::get('/quiz/{topic}/context', QuizLearningContext::class)->name('quiz.context');
    Route::get('/quiz/{topic}', TakeQuiz::class)->name('quiz.take');
    Route::get('/quiz/{attempt}/result', QuizResult::class)->name('quiz.result');
    Route::post('/quiz/{subtopic}/regenerate', [QuizRegenerationController::class, 'regenerate'])->name('quiz.regenerate');
});


// Show password reset request form
Route::get('/forgot-password', function () {
    return view('auth.forgot-password');
})->middleware('guest')->name('password.request');

// Handle password reset link request
Route::post('/forgot-password', function (Request $request) {
    $request->validate(['email' => 'required|email']);

    $status = Password::sendResetLink(
        $request->only('email')
    );

    return $status === Password::RESET_LINK_SENT
        ? back()->with(['status' => __($status)])
        : back()->withErrors(['email' => __($status)]);
})->middleware('guest')->name('password.email');

// Show password reset form
Route::get('/reset-password/{token}', function (string $token) {
    return view('auth.reset-password', ['token' => $token]);
})->middleware('guest')->name('password.reset');

// Handle password reset
Route::post('/reset-password', function (Request $request) {
    $request->validate([
        'token' => 'required',
        'email' => 'required|email',
        'password' => 'required|min:8|confirmed',
    ]);

    $status = Password::reset(
        $request->only('email', 'password', 'password_confirmation', 'token'),
        function ($user, $password) {
            $user->forceFill([
                'password' => Hash::make($password)
            ])->setRememberToken(Str::random(60));

            $user->save();

            event(new PasswordReset($user));
        }
    );

    return $status === Password::PASSWORD_RESET
        ? redirect()->route('login')->with('status', __($status))
        : back()->withErrors(['email' => [__($status)]]);
})->middleware('guest')->name('password.update');