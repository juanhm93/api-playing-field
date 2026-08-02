# Equipo por temporada, plantilla y transferencias

Documento de planificación y registro de acciones para los endpoints de consulta de equipo por temporada, asignación de jugadores y CRUD de transferencias.

**Fecha:** 2026-08-02  
**Rama de trabajo:** `cursor/equipo-temporada-transferencias-1b35`  
**Estado:** En implementación — transferencias **pendiente** (sección 7)

---

## 1. Objetivo

Construir una API REST que permita:

1. **Consultar un equipo en una temporada** pasando `team_id` y `season_id`, devolviendo la plantilla con titulares y suplentes separados (formato por defecto) o todos juntos (`format=clear`).
2. **Incluir datos relacionados** del equipo en esa temporada: formación (`lineup`), liga, país, temporada, etc.
3. **Gestionar la asignación de jugadores** a un equipo por temporada (alta inicial en plantilla).
4. ~~**CRUD completo de transferencias**~~ → **PENDIENTE** (ver sección 7). No se implementa en esta iteración.

Toda la lógica de negocio debe vivir en **servicios** que consumen **repositorios**, inyectados en controladores delgados.

---

## 2. Estado actual del proyecto

### Lo que ya existe

| Componente | Ubicación | Estado |
|------------|-----------|--------|
| Modelo `Team` | `app/Models/Team.php` | Relaciones definidas; `league()` y `division()` no coinciden con el esquema |
| Modelo `TeamSeason` | `app/Models/TeamSeason.php` | Falta `league_id`, `lineup_id` en fillable y relaciones |
| Modelo `PlayerTeamSeason` | `app/Models/PlayerTeamSeason.php` | Completo para roster por temporada |
| Modelo `Lineup` | `app/Models/Lineup.php` | Stub vacío |
| Tabla `team_season` | migración `2026_04_04_100050` | `team_id`, `season_id`, `league_id`, `lineup_id` |
| Tabla `player_team_season` | migración `2026_04_04_100060` | `number`, `is_started` (titular/suplente) |
| `TeamController` | `app/Http/Controllers/Api/TeamController.php` | CRUD básico; usa `players.team_id`, no `player_team_season` |
| Repositorio base | `app/Repositories/BaseApiRepository.php` | Esqueleto incompleto, no reutilizable tal cual |

### Lo que no existe

- Capa `app/Services/`
- Repositorios por entidad (`TeamSeasonRepository`, `TransferRepository`, etc.)
- Tabla/modelo `transfers`
- Endpoint de equipo por temporada
- Endpoints de asignación de jugador a plantilla
- Endpoints de transferencias

### Decisión de dominio

El esquema ya contempla un modelo **season-aware** (roster por temporada). La implementación debe usar:

- `team_season` como vínculo equipo ↔ temporada ↔ liga ↔ formación
- `player_team_season` como membresía del jugador en esa plantilla
- `players.team_id` como equipo **actual** (sincronizado al asignar/transferir)
- Nueva tabla `transfers` como historial auditable de movimientos

---

## 3. Arquitectura propuesta

```
Request → Controller (validación + respuesta JSON)
              ↓
          Service (lógica de negocio, transacciones)
              ↓
          Repository (consultas Eloquent, persistencia)
              ↓
          Models / DB
```

### Estructura de archivos a crear

```
app/
├── Http/Controllers/Api/
│   ├── TeamSeasonController.php      # GET equipo por temporada
│   ├── PlayerAssignmentController.php # CRUD asignación a plantilla
│   └── TransferController.php         # CRUD transferencias
├── Services/
│   ├── TeamSeasonService.php
│   ├── PlayerAssignmentService.php
│   └── TransferService.php
├── Repositories/
│   ├── Contracts/
│   │   ├── TeamSeasonRepositoryInterface.php
│   │   ├── PlayerTeamSeasonRepositoryInterface.php
│   │   └── TransferRepositoryInterface.php
│   ├── TeamSeasonRepository.php
│   ├── PlayerTeamSeasonRepository.php
│   └── TransferRepository.php
└── Models/
    └── Transfer.php                   # nuevo
```

### Registro en `AppServiceProvider`

```php
$this->app->bind(TeamSeasonRepositoryInterface::class, TeamSeasonRepository::class);
$this->app->bind(PlayerTeamSeasonRepositoryInterface::class, PlayerTeamSeasonRepository::class);
$this->app->bind(TransferRepositoryInterface::class, TransferRepository::class);
```

Los servicios se resuelven por constructor en los controladores (Laravel auto-wiring).

---

## 4. Correcciones previas a los modelos

Antes de implementar endpoints, alinear modelos con el esquema:

### 4.1 `TeamSeason`

