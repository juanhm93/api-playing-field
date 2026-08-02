<?php

use App\Http\Controllers\Api\CountryController;
use App\Http\Controllers\Api\LeagueController;
use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Api\PlayerTeamSeasonController;
use App\Http\Controllers\Api\PlayerTransferController;
use App\Http\Controllers\Api\TeamController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [LoginController::class, 'store']);

Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::get('me', fn (Request $r) => $r->user());
    Route::apiResource('countries', CountryController::class);
    Route::apiResource('leagues', LeagueController::class);
    Route::apiResource('teams', TeamController::class)->only(['index', 'store', 'show']);
    Route::get('teams/{team}/seasons/{season}', [TeamController::class, 'showBySeason']);

    Route::apiResource('player-team-seasons', PlayerTeamSeasonController::class);
    Route::post('player-transfers', [PlayerTransferController::class, 'store']);
});
