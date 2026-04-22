<?php

namespace Database\Seeders;

use App\Models\Player;
use App\Models\Position;
use App\Models\Team;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class PlayerSeeder extends Seeder
{
    use WithoutModelEvents;

    private const SQUAD_KEYS = ['number', 'moved', 'position'];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $positions = Position::query()->pluck('id', 'name');
        $players = config('players', [])['real-madrid'];
        $teamId = Team::query()->where('slug', 'real-madrid')->value('id');
        Model::unguarded(function () use ($players, $positions, $teamId) {

            foreach ($players as $player) {
                $this->seedPlayer($player, $positions, $teamId);
            }
            // foreach ($teams as $team) {
            //     if (! is_array($team) || ! isset($team['starters'])) {
            //         continue;
            //     }

            //     foreach (array_merge($team['starters'], $team['bench'] ?? []) as $row) {
            //         $this->seedPlayer($row, $positions);
            //     }
            // }
        });
    }

    /**
     * @param  \Illuminate\Support\Collection<string, int>  $positions
     */
    private function seedPlayer(array $row, $positions, ?int $teamId): void
    {
        $positionName = $row['position'] ?? null;
        if (! $positionName || ! isset($positions[$positionName])) {
            throw new \InvalidArgumentException(
                'Missing or unknown position [' . ($positionName ?? '') . '] for player slug [' . ($row['slug'] ?? '') . '].'
            );
        }

        $attributes = collect($row)
            ->except(self::SQUAD_KEYS)
            ->put('position_id', $positions[$positionName])
            ->when($teamId !== null, fn ($c) => $c->put('team_id', $teamId))
            ->all();

        Player::query()->updateOrCreate(
            ['slug' => $row['slug']],
            $attributes,
        );
    }
}
