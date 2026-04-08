<?php

namespace Database\Factories;

use App\Models\Prize;
use App\Models\Raffle;
use Illuminate\Database\Eloquent\Factories\Factory;

class PrizeFactory extends Factory
{
    protected $model = Prize::class;

    public function definition(): array
    {
        return [
            'raffle_id' => Raffle::factory(),
            'name' => fake()->word().' Prize',
            'description' => fake()->sentence(),
            'winner_count' => 1,
            'order' => 0,
        ];
    }
}
