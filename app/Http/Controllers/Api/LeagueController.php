<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\MatchGame;
use App\Services\LeagueSimulatorService;
use Illuminate\Http\Request;

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
        $state = $this->loadState();
        $name = $request->input('name');
        $power = (int) $request->input('power');
        $team = new Team();
        $team->name = $name;
        $team->power = $power;
        $state['teams'][] = $team->toArray();
        $state['fixturesGenerated'] = false;
        $this->saveState($state);
        return response()->json(['success' => true, 'teams' => $state['teams']]);
    }

    public function getTeams() {
        $state = $this->loadState();
        return response()->json(['teams' => $state['teams']]);
    }

    public function generateFixtures() {
        $state = $this->loadState();
        $teams = array_map(fn($t) => Team::fromArray($t), $state['teams']);
        $sim = new LeagueSimulatorService($teams);
        $sim->generateFixtures();
        $state['simulator'] = $sim->toArray();
        $state['fixturesGenerated'] = true;
        $this->saveState($state);
        return response()->json(['fixtures' => $sim->toArray()['fixtures']]);
    }

    public function simulateWeek(Request $request) {
        $state = $this->loadState();
        if (empty($state['fixturesGenerated']) || !$state['simulator']) {
            return response()->json(['error' => 'Fikstür oluşturulmadı'], 400);
        }
        $week = (int) $request->input('week');
        $sim = LeagueSimulatorService::fromArray($state['simulator']);
        $sim->simulateWeek($week);
        $state['simulator'] = $sim->toArray();
        $this->saveState($state);
        return response()->json(['fixtures' => $sim->toArray()['fixtures']]);
    }

    public function simulateAll() {
        $state = $this->loadState();
        if (empty($state['fixturesGenerated']) || !$state['simulator']) {
            return response()->json(['error' => 'Fikstür oluşturulmadı'], 400);
        }
        $sim = LeagueSimulatorService::fromArray($state['simulator']);
        $sim->simulateAll();
        $state['simulator'] = $sim->toArray();
        $this->saveState($state);
        return response()->json(['fixtures' => $sim->toArray()['fixtures']]);
    }

    public function getStandings() {
        $state = $this->loadState();
        if (empty($state['fixturesGenerated']) || !$state['simulator']) {
            return response()->json(['error' => 'Fikstür oluşturulmadı'], 400);
        }
        $sim = LeagueSimulatorService::fromArray($state['simulator']);
        $standings = $sim->getStandings();
        $weeksLeft = count($sim->fixtures) - $this->getPlayedWeeks($sim);
        $predictions = $sim->getChampionshipPredictions($weeksLeft);
        return response()->json([
            'standings' => array_map(fn($t) => $t->toArray(), $standings),
            'predictions' => $predictions
        ]);
    }

    private function getPlayedWeeks($sim) {
        $count = 0;
        foreach ($sim->fixtures as $week) {
            $played = true;
            foreach ($week as $match) {
                if (!$match->played) $played = false;
            }
            if ($played) $count++;
        }
        return $count;
    }

    public function reset() {
        $this->saveState([
            'teams' => [],
            'simulator' => null,
            'fixturesGenerated' => false,
        ]);
        return response()->json(['success' => true]);
    }
}
