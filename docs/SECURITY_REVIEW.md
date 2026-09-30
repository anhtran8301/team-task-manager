# Security review: task multi-filter/multi-sort, role access and auth

## Multi-filter / multi-sort review

**Assessment: APPROVE for the scoped query/UI changes.** Reviewed using code-review-expert and ponytail-review; no new P0/P1 finding identified in this scope. No pentest or cloud scan was run.

- Form Request accepts only bounded, distinct list filters and at most three ordered sort criteria. Each sort object accepts only field/direction. Invalid scalars, list keys, nested filter values, duplicates and SQL-like field/direction input receive the existing 422 envelope.
- Repository preserves role-based ownership before both whereIn groups and FULLTEXT. TaskSortField maps API fields to qualified trusted columns; SortDirection restricts directions again at query construction. The only raw ordering expression is fixed null-date SQL, with no client interpolation.
- Assignee sort uses a one-to-one FK join and selects tasks.*; eager loading preserves account serialization. The fixed id-descending tie-breaker prevents unstable ordering for equal names/titles/dates in an unchanged dataset. It does not provide a snapshot across concurrent writes.
- Native Vuetify multi-sort supplies ASC/DESC/off and priority badges; the wrapper emits state rather than fetching. Typed mapping and URLSearchParams serialize array filters and ordered sorts. The existing latest-request guard remains active; filter/sort changes reset the page.
- This intentionally breaks scalar list-filter callers: status and assigned_to must now be lists. Write payloads remain scalar. Frontend/backend must be deployed together; no compatibility layer or database migration was added.
- Residual performance limit: title/date/name multi-sort can require a filesort, particularly with FULLTEXT and pagination. This change does not claim indexed sorting or million-row performance; measure real query plans before adding indexes.

**Ponytail review:** reused native table sorting and a small task-specific field map; did not import Fusion's full QueryOption/locale/raw-SQL helper stack. No additional generic abstraction is warranted.

**Skill-security-review:** reread and statically scanned the unchanged local backend skill: one Markdown file, zero scanner findings, no executable surface/hooks or external upload instructions. No global skill/tool installation was performed.

## Code review summary

**Assessment: APPROVE for the scoped implementation; repository-wide formatting remains blocked by unrelated files.** Review used `code-review-expert` for authorization/security and `ponytail-review` for unnecessary complexity. This is a source review plus regression testing, not a penetration test or production certification.

There is no initial Git commit and source remains untracked, so a meaningful changed-line count is unavailable. Review covered User role helpers/serialization, Task/User Policies, AuthServiceProvider, API routes, Form Requests, service interfaces/controllers, repositories, factory/seeder, the new migration, frontend role helpers/form/page/layout and related tests. Existing architectural layers were retained.

### Findings

- **P0 / P1:** no new critical/high issue identified in the scoped implementation. No exploit scanner was run.
- **P2, documented migration limitation:** `be/database/migrations/2026_09_30_000002_simplify_user_roles.php` restores canonical permissions on rollback, not deleted custom grants or multiple memberships. User/task/token records are preserved. MySQL DDL is not fully transactional; migrate without application traffic and keep a backup for exact recovery. Preflight rejects unmappable accounts before any DDL.
- **P3, existing formatting:** full Pint reports whitespace in `be/app/Helpers/ApiExceptionRenderer.php`; full Prettier reports 12 untouched frontend files below. These do not indicate authorization failures. Scoped modified files pass their formatting checks.

### Authorization and data boundaries checked

