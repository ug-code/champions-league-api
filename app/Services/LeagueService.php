<?php

namespace App\Services;

use App\Repositories\FixtureRepository;
use App\Repositories\TeamRepository;
use App\Models\MatchGame;
use Illuminate\Support\Facades\DB;

class LeagueService
{
    protected TeamRepository $teamRepository;
    protected FixtureRepository $fixtureRepository;

    public function __construct(TeamRepository $teamRepository, FixtureRepository $fixtureRepository)
    {
        $this->teamRepository = $teamRepository;
        $this->fixtureRepository = $fixtureRepository;
    }

    public function addTeam(array $data)
    {
        $this->teamRepository->create([
            'name'  => $data['name'],
            'power' => (int) $data['power'],
        ]);

        return $this->teamRepository->all();
    }

    public function getTeams()
    {
        return $this->teamRepository->all();
    }

    public function generateFixtures()
    {
        $this->fixtureRepository->truncate();
        $this->teamRepository->resetStats();

        $teams = $this->teamRepository->all();
        $sim = new LeagueSimulatorService($teams);
        $sim->generateFixtures();

        DB::transaction(function () use ($sim) {
            $bulkData = [];
            foreach ($sim->fixtures as $weekIndex => $week) {
                foreach ($week as $match) {
                    $bulkData[] = [
                        'week'         => $weekIndex + 1,
                        'home_team_id' => $match->home->id,
                        'away_team_id' => $match->away->id,
                        'home_goals'   => $match->homeGoals,
                        'away_goals'   => $match->awayGoals,
                        'played'       => $match->played,
                        'created_at'   => now(),
                        'updated_at'   => now(),
                    ];
                }
            }
            $this->fixtureRepository->bulkInsert($bulkData);
        });

        return $this->prepareFixturesResponse($teams);
    }

    public function simulateWeek(int $week)
    {
        $fixtures = $this->fixtureRepository->getUnplayedByWeek($week);

        if ($fixtures->isEmpty()) {
            return null;
        }

        $teams = $this->teamRepository->all();
        $sim = new LeagueSimulatorService($teams);

        foreach ($fixtures as $fixture) {
            $homeTeam = $teams->firstWhere('id', $fixture->home_team_id);
            $awayTeam = $teams->firstWhere('id', $fixture->away_team_id);

            $match = new MatchGame($homeTeam, $awayTeam);
            $sim->simulateMatch($match);

            $this->fixtureRepository->updateMatch($fixture->id, [
                'home_goals' => $match->homeGoals,
                'away_goals' => $match->awayGoals,
                'played'     => true,
                'updated_at' => now(),
            ]);

            $homeTeam->save();
            $awayTeam->save();
        }

        return $this->prepareFixturesResponse($teams);
    }

    public function simulateAll()
    {
        $fixtures = $this->fixtureRepository->getAllUnplayed();

        if ($fixtures->isEmpty()) {
            return null;
        }

        $teams = $this->teamRepository->all();
        $sim = new LeagueSimulatorService($teams);

        foreach ($fixtures as $fixture) {
            $homeTeam = $teams->firstWhere('id', $fixture->home_team_id);
            $awayTeam = $teams->firstWhere('id', $fixture->away_team_id);

            $match = new MatchGame($homeTeam, $awayTeam);
            $sim->simulateMatch($match);

            $this->fixtureRepository->updateMatch($fixture->id, [
                'home_goals' => $match->homeGoals,
                'away_goals' => $match->awayGoals,
                'played'     => true,
                'updated_at' => now(),
            ]);

            $homeTeam->save();
            $awayTeam->save();
        }

        return $this->prepareFixturesResponse($teams);
    }

    public function getStandings()
    {
        $teams = $this->teamRepository->getStandings();
        $weeksLeft = $this->fixtureRepository->countUnplayedMatches() / 2;

        $sim = new LeagueSimulatorService($teams);
        $predictions = $sim->getChampionshipPredictions($weeksLeft);

        return [
            'standings'   => $teams,
            'predictions' => $predictions,
        ];
    }

    public function reset()
    {
        DB::transaction(function () {
            $this->fixtureRepository->truncate();
            $this->teamRepository->truncate();
        });
    }

    private function prepareFixturesResponse($teams)
    {
        $fixturesGrouped = $this->fixtureRepository->getAllGroupedByWeek();

        return $fixturesGrouped->map(function ($weekFixtures, $weekNumber) use ($teams) {
            return [
                'week'    => $weekNumber,
                'matches' => $weekFixtures->map(function ($fixture) use ($teams) {
                    $homeTeam = $teams->firstWhere('id', $fixture->home_team_id);
                    $awayTeam = $teams->firstWhere('id', $fixture->away_team_id);

                    return [
                        'home'      => [
                            'name'         => $homeTeam->name,
                            'power'        => $homeTeam->power,
                            'points'       => $homeTeam->points,
                            'played'       => $homeTeam->played,
                            'won'          => $homeTeam->won,
                            'drawn'        => $homeTeam->drawn,
                            'lost'         => $homeTeam->lost,
                            'goalsFor'     => $homeTeam->goals_for,
                            'goalsAgainst' => $homeTeam->goals_against,
                        ],
                        'away'      => [
                            'name'         => $awayTeam->name,
                            'power'        => $awayTeam->power,
                            'points'       => $awayTeam->points,
                            'played'       => $awayTeam->played,
                            'won'          => $awayTeam->won,
                            'drawn'        => $awayTeam->drawn,
                            'lost'         => $awayTeam->lost,
                            'goalsFor'     => $awayTeam->goals_for,
                            'goalsAgainst' => $awayTeam->goals_against,
                        ],
                        'homeGoals' => $fixture->home_goals,
                        'awayGoals' => $fixture->away_goals,
                        'played'    => $fixture->played,
                    ];
                })->values()->all()
            ];
        })->values()->all();
    }
}
