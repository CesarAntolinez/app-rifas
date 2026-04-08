<?php

namespace App\Policies;

use App\Models\Participant;
use App\Models\Raffle;
use App\Models\User;

class ParticipantPolicy
{
    public function viewAny(User $user, Raffle $raffle): bool
    {
        return $user->isAdmin() || $raffle->user_id === $user->id;
    }

    public function create(User $user, Raffle $raffle): bool
    {
        return $user->isAdmin() || $raffle->user_id === $user->id;
    }

    public function delete(User $user, Participant $participant, Raffle $raffle): bool
    {
        return $user->isAdmin() || $raffle->user_id === $user->id;
    }
}
