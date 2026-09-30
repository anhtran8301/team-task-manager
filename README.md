# Team Task Manager

Laravel 11 / PHP 8.3 API and Vue 3 / Vuetify 3 / TypeScript SPA. Run both applications natively; Docker runs **only MySQL 8.4**. Administrators manage all tasks, while regular users manage tasks assigned to them.

## Local setup

Prerequisites: PHP **8.3.x** (PDO MySQL, mbstring, DOM/XML, curl), Composer 2, Node **24.x**, npm and Docker Engine/Desktop. The dependency lockfiles are committed. On macOS with Homebrew:

```sh
brew install php@8.3 composer
export PATH="$(brew --prefix php@8.3)/bin:$PATH"
php --version
```

From the repository root:

```sh
cp .env.example .env # new checkouts only; database container settings
sh scripts/database.sh start
```

Wait until `docker inspect --format '{{.State.Health.Status}}' team-task-manager-mysql` reports `healthy` on a newly created container, then:

```sh
sh scripts/setup.sh
sh scripts/dev.sh
```

Open **http://localhost:5173**. Vite proxies `/api` to PHP at `127.0.0.1:8000`, so cookies and API requests share the browser origin. Keep the same browser hostname throughout a session. There is no Compose, Nginx or PHP-FPM container setup.

`setup.sh` installs root tools, frontend dependencies and Composer dependencies; copies the backend environment only if absent; generates a missing application key; runs migrations and repeatable seeders. It never resets the database. `dev.sh` starts both native servers and stops its backend child on exit. Alternatively, use two terminals:

```sh
cd be
php artisan serve --host=127.0.0.1 --port=8000
```

```sh
cd fe
npm run dev -- --host 127.0.0.1
```

The backend uses `be/.env`: `DB_HOST=127.0.0.1`, `DB_PORT=3306`. Root `.env` configures the MySQL container. If changing database credentials or host port, update both configurations. For frontend proxy overrides, set `API_PROXY_TARGET` in the shell running Vite. The default browser API URL remains relative `/api`.

MySQL exposes only `127.0.0.1:3306`. Its image is pinned by digest and data is persisted in `team-task-manager_mysql_data`. `MYSQL_HOST_PORT` and `MYSQL_VOLUME` can be set in root `.env` before initial container creation. Existing containers retain their original settings; changing the env file alone does not reconfigure them. MySQL initialization credentials also do not change accounts inside an existing volume.

| Account | Email               | Password       |
| ------- | ------------------- | -------------- |
| Admin   | `admin@example.com` | `Password123!` |
| User    | `alex@example.com`  | `Password123!` |
| User    | `sam@example.com`   | `Password123!` |

Each new demo account receives 24 tasks. Repeated seeding preserves account passwords and tasks; existing roles are also preserved. Demo credentials are for local evaluation only.

## Structure and conventions

Backend models remain within `app/Modules/V1`, organized into Auth, User and Task. The request flow is:

**Controller → CommonFormRequest validation → Service interface/implementation → Repository interface/implementation → Eloquent**.

Controllers delegate; services build responses with `$transformerResponse`; repositories own persistence and scoped queries. `AuthServiceProvider` registers model Policies with `Gate::policy`. Routes use Laravel's `viewAny`, `create`, `update` and `delete` abilities. Policies live under `app/Policies` and reuse `User::hasRole(UserRole)` / `User::isAdmin()`. Update/delete routes authorize the bound task. Store/update Form Requests check the separate `assign` ability in `passedValidation()`, so invalid assignee input receives 422 and unauthorized assignment receives 403. Services do not call Gate. Internal callers bypassing HTTP must authorize ownership and assignment before writes. Provider bindings connect interfaces to implementations. All public API responses are `Illuminate\Http\Response`.

`routes/api.php` registers the internal V1 routes once. Public URLs have no version prefix. English messages live in `lang/en/internal_code_message.php`; `InternalCodeEnum` separates application codes from HTTP status. Configurable limits, pagination and cookie attributes live in `config/task_manager.php`.

