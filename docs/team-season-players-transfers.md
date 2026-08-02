# Equipo por temporada, plantilla y transferencias

Documentación de planificación y acciones para el endpoint de equipo por temporada (con jugadores) y el CRUD de asignación / transferencias de jugadores.

## Objetivo

1. **Consultar un equipo en una temporada** pasando `team_id` + `season_id`, devolviendo la plantilla y datos relacionados (formación/lineup, liga, etc.).
2. Por defecto separar **titulares** y **suplentes** en dos arrays.
3. Con `format=clear` devolver todos los jugadores en un solo array.
4. **CRUD de asignaciones** (`player_team_season`): primera asignación y transferencia entre equipos.
5. Arquitectura: **Controller → Service → Repository → Model** (lógica fuera del controlador).

## Contexto del dominio (estado previo)

| Concepto | Tabla / modelo | Notas |
|---|---|---|
| Equipo | `teams` / `Team` | Club base |
| Temporada | `seasons` / `Season` | |
| Inscripción equipo-temporada | `team_season` / `TeamSeason` | Incluye `league_id`, `lineup_id` |
| Plantilla estacional | `player_team_season` / `PlayerTeamSeason` | `number`, `is_started` |
| Formación | `lineups` / `Lineup` | Ej. `4-4-2` |
| Club actual del jugador | `players.team_id` | Paralelo a la plantilla por temporada |

No existía tabla de historial de transferencias. Las transferencias se modelan como operaciones sobre `player_team_season` (mover asignación + actualizar `players.team_id`).

## Plan paso a paso

### Fase 1 — Modelos y relaciones

- Completar `TeamSeason`: `league_id`, `lineup_id` en fillable; relaciones `league()`, `lineup()`, y carga de jugadores vía `playerTeamSeasons`.
- Completar `Lineup`: fillable `name`.
- Asegurar relaciones útiles en `Player` / `Team` para consultas por temporada.

### Fase 2 — Capas Repository + Service

Estructura:

```
app/Repositories/Contracts/
app/Repositories/
app/Services/
```

- `TeamSeasonRepository`: resolver `TeamSeason` por team + season (+ league opcional), eager load de relaciones.
- `PlayerTeamSeasonRepository`: CRUD de asignaciones, búsqueda por jugador/temporada.
- `TeamSeasonService`: arma la respuesta (default vs `format=clear`).
- `PlayerAssignmentService`: assign (primera vez), update, unassign, transfer.

### Fase 3 — Endpoints API

**Equipo por temporada**

```
GET /api/teams/{team}/seasons/{season}
GET /api/teams/{team}/seasons/{season}?format=clear
GET /api/teams/{team}/seasons/{season}?league_id={id}
```

Respuesta default:

```json
{
  "success": true,
  "message": "...",
  "data": {
    "team": { "...": "..." },
    "season": { "...": "..." },
    "league": { "...": "..." },
    "lineup": { "id": 1, "name": "4-4-2" },
    "team_season_id": 1,
    "starters": [ { "assignment_id": 1, "number": 9, "is_started": true, "player": {} } ],
    "substitutes": [ { "assignment_id": 2, "number": 12, "is_started": false, "player": {} } ]
  }
}
```

Respuesta `format=clear`:

```json
{
  "data": {
    "team": {},
    "season": {},
    "league": {},
    "lineup": {},
    "team_season_id": 1,
    "players": [ { "assignment_id": 1, "number": 9, "is_started": true, "player": {} } ]
  }
}
```

**Asignaciones / transferencias**

| Método | Ruta | Acción |
|---|---|---|
| GET | `/api/player-team-seasons` | Listar (filtros: player_id, team_season_id, season_id, team_id) |
| POST | `/api/player-team-seasons` | Primera asignación a un `team_season` |
| GET | `/api/player-team-seasons/{id}` | Detalle |
| PUT/PATCH | `/api/player-team-seasons/{id}` | Actualizar número / titularidad |
| DELETE | `/api/player-team-seasons/{id}` | Quitar de la plantilla |
| POST | `/api/player-team-seasons/transfer` | Mover jugador a otro equipo (misma u otra temporada) |

### Fase 4 — Rutas, binding DI y tests

- Registrar rutas bajo `auth:sanctum`.
- Bind de interfaces de repositorio en `AppServiceProvider`.
- Tests feature con SQLite in-memory.

## Decisiones de diseño

1. **`is_started`** (columna existente) = titular (`true`) / suplente (`false`).
2. **`format=clear`**: solo cambia el shape del JSON; no cambia la query de datos.
3. **`league_id` opcional**: la unique de `team_season` es `(team_id, season_id, league_id)`. Si hay más de un registro y no se pasa `league_id`, se responde error de ambigüedad.
4. **Primera asignación**: crea `player_team_season` y setea `players.team_id`.
5. **Transferencia**: elimina (o reubica) la asignación origen, crea la destino, actualiza `players.team_id`. No hay tabla de historial en esta iteración.
6. Controllers solo validan HTTP y delegan al Service.

## Acciones tomadas

- [x] Branch `cursor/team-season-players-transfers-293a`
- [x] Documentación en `docs/team-season-players-transfers.md`
- [x] Modelos `TeamSeason` (league/lineup + fillable) y `Lineup` completados
- [x] Fix `Player::$fillable` (el `#[Fillable]` estaba mal aplicado al método, no a la clase)
- [x] Repositories: `TeamSeasonRepository`, `PlayerTeamSeasonRepository` + contracts
- [x] Services: `TeamSeasonService`, `PlayerAssignmentService`
- [x] Controllers: `TeamSeasonController`, `PlayerTeamSeasonController`
- [x] Rutas API bajo `auth:sanctum`
- [x] Binding DI en `AppServiceProvider`
- [x] Factories `Season` / `Lineup` / `League` para tests
- [x] Tests feature `TeamSeasonSquadAndTransfersTest` (6 passing)
- [x] Commit / push / PR

## Cómo probar

```bash
# Equipo por temporada (titulares / suplentes)
GET /api/teams/{teamId}/seasons/{seasonId}

# Plantilla plana
GET /api/teams/{teamId}/seasons/{seasonId}?format=clear

# Asignar jugador
POST /api/player-team-seasons
{
  "player_id": 1,
  "team_season_id": 1,
  "number": 10,
  "is_started": true
}

# Transferir
POST /api/player-team-seasons/transfer
{
  "player_id": 1,
  "from_team_season_id": 1,
  "to_team_season_id": 2,
  "number": 7,
  "is_started": false
}
```
