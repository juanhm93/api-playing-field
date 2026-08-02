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
        $start = fake()->dateTimeBetween('-2 years', 'now');
        $end = (clone $start)->modify('+1 year');
        $label = $start->format('Y').'-'.$end->format('Y');

        return [
            'name' => $label,
            'slug' => Str::slug($label).'-'.fake()->unique()->numerify('####'),
            'code' => $start->format('Y'),
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
        ];
    }
}
