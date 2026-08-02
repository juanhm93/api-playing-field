<?php

namespace Database\Seeders;

use App\Models\League;
use App\Models\Lineup;
use App\Models\Player;
use App\Models\PlayerTeamSeason;
use App\Models\Season;
use App\Models\Team;
use App\Models\TeamSeason;
use Illuminate\Database\Seeder;

class TeamSeasonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $team = Team::query()->where('slug', 'real-madrid')->first();
        $season = Season::query()->where('slug', '2025-26')->first();
        $league = League::query()->where('slug', 'la-liga')->first();
        $lineup = Lineup::query()->where('name', '4-3-3')->first()
            ?? Lineup::query()->first();

        if ($team === null || $season === null || $league === null || $lineup === null) {
            return;
        }

        $teamSeason = TeamSeason::query()->firstOrCreate(
            [
                'team_id' => $team->id,
                'season_id' => $season->id,
                'league_id' => $league->id,
            ],
            ['lineup_id' => $lineup->id],
        );

        $players = Player::query()
            ->where('team_id', $team->id)
            ->orderBy('id')
            ->get();

        $playersConfig = collect(config('players.real-madrid', []))
            ->keyBy('slug');

        foreach ($players as $index => $player) {
            $config = $playersConfig->get($player->slug, []);

            PlayerTeamSeason::query()->firstOrCreate(
                [
                    'player_id' => $player->id,
                    'team_season_id' => $teamSeason->id,
                ],
                [
                    'number' => $config['number'] ?? ($index + 1),
                    'is_started' => $index < 11,
                ],
            );
        }
    }
}
