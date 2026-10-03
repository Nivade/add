# Codebase Rules Index

**Read [overview.md](overview.md) first.**

Before planning or editing, find the row whose globs match the file's path and
read that rule file.

| Applies to | Rule file |
| --- | --- |
| `backend/resources/js/**`, `backend/app/Enums/**`, `mobile/**`, `packages/shared/**` | [product-invariants.md](product-invariants.md) — no `failed`/`abandoned`/`overdue`/`due_at`, no streaks, no backlog dumps; skip weighs the same as done; every recommendation carries a `why`. |
| `backend/app/Models/**`, `backend/app/Actions/**`, `backend/database/migrations/**` | [domain-model.md](domain-model.md) — the spec's "Next Action" is the model `Step`; use-case classes are `laravel-actions` on `AsObject`; captures are immutable; skipped steps are kept; dates are UTC instants, converted at the connection and the model, never by hand; migrations stay SQLite-compatible. |
| `backend/app/Data/**`, `backend/app/Http/**`, `backend/routes/api.php` | [api-and-data.md](api-and-data.md) — laravel-data replaces API Resources; every `from*` method is a spatie magic creation method and recurses forever; `next-action` and `sessions/current` return null-shaped 200s. |
| `backend/app/Support/Ai/**`, `backend/app/Providers/AiServiceProvider.php`, `backend/config/ai-toolkit.php` | [ai-layer.md](ai-layer.md) — the provider never judges its own answer; a missing fixture throws; one schema definition; prompt and schema versions live in the cache key. |
| `backend/tests/**`, `backend/phpunit.xml`, `backend/composer.json`, `backend/devtools.json` | [testing.md](testing.md) — parallel by default; pin `memory_limit=1G`; never import a global class in a Pest file; the resolver gets scenario tests. |
| `backend/compose.yaml`, `backend/.env*`, `backend/vite.config.ts`, `package.json`, `packages/shared/**`, `mobile/**`, `backend/composer.json`, `backend/devtools.json`, `backend/composer-dependency-analyser.php`, `.husky/**`, `.gitlab-ci.yml`, `.gitlab/ci/**`, `docker/ci/**` | [toolchain.md](toolchain.md) — Redis only resolves inside Compose; `compose.yaml` is hand-edited and `devtools:sync` writes one component at a time; the two frontends share types, never components; debug tooling is a second container and Bugsink's DSN host is not the browser's. |
| `backend/app/Support/**`, `backend/app/Concerns/**`, `backend/app/Exceptions/**` | [support-and-concerns.md](support-and-concerns.md) — `Support` holds adapters, framework glue and pure calculation; no `app/Services`; jobs are actions with `AsJob`; `app/Concerns/` holds only the Fortify validation traits. |
| `**` | [general.md](general.md) — refactor freely, nothing is deployed; comments carry one fact and never cite a rule file; prefer Laravel attributes; documents state decisions, never counts or status, and a guard test checks the parts a machine can see. |
| `backend/**/*.php` | [nvade-devtools-general.md](nvade-devtools-general.md) — shipped by devtools: one-fact comments, no counts in documents, a rule needs a test, Pint does not run Rector. |
| `backend/app/**/*.php` | [nvade-devtools-laravel.md](nvade-devtools-laravel.md) — shipped by devtools: never rethrow `getCode()`, never name a job helper `fail()`, dispatch each fact from one site. |
| `backend/rector.php` | [nvade-devtools-rector.md](nvade-devtools-rector.md) — shipped by devtools: `withSkipPath()` for host paths, `withComposerBased` already gates the Laravel sets. |
| `backend/vite.config.ts` | [vite.md](vite.md) — activate the `vite` skill first; it is locked upstream and cannot carry a `paths` trigger. |
| `.ai/skills/**`, `.ai/rules/**`, `CLAUDE.md`, `AGENTS.md` | [writing-for-agents.md](writing-for-agents.md) — activate the `writing-for-agents` skill first; same reason. |
| `backend/tests/**` | [nvade-devtools-testing.md](nvade-devtools-testing.md) — shipped by devtools: Pest import traps under `--parallel`, pinned `memory_limit`, Laravel test-harness timing traps. |
