<?php

namespace App\Jobs;

use App\Models\Raffle;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendRaffleReminder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly Raffle $raffle,
    ) {}

    public function handle(NotificationService $notificationService): void
    {
        $this->raffle->load('participants');
        $notificationService->notifyRaffleReminder($this->raffle);
    }
}
