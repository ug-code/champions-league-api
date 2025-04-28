<?php

namespace App\Repositories;

use App\Models\Team;
use Illuminate\Support\Collection;

class TeamRepository
{
    public function all(): Collection
    {
        return Team::all();
    }

    public function create(array $data): Team
    {
        return Team::create($data);
    }

    public function find(int $id): ?Team
    {
        return Team::find($id);
    }

    public function update(Team $team, array $data): bool
    {
        return $team->update($data);
    }

    public function delete(Team $team): bool
    {
        return $team->delete();
    }

    public function resetStats(): void
    {
        Team::query()->update([
            'points'        => 0,
            'played'        => 0,
            'won'           => 0,
            'drawn'         => 0,
            'lost'          => 0,
            'goals_for'     => 0,
            'goals_against' => 0
        ]);
    }

    public function getStandings(): Collection
    {
        return Team::orderBy('points', 'desc')
            ->orderByRaw('goals_for - goals_against DESC')
            ->orderBy('goals_for', 'desc')
            ->get();
    }

    public function truncate()
    {
        Team::truncate();
    }
}
