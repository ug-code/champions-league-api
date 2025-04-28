<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddTeamRequest;
use App\Http\Requests\SimulateWeekRequest;
use App\Services\LeagueService;

class LeagueController extends Controller
{
    protected LeagueService $leagueService;

    public function __construct(LeagueService $leagueService)
    {
        $this->leagueService = $leagueService;
    }

    public function addTeam(AddTeamRequest $request)
    {
        $data  = $request->only('name', 'power');
        $teams = $this->leagueService->addTeam($data);

        return response()->json([
            'success' => true,
            'teams'   => $teams,
        ]);
    }

    public function getTeams()
    {
        $teams = $this->leagueService->getTeams();

        return response()->json([
            'teams' => $teams,
        ]);
    }

    public function generateFixtures()
    {
        $fixtures = $this->leagueService->generateFixtures();

        return response()->json([
            'fixtures' => $fixtures,
        ]);
    }

    public function simulateWeek(SimulateWeekRequest $request)
    {
        $week     = (int)$request->input('week');
        $fixtures = $this->leagueService->simulateWeek($week);

        if (is_null($fixtures)) {
            return response()->json(['error' => 'Bu hafta için oynanmamış maç bulunamadı'], 400);
        }

        return response()->json([
            'fixtures' => $fixtures,
        ]);
    }

    public function simulateAll()
    {
        $fixtures = $this->leagueService->simulateAll();

        if (is_null($fixtures)) {
            return response()->json(['error' => 'Oynanmamış maç bulunamadı'], 400);
        }

        return response()->json([
            'fixtures' => $fixtures,
        ]);
    }

    public function getStandings()
    {
        $result = $this->leagueService->getStandings();

        return response()->json([
            'standings'   => $result['standings'],
            'predictions' => $result['predictions'],
        ]);
    }

    public function reset()
    {
        $this->leagueService->reset();

        return response()->json(['success' => true]);
    }
}
