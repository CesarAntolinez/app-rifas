<?php

namespace App\Services;

use App\Jobs\SendRaffleNotification;
use App\Models\NotificationLog;
use App\Models\Participant;
use App\Models\Raffle;
use App\Models\User;

class NotificationService
{
    public function notifyOrganizerWelcome(User $organizer, string $temporaryPassword): void
    {
        dispatch(new SendRaffleNotification('welcome_organizer', [
            'user_id' => $organizer->id,
            'name' => $organizer->name,
            'email' => $organizer->email,
            'temporary_password' => $temporaryPassword,
        ]));
    }

    public function notifyParticipationConfirmed(Participant $participant, Raffle $raffle): void
    {
        dispatch(new SendRaffleNotification('participation_confirmed', [
            'participant_id' => $participant->id,
            'participant_name' => $participant->name,
            'participant_email' => $participant->email,
            'raffle_id' => $raffle->id,
            'raffle_name' => $raffle->name,
        ]));
    }

    public function notifyRaffleResults(Raffle $raffle): void
    {
        $raffle->load(['participants', 'results.ticket.participant', 'results.prize']);
        $winnerParticipantIds = $raffle->results
            ->pluck('ticket.participant_id')
            ->unique()
            ->toArray();

        foreach ($raffle->participants as $participant) {
            $isWinner = in_array($participant->id, $winnerParticipantIds);
            $type = $isWinner ? 'raffle_winner' : 'raffle_not_winner';

            dispatch(new SendRaffleNotification($type, [
                'participant_id' => $participant->id,
                'participant_name' => $participant->name,
                'participant_email' => $participant->email,
                'raffle_id' => $raffle->id,
                'raffle_name' => $raffle->name,
            ]));
        }
    }

    public function notifyRaffleReminder(Raffle $raffle): void
    {
        foreach ($raffle->participants as $participant) {
            dispatch(new SendRaffleNotification('raffle_reminder', [
                'participant_id' => $participant->id,
                'participant_name' => $participant->name,
                'participant_email' => $participant->email,
                'raffle_id' => $raffle->id,
                'raffle_name' => $raffle->name,
            ]));
        }
    }

    public function logNotification(string $type, string $email, string $status, ?int $raffleId = null): void
    {
        NotificationLog::create([
            'raffle_id' => $raffleId,
            'recipient_email' => $email,
            'notification_type' => $type,
            'status' => $status,
            'attempts' => 1,
            'sent_at' => $status === 'sent' ? now() : null,
        ]);
    }
}
