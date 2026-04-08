<?php

use App\Jobs\SendRaffleNotification;
use App\Jobs\SendRaffleReminder;
use App\Models\Participant;
use App\Models\Prize;
use App\Models\Raffle;
use App\Models\Ticket;
use App\Models\User;
use App\Services\DrawExecutionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

it('dispatches SendRaffleNotification job on raffle execution', function () {
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

    Queue::assertPushed(SendRaffleNotification::class, function ($job) {
        return $job->type === 'raffle_results';
    });
});

it('SendRaffleNotification job has 3 tries', function () {
    $job = new SendRaffleNotification('test_type', []);
    expect($job->tries)->toBe(3);
});

it('SendRaffleNotification job has exponential backoff', function () {
    $job = new SendRaffleNotification('test_type', []);
    expect($job->backoff())->toBe([10, 30, 60]);
});

it('dispatches SendRaffleReminder for each active raffle closing within 24h', function () {
    Queue::fake();

    $user = User::factory()->create(['role' => 'organizer']);
    $raffle = Raffle::factory()->create([
        'user_id' => $user->id,
        'status' => Raffle::STATUS_ACTIVE,
        'ends_at' => now()->addHours(12),
    ]);

    $this->artisan('raffles:dispatch-reminders');

    Queue::assertPushed(SendRaffleReminder::class, function ($job) use ($raffle) {
        return $job->raffle->id === $raffle->id;
    });
});

it('does not dispatch reminder for raffles not closing within 24h', function () {
    Queue::fake();

    $user = User::factory()->create(['role' => 'organizer']);
    Raffle::factory()->create([
        'user_id' => $user->id,
        'status' => Raffle::STATUS_ACTIVE,
        'ends_at' => now()->addDays(5),
    ]);

    $this->artisan('raffles:dispatch-reminders');

    Queue::assertNothingPushed();
});
