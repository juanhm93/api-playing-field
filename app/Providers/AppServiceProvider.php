<?php

namespace App\Providers;

use App\Repositories\BaseApiInterface;
use App\Repositories\BaseApiRepository;
use App\Repositories\Contracts\PlayerTeamSeasonRepositoryInterface;
use App\Repositories\Contracts\TeamSeasonRepositoryInterface;
use App\Repositories\PlayerTeamSeasonRepository;
use App\Repositories\TeamSeasonRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(TeamSeasonRepositoryInterface::class, TeamSeasonRepository::class);
        $this->app->bind(PlayerTeamSeasonRepositoryInterface::class, PlayerTeamSeasonRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->app->bind(BaseApiInterface::class, BaseApiRepository::class);
    }
}
