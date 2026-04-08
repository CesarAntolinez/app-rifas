<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Raffle;
use App\Models\User;

class RaffleService
{
    public function createRaffle(array $data, User $owner): Raffle
    {
        $raffle = Raffle::create(array_merge($data, [
            'user_id' => $owner->id,
            'status' => Raffle::STATUS_DRAFT,
        ]));

        $this->logActivity($owner, 'raffle.created', Raffle::class, $raffle->id, ['name' => $raffle->name]);

        return $raffle;
    }

    public function updateRaffle(Raffle $raffle, array $data): Raffle
    {
        $raffle->update($data);

        $user = auth()->user();
        if ($user) {
            $this->logActivity($user, 'raffle.updated', Raffle::class, $raffle->id, ['name' => $raffle->name]);
        }

        return $raffle->fresh();
    }

    public function publishRaffle(Raffle $raffle, User $user): void
    {
        $raffle->update(['status' => Raffle::STATUS_ACTIVE]);
        $this->logActivity($user, 'raffle.published', Raffle::class, $raffle->id, ['name' => $raffle->name]);
    }

    public function closeRaffle(Raffle $raffle, User $user): void
    {
        $raffle->update(['status' => Raffle::STATUS_CLOSED]);
        $this->logActivity($user, 'raffle.closed', Raffle::class, $raffle->id, ['name' => $raffle->name]);
    }

    public function deleteRaffle(Raffle $raffle, User $user): void
    {
        $raffle->update(['status' => Raffle::STATUS_DELETED]);
        $this->logActivity($user, 'raffle.deleted', Raffle::class, $raffle->id, ['name' => $raffle->name]);
    }

    private function logActivity(User $user, string $action, string $subjectType, int $subjectId, array $payload = []): void
    {
        ActivityLog::create([
            'user_id' => $user->id,
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'payload' => $payload,
            'ip_address' => request()->ip(),
        ]);
    }
}
