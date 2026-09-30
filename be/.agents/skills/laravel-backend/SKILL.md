---
name: laravel-backend
description: Implement or review Team Task Manager Laravel backend changes, including request validation, cookie auth, role-based access, task Policies, queries and migrations.
---

# Laravel backend

- Use Form Requests for validation. Extend CommonFormRequest so validation uses the shared response contract.
- Use Policies in `app/Policies` for task authorization. Register model mappings with `Gate::policy`. Routes check standard `viewAny/create/update/delete` abilities, passing the bound task for ownership. Store/update Form Requests check `assign` in `passedValidation()` with validated input. Keep Gate calls out of services; internal callers must authorize before bypassing the HTTP boundary. Reuse `User::hasRole(UserRole)` / `isAdmin()`. Admin manages all tasks; user manages only tasks assigned to self and cannot reassign to others.
- Keep controllers thin; use the existing module service interfaces and provider bindings.
- Avoid unnecessary Repository pattern. This project explicitly retains its module repositories/interfaces; do not remove those layers or introduce generic repositories and speculative abstractions.
- Use eager loading to prevent N+1, loading the assignee without obsolete permission relations.
- Use enums for task status and user roles. Keep configurable limits in config.
- Keep migrations reversible and preserve existing accounts/tasks during role backfills. Single-role migration preflight rejects unmappable users before DDL; rollback restores canonical legacy grants, not removed custom memberships. Document that limitation.
- Prefer framework-native Laravel features, especially Sanctum token expiry, encrypted cookies, sessions and CSRF middleware.
- Do not over-engineer this assignment: no role-management UI, refresh tokens, queues or caching infrastructure without a concrete requirement.

Return Illuminate\Http\Response through TransformerResponse, with InternalCodeEnum messages. Keep failure success=false, validation HTTP 422 with field errors, and delete/logout HTTP 200. Add meaningful method docblocks. Run Pint and PHPUnit against the isolated MySQL test database after backend changes; exercise the real CSRF flow in browser tests when auth changes.
