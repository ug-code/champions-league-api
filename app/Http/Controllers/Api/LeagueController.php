<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\MatchGame;
use App\Services\LeagueSimulatorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeagueController extends Controller
{
    private function getStateFile() {
        return storage_path('league.json');
    }

    private function loadState() {
        $file = $this->getStateFile();
        if (!file_exists($file)) {
            return [
                'teams' => [],
                'simulator' => null,
                'fixturesGenerated' => false,
            ];
        }
        $data = json_decode(file_get_contents($file), true);
        return $data;
    }

    private function saveState($state) {
        file_put_contents($this->getStateFile(), json_encode($state));
    }

    public function addTeam(Request $request) {
        $name = $request->input('name');
        $power = (int) $request->input('power');
        
        $team = new Team();
        $team->name = $name;
        $team->power = $power;
        $team->save();
        
        return response()->json(['success' => true, 'teams' => Team::all()]);
    }

    public function getTeams() {
        return response()->json(['teams' => Team::all()]);
    }

    public function generateFixtures() {
        // Önceki fikstürleri sil
        DB::table('fixtures')->truncate();
        
        // Takımları sıfırla
        Team::query()->update([
            'points' => 0,
            'played' => 0,
            'won' => 0,
            'drawn' => 0,
            'lost' => 0,
            'goals_for' => 0,
            'goals_against' => 0
        ]);
        
        $teams = Team::all();
        $sim = new LeagueSimulatorService($teams);
        $sim->generateFixtures();
        
        // Fikstürleri veritabanına kaydet
        DB::transaction(function() use ($sim) {
            foreach ($sim->fixtures as $weekIndex => $week) {
                foreach ($week as $match) {
                    DB::table('fixtures')->insert([
                        'week' => $weekIndex + 1,
                        'home_team_id' => $match->home->id,
                        'away_team_id' => $match->away->id,
                        'home_goals' => $match->homeGoals,
                        'away_goals' => $match->awayGoals,
                        'played' => $match->played,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }
        });
        
        return response()->json(['fixtures' => $sim->toArray()['fixtures']]);
    }

    public function simulateWeek(Request $request) {
        $week = (int) $request->input('week');
        
        // Haftanın maçlarını getir
        $fixtures = DB::table('fixtures')
            ->where('week', $week)
            ->where('played', false)
            ->get();
            
        if ($fixtures->isEmpty()) {
            return response()->json(['error' => 'Bu hafta için oynanmamış maç bulunamadı'], 400);
        }
        
        $teams = Team::all();
        $sim = new LeagueSimulatorService($teams);
        
        // Maçları simüle et
        foreach ($fixtures as $fixture) {
            $homeTeam = $teams->firstWhere('id', $fixture->home_team_id);
            $awayTeam = $teams->firstWhere('id', $fixture->away_team_id);
            
            $match = new MatchGame($homeTeam, $awayTeam);
            $sim->simulateMatch($match);
            
            // Maç sonucunu veritabanına kaydet
            DB::table('fixtures')
                ->where('id', $fixture->id)
                ->update([
                    'home_goals' => $match->homeGoals,
                    'away_goals' => $match->awayGoals,
                    'played' => true,
                    'updated_at' => now()
                ]);
                
            // Takım istatistiklerini güncelle
            $homeTeam->save();
            $awayTeam->save();
        }
        
        // Tüm fikstürü haftalara göre gruplandırarak getir
        $allFixtures = DB::table('fixtures')
            ->orderBy('week')
            ->get()
            ->groupBy('week')
            ->map(function($weekFixtures) use ($teams) {
                return $weekFixtures->map(function($fixture) use ($teams) {
                    $homeTeam = $teams->firstWhere('id', $fixture->home_team_id);
                    $awayTeam = $teams->firstWhere('id', $fixture->away_team_id);
                    
                    return [
                        'home' => [
                            'name' => $homeTeam->name,
                            'power' => $homeTeam->power,
                            'points' => $homeTeam->points,
                            'played' => $homeTeam->played,
                            'won' => $homeTeam->won,
                            'drawn' => $homeTeam->drawn,
                            'lost' => $homeTeam->lost,
                            'goalsFor' => $homeTeam->goals_for,
                            'goalsAgainst' => $homeTeam->goals_against
                        ],
                        'away' => [
                            'name' => $awayTeam->name,
                            'power' => $awayTeam->power,
                            'points' => $awayTeam->points,
                            'played' => $awayTeam->played,
                            'won' => $awayTeam->won,
                            'drawn' => $awayTeam->drawn,
                            'lost' => $awayTeam->lost,
                            'goalsFor' => $awayTeam->goals_for,
                            'goalsAgainst' => $awayTeam->goals_against
                        ],
                        'homeGoals' => $fixture->home_goals,
                        'awayGoals' => $fixture->away_goals,
                        'played' => $fixture->played
                    ];
                })->values()->all();
            })->values()->all();
        
        return response()->json(['fixtures' => $allFixtures]);
    }

    public function simulateAll() {
        $teams = Team::all();
        $sim = new LeagueSimulatorService($teams);
        
        // Tüm oynanmamış maçları getir
        $fixtures = DB::table('fixtures')
            ->where('played', false)
            ->get();
            
        if ($fixtures->isEmpty()) {
            return response()->json(['error' => 'Oynanmamış maç bulunamadı'], 400);
        }
        
        // Maçları simüle et
        foreach ($fixtures as $fixture) {
            $homeTeam = $teams->firstWhere('id', $fixture->home_team_id);
            $awayTeam = $teams->firstWhere('id', $fixture->away_team_id);
            
            $match = new MatchGame($homeTeam, $awayTeam);
            $sim->simulateMatch($match);
            
            // Maç sonucunu veritabanına kaydet
            DB::table('fixtures')
                ->where('id', $fixture->id)
                ->update([
                    'home_goals' => $match->homeGoals,
                    'away_goals' => $match->awayGoals,
                    'played' => true,
                    'updated_at' => now()
                ]);
                
            // Takım istatistiklerini güncelle
            $homeTeam->save();
            $awayTeam->save();
        }
        
        return response()->json(['success' => true]);
    }

    public function getStandings() {
        $teams = Team::orderBy('points', 'desc')
            ->orderBy(DB::raw('goals_for - goals_against'), 'desc')
            ->orderBy('goals_for', 'desc')
            ->get();
            
        $weeksLeft = DB::table('fixtures')
            ->where('played', false)
            ->count() / 2; // Her haftada 2 maç olduğunu varsayıyoruz
            
        $sim = new LeagueSimulatorService($teams);
        $predictions = $sim->getChampionshipPredictions($weeksLeft);
        
        return response()->json([
            'standings' => $teams,
            'predictions' => $predictions
        ]);
    }

    public function reset() {
        DB::transaction(function() {
            // Tüm maçları sil
            DB::table('fixtures')->truncate();
            
            // Tüm takımları sıfırla
            Team::query()->update([
                'points' => 0,
                'played' => 0,
                'won' => 0,
                'drawn' => 0,
                'lost' => 0,
                'goals_for' => 0,
                'goals_against' => 0
            ]);
        });
        
        return response()->json(['success' => true]);
    }
}
