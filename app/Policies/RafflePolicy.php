<?php

namespace App\Policies;

use App\Models\Raffle;
use App\Models\User;

class RafflePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Raffle $raffle): bool
    {
        return $user->isAdmin() || $raffle->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isOrganizer() || $user->isAdmin();
    }

    public function update(User $user, Raffle $raffle): bool
    {
        $canEdit = $user->isAdmin() || $raffle->user_id === $user->id;
        $editableStatus = in_array($raffle->status, [Raffle::STATUS_DRAFT, Raffle::STATUS_ACTIVE]);

        return $canEdit && $editableStatus;
    }

    public function delete(User $user, Raffle $raffle): bool
    {
        $canDelete = $user->isAdmin() || $raffle->user_id === $user->id;

        return $canDelete && $raffle->status === Raffle::STATUS_DRAFT;
    }

    public function execute(User $user, Raffle $raffle): bool
    {
        $canExecute = $user->isAdmin() || $raffle->user_id === $user->id;

        return $canExecute && $raffle->status === Raffle::STATUS_CLOSED;
    }

    public function publish(User $user, Raffle $raffle): bool
    {
        $canPublish = $user->isAdmin() || $raffle->user_id === $user->id;

        return $canPublish && $raffle->status === Raffle::STATUS_DRAFT;
    }

    public function close(User $user, Raffle $raffle): bool
    {
        $canClose = $user->isAdmin() || $raffle->user_id === $user->id;

        return $canClose && $raffle->status === Raffle::STATUS_ACTIVE;
    }
}
