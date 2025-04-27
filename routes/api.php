<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LeagueController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/teams', [LeagueController::class, 'addTeam']);
Route::get('/teams', [LeagueController::class, 'getTeams']);
Route::post('/fixtures', [LeagueController::class, 'generateFixtures']);
Route::post('/simulate-week', [LeagueController::class, 'simulateWeek']);
Route::post('/simulate-all', [LeagueController::class, 'simulateAll']);
Route::get('/standings', [LeagueController::class, 'getStandings']);
Route::post('/reset', [LeagueController::class, 'reset']);
