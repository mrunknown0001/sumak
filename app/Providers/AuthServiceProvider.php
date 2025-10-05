<?php

namespace App\Providers;

use App\Models\Course;
use App\Models\Document;
use App\Models\QuizAttempt;
use App\Models\Feedback;
use App\Policies\CoursePolicy;
use App\Policies\DocumentPolicy;
use App\Policies\QuizAttemptPolicy;
use App\Policies\FeedbackPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Course::class => CoursePolicy::class,
        Document::class => DocumentPolicy::class,
        QuizAttempt::class => QuizAttemptPolicy::class,
        Feedback::class => FeedbackPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}