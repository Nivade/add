# Codebase Rules Index

**Read [overview.md](overview.md) first.**

Before planning or editing, find the row whose globs match the file's path and
read that rule file.

| Applies to | Rule file |
| --- | --- |
| `backend/resources/js/**`, `backend/app/Enums/**`, `mobile/**`, `packages/shared/**` | [product-invariants.md](product-invariants.md) — no `failed`/`abandoned`/`overdue`/`due_at`, no streaks, no backlog dumps; skip weighs the same as done; every recommendation carries a `why`. |
| `backend/app/Models/**`, `backend/app/Actions/**`, `backend/database/migrations/**` | [domain-model.md](domain-model.md) — the spec's "Next Action" is the model `Step`; use-case classes are `laravel-actions` on `AsObject`; captures are immutable; skipped steps are kept; migrations stay SQLite-compatible. |
| `backend/app/Data/**`, `backend/app/Http/**`, `backend/routes/api.php` | [api-and-data.md](api-and-data.md) — laravel-data replaces API Resources; every `from*` method is a spatie magic creation method and recurses forever; `next-action` and `sessions/current` return null-shaped 200s. |
| `backend/app/Support/Ai/**`, `backend/app/Contracts/AiProvider.php`, `backend/config/ai.php` | [ai-layer.md](ai-layer.md) — the provider never judges its own answer; a missing fixture throws; one schema definition; prompt and schema versions live in the cache key. |
| `backend/tests/**`, `backend/phpunit.xml`, `backend/composer.json` | [testing.md](testing.md) — parallel by default; pin `memory_limit=1G`; never import a global class in a Pest file; the resolver gets scenario tests. |
| `backend/compose.yaml`, `backend/.env*`, `backend/vite.config.ts`, `package.json`, `packages/shared/**`, `mobile/**` | [toolchain.md](toolchain.md) — Redis only resolves inside Compose; `compose.yaml` is hand-edited and `devtools:sync` reverts it; the two frontends share types, never components. |
| `**` | [general.md](general.md) — refactor freely, nothing is deployed; comments carry one fact and never cite a rule file; prefer Laravel attributes; Pint does not run Rector. |
