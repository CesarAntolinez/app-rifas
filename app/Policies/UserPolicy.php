<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, User $model): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isOrganizer() && $user->id === $model->id;
    }

    public function toggleStatus(User $user, User $model): bool
    {
        return $user->isAdmin();
    }
}
