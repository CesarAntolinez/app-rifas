<?php

namespace App\Services;

use App\Jobs\SendRaffleNotification;
use App\Models\Raffle;
use App\Models\RaffleAuditLog;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DrawExecutionService
{
    public function execute(Raffle $raffle, User $executedBy): RaffleAuditLog
    {
        if ($raffle->status !== Raffle::STATUS_CLOSED) {
            throw new RuntimeException('El sorteo debe estar cerrado para ejecutarse.');
        }

        if ($raffle->participants()->count() === 0) {
            throw new RuntimeException('El sorteo debe tener al menos un participante.');
        }

        $auditLog = DB::transaction(function () use ($raffle, $executedBy) {
            $prizes = $raffle->prizes()->orderBy('order')->get();
            $allTickets = $raffle->tickets()->get();
            $selectedTicketIds = [];
            $winnersPayload = [];

            foreach ($prizes as $prize) {
                $availableTickets = $allTickets
                    ->whereNotIn('id', $selectedTicketIds)
                    ->values();

                if ($availableTickets->isEmpty()) {
                    break;
                }

                $count = min($prize->winner_count, $availableTickets->count());

                for ($i = 0; $i < $count; $i++) {
                    $remaining = $availableTickets->whereNotIn('id', $selectedTicketIds)->values();

                    if ($remaining->isEmpty()) {
                        break;
                    }

                    $randomIndex = random_int(0, $remaining->count() - 1);
                    $selectedTicket = $remaining[$randomIndex];
                    $selectedTicketIds[] = $selectedTicket->id;

                    $selectedTicket->update(['is_winner' => true]);

                    \App\Models\RaffleResult::create([
                        'raffle_id' => $raffle->id,
                        'prize_id' => $prize->id,
                        'ticket_id' => $selectedTicket->id,
                    ]);

                    $winnersPayload[] = [
                        'prize_id' => $prize->id,
                        'prize_name' => $prize->name,
                        'ticket_id' => $selectedTicket->id,
                        'serie' => $selectedTicket->serie,
                        'number' => $selectedTicket->number,
                        'participant_id' => $selectedTicket->participant_id,
                    ];
                }
            }

            $raffle->update(['status' => Raffle::STATUS_EXECUTED]);

            $auditLog = RaffleAuditLog::create([
                'raffle_id' => $raffle->id,
                'executed_at' => now(),
                'executed_by' => $executedBy->id,
                'algorithm_version' => '1.0',
                'seed_info' => 'PHP random_int() — CSPRNG',
                'payload' => [
                    'winners' => $winnersPayload,
                    'total_tickets' => $allTickets->count(),
                    'total_prizes' => $prizes->count(),
                ],
            ]);

            return $auditLog;
        });

        dispatch(new SendRaffleNotification('raffle_results', [
            'raffle_id' => $raffle->id,
        ]));

        return $auditLog;
    }
}
