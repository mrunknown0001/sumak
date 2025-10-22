<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Document;
use Illuminate\Auth\Access\HandlesAuthorization;

class DocumentPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Document $document): bool
    {
        if ($user->id === $document->user_id) {
            return true;
        }

        $course = $document->course;

        if ($course) {
            if ($course->user_id === $user->id) {
                return true;
            }

            if ($course->isEnrolledBy($user->id)) {
                return true;
            }
        }

        return in_array($user->role, ['admin', 'superadmin'], true);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Document $document): bool
    {
        return $user->id === $document->user_id;
    }

    public function delete(User $user, Document $document): bool
    {
        return $user->id === $document->user_id;
    }
}