- Agregar a `$fillable`: `league_id`, `lineup_id`
- Relaciones: `league()`, `lineup()`, `players()` vía `hasManyThrough` o accessor en servicio

### 4.2 `Lineup`

- `$fillable = ['name']`
- Relación `teamSeasons(): HasMany`

### 4.3 `Team`

- Eliminar o corregir `league()` y `division()` (no existen en tabla `teams`)
- Opcional: `currentLeague()` vía último `team_season` si se necesita en otro contexto

### 4.4 Migración `transfers`

```php
Schema::create('transfers', function (Blueprint $table) {
    $table->id();
    $table->foreignId('player_id')->constrained()->cascadeOnDelete();
    $table->foreignId('from_team_id')->nullable()->constrained('teams')->nullOnDelete();
    $table->foreignId('to_team_id')->constrained('teams')->cascadeOnDelete();
    $table->foreignId('season_id')->constrained()->cascadeOnDelete();
    $table->foreignId('league_id')->nullable()->constrained()->nullOnDelete();
    $table->date('transfer_date');
    $table->enum('type', ['assignment', 'transfer', 'loan', 'release'])->default('transfer');
    $table->decimal('fee', 15, 2)->nullable();
    $table->text('notes')->nullable();
    $table->softDeletes();
    $table->timestamps();
});
```

| `type` | Uso |
|--------|-----|
| `assignment` | Primera incorporación (sin equipo previo o `from_team_id` null) |
| `transfer` | Movimiento entre equipos |
| `loan` | Cesión (futuro) |
| `release` | Baja del equipo |

---

## 5. Endpoint: equipo por temporada

### Ruta

```
GET /api/teams/{team}/seasons/{season}
```

**Query params:**

| Parámetro | Valores | Default | Descripción |
|-----------|---------|---------|-------------|
| `format` | omitido \| `clear` | omitido | `clear` devuelve todos los jugadores en un solo array |
| `league_id` | int | opcional | Filtra `team_season` cuando un equipo participa en varias ligas la misma temporada |

### Flujo en `TeamSeasonService::getTeamBySeason()`

1. Buscar `TeamSeason` por `team_id`, `season_id` y opcionalmente `league_id`.
2. Si no existe → `404` con mensaje claro.
3. Cargar relaciones: `team.country`, `season`, `league`, `lineup`, `playerTeamSeasons.player.position`.
4. Partir jugadores según `is_started`:
   - `true` → `starters`
   - `false` → `substitutes`
5. Si `format=clear`, unificar en `players` y no incluir `starters`/`substitutes`.

### Respuesta — formato por defecto

```json
{
  "success": true,
  "message": "Team season fetched successfully",
  "data": {
    "team": {
      "id": 1,
      "name": "Real Madrid",
      "slug": "real-madrid",
      "code": "RMA",
      "logo": "...",
      "country": { "id": 1, "name": "Spain", "code": "ES" }
    },
    "season": {
      "id": 3,
      "name": "2025/26",
      "slug": "2025-26",
      "start_date": "2025-08-01",
      "end_date": "2026-05-31"
    },
    "league": {
      "id": 1,
      "name": "La Liga",
      "slug": "la-liga"
    },
    "lineup": {
      "id": 1,
      "name": "4-3-3"
    },
    "starters": [
      {
        "id": 10,
        "name": "Thibaut",
        "lastname": "Courtois",
        "number": 1,
        "is_started": true,
        "position": { "id": 1, "name": "GK" }
      }
    ],
    "substitutes": [
      {
        "id": 25,
        "name": "Andriy",
        "lastname": "Lunin",
        "number": 13,
        "is_started": false,
        "position": { "id": 1, "name": "GK" }
      }
    ]
  }
}
```

### Respuesta — `?format=clear`

```json
{
  "success": true,
  "message": "Team season fetched successfully",
  "data": {
    "team": { "...": "..." },
    "season": { "...": "..." },
    "league": { "...": "..." },
    "lineup": { "...": "..." },
    "players": [
      { "id": 10, "name": "...", "number": 1, "is_started": true, "position": {} },
      { "id": 25, "name": "...", "number": 13, "is_started": false, "position": {} }
    ]
  }
}
```

### Datos relacionados incluidos

| Campo | Origen |
|-------|--------|
| `team` | `teams` + `country` |
| `season` | `seasons` |
| `league` | `team_season.league_id` → `leagues` |
| `lineup` (formación) | `team_season.lineup_id` → `lineups.name` |
| Jugadores | `player_team_season` + `players` + `positions` |
| `number` | `player_team_season.number` |
| `is_started` | `player_team_season.is_started` |

---

## 6. Endpoints: asignación de jugador a plantilla

