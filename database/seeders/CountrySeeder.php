<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\League;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rows = config('countries', []);

        Model::unguarded(function () use ($rows) {
            foreach ($rows as $row) {
                $leagues = $row['leagues'] ?? [];
                unset($row['leagues']);

                $country = Country::query()->updateOrCreate(
                    ['slug' => $row['slug']],
                    $row,
                );

                // foreach ($leagues as $league) {
                //     League::query()->updateOrCreate(
                //         ['slug' => $league['slug']],
                //         array_merge($league, ['country_id' => $country->id]),
                //     );
                // }
            }
        });
    }
}
