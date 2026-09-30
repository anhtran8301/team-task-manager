# Frontend conventions

- Use Vue SFC with `<script setup lang="ts">`, Pinia, Router, Axios and Vuetify. Keep UI copy English.
- Follow `common`, `helpers`, `api`, `services`, `stores`, `router`, `typings`, `components`, `views` boundaries.
- Common holds enums/constants; helpers are reusable functions; HTTP services never mutate Pinia or display notifications. Stores own asynchronous state.
- Access tokens belong only in HttpOnly cookies. Never add storage tokens or Authorization headers. Bootstrap CSRF before authentication, restore identity through `/api/me`, and never automatically replay failed mutations.
- Parse internal-code message maps and field errors centrally. Always reject HTTP/network failures. Backend authorization remains authoritative.
- User JSON has one role: Role.Admin or Role.User. Reuse helpers/role.ts; do not add permission arrays or scattered role strings.
- Keep server pagination, filter reset, stale-response protection and duplicate-submit guards intact.
- Reuse form/table/dialog wrappers. Avoid global paging state shared by unrelated screens.
- Use meaningful TSDoc for reusable contracts and non-obvious behavior; avoid duplicated constants and untyped payloads.
- Commands from `fe`: `npm ci`, `npm run dev`, `npm run lint`, `npm run typecheck`, `npm test`, `npm run build`, `npm run test:e2e`.
- Root Prettier formats code; ESLint checks correctness. Root Husky/lint-staged/commitlint enforce staged checks and Conventional Commits.
