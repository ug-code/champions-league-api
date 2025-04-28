<?php

namespace App\Services;

use App\Models\Team;
use App\Models\MatchGame;
use Illuminate\Support\Collection;

class LeagueSimulatorService
{
    public Collection $teams;
    public array $fixtures = [];

    private const HOME_ADVANTAGE = 1.1;
    private const POINTS_FOR_WIN = 3;
    private const POINTS_FOR_DRAW = 1;

    public function __construct($teams)
    {
        $this->teams = $teams instanceof Collection ? $teams : collect($teams);
    }

    public function generateFixtures()
    {
        $n = $this->teams->count();
        $rounds = [];
        $teams = $this->teams->values()->all();

        if ($n % 2 !== 0) {
            $bayTeam = new Team();
            $bayTeam->name = 'Bay';
            $bayTeam->power = 0;
            $teams[] = $bayTeam;
        }

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

    public function simulateMatch($match)
    {
        $homePower = $match->home->power * self::HOME_ADVANTAGE;
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
        $match->home->goals_for += $homeGoals;
        $match->home->goals_against += $awayGoals;
        $match->away->goals_for += $awayGoals;
        $match->away->goals_against += $homeGoals;

        if ($homeGoals > $awayGoals) {
            $match->home->won++;
            $match->home->points += self::POINTS_FOR_WIN;
            $match->away->lost++;
        } elseif ($homeGoals < $awayGoals) {
            $match->away->won++;
            $match->away->points += self::POINTS_FOR_WIN;
            $match->home->lost++;
        } else {
            $match->home->drawn++;
            $match->away->drawn++;
            $match->home->points += self::POINTS_FOR_DRAW;
            $match->away->points += self::POINTS_FOR_DRAW;
        }
    }

    public function randomGoal($prob)
    {
        $r = mt_rand() / mt_getrandmax();
        if ($r < $prob * 0.5) return 2 + mt_rand() / mt_getrandmax();
        if ($r < $prob) return 1 + mt_rand() / mt_getrandmax();
        return mt_rand() / mt_getrandmax();
    }

    public function simulateWeek($week)
    {
        if (!isset($this->fixtures[$week])) return;
        foreach ($this->fixtures[$week] as $match) {
            if (!$match->played) $this->simulateMatch($match);
        }
    }

    public function simulateAll()
    {
        for ($i = 0; $i < count($this->fixtures); $i++) {
            $this->simulateWeek($i);
        }
    }

    public function getStandings()
    {
        return $this->teams->sortByDesc(function($team) {
            return [$team->points, $team->goalDifference(), $team->goals_for];
        })->values();
    }

    public function getChampionshipPredictions($weeksLeft)
    {
        $standings = $this->getStandings();
        $maxPoint = $standings->first()->points;
        $predictions = [];
        foreach ($standings as $team) {
            if ($weeksLeft === 0) {
                $predictions[] = $team->points === $maxPoint ? 100 : 0;
                continue;
            }
            $possibleMax = $team->points + $weeksLeft * self::POINTS_FOR_WIN;
            $isStillPossible = $possibleMax >= $maxPoint;
            if (!$isStillPossible) {
                $predictions[] = 0;
                continue;
            }
            $predictions[] = round(($team->points / ($maxPoint ?: 1)) * 100);
        }
        return $predictions;
    }

    public static function fromArray($arr)
    {
        $teams = collect($arr['teams'])->map(function($t) {
            return Team::fromArray($t);
        });
        $teamMap = $teams->keyBy('name');
        $sim = new LeagueSimulatorService($teams);
        $fixtures = [];
        if (isset($arr['fixtures'])) {
            foreach ($arr['fixtures'] as $week) {
                $weekArr = [];
                foreach ($week as $m) {
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

    public function toArray()
    {
        return [
            'teams' => $this->teams->map(fn($t) => $t->toArray())->toArray(),
            'fixtures' => array_map(fn($w) => array_map(fn($m) => $m->toArray(), $w), $this->fixtures),
        ];
    }
}