- `users.role` is restricted to admin/user by the new database enum. `User::hasRole()` / `isAdmin()` centralize checks and do not treat unknown roles as users. Role is not mass-assignable. Client role/permission fields in a task payload cannot elevate the actor.
- Model Policies replace permission-code Gates. Routes authorize listing, creation, update and deletion. Update/delete use the currently bound task; ownership means assignee, including admin-created tasks.
- `StoreTaskRequest::passedValidation()` checks the separate `assign` ability; the update request inherits it. Nonexistent/malformed assignees receive 422, valid unauthorized assignees receive 403. Foreign-task authorization occurs before payload validation.
- TaskService contains no Gate calls and store/update no longer accept an unused actor. Internal callers bypassing the HTTP boundary must authorize before writing; there are no added queue/CLI write entrypoints.
- Repositories scope user lists/tasks before filters; admins can see all. FULLTEXT remains bound, title-only and natural-language, with fixed ordering and bounded pagination. Assignees are eager-loaded without permission queries.
- User JSON consistently exposes one `role`, including login, me, user list and task assignee. Frontend action/filter/form visibility uses role helpers and never substitutes for backend enforcement.
- Cookie encryption/HttpOnly, token expiry/revocation, session regeneration, CSRF and generic errors are preserved. Tests exercise real cookie/CSRF behavior and prohibit legacy Bearer authentication.
- Migration checks missing/unsupported memberships, preserves users/passwords/tasks/tokens, and gives admin precedence for the two known memberships. Seed runs preserve existing roles/passwords/tasks.

### Ponytail review

Removed permission enums/models/seeder, membership query methods, role/permission serialization queries and unused repository injection. Retained the user-requested Service/Repository interfaces and Laravel Policies; no replacement generic authorization layer was introduced. No further speculative abstraction was identified in this change: **Lean already. Ship.**

### Residual limits

Last-write-wins behavior and the gap between HTTP authorization and concurrent database writes are unchanged; this review does not establish serializable authorization under concurrent reassignment. Internal service callers require explicit authorization. The documented Laravel 11 support/advisory limitation and per-email/IP login throttle remain. No production infrastructure review, load test, fresh dependency audit or cloud/pentest scan was performed. Dummy-hash algorithm/cost compatibility remains an operator configuration check documented in README.

## Backend skill review

**Verdict: safe for the reviewed local Markdown payload.** `be/.agents/skills/laravel-backend/SKILL.md` was read and statically scanned after replacing Fusion permission guidance with single-role/model-policy guidance. It contains one Markdown file, no executable surface, symlinks, scripts, hooks or MCP configuration. The scanner found zero matches; manual review confirmed project-specific validation, authorization, migration and testing instructions, with no secret access or external upload instructions. No new global tools/skills were installed during this role refactor. This verdict does not claim application code is vulnerability-free.

## Prior global skill audit and installation

**Verdict: safe-with-caveats for the selected Markdown skill directories only.** This verdict does not cover either project's full CLI/plugin distribution.

