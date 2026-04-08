<?php

use App\Models\Participant;
use App\Models\Raffle;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('validates ticket number is within range for double type', function () {
    $service = new TicketService;

    $range = $service->getNumberRange('double');
    expect($range['min'])->toBe(0);
    expect($range['max'])->toBe(99);
});

it('validates ticket number is within range for triple type', function () {
    $service = new TicketService;

    $range = $service->getNumberRange('triple');
    expect($range['min'])->toBe(0);
    expect($range['max'])->toBe(999);
});

it('validates ticket number is within range for quadruple type', function () {
    $service = new TicketService;

    $range = $service->getNumberRange('quadruple');
    expect($range['min'])->toBe(0);
    expect($range['max'])->toBe(9999);
});

it('throws exception for invalid digit type', function () {
    $service = new TicketService;
    expect(fn () => $service->getNumberRange('invalid'))->toThrow(InvalidArgumentException::class);
});

it('validates uniqueness returns true when ticket does not exist', function () {
    $user = User::factory()->create(['role' => 'organizer']);
    $raffle = Raffle::factory()->create(['user_id' => $user->id]);

    $service = new TicketService;
    expect($service->validateUniqueness($raffle->id, 'A', '01'))->toBeTrue();
});

it('validates uniqueness returns false when ticket already exists', function () {
    $user = User::factory()->create(['role' => 'organizer']);
    $raffle = Raffle::factory()->create(['user_id' => $user->id]);
    $participant = Participant::factory()->create(['raffle_id' => $raffle->id]);

    Ticket::factory()->create([
        'raffle_id' => $raffle->id,
        'participant_id' => $participant->id,
        'serie' => 'A',
        'number' => '01',
    ]);

    $service = new TicketService;
    expect($service->validateUniqueness($raffle->id, 'A', '01'))->toBeFalse();
});

it('generates ticket successfully with valid data', function () {
    $user = User::factory()->create(['role' => 'organizer']);
    $raffle = Raffle::factory()->create([
        'user_id' => $user->id,
        'ticket_digit_type' => 'double',
    ]);
    $participant = Participant::factory()->create(['raffle_id' => $raffle->id]);

    $service = new TicketService;
    $ticket = $service->generateTicket($raffle, $participant, 'A', '42');

    expect($ticket)->toBeInstanceOf(Ticket::class);
    expect($ticket->number)->toBe('42');
    expect($ticket->serie)->toBe('A');
});

it('throws exception for number out of range', function () {
    $user = User::factory()->create(['role' => 'organizer']);
    $raffle = Raffle::factory()->create([
        'user_id' => $user->id,
        'ticket_digit_type' => 'double',
    ]);
    $participant = Participant::factory()->create(['raffle_id' => $raffle->id]);

    $service = new TicketService;
    expect(fn () => $service->generateTicket($raffle, $participant, 'A', '999'))
        ->toThrow(InvalidArgumentException::class);
});

it('throws exception for duplicate ticket', function () {
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

    $service = new TicketService;
    expect(fn () => $service->generateTicket($raffle, $participant, 'A', '42'))
        ->toThrow(InvalidArgumentException::class);
});
