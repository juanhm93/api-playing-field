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

class TeamSeasonSquadAndTransfersTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Team $teamA;

    private Team $teamB;

    private Season $season;

    private League $league;

    private Lineup $lineup;

    private TeamSeason $teamSeasonA;

    private TeamSeason $teamSeasonB;

    private Position $position;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);

        $country = Country::factory()->create();
        $this->league = League::factory()->create(['country_id' => $country->id]);
        $this->lineup = Lineup::factory()->create(['name' => '4-3-3']);
        $this->season = Season::factory()->create();
        $this->position = Position::factory()->create(['name' => 'FW']);

        $this->teamA = Team::factory()->create(['country_id' => $country->id, 'name' => 'Team A']);
        $this->teamB = Team::factory()->create(['country_id' => $country->id, 'name' => 'Team B']);

        $this->teamSeasonA = TeamSeason::query()->create([
            'team_id' => $this->teamA->id,
            'season_id' => $this->season->id,
            'league_id' => $this->league->id,
            'lineup_id' => $this->lineup->id,
        ]);

        $this->teamSeasonB = TeamSeason::query()->create([
            'team_id' => $this->teamB->id,
            'season_id' => $this->season->id,
            'league_id' => $this->league->id,
            'lineup_id' => $this->lineup->id,
        ]);
    }

    public function test_squad_default_splits_starters_and_substitutes(): void
    {
        $starter = Player::factory()->create(['position_id' => $this->position->id, 'team_id' => $this->teamA->id]);
        $sub = Player::factory()->create(['position_id' => $this->position->id, 'team_id' => $this->teamA->id]);

        PlayerTeamSeason::query()->create([
            'player_id' => $starter->id,
            'team_season_id' => $this->teamSeasonA->id,
            'number' => 9,
            'is_started' => true,
        ]);

        PlayerTeamSeason::query()->create([
            'player_id' => $sub->id,
            'team_season_id' => $this->teamSeasonA->id,
            'number' => 12,
            'is_started' => false,
        ]);

        $response = $this->getJson("/api/teams/{$this->teamA->id}/seasons/{$this->season->id}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.lineup.name', '4-3-3')
            ->assertJsonPath('data.team.id', $this->teamA->id)
            ->assertJsonCount(1, 'data.starters')
            ->assertJsonCount(1, 'data.substitutes')
            ->assertJsonMissingPath('data.players');

        $this->assertSame(9, $response->json('data.starters.0.number'));
        $this->assertSame(12, $response->json('data.substitutes.0.number'));
    }

    public function test_squad_format_clear_returns_flat_players_array(): void
    {
        $starter = Player::factory()->create(['position_id' => $this->position->id]);
        $sub = Player::factory()->create(['position_id' => $this->position->id]);

        PlayerTeamSeason::query()->create([
            'player_id' => $starter->id,
            'team_season_id' => $this->teamSeasonA->id,
            'number' => 1,
            'is_started' => true,
        ]);
        PlayerTeamSeason::query()->create([
            'player_id' => $sub->id,
            'team_season_id' => $this->teamSeasonA->id,
            'number' => 2,
            'is_started' => false,
        ]);

        $response = $this->getJson("/api/teams/{$this->teamA->id}/seasons/{$this->season->id}?format=clear");

        $response->assertOk()
            ->assertJsonCount(2, 'data.players')
            ->assertJsonMissingPath('data.starters')
            ->assertJsonMissingPath('data.substitutes');
    }

    public function test_assign_player_first_time(): void
    {
        $player = Player::factory()->create(['position_id' => $this->position->id, 'team_id' => null]);

        $response = $this->postJson('/api/player-team-seasons', [
            'player_id' => $player->id,
            'team_season_id' => $this->teamSeasonA->id,
            'number' => 10,
            'is_started' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.number', 10)
            ->assertJsonPath('data.is_started', true);

        $this->assertDatabaseHas('player_team_season', [
            'player_id' => $player->id,
            'team_season_id' => $this->teamSeasonA->id,
            'number' => 10,
        ]);

        $this->assertSame($this->teamA->id, $player->fresh()->team_id);
    }

    public function test_transfer_moves_player_between_teams(): void
    {
        $player = Player::factory()->create([
            'position_id' => $this->position->id,
            'team_id' => $this->teamA->id,
        ]);

        $assignment = PlayerTeamSeason::query()->create([
            'player_id' => $player->id,
            'team_season_id' => $this->teamSeasonA->id,
            'number' => 7,
            'is_started' => true,
        ]);

        $response = $this->postJson('/api/player-team-seasons/transfer', [
            'player_id' => $player->id,
            'from_team_season_id' => $this->teamSeasonA->id,
            'to_team_season_id' => $this->teamSeasonB->id,
            'number' => 11,
            'is_started' => false,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.team_season_id', $this->teamSeasonB->id)
            ->assertJsonPath('data.number', 11)
            ->assertJsonPath('data.is_started', false);

        $this->assertDatabaseMissing('player_team_season', ['id' => $assignment->id]);
        $this->assertDatabaseHas('player_team_season', [
            'player_id' => $player->id,
            'team_season_id' => $this->teamSeasonB->id,
            'number' => 11,
        ]);
        $this->assertSame($this->teamB->id, $player->fresh()->team_id);
    }

    public function test_update_and_delete_assignment(): void
    {
        $player = Player::factory()->create(['position_id' => $this->position->id, 'team_id' => $this->teamA->id]);

        $assignment = PlayerTeamSeason::query()->create([
            'player_id' => $player->id,
            'team_season_id' => $this->teamSeasonA->id,
            'number' => 5,
            'is_started' => false,
        ]);

        $this->putJson("/api/player-team-seasons/{$assignment->id}", [
            'number' => 8,
            'is_started' => true,
        ])->assertOk()
            ->assertJsonPath('data.number', 8)
            ->assertJsonPath('data.is_started', true);

        $this->deleteJson("/api/player-team-seasons/{$assignment->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('player_team_season', ['id' => $assignment->id]);
        $this->assertNull($player->fresh()->team_id);
    }

    public function test_duplicate_assignment_is_rejected(): void
    {
        $player = Player::factory()->create(['position_id' => $this->position->id]);

        PlayerTeamSeason::query()->create([
            'player_id' => $player->id,
            'team_season_id' => $this->teamSeasonA->id,
            'number' => 3,
            'is_started' => true,
        ]);

        $this->postJson('/api/player-team-seasons', [
            'player_id' => $player->id,
            'team_season_id' => $this->teamSeasonA->id,
            'number' => 4,
        ])->assertStatus(422);
    }
}
