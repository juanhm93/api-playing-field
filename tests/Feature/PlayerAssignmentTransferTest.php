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

class PlayerAssignmentTransferTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsApiUser(): void
    {
        Sanctum::actingAs(User::factory()->create());
    }

    /**
     * @return array<string, mixed>
     */
    private function baseContext(): array
    {
        $country = Country::factory()->create();
        $league = League::factory()->create(['country_id' => $country->id]);
        $lineup = Lineup::factory()->create(['name' => '4-4-2']);
        $season = Season::factory()->create();
        $fromTeam = Team::factory()->create(['country_id' => $country->id]);
        $toTeam = Team::factory()->create(['country_id' => $country->id]);
        $position = Position::factory()->create();
        $player = Player::factory()->create([
            'position_id' => $position->id,
            'team_id' => null,
        ]);

        return compact('country', 'league', 'lineup', 'season', 'fromTeam', 'toTeam', 'position', 'player');
    }

    public function test_can_assign_player_for_the_first_time(): void
    {
        $this->actingAsApiUser();
        $ctx = $this->baseContext();

        $response = $this->postJson('/api/player-team-seasons', [
            'player_id' => $ctx['player']->id,
            'team_id' => $ctx['fromTeam']->id,
            'season_id' => $ctx['season']->id,
            'league_id' => $ctx['league']->id,
            'lineup_id' => $ctx['lineup']->id,
            'number' => 7,
            'is_started' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.number', 7)
            ->assertJsonPath('data.is_started', true);

        $this->assertDatabaseHas('team_season', [
            'team_id' => $ctx['fromTeam']->id,
            'season_id' => $ctx['season']->id,
        ]);

        $this->assertDatabaseHas('player_team_season', [
            'player_id' => $ctx['player']->id,
            'number' => 7,
            'is_started' => 1,
        ]);

        $this->assertDatabaseHas('players', [
            'id' => $ctx['player']->id,
            'team_id' => $ctx['fromTeam']->id,
        ]);
    }

    public function test_assignment_crud_list_show_update_delete(): void
    {
        $this->actingAsApiUser();
        $ctx = $this->baseContext();

        $create = $this->postJson('/api/player-team-seasons', [
            'player_id' => $ctx['player']->id,
            'team_id' => $ctx['fromTeam']->id,
            'season_id' => $ctx['season']->id,
            'league_id' => $ctx['league']->id,
            'lineup_id' => $ctx['lineup']->id,
            'number' => 5,
            'is_started' => false,
        ])->assertCreated();

        $id = $create->json('data.id');

        $this->getJson('/api/player-team-seasons?team_id='.$ctx['fromTeam']->id)
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->getJson("/api/player-team-seasons/{$id}")
            ->assertOk()
            ->assertJsonPath('data.id', $id);

        $this->putJson("/api/player-team-seasons/{$id}", [
            'number' => 11,
            'is_started' => true,
        ])->assertOk()
            ->assertJsonPath('data.number', 11)
            ->assertJsonPath('data.is_started', true);

        $this->deleteJson("/api/player-team-seasons/{$id}")
            ->assertOk();

        $this->assertDatabaseMissing('player_team_season', ['id' => $id]);
    }

    public function test_can_transfer_player_between_teams(): void
    {
        $this->actingAsApiUser();
        $ctx = $this->baseContext();

        $originSeason = TeamSeason::query()->create([
            'team_id' => $ctx['fromTeam']->id,
            'season_id' => $ctx['season']->id,
            'league_id' => $ctx['league']->id,
            'lineup_id' => $ctx['lineup']->id,
        ]);

        PlayerTeamSeason::query()->create([
            'player_id' => $ctx['player']->id,
            'team_season_id' => $originSeason->id,
            'number' => 9,
            'is_started' => true,
        ]);

        $ctx['player']->update(['team_id' => $ctx['fromTeam']->id]);

        $response = $this->postJson('/api/player-transfers', [
            'player_id' => $ctx['player']->id,
            'from_team_id' => $ctx['fromTeam']->id,
            'to_team_id' => $ctx['toTeam']->id,
            'season_id' => $ctx['season']->id,
            'league_id' => $ctx['league']->id,
            'lineup_id' => $ctx['lineup']->id,
            'number' => 19,
            'is_started' => false,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.number', 19)
            ->assertJsonPath('data.is_started', false)
            ->assertJsonPath('data.team_season.team_id', $ctx['toTeam']->id);

        $this->assertDatabaseMissing('player_team_season', [
            'player_id' => $ctx['player']->id,
            'team_season_id' => $originSeason->id,
        ]);

        $this->assertDatabaseHas('players', [
            'id' => $ctx['player']->id,
            'team_id' => $ctx['toTeam']->id,
        ]);
    }

    public function test_cannot_assign_player_twice_in_same_season(): void
    {
        $this->actingAsApiUser();
        $ctx = $this->baseContext();

        $payload = [
            'player_id' => $ctx['player']->id,
            'team_id' => $ctx['fromTeam']->id,
            'season_id' => $ctx['season']->id,
            'league_id' => $ctx['league']->id,
            'lineup_id' => $ctx['lineup']->id,
            'number' => 1,
        ];

        $this->postJson('/api/player-team-seasons', $payload)->assertCreated();

        $this->postJson('/api/player-team-seasons', array_merge($payload, [
            'team_id' => $ctx['toTeam']->id,
        ]))->assertStatus(422);
    }
}
