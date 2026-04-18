<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use App\Models\Team;
use App\Models\League;
use App\Models\Country;

class TeamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rows = config('teams', []);
        // $leagues = League::all();
        foreach ($rows as $league => $teams) {
            $league = League::firstWhere('slug', $league);

            foreach ($teams as $team) {
                Team::query()->updateOrCreate(
                    ['slug' => $team['slug']],
                    array_merge(
                        $team,
                        ['country_id' => $league->country_id]
                    ),
                );
            }
        }
    }
}
