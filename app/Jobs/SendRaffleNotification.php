<?php

namespace App\Jobs;

use App\Mail\ParticipationConfirmed;
use App\Mail\RaffleNotWinner;
use App\Mail\RaffleReminder;
use App\Mail\RaffleWinner;
use App\Mail\WelcomeOrganizer;
use App\Models\Participant;
use App\Models\Raffle;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendRaffleNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly string $type,
        public readonly array $data,
    ) {}

    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function handle(NotificationService $notificationService): void
    {
        try {
            $mailable = $this->buildMailable();

            if ($mailable === null) {
                return;
            }

            $recipientEmail = $this->resolveRecipientEmail();
            Mail::to($recipientEmail)->send($mailable);

            $notificationService->logNotification(
                $this->type,
                $recipientEmail,
                'sent',
                $this->data['raffle_id'] ?? null,
            );
        } catch (\Throwable $e) {
            $notificationService->logNotification(
                $this->type,
                $this->resolveRecipientEmail(),
                'failed',
                $this->data['raffle_id'] ?? null,
            );

            throw $e;
        }
    }

    private function buildMailable(): mixed
    {
        return match ($this->type) {
            'welcome_organizer' => new WelcomeOrganizer(
                User::findOrFail($this->data['user_id']),
                $this->data['temporary_password'],
            ),
            'participation_confirmed' => new ParticipationConfirmed(
                Participant::findOrFail($this->data['participant_id']),
                Raffle::findOrFail($this->data['raffle_id']),
            ),
            'raffle_winner' => new RaffleWinner(
                Raffle::findOrFail($this->data['raffle_id']),
                $this->data['participant_name'],
                $this->data['prizes'] ?? [],
            ),
            'raffle_not_winner' => new RaffleNotWinner(
                Raffle::findOrFail($this->data['raffle_id']),
                $this->data['participant_name'],
            ),
            'raffle_reminder' => new RaffleReminder(
                Raffle::findOrFail($this->data['raffle_id']),
                $this->data['participant_name'],
            ),
            'raffle_results' => null, // Handled by NotificationService::notifyRaffleResults
            default => null,
        };
    }

    private function resolveRecipientEmail(): string
    {
        return $this->data['participant_email']
            ?? $this->data['email']
            ?? 'noreply@example.com';
    }
}