| Source                                                                | Reviewed commit                            | Selected contents              | Result                                                                            |
| --------------------------------------------------------------------- | ------------------------------------------ | ------------------------------ | --------------------------------------------------------------------------------- |
| [usestrix/strix](https://github.com/usestrix/strix)                   | `6ae036e6c1140c4ecece2376f73755351fbea098` | Nine `skills/*/SKILL.md` files | Existing global copies are byte-identical; left unchanged                         |
| [DietrichGebert/ponytail](https://github.com/DietrichGebert/ponytail) | `e3ba2aa6f1e6f0bc4d69eb09c9f0d0a93af56156` | Six `skills/*/SKILL.md` files  | Installed at `~/.agents/skills`, then compared byte-for-byte with reviewed source |

Installed: `ponytail`, `ponytail-review`, `ponytail-audit`, `ponytail-debt`, `ponytail-gain`, `ponytail-help`. No previous Ponytail directories existed, so no replacement/backup was needed. The trusted `skill-installer` helper fetched the pinned commit and copied only selected directories. Each installed directory contains only its `SKILL.md`.

### Automatic execution and invocation

The selected payload contains 15 Markdown files, no executable scripts, binaries, escaping symlinks, recorded agent traces, lifecycle hooks, MCP declarations or plugin wiring. No target code was executed. No CLI, plugin/hooks, cloud scan, account, payment or source upload was installed/started.

Markdown still influences agent behavior when loaded. `ponytail/SKILL.md:7` advertises broad coding-task applicability; its Persistence and Rules sections request persistent minimalism and challenge single-implementation interfaces. Those instructions are subordinate to the user's current explicit architecture and project AGENTS guidance. Installing the skill does not authorize removing the agreed Service/Repository layers. Say `stop ponytail` or `normal mode` to stop its requested behavior if invoked. Plugin auto-activation/configuration instructions in `ponytail-help` do not mean a plugin was installed.

Ponytail review/audit explicitly exclude correctness/security from their scope. They complement, not replace, security review. Ponytail gain references upstream README/benchmarks as published metrics; those claims were not independently reproduced and do not measure this repository.

### Scanner flags manually assessed

The trusted `skill-security-review` scanner reported zero matches for Ponytail skills and seven static-text matches for Strix skills. Counts alone are not the verdict.

- `ci-security-scanning-with-strix/SKILL.md:44,91,128` and `penetration-testing-with-strix/SKILL.md:46`: real instructions to download and execute a remote installer with `curl | bash`. They are dormant Markdown instructions here, but require a separate installer review before execution; no such commands were run.
- `managed-pentesting-with-strix/SKILL.md:16`: the same remote-installer capability, despite the scanner assigning a lower severity to prose. Its cloud workflow also uploads selected source and can incur charges. None was invoked; missing local prerequisites do not authorize a cloud fallback for this task.
- `fix-security-vulnerabilities-with-strix/SKILL.md:77`: false positive for exfiltration. The text prohibits including live secrets in reports.
- `owasp-top-10-testing/SKILL.md:14`: false positive for exfiltration. The text selects an OWASP taxonomy edition and requests clarification for older editions.

The full Ponytail repository's hooks/adapters/binaries and Strix CLI/runtime/dependencies are outside this installation verdict. The benchmark results and remote installer contents were not validated. Read-only clones and static scanning do not establish those surfaces are safe.

## Verification results

The first multi-select E2E run exposed an ambiguous label locator (input and menu share the label); selectors were corrected to target comboboxes. An immediate rerun hit the existing login throttle. The final full suite passed after the throttle window expired; production throttling was not changed.

- PHPUnit/MySQL: **27 tests, 282 assertions passed**. Covers role/ownership, assignment validation, attempted role injection, cookie/CSRF auth, combined FULLTEXT/list filters, both sort directions, null dates, tied values across pages, invalid sort input, eager loading, migration preflight/round trips and repeatable seeds.
- Frontend: ESLint, TypeScript, **17 Vitest tests**, and production build passed. Query tests cover ordered bracket serialization and table-key mapping; latest-response tests now combine multiple filters and sort.
- Playwright/native servers: **5 passed**, including admin reassignment from Alex to Sam and verification of both users' visible task lists. Login responses are checked for the single-role contract. Test-created tasks are cleaned up. Multi-filter and ordered header sorting cover priority badges, ASC/DESC/off, chip removal, clear-all and exactly one page-1 request after sorting from page 2.
- Migration applied to the existing application database after a private backup at `/tmp/team-task-manager-before-single-role.URP4ol`. Pre/post hashes verified user fields (except the new role column), task rows and token rows unchanged: **3 users, 72 tasks, 15 tokens** at migration time. No application reset/reseed was run.
- Initial backend execution was blocked by a stopped Docker daemon/MySQL container. After starting the existing container with its existing volume, tests passed. No substitute SQLite testing was used.
- Scoped Pint/Prettier pass. Full Pint has the unrelated whitespace failure noted above. Full Prettier reports these untouched files:

```text
fe/src/api/http.ts
fe/src/components/DialogConfirm.vue
fe/src/components/forms/FormManager.vue
fe/src/components/forms/MasterDatePicker.vue
fe/src/components/forms/MasterForm.vue
fe/src/components/forms/MasterInput.vue
fe/src/components/forms/MasterSelect.vue
fe/src/components/forms/MasterTextarea.vue
fe/src/helpers/apiError.ts
fe/src/stores/auth.ts
fe/src/stores/task.ts
fe/src/views/pages/LoginPage.vue
```
