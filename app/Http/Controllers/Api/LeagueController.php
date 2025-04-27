<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

// OOP class'lar dosya başında
class Team {
    public string $name;
    public int $power;
    public int $points = 0;
    public int $played = 0;
    public int $won = 0;
    public int $drawn = 0;
    public int $lost = 0;
    public int $goalsFor = 0;
    public int $goalsAgainst = 0;
    public function __construct($name, $power) {
        $this->name = $name;
        $this->power = $power;
    }
    public function goalDifference() {
        return $this->goalsFor - $this->goalsAgainst;
    }
    public static function fromArray($arr) {
        $team = new Team($arr['name'], $arr['power']);
        $team->points = $arr['points'] ?? 0;
        $team->played = $arr['played'] ?? 0;
        $team->won = $arr['won'] ?? 0;
        $team->drawn = $arr['drawn'] ?? 0;
        $team->lost = $arr['lost'] ?? 0;
        $team->goalsFor = $arr['goalsFor'] ?? 0;
        $team->goalsAgainst = $arr['goalsAgainst'] ?? 0;
        return $team;
    }
    public function toArray() {
        return [
            'name' => $this->name,
            'power' => $this->power,
            'points' => $this->points,
            'played' => $this->played,
            'won' => $this->won,
            'drawn' => $this->drawn,
            'lost' => $this->lost,
            'goalsFor' => $this->goalsFor,
            'goalsAgainst' => $this->goalsAgainst,
        ];
    }
}

class MatchGame {
    public Team $home;
    public Team $away;
    public ?int $homeGoals = null;
    public ?int $awayGoals = null;
    public bool $played = false;
    public function __construct(Team $home, Team $away) {
        $this->home = $home;
        $this->away = $away;
    }
    public static function fromArray($arr) {
        $home = Team::fromArray($arr['home']);
        $away = Team::fromArray($arr['away']);
        $m = new MatchGame($home, $away);
        $m->homeGoals = $arr['homeGoals'] ?? null;
        $m->awayGoals = $arr['awayGoals'] ?? null;
        $m->played = $arr['played'] ?? false;
        return $m;
    }
    public function toArray() {
        return [
            'home' => $this->home->toArray(),
            'away' => $this->away->toArray(),
            'homeGoals' => $this->homeGoals,
            'awayGoals' => $this->awayGoals,
            'played' => $this->played,
        ];
    }
}

