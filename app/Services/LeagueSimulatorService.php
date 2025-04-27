<?php

namespace App\Services;

use App\Models\Team;
use App\Models\MatchGame;

class LeagueSimulatorService {
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
        $teams = array_map(fn($t) => Team::fromArray($t), $arr['teams']);
        $teamMap = [];
        foreach ($teams as $t) {
            $teamMap[$t->name] = $t;
        }
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

    public function toArray() {
        return [
            'teams' => array_map(fn($t) => $t->toArray(), $this->teams),
            'fixtures' => array_map(fn($w) => array_map(fn($m) => $m->toArray(), $w), $this->fixtures),
        ];
    }
} 