Frontend follows `common`, `helpers`, `api`, `services`, `stores`, `router`, `typings`, `components` and `views`. Shared forms and the server table use Vuetify. Services perform typed HTTP calls without UI notifications or shared paging-store side effects. Helpers centralize messages, dates and role/ownership checks. Pinia owns identity and list-request state; stale responses cannot overwrite newer results.

Read [backend guidance](be/AGENTS.md), [frontend guidance](fe/AGENTS.md) and the [backend custom skill](be/.agents/skills/laravel-backend/SKILL.md) before extending the project.

Root Prettier, Husky, lint-staged and commitlint enforce formatting and Conventional Commits. PHP uses Pint; frontend uses ESLint with Prettier formatting rules disabled. `npm ci` at the root installs the hooks in a Git checkout. Pre-commit operates on staged files; CI runs checks without auto-fixes. Example commit: `feat(auth): move access tokens to HttpOnly cookies`.

## Role-based access

Each account has exactly one `users.role`: `admin` or `user`. There are no permission tables, role pivots, custom grants or role-management endpoints. Enum values and reusable model helpers define role checks centrally; role is excluded from mass-assignable account fields.

| Role  | Tasks                                                             | Assignees                                                     |
| ----- | ----------------------------------------------------------------- | ------------------------------------------------------------- |
| Admin | View, create, update and delete every task                        | View all accounts and assign/reassign to any existing account |
| User  | View, create, update and delete only tasks assigned to themselves | View/select only self                                         |

Ownership means `assigned_to`, not creator. A user may edit/delete a task created by an admin and assigned to them. Frontend helpers control visibility; server Policies and query scoping enforce access independently. Unsupported identities fail closed.

### Migrating an existing installation

Back up the application database before `php artisan migrate`. Stop application traffic while applying the schema change and deploy the matching frontend/backend together. The new migration backfills a single role, choosing admin when both known roles are present, then removes the four RBAC tables. Users, passwords, tasks and tokens are preserved. It rejects accounts without roles or with unsupported roles **before any DDL** and lists the first 20 IDs to resolve; it never silently grants a default role to those accounts.

Rollback recreates the historical tables and canonical permissions from the current role. It cannot restore removed custom grants or multiple memberships; use the pre-migration backup when that exact history is required. MySQL DDL is not fully transactional: an infrastructure failure midway through a migration requires inspecting the schema and restoring/reconciling before retrying. Historical migrations remain unchanged so fresh checkouts follow the same migration chain. Repeated seeding preserves existing roles, passwords and tasks.

## API contract

Send JSON with `Accept: application/json`. Bootstrap `GET /api/csrf-cookie`, retain cookies, and send the URL-decoded `XSRF-TOKEN` cookie value as `X-XSRF-TOKEN` for every mutation, including login/logout. Axios handles this automatically in the SPA.

| Method | Path               | Result                                                                      |
| ------ | ------------------ | --------------------------------------------------------------------------- |
| GET    | `/api/csrf-cookie` | Initialize native CSRF cookies; 200                                         |
| POST   | `/api/login`       | `{email,password}` → user and `expires_at`; sets HttpOnly token cookie; 200 |
| GET    | `/api/me`          | Current user with `role: "admin"                                            | "user"`; 200 |
| POST   | `/api/logout`      | Revoke current token and expire cookie; 200                                 |
| GET    | `/api/users`       | Authorized assignees; 200                                                   |
| GET    | `/api/tasks`       | Scoped, filtered Laravel paginator; 200                                     |
| POST   | `/api/tasks`       | Create task; 201                                                            |
| PUT    | `/api/tasks/{id}`  | Update task; 200                                                            |
| DELETE | `/api/tasks/{id}`  | Hard-delete task; 200                                                       |

```json
{
  "success": true,
  "code": 200,
  "message": { "10000": "Data retrieved successfully." },
  "data": {
    "data": [],
    "current_page": 1,
    "per_page": 20,
    "last_page": 1,
    "total": 0
  }
}
```

`code` equals HTTP status. `message` maps stable internal codes to English text. Delete/logout also return the envelope, with `data: []`. Validation returns 422 with `errors: {"field": ["message"]}`. Authentication, authorization, missing routes, unsupported methods, CSRF and throttling return 401/403/404/405/419/429. Unexpected errors return a generic 500 without SQL or stack traces; relevant framework headers are preserved.

