<?php

namespace App\Models;

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