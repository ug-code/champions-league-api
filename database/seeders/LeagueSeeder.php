<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Team;
use App\Models\Fixture;
use Illuminate\Support\Facades\File;

class LeagueSeeder extends Seeder
{
    public function run()
    {
        $json = File::get(storage_path('league.json'));
        $data = json_decode($json, true);

        // Teams'i aktar
        foreach ($data['teams'] as $teamData) {
            Team::create([
                'name' => $teamData['name'],
                'power' => $teamData['power'],
                'points' => $teamData['points'],
                'played' => $teamData['played'],
                'won' => $teamData['won'],
                'drawn' => $teamData['drawn'],
                'lost' => $teamData['lost'],
                'goals_for' => $teamData['goalsFor'],
                'goals_against' => $teamData['goalsAgainst']
            ]);
        }

        // Fixtures'ı aktar
        if (isset($data['simulator']['fixtures'])) {
            foreach ($data['simulator']['fixtures'] as $weekFixtures) {
                foreach ($weekFixtures as $fixtureData) {
                    $homeTeam = Team::where('name', $fixtureData['home']['name'])->first();
                    $awayTeam = Team::where('name', $fixtureData['away']['name'])->first();

                    if ($homeTeam && $awayTeam) {
                        Fixture::create([
                            'home_team_id' => $homeTeam->id,
                            'away_team_id' => $awayTeam->id,
                            'home_goals' => $fixtureData['homeGoals'],
                            'away_goals' => $fixtureData['awayGoals'],
                            'played' => $fixtureData['played']
                        ]);
                    }
                }
            }
        }
    }
} 