<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

Route::get('/', function () {
    return view('welcome');
});


Route::get('/run-migrations', function () {
    $exitCode = Artisan::call('migrate', [
        '--force' => true,
        '--seed' => true,
    ]);

    return response(Artisan::output(), $exitCode === 0 ? 200 : 500)
        ->header('Content-Type', 'text/plain; charset=UTF-8');
});
