<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Juan Manuel Admin',
            'email' => 'juanhm.co93@gmail.com',
            'password' => Hash::make('juan1234'),
        ]);
        // $this->call([
        //     CountrySeeder::class,
        //     PositionSeeder::class,
        //     LeagueSeeder::class,
        //     TeamSeeder::class,
        //     PlayerSeeder::class,
        //     // GameSeeder::class,
        //     // GoalSeeder::class,
        // ]);
    }
}
