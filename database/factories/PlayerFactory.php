<?php

namespace Database\Factories;

use App\Models\Player;
use App\Models\Position;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Player>
 */
class PlayerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $first = fake()->firstName();
        $last = fake()->lastName();

        return [
            'name' => $first,
            'lastname' => $last,
            'nickname' => fake()->optional(0.3)->firstName(),
            'slug' => Str::slug($first.' '.$last).'-'.fake()->unique()->numerify('####'),
            'code' => strtoupper(fake()->optional()->lexify('???')),
            'photo' => null,
            'date_of_birth' => fake()->optional()->date(),
            'nationality' => fake()->optional()->countryCode(),
            'height' => fake()->optional()->numerify('###').' cm',
            'weight' => fake()->optional()->numerify('##').' kg',
            'foot' => fake()->optional()->randomElement(['L', 'R']),
            'position_id' => Position::factory(),
            'team_id' => null,
        ];
    }
}
