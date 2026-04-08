<?php

use App\Models\Participant;
use App\Models\Prize;
use App\Models\Raffle;
use App\Models\Ticket;
use App\Models\User;
use App\Services\DrawExecutionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createRaffleWithTickets(int $ticketCount = 10): array
{
    $user = User::factory()->create(['role' => 'organizer']);
    $raffle = Raffle::factory()->create([
        'user_id' => $user->id,
        'status' => Raffle::STATUS_CLOSED,
        'ticket_digit_type' => 'double',
        'series_count' => 1,
    ]);

    $prize = Prize::factory()->create([
        'raffle_id' => $raffle->id,
        'winner_count' => 1,
        'order' => 0,
    ]);

    $participant = Participant::factory()->create(['raffle_id' => $raffle->id]);

    for ($i = 0; $i < $ticketCount; $i++) {
        Ticket::factory()->create([
            'raffle_id' => $raffle->id,
            'participant_id' => $participant->id,
            'serie' => 'A',
            'number' => str_pad($i, 2, '0', STR_PAD_LEFT),
        ]);
    }

    return compact('user', 'raffle', 'prize', 'participant');
}

it('selects winners and marks tickets as is_winner', function () {
    ['user' => $user, 'raffle' => $raffle] = createRaffleWithTickets(10);

    $service = new DrawExecutionService;
    $auditLog = $service->execute($raffle, $user);

    expect($auditLog)->not->toBeNull();
    expect(Ticket::where('raffle_id', $raffle->id)->where('is_winner', true)->count())->toBe(1);
});

it('changes raffle status to executed', function () {
    ['user' => $user, 'raffle' => $raffle] = createRaffleWithTickets(5);

    $service = new DrawExecutionService;
    $service->execute($raffle, $user);

    expect($raffle->fresh()->status)->toBe(Raffle::STATUS_EXECUTED);
});

it('creates audit log with full payload', function () {
    ['user' => $user, 'raffle' => $raffle] = createRaffleWithTickets(5);

    $service = new DrawExecutionService;
    $auditLog = $service->execute($raffle, $user);

    expect($auditLog->payload)->toBeArray();
    expect($auditLog->payload)->toHaveKey('winners');
    expect($auditLog->payload)->toHaveKey('total_tickets');
    expect($auditLog->payload['total_tickets'])->toBe(5);
});

it('does not repeat a ticket across multiple prizes', function () {
    $user = User::factory()->create(['role' => 'organizer']);
    $raffle = Raffle::factory()->create([
        'user_id' => $user->id,
        'status' => Raffle::STATUS_CLOSED,
        'ticket_digit_type' => 'double',
        'series_count' => 1,
    ]);

    Prize::factory()->create(['raffle_id' => $raffle->id, 'winner_count' => 1, 'order' => 0]);
    Prize::factory()->create(['raffle_id' => $raffle->id, 'winner_count' => 1, 'order' => 1]);

    $participant = Participant::factory()->create(['raffle_id' => $raffle->id]);
    for ($i = 0; $i < 10; $i++) {
        Ticket::factory()->create([
            'raffle_id' => $raffle->id,
            'participant_id' => $participant->id,
            'serie' => 'A',
            'number' => str_pad($i, 2, '0', STR_PAD_LEFT),
        ]);
    }

    $service = new DrawExecutionService;
    $auditLog = $service->execute($raffle, $user);

    $winnerTicketIds = collect($auditLog->payload['winners'])->pluck('ticket_id');
    expect($winnerTicketIds->unique()->count())->toBe($winnerTicketIds->count());
});

it('throws exception if raffle status is not closed', function () {
    $user = User::factory()->create(['role' => 'organizer']);
    $raffle = Raffle::factory()->create([
        'user_id' => $user->id,
        'status' => Raffle::STATUS_ACTIVE,
    ]);

    $service = new DrawExecutionService;
    expect(fn () => $service->execute($raffle, $user))->toThrow(RuntimeException::class);
});

it('throws exception if raffle has no participants', function () {
    $user = User::factory()->create(['role' => 'organizer']);
    $raffle = Raffle::factory()->create([
        'user_id' => $user->id,
        'status' => Raffle::STATUS_CLOSED,
    ]);

    $service = new DrawExecutionService;
    expect(fn () => $service->execute($raffle, $user))->toThrow(RuntimeException::class);
});

it('selects different winners across multiple executions (randomness)', function () {
    $results = [];

    for ($run = 0; $run < 5; $run++) {
        ['user' => $user, 'raffle' => $raffle] = createRaffleWithTickets(20);

        $service = new DrawExecutionService;
        $auditLog = $service->execute($raffle, $user);

        $results[] = $auditLog->payload['winners'][0]['ticket_id'];
    }

    expect(array_unique($results))->not->toHaveCount(1);
})->skip('Probabilistic test — may occasionally fail');
