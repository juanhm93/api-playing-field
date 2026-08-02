<?php

namespace Database\Seeders;

use App\Models\Season;
use Illuminate\Database\Seeder;

class SeasonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Season::query()->firstOrCreate(
            ['slug' => '2025-26'],
            [
                'name' => '2025/26',
                'code' => '2526',
                'start_date' => '2025-08-01',
                'end_date' => '2026-05-31',
            ],
        );
    }
}
