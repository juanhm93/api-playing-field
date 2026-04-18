<?php

namespace Database\Factories;

use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Country>
 */
class CountryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->country(),
            'slug' => $this->faker->slug(),
            'code' => $this->faker->countryCode(),
            'flag' => $this->faker->imageUrl(),
            'currency' => $this->faker->currencyCode(),
        ];
    }
}