Task payload:

```json
{
  "title": "Review release checklist",
  "description": "Verify permissions.",
  "status": "in_progress",
  "assigned_to": 2,
  "due_date": "2026-10-01"
}
```

Title is required, maximum 255 characters. Description is nullable, maximum 10,000 characters. Status is `todo`, `in_progress` or `done`; assignee must exist. Due date is nullable `YYYY-MM-DD`, including past dates, and never converted between timezones. Send all editable fields for updates. User deletion is not exposed; referenced assignees are protected by a foreign key.

List filters: `status[]` (up to three distinct status values), `assigned_to[]` (up to 100 distinct positive account IDs), `search` (natural-language FULLTEXT on title), `page` (default 1), `per_page` (default 20, maximum 100). Values within each filter group use OR; filter groups, search and ownership scope combine with AND. Missing/empty lists apply no condition; unknown assignee IDs simply have no matches. Scalar status/assignee filters are no longer accepted and return 422. Assignees are eager-loaded.

Multi-sort accepts an ordered `sort` list with at most three distinct fields: `title`, `assignee` (account display name), `due_date`. Each entry requires `field` and `direction` (`asc` or `desc`), with no extra keys. Example query (brackets shown unescaped for readability):

```text
/api/tasks?status[]=todo&status[]=done&assigned_to[]=2&assigned_to[]=3&sort[0][field]=assignee&sort[0][direction]=asc&sort[1][field]=due_date&sort[1][direction]=desc
```

Criteria apply in the given order, followed by `tasks.id DESC` as a stable tie-breaker. Missing/empty sort uses only `tasks.id DESC`. Undated tasks follow dated ones within each preceding sort group, in both date directions. Text ordering follows the database collation. Invalid list shapes, duplicates, unknown fields/directions and oversized lists return 422 with field errors; client input is never interpolated into SQL identifiers.

The SPA provides multi-select filters with removable chips and clear-all. Only admins see the assignee filter; even a manually crafted user request cannot expand their assigned-task scope. Clicking Title, Assignee or Due date cycles ASC → DESC → off; sort badges show priority, and adding a column appends it. Filter/search/sort changes reset page 1; refresh, edits and pagination preserve criteria. Reload starts with default criteria. This contract change requires deploying the frontend and backend together. Task write payloads still use a single status and assignee.

Search uses MySQL's native FULLTEXT index on `tasks.title`, following Fusion's `whereFullText` pattern. It matches whole indexed words rather than arbitrary substrings or prefixes. Multiple words use natural-language relevance matching, not an AND/phrase requirement; results still sort by `id DESC`. InnoDB's default parser ignores tokens shorter than three characters and its stopwords. Queries containing only ignored terms (including `0`) return no results; an empty search applies no search condition. Boolean operators are not enabled. Changing parser settings requires rebuilding the index. See [MySQL FULLTEXT semantics](https://dev.mysql.com/doc/refman/8.4/en/fulltext-natural-language.html).

## Authentication and security boundaries

- Sanctum personal access tokens expire after `SANCTUM_EXPIRATION` minutes (default 120). Laravel encrypts the `team_tasks_access_token` HttpOnly cookie. The token never appears in response JSON, sessionStorage or localStorage.
- Cookie path is `/`, SameSite is Lax, domain is omitted (host-only). Local HTTP uses `AUTH_COOKIE_SECURE=false`; HTTPS deployment must enable it and `SESSION_SECURE_COOKIE=true`.
- A native Laravel session supports CSRF only; it cannot authenticate requests. Bearer headers are no longer accepted. No refresh token, remember-me or registration flow is present.
- Login regenerates the CSRF session. Logout revokes the current token and invalidates the session. A protected 401 clears frontend state and redirects to login. Failed logout remains retryable.
- On 419 the client refreshes CSRF cookies and reports the error; it never automatically replays the failed write. The user can retry deliberately.
- HttpOnly prevents JavaScript from reading the token; it does not prevent XSS from issuing authenticated requests. Vue escapes task content. Add deployment-specific CSP/HTTPS at the serving layer before public deployment; the native development server is not a production server.
- Local Vite proxy avoids credentialed cross-origin requests. Separate-origin deployments need an explicit trusted-origin/CORS/cookie design; wildcard credentialed CORS is not enabled.

