<?php

use App\Jobs\SendRaffleNotification;
use App\Models\Participant;
use App\Models\Prize;
use App\Models\Raffle;
use App\Models\RaffleAuditLog;
use App\Models\Ticket;
use App\Models\User;
use App\Services\DrawExecutionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

it('creates an audit log after execution', function () {
    $user = User::factory()->create(['role' => 'organizer']);
    $raffle = Raffle::factory()->create([
        'user_id' => $user->id,
        'status' => Raffle::STATUS_CLOSED,
        'ticket_digit_type' => 'double',
        'series_count' => 1,
    ]);
    Prize::factory()->create(['raffle_id' => $raffle->id, 'winner_count' => 1, 'order' => 0]);
    $participant = Participant::factory()->create(['raffle_id' => $raffle->id]);
    Ticket::factory()->create([
        'raffle_id' => $raffle->id,
        'participant_id' => $participant->id,
        'serie' => 'A',
        'number' => '01',
    ]);

    $service = new DrawExecutionService();
    $auditLog = $service->execute($raffle, $user);

    expect(RaffleAuditLog::where('raffle_id', $raffle->id)->count())->toBe(1);
    expect($auditLog)->toBeInstanceOf(RaffleAuditLog::class);
});

it('dispatches notification job after execution', function () {
    Queue::fake();

    $user = User::factory()->create(['role' => 'organizer']);
    $raffle = Raffle::factory()->create([
        'user_id' => $user->id,
        'status' => Raffle::STATUS_CLOSED,
        'ticket_digit_type' => 'double',
        'series_count' => 1,
    ]);
    Prize::factory()->create(['raffle_id' => $raffle->id, 'winner_count' => 1, 'order' => 0]);
    $participant = Participant::factory()->create(['raffle_id' => $raffle->id]);
    Ticket::factory()->create([
        'raffle_id' => $raffle->id,
        'participant_id' => $participant->id,
        'serie' => 'A',
        'number' => '01',
    ]);

    $service = new DrawExecutionService();
    $service->execute($raffle, $user);

    Queue::assertPushed(SendRaffleNotification::class);
});

it('audit log has no updated_at column', function () {
    $auditLog = new RaffleAuditLog();
    expect($auditLog::UPDATED_AT)->toBeNull();
    expect($auditLog->timestamps)->toBeFalse();
});
