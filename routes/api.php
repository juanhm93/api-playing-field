<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Api\CountryController;
use App\Http\Controllers\Api\LeagueController;
use App\Http\Controllers\Api\TeamController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');


Route::post('/login', [LoginController::class, 'store']);

Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::apiResource('countries', CountryController::class);
    Route::apiResource('leagues', LeagueController::class);
    Route::apiResource('teams', TeamController::class)->only(['index', 'store', 'show']);
});
