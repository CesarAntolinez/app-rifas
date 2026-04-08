<?php

namespace Database\Factories;

use App\Models\Participant;
use App\Models\Raffle;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition(): array
    {
        return [
            'raffle_id' => Raffle::factory(),
            'participant_id' => Participant::factory(),
            'serie' => fake()->randomElement(['A', 'B', 'C']),
            'number' => str_pad(fake()->numberBetween(0, 99), 2, '0', STR_PAD_LEFT),
            'is_winner' => false,
        ];
    }
}
