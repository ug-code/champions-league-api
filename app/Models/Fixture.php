<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fixture extends Model
{
    protected $fillable = [
        'home_team_id',
        'away_team_id',
        'home_goals',
        'away_goals',
        'played'
    ];

    protected $casts = [
        'played' => 'boolean'
    ];

    public function homeTeam()
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    public function awayTeam()
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }
} 