### Dummy password hash

Unknown emails still pass through `Hash::check()` using `auth.dummy_password_hash`. This precomputed, non-secret hash reduces differences in hashing work; it does not guarantee constant response time or let an unknown account log in. The default is bcrypt cost 12. Never generate the fallback during a login request.

If changing `HASH_DRIVER` or `BCRYPT_ROUNDS`, generate a matching hash once from `be`:

```sh
php artisan tinker --execute='echo Illuminate\Support\Facades\Hash::make(bin2hex(random_bytes(32)));'
```

Set `AUTH_DUMMY_PASSWORD_HASH='generated-hash'` in `be/.env` using **single quotes** to preserve `$` characters. Clear/rebuild any cached configuration, then verify that the hash matches the configured driver/cost:

```sh
php artisan tinker --execute='if (Illuminate\Support\Facades\Hash::needsRehash(config("auth.dummy_password_hash"))) { throw new RuntimeException("Regenerate AUTH_DUMMY_PASSWORD_HASH for the configured hasher."); }'
```

An incompatible or missing override must be corrected before serving traffic. PHPUnit uses a matching cost-4 fallback fixture to keep test hashing consistent.

## Verification

Create the isolated test database (the helper assumes the default `team_tasks` application user):

```sh
sh scripts/database.sh test-db
sh scripts/check.sh
```

FULLTEXT tests use committed fixtures and migration-based cleanup: InnoDB search cannot see uncommitted inserts. Migration round-trip tests verify data preservation.

PHPUnit fixes its database name to `team_tasks_test`, uses MySQL and refreshes only that test schema. Never point tests at a business database. Individual commands:

```sh
composer --working-dir=be lint
composer --working-dir=be test
npm run format:check
npm --prefix fe run lint
npm --prefix fe run typecheck
npm --prefix fe test
npm --prefix fe run build
```

With the native servers running and demo data seeded:

```sh
cd fe
npx playwright install chromium
npm run test:e2e
```

Override `E2E_BASE_URL` if necessary. Browser tests create and clean up uniquely named tasks, check two-user isolation, auth restoration, filters and real cookie/CSRF behavior. Tests also cover role/ownership checks, assignment rejection, bounded query counts, validation, expiry/revocation, single-role migration preflight/backfill/rollback and repeatable seeding. CI runs native PHP/Node with only a MySQL service container, retaining diagnostics on failure.

## Dependencies and operations

Framework constraints remain Laravel 11, PHP 8.3, MySQL 8.4, Vue 3 and Vuetify 3. Node 24 runs the toolchain. Root and frontend `package-lock.json` plus backend `composer.lock` pin resolved dependencies. TypeScript remains 6.0 for compatibility with typescript-eslint. `std-env` remains overridden to 4.2.0 because the advertised 4.3.0 tarball was unavailable when resolving dependencies.

Laravel 11 is retained for the assignment despite its ended security support. The existing Composer advisory-block exception is restricted to Laravel 11.56.1; audit reporting remains enabled. Known framework advisories require upgrading Laravel before public deployment. There are no signed-URL or mail-sending flows, and login rejects CR/LF in email input; these constraints do not patch the framework.

```sh
sh scripts/database.sh stop       # preserves the database volume
sh scripts/database.sh start
php be/artisan sanctum:prune-expired --hours=24
```

No migration, seeding or reset happens on container restart. `/up` checks backend database connectivity directly at port 8000. `APP_DEBUG=false` is the default. Keep the same `APP_KEY` across restarts; changing it invalidates encrypted cookies. Back up the MySQL database before schema changes. No app Docker images, queue workers, Redis, WebSockets, uploads or old gym/commerce integrations remain.

Updates are last-write-wins. Deep pagination, FULLTEXT search and multi-instance session/cache storage improvements are discussed in [ANSWERS.md](ANSWERS.md).
