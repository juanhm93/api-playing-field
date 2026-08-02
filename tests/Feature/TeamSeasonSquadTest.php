<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\League;
use App\Models\Lineup;
use App\Models\Player;
use App\Models\PlayerTeamSeason;
use App\Models\Position;
use App\Models\Season;
use App\Models\Team;
use App\Models\TeamSeason;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TeamSeasonSquadTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsApiUser(): User
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        return $user;
    }

    /**
     * @return array{team: Team, season: Season, teamSeason: TeamSeason, starter: Player, sub: Player}
     */
    private function seedSquad(): array
    {
        $country = Country::factory()->create();
        $league = League::factory()->create(['country_id' => $country->id]);
        $lineup = Lineup::factory()->create(['name' => '4-3-3']);
        $season = Season::factory()->create();
        $team = Team::factory()->create(['country_id' => $country->id]);
        $position = Position::factory()->create();

        $teamSeason = TeamSeason::query()->create([
            'team_id' => $team->id,
            'season_id' => $season->id,
            'league_id' => $league->id,
            'lineup_id' => $lineup->id,
        ]);

        $starter = Player::factory()->create([
            'position_id' => $position->id,
            'team_id' => $team->id,
        ]);
        $sub = Player::factory()->create([
            'position_id' => $position->id,
            'team_id' => $team->id,
        ]);

        PlayerTeamSeason::query()->create([
            'player_id' => $starter->id,
            'team_season_id' => $teamSeason->id,
            'number' => 10,
            'is_started' => true,
        ]);

        PlayerTeamSeason::query()->create([
            'player_id' => $sub->id,
            'team_season_id' => $teamSeason->id,
            'number' => 12,
            'is_started' => false,
        ]);

        return compact('team', 'season', 'teamSeason', 'starter', 'sub') + [
            'league' => $league,
            'lineup' => $lineup,
        ];
    }

    public function test_squad_default_splits_starters_and_substitutes(): void
    {
        $this->actingAsApiUser();
        $ctx = $this->seedSquad();

        $response = $this->getJson("/api/teams/{$ctx['team']->id}/seasons/{$ctx['season']->id}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.lineup.name', '4-3-3')
            ->assertJsonPath('data.league.id', $ctx['league']->id)
            ->assertJsonCount(1, 'data.starters')
            ->assertJsonCount(1, 'data.substitutes')
            ->assertJsonMissingPath('data.players');

        $this->assertSame($ctx['starter']->id, $response->json('data.starters.0.player.id'));
        $this->assertSame($ctx['sub']->id, $response->json('data.substitutes.0.player.id'));
    }

    public function test_squad_format_clear_returns_all_players_together(): void
    {
        $this->actingAsApiUser();
        $ctx = $this->seedSquad();

        $response = $this->getJson("/api/teams/{$ctx['team']->id}/seasons/{$ctx['season']->id}?format=clear");

        $response->assertOk()
            ->assertJsonCount(2, 'data.players')
            ->assertJsonMissingPath('data.starters')
            ->assertJsonMissingPath('data.substitutes');
    }

    public function test_squad_returns_404_when_team_not_in_season(): void
    {
        $this->actingAsApiUser();
        $team = Team::factory()->create();
        $season = Season::factory()->create();

        $this->getJson("/api/teams/{$team->id}/seasons/{$season->id}")
            ->assertNotFound();
    }
}
