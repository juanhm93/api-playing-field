<?php

namespace Database\Factories;

use App\Models\Season;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Season>
 */
class SeasonFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $year = fake()->unique()->numberBetween(1990, 2099);

        return [
            'name' => "{$year}/".($year + 1),
            'slug' => Str::slug("season-{$year}-".fake()->unique()->numerify('####')),
            'code' => (string) $year,
            'start_date' => "{$year}-08-01",
            'end_date' => ($year + 1).'-06-30',
        ];
    }
}