Primera incorporación o alta manual en `player_team_season` sin movimiento desde otro club (o con `type=assignment` en transfer).

### Rutas

```
GET    /api/teams/{team}/seasons/{season}/players
POST   /api/teams/{team}/seasons/{season}/players
GET    /api/teams/{team}/seasons/{season}/players/{player}
PUT    /api/teams/{team}/seasons/{season}/players/{player}
PATCH  /api/teams/{team}/seasons/{season}/players/{player}
DELETE /api/teams/{team}/seasons/{season}/players/{player}
```

### POST — asignar jugador (primera vez o nuevo en plantilla)

**Body:**

```json
{
  "player_id": 42,
  "number": 9,
  "is_started": true,
  "league_id": 1,
  "create_transfer_record": true,
  "transfer_date": "2026-07-01",
  "notes": "Fichaje desde cantera"
}
```

**Lógica en `PlayerAssignmentService::assign()`:**

1. Resolver o crear `TeamSeason` (requiere `league_id` y `lineup_id` por defecto si no existe).
2. Verificar que el jugador no esté ya en esa plantilla (`unique player_id + team_season_id`).
3. Crear `PlayerTeamSeason`.
4. Actualizar `players.team_id` al equipo destino.
5. Si `create_transfer_record` → crear `Transfer` con `type=assignment`, `from_team_id=null`.

### PUT/PATCH — actualizar dorsal o titularidad

Campos editables: `number`, `is_started`.

### DELETE — quitar de plantilla

- Eliminar registro `player_team_season`.
- Si el jugador no tiene otra membresía activa en la temporada, opcionalmente poner `players.team_id = null`.
- No borrar el jugador de la tabla `players`.

---

## 7. Endpoints: CRUD de transferencias

> **Estado: PENDIENTE** — Diseño documentado a continuación. No implementar hasta nueva iteración. La asignación de jugadores (sección 6) cubre la incorporación inicial a plantilla.

### Rutas

```
GET    /api/transfers
GET    /api/transfers/{transfer}
POST   /api/transfers
PUT    /api/transfers/{transfer}
PATCH  /api/transfers/{transfer}
DELETE /api/transfers/{transfer}
```

### Filtros en index

| Query | Descripción |
|-------|-------------|
| `player_id` | Transferencias de un jugador |
| `team_id` | Entradas o salidas de un equipo (`from` o `to`) |
| `season_id` | Por temporada |
| `type` | `assignment`, `transfer`, etc. |

### POST — transferir jugador entre equipos

**Body:**

```json
{
  "player_id": 42,
  "from_team_id": 1,
  "to_team_id": 5,
  "season_id": 3,
  "league_id": 1,
  "transfer_date": "2026-01-15",
  "type": "transfer",
  "fee": 50000000,
  "notes": "Ventana de invierno",
  "number": 10,
  "is_started": false
}
```

**Lógica en `TransferService::create()` (transacción DB):**

1. Validar que `from_team_id` ≠ `to_team_id` (si `from` no es null).
2. Validar que el jugador pertenece a `from_team` en esa temporada (existe `player_team_season` en el `team_season` origen), salvo `type=assignment`.
3. Eliminar `player_team_season` del equipo origen (si aplica).
4. Crear o actualizar `player_team_season` en equipo destino.
5. Actualizar `players.team_id` → `to_team_id`.
6. Persistir registro en `transfers`.

### POST — primera asignación vía transferencias

Mismo endpoint con `type: "assignment"` y `from_team_id: null`. El servicio delega la parte de plantilla a la misma lógica que `PlayerAssignmentService` o un método compartido interno.

### DELETE

Soft delete del registro `transfers`. **No revierte automáticamente** la plantilla (documentar; revert manual o endpoint futuro `POST /transfers/{id}/revert`).

---

## 8. Plan de implementación por fases

### Fase 0 — Preparación (modelos y migración)

- [ ] ~~Migración `create_transfers_table`~~ **PENDIENTE**
- [ ] ~~Modelo `Transfer`~~ **PENDIENTE**
- [x] Completar `TeamSeason`, `Lineup`
- [x] Ajustar relaciones incorrectas en `Team`
- [x] Seeder mínimo: `LineupSeeder`, `SeasonSeeder`, `TeamSeasonSeeder`

### Fase 1 — Repositorios

- [x] Interfaces en `app/Repositories/Contracts/`
- [x] `TeamSeasonRepository`: `findByTeamAndSeason()`, `findOrCreate()`
- [x] `PlayerTeamSeasonRepository`: CRUD + `getByTeamSeason()`, `existsInRoster()`
- [ ] ~~`TransferRepository`~~ **PENDIENTE**
- [x] Bindings en `AppServiceProvider`

