<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use App\Models\League;
use App\Models\Country;

class LeagueSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rows = config('leagues', []);
        $countries = Country::all();

        Model::unguarded(function () use ($rows, $countries) {
            foreach ($rows as $country => $row) {
                $country = $countries->firstWhere('code', strtoupper($country));
                League::query()->updateOrCreate(
                    ['slug' => $row['slug']],
                    array_merge($row, [
                        'country_id' => $country->id,
                    ]),
                );
            }
        });
    }
}
