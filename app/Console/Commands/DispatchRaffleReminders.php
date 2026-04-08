<?php

namespace App\Console\Commands;

use App\Jobs\SendRaffleReminder;
use App\Models\Raffle;
use Illuminate\Console\Command;

class DispatchRaffleReminders extends Command
{
    protected $signature = 'raffles:dispatch-reminders';

    protected $description = 'Dispatch reminder notifications for raffles closing within 24 hours';

    public function handle(): int
    {
        $raffles = Raffle::where('status', Raffle::STATUS_ACTIVE)
            ->whereBetween('ends_at', [now(), now()->addHours(24)])
            ->get();

        foreach ($raffles as $raffle) {
            SendRaffleReminder::dispatch($raffle);
            $this->info("Dispatched reminder for raffle: {$raffle->name} (ID: {$raffle->id})");
        }

        $this->info("Total: {$raffles->count()} reminder(s) dispatched.");

        return self::SUCCESS;
    }
}
