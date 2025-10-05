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
        return $user->id === $document->user_id;
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