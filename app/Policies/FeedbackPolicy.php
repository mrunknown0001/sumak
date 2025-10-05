<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Feedback;
use Illuminate\Auth\Access\HandlesAuthorization;

class FeedbackPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Feedback $feedback): bool
    {
        return $user->id === $feedback->user_id;
    }
}