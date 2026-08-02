<?php

namespace Database\Seeders;

use App\Models\Lineup;
use Illuminate\Database\Seeder;

class LineupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (['4-4-2', '4-3-3', '3-5-2'] as $name) {
            Lineup::query()->firstOrCreate(['name' => $name]);
        }
    }
}
