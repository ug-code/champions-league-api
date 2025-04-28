<?php

namespace Tests\Unit;

use App\Services\LeagueService;
use App\Repositories\TeamRepository;
use App\Repositories\FixtureRepository;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use Mockery;
use Illuminate\Support\Facades\DB; // <-- BU SATIRI EKLE

class LeagueServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testAddTeam()
    {
        $teamRepoMock = Mockery::mock(TeamRepository::class);
        $fixtureRepoMock = Mockery::mock(FixtureRepository::class);

        $teamRepoMock->shouldReceive('create')
            ->once()
            ->with([
                'name' => 'Test Team',
                'power' => 80,
            ]);

        $teamRepoMock->shouldReceive('all')
            ->once()
            ->andReturn(new Collection([
                (object)['id' => 1, 'name' => 'Test Team', 'power' => 80]
            ]));

        $service = new LeagueService($teamRepoMock, $fixtureRepoMock);

        $result = $service->addTeam(['name' => 'Test Team', 'power' => 80]);

        $this->assertCount(1, $result);
        $this->assertEquals('Test Team', $result->first()->name);
    }

    public function testGetTeams()
    {
        $teamRepoMock = Mockery::mock(TeamRepository::class);
        $fixtureRepoMock = Mockery::mock(FixtureRepository::class);

        $teams = new Collection([
            (object)['id' => 1, 'name' => 'Team A'],
            (object)['id' => 2, 'name' => 'Team B'],
        ]);

        $teamRepoMock->shouldReceive('all')
            ->once()
            ->andReturn($teams);

        $service = new LeagueService($teamRepoMock, $fixtureRepoMock);

        $result = $service->getTeams();

        $this->assertCount(2, $result);
        $this->assertEquals('Team A', $result[0]->name);
    }

    public function testReset()
    {
        $teamRepoMock = Mockery::mock(TeamRepository::class);
        $fixtureRepoMock = Mockery::mock(FixtureRepository::class);

        $fixtureRepoMock->shouldReceive('truncate')->once();
        $teamRepoMock->shouldReceive('truncate')->once();

        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) {
                return $callback(); // transaction içindeki işlemleri çalıştır
            });

        $service = new LeagueService($teamRepoMock, $fixtureRepoMock);

        $service->reset();

        $this->assertTrue(true);
    }
}
