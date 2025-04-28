<?php

namespace App\Repositories;

use App\Models\Fixture;
use Illuminate\Support\Collection;

class FixtureRepository
{
    public function truncate()
    {
        Fixture::truncate();
    }

    public function insert(array $data)
    {
        Fixture::create($data);
    }

    public function bulkInsert(array $fixtures)
    {
        Fixture::insert($fixtures);
    }

    public function getAllGroupedByWeek(): Collection
    {
        return Fixture::orderBy('week')
            ->get()
            ->groupBy('week');
    }

    public function getUnplayedByWeek(int $week): Collection
    {
        return Fixture::where('week', $week)
            ->where('played', false)
            ->get();
    }

    public function getAllUnplayed(): Collection
    {
        return Fixture::where('played', false)
            ->get();
    }

    public function updateMatch(int $id, array $data)
    {
        Fixture::where('id', $id)->update($data);
    }

    public function countUnplayedMatches(): int
    {
        return Fixture::where('played', false)->count();
    }
}
