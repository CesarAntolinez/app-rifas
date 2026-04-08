<?php

use App\Models\Participant;
use App\Models\Prize;
use App\Models\Raffle;
use App\Models\User;
use App\Services\DrawExecutionService;
use App\Services\RaffleService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can create a raffle with draft status', function () {
    $user = User::factory()->create(['role' => 'organizer']);

    $service = new RaffleService();
    $raffle = $service->createRaffle([
        'name' => 'Test Raffle',
        'starts_at' => now(),
        'ends_at' => now()->addDays(7),
        'ticket_digit_type' => 'double',
        'series_count' => 1,
    ], $user);

    expect($raffle->status)->toBe(Raffle::STATUS_DRAFT);
});

it('can transition draft to active', function () {
    $user = User::factory()->create(['role' => 'organizer']);
    $raffle = Raffle::factory()->create(['user_id' => $user->id, 'status' => Raffle::STATUS_DRAFT]);

    $service = new RaffleService();
    $service->publishRaffle($raffle, $user);

    expect($raffle->fresh()->status)->toBe(Raffle::STATUS_ACTIVE);
});

it('can transition active to closed', function () {
    $user = User::factory()->create(['role' => 'organizer']);
    $raffle = Raffle::factory()->create(['user_id' => $user->id, 'status' => Raffle::STATUS_ACTIVE]);

    $service = new RaffleService();
    $service->closeRaffle($raffle, $user);

    expect($raffle->fresh()->status)->toBe(Raffle::STATUS_CLOSED);
});

it('can transition closed to executed', function () {
    $user = User::factory()->create(['role' => 'organizer']);
    $raffle = Raffle::factory()->create([
        'user_id' => $user->id,
        'status' => Raffle::STATUS_CLOSED,
        'ticket_digit_type' => 'double',
        'series_count' => 1,
    ]);

    Prize::factory()->create(['raffle_id' => $raffle->id, 'winner_count' => 1, 'order' => 0]);
    $participant = Participant::factory()->create(['raffle_id' => $raffle->id]);
    \App\Models\Ticket::factory()->create([
        'raffle_id' => $raffle->id,
        'participant_id' => $participant->id,
        'serie' => 'A',
        'number' => '01',
    ]);

    $drawService = new DrawExecutionService();
    $drawService->execute($raffle, $user);

    expect($raffle->fresh()->status)->toBe(Raffle::STATUS_EXECUTED);
});

it('can soft delete a draft raffle', function () {
    $user = User::factory()->create(['role' => 'organizer']);
    $raffle = Raffle::factory()->create(['user_id' => $user->id, 'status' => Raffle::STATUS_DRAFT]);

    $service = new RaffleService();
    $service->deleteRaffle($raffle, $user);

    expect($raffle->fresh()->status)->toBe(Raffle::STATUS_DELETED);
});

it('cannot delete a raffle with active status via policy', function () {
    $user = User::factory()->create(['role' => 'organizer']);
    $raffle = Raffle::factory()->create(['user_id' => $user->id, 'status' => Raffle::STATUS_ACTIVE]);

    expect($user->can('delete', $raffle))->toBeFalse();
});

it('cannot execute a raffle that is not closed', function () {
    $user = User::factory()->create(['role' => 'organizer']);
    $raffle = Raffle::factory()->create(['user_id' => $user->id, 'status' => Raffle::STATUS_ACTIVE]);

    $drawService = new DrawExecutionService();
    expect(fn () => $drawService->execute($raffle, $user))->toThrow(RuntimeException::class);
});
