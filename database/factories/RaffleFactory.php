<?php

namespace Database\Factories;

use App\Models\Raffle;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class RaffleFactory extends Factory
{
    protected $model = Raffle::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'image_path' => null,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(7),
            'status' => Raffle::STATUS_DRAFT,
            'ticket_digit_type' => 'double',
            'series_count' => 1,
        ];
    }
}
