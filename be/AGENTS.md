# Backend conventions

Read [.agents/skills/laravel-backend/SKILL.md](.agents/skills/laravel-backend/SKILL.md) before backend work.

- Use PHP 8.3 and Laravel 11. Models live in `app/Modules/V1`.
- Preserve Controller → CommonFormRequest → Service interface/implementation → Repository interface/implementation → Eloquent and provider bindings.
- Keep controllers thin. Register model Policies with Gate::policy; routes check viewAny/create/update/delete. Form Requests authorize assign after validation. Services return Illuminate\Http\Response through $transformerResponse and do not call Gate; internal callers must authorize before writing.
- All API outcomes use the shared envelope, English internal-code messages and field errors for validation. Delete/logout return 200.
- users.role (admin|user) is authoritative. Reuse User::hasRole(UserRole) / isAdmin(). Ownership means assigned_to; check the proposed assignee separately. Never accept role changes from task/auth payloads.
- Sanctum tokens are encrypted HttpOnly cookies. Keep native CSRF enabled for login and all mutations; the CSRF session alone never authenticates.
- Put tunable settings in config, domain values in enums, and meaningful contracts in docblocks. Never log credentials or tokens.
- Native commands from `be`: `composer install`, `php artisan serve`, `composer lint`, `composer test`. PHPUnit uses `team_tasks_test`; never run destructive tests against the application database.
- Use reversible migrations and repeatable seeders. Never reset a database or remove Docker volumes as part of routine setup.
- Format PHP with Pint. Review auth, ownership, errors and eager loading after changes.
