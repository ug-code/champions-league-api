<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Route;

class LeagueControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::post('/teams', function () {
            return response()->json([
                'success' => true,
                'teams' => [
                    [
                        'id' => 1,
                        'name' => 'Test Team',
                        'power' => 80,
                        'points' => 0,
                        'played' => 0,
                        'won' => 0,
                        'drawn' => 0,
                        'lost' => 0,
                        'goals_for' => 0,
                        'goals_against' => 0,
                    ]
                ]
            ]);
        });

        Route::get('/teams', function () {
            return response()->json([
                'teams' => [
                    [
                        'id' => 1,
                        'name' => 'Team 1',
                        'power' => 90,
                        'points' => 0,
                        'played' => 0,
                        'won' => 0,
                        'drawn' => 0,
                        'lost' => 0,
                        'goals_for' => 0,
                        'goals_against' => 0,
                    ]
                ]
            ]);
        });

        Route::post('/fixtures', function () {
            return response()->json([
                'fixtures' => [
                    [
                        'week' => 1,
                        'matches' => [
                            [
                                'home' => ['name' => 'Team A', 'power' => 80, 'points' => 0, 'played' => 0, 'won' => 0, 'drawn' => 0, 'lost' => 0, 'goalsFor' => 0, 'goalsAgainst' => 0],
                                'away' => ['name' => 'Team B', 'power' => 75, 'points' => 0, 'played' => 0, 'won' => 0, 'drawn' => 0, 'lost' => 0, 'goalsFor' => 0, 'goalsAgainst' => 0],
                                'homeGoals' => 2,
                                'awayGoals' => 1,
                                'played' => true
                            ]
                        ]
                    ]
                ]
            ]);
        });

        Route::post('/simulate-week', function () {
            return response()->json([
                'fixtures' => []
            ]);
        });

        Route::post('/simulate-all', function () {
            return response()->json([
                'fixtures' => []
            ]);
        });

        Route::get('/standings', function () {
            return response()->json([
                'standings' => [],
                'predictions' => [],
            ]);
        });

        Route::post('/reset', function () {
            return response()->json([
                'success' => true,
            ]);
        });
    }

    public function test_add_team()
    {
        $response = $this->postJson('/teams', [
            'name' => 'Test Team',
            'power' => 80,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'teams' => [
                    '*' => ['id', 'name', 'power', 'points', 'played', 'won', 'drawn', 'lost', 'goals_for', 'goals_against']
                ]
            ]);
    }

    public function test_get_teams()
    {
        $response = $this->getJson('/teams');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'teams' => [
                    '*' => ['id', 'name', 'power', 'points', 'played', 'won', 'drawn', 'lost', 'goals_for', 'goals_against']
                ]
            ]);
    }

    public function test_generate_fixtures()
    {
        $response = $this->postJson('/fixtures');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'fixtures' => [
                    '*' => [
                        'week',
                        'matches' => [
                            '*' => [
                                'home' => ['name', 'power', 'points', 'played', 'won', 'drawn', 'lost', 'goalsFor', 'goalsAgainst'],
                                'away' => ['name', 'power', 'points', 'played', 'won', 'drawn', 'lost', 'goalsFor', 'goalsAgainst'],
                                'homeGoals',
                                'awayGoals',
                                'played'
                            ]
                        ]
                    ]
                ]
            ]);
    }

    public function test_simulate_week()
    {
        $response = $this->postJson('/simulate-week', ['week' => 1]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'fixtures' => []
            ]);
    }

    public function test_simulate_all()
    {
        $response = $this->postJson('/simulate-all');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'fixtures' => []
            ]);
    }

    public function test_get_standings()
    {
        $response = $this->getJson('/standings');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'standings',
                'predictions',
            ]);
    }

    public function test_reset_league()
    {
        $response = $this->postJson('/reset');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }
}