class LeagueSimulator {
    public array $teams = [];
    public array $fixtures = [];
    public function __construct($teams) {
        $this->teams = $teams;
    }
    public function generateFixtures() {
        $n = count($this->teams);
        $rounds = [];
        $teams = $this->teams;
        if ($n % 2 !== 0) $teams[] = new Team('Bay', 0);
        $totalRounds = (count($teams) - 1) * 2;
        $matchesPerRound = count($teams) / 2;
        for ($round = 0; $round < $totalRounds; $round++) {
            $roundMatches = [];
            for ($i = 0; $i < $matchesPerRound; $i++) {
                $homeIdx = ($round + $i) % (count($teams) - 1);
                $awayIdx = (count($teams) - 1 - $i + $round) % (count($teams) - 1);
                if ($i === 0) $awayIdx = count($teams) - 1;
                $home = $teams[$homeIdx];
                $away = $teams[$awayIdx];
                if ($round < $totalRounds / 2) {
                    $roundMatches[] = new MatchGame($home, $away);
                } else {
                    $roundMatches[] = new MatchGame($away, $home);
                }
            }
            $rounds[] = array_filter($roundMatches, fn($m) => $m->home->name !== 'Bay' && $m->away->name !== 'Bay');
        }
        $this->fixtures = $rounds;
    }
    public function simulateMatch($match) {
        $homeAdv = 1.1;
        $homePower = $match->home->power * $homeAdv;
        $awayPower = $match->away->power;
        $totalPower = $homePower + $awayPower;
        $homeProb = $homePower / $totalPower;
        $awayProb = $awayPower / $totalPower;
        $homeGoals = max(0, round($this->randomGoal($homeProb)));
        $awayGoals = max(0, round($this->randomGoal($awayProb)));
        $match->homeGoals = $homeGoals;
        $match->awayGoals = $awayGoals;
        $match->played = true;
        $match->home->played++;
        $match->away->played++;
        $match->home->goalsFor += $homeGoals;
        $match->home->goalsAgainst += $awayGoals;
        $match->away->goalsFor += $awayGoals;
        $match->away->goalsAgainst += $homeGoals;
        if ($homeGoals > $awayGoals) {
            $match->home->won++;
            $match->home->points += 3;
            $match->away->lost++;
        } elseif ($homeGoals < $awayGoals) {
            $match->away->won++;
            $match->away->points += 3;
            $match->home->lost++;
        } else {
            $match->home->drawn++;
            $match->away->drawn++;
            $match->home->points++;
            $match->away->points++;
        }
    }
    public function randomGoal($prob) {
        $r = mt_rand() / mt_getrandmax();
        if ($r < $prob * 0.5) return 2 + mt_rand() / mt_getrandmax();
        if ($r < $prob) return 1 + mt_rand() / mt_getrandmax();
        return mt_rand() / mt_getrandmax();
    }
    public function simulateWeek($week) {
        if (!isset($this->fixtures[$week])) return;
        foreach ($this->fixtures[$week] as $match) {
            if (!$match->played) $this->simulateMatch($match);
        }
    }
    public function simulateAll() {
        for ($i = 0; $i < count($this->fixtures); $i++) {
            $this->simulateWeek($i);
        }
    }
    public function getStandings() {
        $teams = $this->teams;
        usort($teams, function($a, $b) {
            if ($b->points !== $a->points) return $b->points - $a->points;
            if ($b->goalDifference() !== $a->goalDifference()) return $b->goalDifference() - $a->goalDifference();
            return $b->goalsFor - $a->goalsFor;
        });
        return $teams;
    }
    public function getChampionshipPredictions($weeksLeft) {
        $standings = $this->getStandings();
        $maxPoint = $standings[0]->points;
        $predictions = [];
        foreach ($standings as $team) {
            if ($weeksLeft === 0) {
                $predictions[] = $team->points === $maxPoint ? 100 : 0;
                continue;
            }
            $possibleMax = $team->points + $weeksLeft * 3;
            $isStillPossible = $possibleMax >= $maxPoint;
            if (!$isStillPossible) {
                $predictions[] = 0;
                continue;
            }
            $predictions[] = round(($team->points / ($maxPoint ?: 1)) * 100);
        }
        return $predictions;
    }
    public static function fromArray($arr) {
        // Takım objelerini oluştur
        $teams = array_map(fn($t) => Team::fromArray($t), $arr['teams']);
        // Takım adını referans anahtar olarak kullan
        $teamMap = [];
        foreach ($teams as $t) {
            $teamMap[$t->name] = $t;
        }
        $sim = new LeagueSimulator($teams);
        $fixtures = [];
        if (isset($arr['fixtures'])) {
            foreach ($arr['fixtures'] as $week) {
                $weekArr = [];
                foreach ($week as $m) {
                    // Maçtaki takımları ana listeden referansla eşleştir
                    $home = $teamMap[$m['home']['name']];
                    $away = $teamMap[$m['away']['name']];
                    $match = new MatchGame($home, $away);
                    $match->homeGoals = $m['homeGoals'] ?? null;
                    $match->awayGoals = $m['awayGoals'] ?? null;
                    $match->played = $m['played'] ?? false;
                    $weekArr[] = $match;
                }
                $fixtures[] = $weekArr;
            }
        }
        $sim->fixtures = $fixtures;
        return $sim;
    }
    public function toArray() {
        return [
            'teams' => array_map(fn($t) => $t->toArray(), $this->teams),
            'fixtures' => array_map(fn($w) => array_map(fn($m) => $m->toArray(), $w), $this->fixtures),
        ];
    }
}

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

    // Takım ekle
    public function addTeam(Request $request) {
        $state = $this->loadState();
        $name = $request->input('name');
        $power = (int) $request->input('power');
        $state['teams'][] = (new Team($name, $power))->toArray();
        $state['fixturesGenerated'] = false;
        $this->saveState($state);
        return response()->json(['success' => true, 'teams' => $state['teams']]);
    }

    // Takım listele
    public function getTeams() {
        $state = $this->loadState();
        return response()->json(['teams' => $state['teams']]);
    }

    // Fikstür oluştur
    public function generateFixtures() {
        $state = $this->loadState();
        $teams = array_map(fn($t) => Team::fromArray($t), $state['teams']);
        $sim = new LeagueSimulator($teams);
        $sim->generateFixtures();
        $state['simulator'] = $sim->toArray();
        $state['fixturesGenerated'] = true;
        $this->saveState($state);
        return response()->json(['fixtures' => $sim->toArray()['fixtures']]);
    }

    // Haftalık simülasyon
    public function simulateWeek(Request $request) {
        $state = $this->loadState();
        if (empty($state['fixturesGenerated']) || !$state['simulator']) {
            return response()->json(['error' => 'Fikstür oluşturulmadı'], 400);
        }
        $week = (int) $request->input('week');
        $sim = LeagueSimulator::fromArray($state['simulator']);
        $sim->simulateWeek($week);
        $state['simulator'] = $sim->toArray();
        $this->saveState($state);
        return response()->json(['fixtures' => $sim->toArray()['fixtures']]);
    }

    // Tüm ligi simüle et
    public function simulateAll() {
        $state = $this->loadState();
        if (empty($state['fixturesGenerated']) || !$state['simulator']) {
            return response()->json(['error' => 'Fikstür oluşturulmadı'], 400);
        }
        $sim = LeagueSimulator::fromArray($state['simulator']);
        $sim->simulateAll();
        $state['simulator'] = $sim->toArray();
        $this->saveState($state);
        return response()->json(['fixtures' => $sim->toArray()['fixtures']]);
    }

    // Lig tablosu ve tahmin
    public function getStandings() {
        $state = $this->loadState();
        if (empty($state['fixturesGenerated']) || !$state['simulator']) {
            return response()->json(['error' => 'Fikstür oluşturulmadı'], 400);
        }
        $sim = LeagueSimulator::fromArray($state['simulator']);
        $standings = $sim->getStandings();
        $weeksLeft = count($sim->fixtures) - $this->getPlayedWeeks($sim);
        $predictions = $sim->getChampionshipPredictions($weeksLeft);
        return response()->json([
            'standings' => array_map(fn($t) => $t->toArray(), $standings),
            'predictions' => $predictions
        ]);
    }

    // Yardımcı: Kaç hafta oynandı?
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

    // Reset
    public function reset() {
        $this->saveState([
            'teams' => [],
            'simulator' => null,
            'fixturesGenerated' => false,
        ]);
        return response()->json(['success' => true]);
    }
}
