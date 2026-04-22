<?php

namespace Database\Seeders;

use App\Models\Player;
use App\Models\Position;
use App\Models\Team;
use Illuminate\Database\Seeder;

class TeamWithPlayersSeeder extends Seeder
{
    /**
     * Creates one team via factory and attaches five players (requires positions).
     */
    public function run(): void
    {
        $positionIds = Position::query()->pluck('id');
        if ($positionIds->isEmpty()) {
            $this->command?->warn('TeamWithPlayersSeeder skipped: run PositionSeeder first.');

            return;
        }

        $team = Team::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            Player::factory()
                ->for($team)
                ->create([
                    'position_id' => $positionIds->random(),
                ]);
        }
    }
}
