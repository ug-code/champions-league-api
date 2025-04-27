<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    protected $fillable = [
        'name',
        'power',
        'points',
        'played',
        'won',
        'drawn',
        'lost',
        'goals_for',
        'goals_against'
    ];

    protected $attributes = [
        'points' => 0,
        'played' => 0,
        'won' => 0,
        'drawn' => 0,
        'lost' => 0,
        'goals_for' => 0,
        'goals_against' => 0
    ];

    public function homeFixtures()
    {
        return $this->hasMany(Fixture::class, 'home_team_id');
    }

    public function awayFixtures()
    {
        return $this->hasMany(Fixture::class, 'away_team_id');
    }

    public function goalDifference() {
        return $this->goals_for - $this->goals_against;
    }

    public static function fromArray($arr) {
        $team = new static();
        $team->name = $arr['name'];
        $team->power = $arr['power'];
        $team->points = $arr['points'] ?? 0;
        $team->played = $arr['played'] ?? 0;
        $team->won = $arr['won'] ?? 0;
        $team->drawn = $arr['drawn'] ?? 0;
        $team->lost = $arr['lost'] ?? 0;
        $team->goals_for = $arr['goalsFor'] ?? 0;
        $team->goals_against = $arr['goalsAgainst'] ?? 0;
        return $team;
    }

    public function toArray()
    {
        return [
            'name' => $this->name,
            'power' => $this->power,
            'points' => $this->points,
            'played' => $this->played,
            'won' => $this->won,
            'drawn' => $this->drawn,
            'lost' => $this->lost,
            'goalsFor' => $this->goals_for,
            'goalsAgainst' => $this->goals_against,
        ];
    }
} 