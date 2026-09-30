# Written answers

## 1. Performance: serving 1,000,000+ tasks

1. Measure actual query plans and p95/p99 latency with realistic assignee/status distributions. Use `EXPLAIN ANALYZE` and a slow-query log before adding indexes or caches.
2. Replace deep offset pagination with keyset/cursor pagination over a stable indexed order, such as `(created_at, id)` or `id`. This avoids scanning/skipping increasingly large offsets. If exact totals are not required, stop issuing `COUNT(*)` on every request.
3. Design composite indexes around access scope and common filters. This implementation has `(assigned_to, id)`, `(status, id)` and `(assigned_to, status, id)`; validate their benefit and write/storage cost against actual plans.
4. Eager-load the assignee and select only required columns. Avoid loading whole task/user relations or issuing one assignee query for every row.
5. Keep pagination bounded (currently at most 100 items). Return summary fields in list responses and load large descriptions only when editing if payload size becomes significant.
6. Title search already uses MySQL FULLTEXT in natural-language mode with a dedicated index. Measure MATCH/AGAINST together with ownership filters, ordering and count queries; the index does not eliminate all pagination costs. Consider a dedicated search engine only when requirements such as typo tolerance or language-specific tokenization justify it.
7. Cache only measured hot queries/aggregates. Include authorization scope, filters and pagination in cache keys, and invalidate affected entries on task changes. Never share an admin result cache with regular users.
8. Move expensive reporting, exports and derived metrics into queues or materialized summary tables. None are needed for the current CRUD scope.
9. Consider read replicas, connection tuning and horizontal API scaling after query optimization. Account for replication lag after writes and move file-based sessions and rate limiting to shared stores.
10. Archive cold records if product retention permits. Keep observability for error rate, latency, DB CPU/I/O, connection counts and index effectiveness, and load-test before and after each change.

## 2. Security: Laravel API and Vue SPA

- **Authorization / IDOR:** every protected request is authenticated, list queries apply the caller's ownership scope, and task policies check the single account role and assignment ownership. Admins manage all tasks; users manage only tasks assigned to themselves. Form Requests authorize the proposed assignee after validation; users cannot assign to another account. Hiding buttons is not a security boundary. Tests exercise foreign task IDs and unauthorized reassignment directly.
- **SQL injection / mass assignment:** Eloquent uses bound parameters; sort fields are enum-mapped to trusted columns, directions are restricted to asc/desc, and list filters are validated and bounded. Only validated task fields reach the model, and `$fillable` is limited. FULLTEXT queries use Laravel parameter binding; user input never becomes SQL or a selectable query mode.
- **XSS:** render data through Vue's escaped interpolation, never `v-html`. HttpOnly cookies prevent direct token reads but an XSS payload can still perform authenticated actions. Deploy with a restrictive CSP and HTTPS; native development servers are not the production serving layer.
- **CSRF / CORS:** Sanctum access tokens travel in encrypted HttpOnly cookies. Native Laravel session/CSRF middleware protects all mutations, including login/logout; clients bootstrap `/api/csrf-cookie` and send `X-XSRF-TOKEN`. The session alone does not authenticate. Use host-only, SameSite cookies, Secure on HTTPS, and an explicit origin allowlist if adding cross-origin hosting. A failed 419 is not automatically replayed.
- **Passwords and brute force:** hash passwords with Laravel's password hasher, return a generic authentication failure, rate-limit login per email/IP, and additionally configure edge/IP limits in a real deployment. Never log passwords or access tokens.
- **Token lifecycle:** enforce short expiry, revoke the current token on logout and periodically prune expired records. Restoring a page verifies identity with the server; a frontend token-presence check alone is insufficient.
- **Transport and secrets:** use HTTPS in deployment, keep keys/passwords out of Git and frontend bundles, use a secret manager, restrict DB/network access and apply least-privilege database credentials. The seeded credentials and `.env.example` values are intentionally local demo defaults.
- **Validation and errors:** validate enum values, foreign keys, lengths and date-only input. Reject CR/LF in login email input. Return field errors with 422, but hide internal exception/SQL details. Keep logs accessible only to operators.
- **Supply chain:** commit lockfiles, pin the database image, audit dependencies and apply updates. Laravel 11 is deliberately retained for this exercise despite being out of security support; its known advisories remain visible in Composer audit. It must be upgraded before public deployment.

## 3. Benefits of TypeScript in a Vue 3 SPA

- Typed API models make frontend/backend contracts explicit. A task status cannot accidentally become an unsupported value without a type error.
- Typed props, events and store actions give useful editor completion and detect incorrect component usage before runtime.
- Refactors across services, stores and components are easier to verify through compiler errors.
- Nullability forces the UI to account for missing users, optional descriptions and unset due dates.
- Types document intent close to implementation and reduce repeated defensive casts. They do not validate untrusted JSON at runtime; backend validation and tests still matter.

## 4. Strongly typed component example

```vue
<script setup lang="ts">
import type { Task, TaskStatus } from "@/typings/task";

const props = defineProps<{
  task: Task;
  disabled?: boolean;
}>();

const emit = defineEmits<{
  changeStatus: [taskId: number, status: TaskStatus];
}>();

function markDone() {
  emit("changeStatus", props.task.id, "done");
}
</script>

<template>
  <article>
    <h3>{{ task.title }}</h3>
    <p>{{ task.description ?? "No description" }}</p>
    <button :disabled="disabled || task.status === 'done'" @click="markDone">
      Mark done
    </button>
  </article>
</template>
```

The parent receives a numeric task ID and a `TaskStatus` union. An unsupported status or wrongly typed prop fails TypeScript checking. Rendering remains escaped by Vue.
