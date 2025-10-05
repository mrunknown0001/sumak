<?php

namespace App\Policies;

namespace App\Policies;

use App\Models\User;
use App\Models\QuizAttempt;
use Illuminate\Auth\Access\HandlesAuthorization;

class QuizAttemptPolicy
{
    use HandlesAuthorization;

    public function view(User $user, QuizAttempt $attempt): bool
    {
        return $user->id === $attempt->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, QuizAttempt $attempt): bool
    {
        return $user->id === $attempt->user_id;
    }
}
