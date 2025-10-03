<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Services\Examples\OpenAiServiceExamples;
use App\Services\OpenAiService;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test-ai', function (){
    // call instance of service
    $ai = new OpenAiService();
    $instance = new OpenAiServiceExamples($ai);
    $instance->analyzeContentExample();
    return "done";
});

Route::post('/login', [LoginController::class, 'login'])->name('login.submit');

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');

Route::group(['middleware' => ['auth']], function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/logout', [LoginController::class, 'logout'])->name('logout.get');
});
