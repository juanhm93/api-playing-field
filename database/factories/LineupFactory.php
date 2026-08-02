<?php

namespace Database\Factories;

use App\Models\Lineup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lineup>
 */
class LineupFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['4-4-2', '4-3-3', '3-5-2', '4-2-3-1', '5-3-2']),
        ];
    }
}
