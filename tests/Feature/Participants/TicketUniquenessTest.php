<?php

use App\Models\Participant;
use App\Models\Raffle;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('rejects duplicate ticket (raffle_id, serie, number) combination at the database level', function () {
    $user = User::factory()->create(['role' => 'organizer']);
    $raffle = Raffle::factory()->create([
        'user_id' => $user->id,
        'ticket_digit_type' => 'double',
    ]);
    $participant = Participant::factory()->create(['raffle_id' => $raffle->id]);

    Ticket::factory()->create([
        'raffle_id' => $raffle->id,
        'participant_id' => $participant->id,
        'serie' => 'A',
        'number' => '42',
    ]);

    expect(function () use ($raffle, $participant) {
        Ticket::factory()->create([
            'raffle_id' => $raffle->id,
            'participant_id' => $participant->id,
            'serie' => 'A',
            'number' => '42',
        ]);
    })->toThrow(QueryException::class);
});

it('rejects duplicate via TicketService', function () {
    $user = User::factory()->create(['role' => 'organizer']);
    $raffle = Raffle::factory()->create([
        'user_id' => $user->id,
        'ticket_digit_type' => 'double',
    ]);
    $participant = Participant::factory()->create(['raffle_id' => $raffle->id]);

    $service = new TicketService;
    $service->generateTicket($raffle, $participant, 'A', '42');

    expect(fn () => $service->generateTicket($raffle, $participant, 'A', '42'))
        ->toThrow(InvalidArgumentException::class);
});

it('allows same number in different series', function () {
    $user = User::factory()->create(['role' => 'organizer']);
    $raffle = Raffle::factory()->create([
        'user_id' => $user->id,
        'ticket_digit_type' => 'double',
        'series_count' => 2,
    ]);
    $participant = Participant::factory()->create(['raffle_id' => $raffle->id]);

    $service = new TicketService;
    $ticket1 = $service->generateTicket($raffle, $participant, 'A', '42');
    $ticket2 = $service->generateTicket($raffle, $participant, 'B', '42');

    expect($ticket1->id)->not->toBe($ticket2->id);
});

it('allows same number in different raffles', function () {
    $user = User::factory()->create(['role' => 'organizer']);
    $raffle1 = Raffle::factory()->create(['user_id' => $user->id, 'ticket_digit_type' => 'double']);
    $raffle2 = Raffle::factory()->create(['user_id' => $user->id, 'ticket_digit_type' => 'double']);

    $participant1 = Participant::factory()->create(['raffle_id' => $raffle1->id]);
    $participant2 = Participant::factory()->create(['raffle_id' => $raffle2->id]);

    $service = new TicketService;
    $ticket1 = $service->generateTicket($raffle1, $participant1, 'A', '42');
    $ticket2 = $service->generateTicket($raffle2, $participant2, 'A', '42');

    expect($ticket1->id)->not->toBe($ticket2->id);
});