### Fase 2 — Servicios

- [x] `TeamSeasonService::getTeamBySeason(Team, Season, ?leagueId, format)`
- [x] `PlayerAssignmentService`: assign, update, remove, list
- [ ] ~~`TransferService`~~ **PENDIENTE**

### Fase 3 — Controladores y rutas

- [x] `TeamSeasonController@show`
- [x] `PlayerAssignmentController` (rutas anidadas)
- [ ] ~~`TransferController`~~ **PENDIENTE**
- [x] Registrar rutas en `routes/api.php` bajo `auth:sanctum`
- [x] Validación inline en controladores (patrón existente del proyecto)

### Fase 4 — Respuestas y formato

- [ ] Mapper/array builder en servicio para `starters` / `substitutes` / `clear`
- [ ] Códigos HTTP: 200, 201, 404, 422, 409 (jugador ya en plantilla)

### Fase 5 — Pruebas

- [ ] Feature tests: equipo por temporada (default y `format=clear`)
- [ ] Feature tests: asignación, transferencia, validaciones de negocio
- [ ] Factory para `TeamSeason`, `PlayerTeamSeason`, `Transfer`

### Fase 6 — Documentación y despliegue

- [ ] Actualizar este documento con acciones completadas
- [ ] Ejemplos curl en sección de anexos

---

## 9. Contratos de validación (resumen)

### GET equipo por temporada

```
format: nullable|in:clear
league_id: nullable|exists:leagues,id
```

### Asignar jugador

```
player_id: required|exists:players,id
number: required|integer|min:1|max:99
is_started: boolean
league_id: required|exists:leagues,id
lineup_id: nullable|exists:lineups,id
create_transfer_record: boolean
transfer_date: required_if:create_transfer_record,true|date
```

### Crear transferencia

```
player_id: required|exists:players,id
from_team_id: nullable|exists:teams,id|different:to_team_id
to_team_id: required|exists:teams,id
season_id: required|exists:seasons,id
league_id: required|exists:leagues,id
transfer_date: required|date
type: required|in:assignment,transfer,loan,release
fee: nullable|numeric|min:0
number: nullable|integer|min:1|max:99
is_started: boolean
```

---

## 10. Consideraciones y riesgos

| Tema | Detalle |
|------|---------|
| `team_season` único por `(team, season, league)` | Siempre pasar `league_id` al crear o resolver plantilla |
| `lineup_id` obligatorio en DB | Al crear `team_season`, usar lineup por defecto `4-4-2` si no se envía |
| Doble modelo de equipo (`team_id` vs `player_team_season`) | Servicios deben mantener ambos sincronizados |
| `BaseApiRepository` actual | No extender; crear repositorios específicos por dominio |
| Soft deletes | Activar trait en modelos que lo tengan en migración cuando se toquen |
| Sin Form Requests por ahora | Mantener validación inline como `TeamController` |

---

## 11. Acciones tomadas

| Fecha | Acción |
|-------|--------|
| 2026-08-02 | Análisis del codebase: modelos, migraciones, rutas y patrones existentes |
| 2026-08-02 | Definición de arquitectura Repository + Service + Controller |
| 2026-08-02 | Diseño de endpoint `GET /teams/{team}/seasons/{season}` con `format=clear` |
| 2026-08-02 | Diseño de CRUD de asignación (`player_team_season`) y transferencias |
| 2026-08-02 | Esquema propuesto para tabla `transfers` |
| 2026-08-02 | Plan por fases (0–6) documentado en este archivo |
| 2026-08-02 | Implementación sin transferencias: modelos, repositorios, servicios, controladores, rutas y seeders |

---

## 12. Anexo — Ejemplos curl (referencia futura)

```bash
# Equipo por temporada (titulares / suplentes)
curl -H "Authorization: Bearer {token}" \
  "https://api.example.com/api/teams/1/seasons/3?league_id=1"

# Mismo equipo, todos los jugadores juntos
curl -H "Authorization: Bearer {token}" \
  "https://api.example.com/api/teams/1/seasons/3?format=clear"

# Asignar jugador a plantilla
curl -X POST -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"player_id":42,"number":9,"is_started":true,"league_id":1}' \
  "https://api.example.com/api/teams/1/seasons/3/players"

# Transferir jugador
curl -X POST -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"player_id":42,"from_team_id":1,"to_team_id":5,"season_id":3,"league_id":1,"transfer_date":"2026-01-15","type":"transfer","number":10}' \
  "https://api.example.com/api/transfers"
```

---

## 13. Próximo paso recomendado

1. Probar endpoints de equipo por temporada y asignación de jugadores.
2. **Pendiente:** implementar transferencias (sección 7) cuando se retome.
