# Equipo por temporada, plantilla y transferencias

Documentación de planificación e implementación para el endpoint de equipo por temporada (con plantilla) y el CRUD de asignación / transferencia de jugadores.

## Contexto

El dominio ya modela la relación temporada–equipo–jugador así:

- `team_season`: equipo inscrito en una temporada (incluye `league_id` y `lineup_id` / formación).
- `player_team_season`: jugador asignado a ese `team_season` (`number`, `is_started`).
- `players.team_id`: club actual del jugador (no es historial por temporada).
- `lineups.name`: formación (ej. `4-4-2`).

Antes no existían endpoints de plantilla por temporada ni de asignación/transferencia. La lógica vivía (o iba a vivir) en controladores; se adopta **Controller → Service → Repository**.

## Objetivos

1. **GET equipo + temporada**: devolver el equipo con datos de temporada (liga, formación) y jugadores.
   - Default: titulares (`starters`) y suplentes (`substitutes`) en arrays separados (`is_started`).
   - `?format=clear`: un solo array `players` con todos.
2. **CRUD de asignaciones**: alta inicial de un jugador a un equipo en una temporada.
3. **Transferencias**: mover un jugador de un equipo a otro en la misma temporada (cierra la asignación origen y crea la destino; actualiza `players.team_id`).

## Arquitectura

```
HTTP Request
    → Controller (validación + respuesta JSON)
        → Service (reglas de negocio / transacciones)
            → Repository (acceso a datos Eloquent)
```

| Capa | Responsabilidad |
|------|-----------------|
| Controller | Validar request, llamar al service, devolver `{ success, message, data }` |
| Service | Orquestar findOrCreate de `team_season`, split starters/subs, transferencias |
| Repository | Queries y persistencia |

## Endpoints

### Plantilla de equipo por temporada

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/api/teams/{team}/seasons/{season}` | Equipo + plantilla de la temporada |

Query:

- `format` (opcional): omitido o distinto de `clear` → `starters` + `substitutes`.
- `format=clear` → `players` (todos juntos).

Respuesta (default):

```json
{
  "success": true,
  "message": "Team season squad fetched successfully",
  "data": {
    "team": { "...": "..." },
    "season": { "...": "..." },
    "league": { "...": "..." },
    "lineup": { "id": 1, "name": "4-4-2" },
    "team_season_id": 10,
    "starters": [ { "assignment_id": 1, "number": 10, "is_started": true, "player": {} } ],
    "substitutes": [ { "assignment_id": 2, "number": 12, "is_started": false, "player": {} } ]
  }
}
```

Con `format=clear`, en lugar de `starters`/`substitutes` se expone `players`.

### Asignaciones (`player-team-seasons`)

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/api/player-team-seasons` | Listar (filtros: `player_id`, `team_id`, `season_id`) |
| `POST` | `/api/player-team-seasons` | Primera asignación a un equipo/temporada |
| `GET` | `/api/player-team-seasons/{id}` | Detalle |
| `PUT/PATCH` | `/api/player-team-seasons/{id}` | Actualizar `number` / `is_started` |
| `DELETE` | `/api/player-team-seasons/{id}` | Quitar de la plantilla |

Body de creación:

```json
{
  "player_id": 1,
  "team_id": 2,
  "season_id": 3,
  "league_id": 1,
  "lineup_id": 1,
  "number": 10,
  "is_started": true
}
```

Notas:

- Si no existe `team_season` para `(team_id, season_id, league_id)`, se crea (requiere `league_id` y `lineup_id`).
- Si ya existe asignación del jugador a ese `team_season`, se responde conflicto.
- Al asignar se actualiza `players.team_id`.

### Transferencias

| Método | Ruta | Descripción |
|--------|------|-------------|
| `POST` | `/api/player-transfers` | Mover jugador de un equipo a otro en la temporada |

Body:

```json
{
  "player_id": 1,
  "from_team_id": 2,
  "to_team_id": 5,
  "season_id": 3,
  "league_id": 1,
  "lineup_id": 1,
  "number": 9,
  "is_started": false
}
```

Flujo:

1. Validar que el jugador esté en `from_team` para esa temporada.
2. Eliminar (o liberar) la asignación origen.
3. Crear asignación destino (findOrCreate `team_season` destino).
4. Actualizar `players.team_id` al equipo destino.
5. Todo en transacción.

## Plan de implementación (pasos)

1. Completar modelos: `TeamSeason` (`league_id`, `lineup_id`, relaciones), `Lineup`, factories vacías.
2. Crear contratos e implementaciones de repositorio.
3. Crear servicios `TeamSeasonService` y `PlayerAssignmentService`.
4. Extender `TeamController` (o acción dedicada) para GET plantilla; nuevo controller de asignaciones/transferencias.
5. Registrar bindings en `AppServiceProvider` y rutas en `routes/api.php`.
6. Tests feature (plantilla default/`clear`, assign, transfer, update, delete).
7. Documentar acciones tomadas en este archivo.

## Acciones tomadas

- [x] Rama `cursor/team-season-players-transfers-56c5`
- [x] Documentación de planificación (`docs/team-season-squad-transfers.md`)
- [x] Modelos y factories alineados con el esquema
- [x] Repositorios + servicios + bindings DI
- [x] Endpoint GET `/teams/{team}/seasons/{season}` con `format=clear`
- [x] CRUD `/player-team-seasons` + `POST /player-transfers`
- [x] Tests feature
- [x] Commit / push / PR

## Archivos principales

```
app/Repositories/Contracts/TeamSeasonRepositoryInterface.php
app/Repositories/Contracts/PlayerTeamSeasonRepositoryInterface.php
app/Repositories/TeamSeasonRepository.php
app/Repositories/PlayerTeamSeasonRepository.php
app/Services/TeamSeasonService.php
app/Services/PlayerAssignmentService.php
app/Http/Controllers/Api/TeamController.php          (showBySeason)
app/Http/Controllers/Api/PlayerTeamSeasonController.php
app/Http/Controllers/Api/PlayerTransferController.php
docs/team-season-squad-transfers.md
tests/Feature/TeamSeasonSquadTest.php
tests/Feature/PlayerAssignmentTransferTest.php
```
