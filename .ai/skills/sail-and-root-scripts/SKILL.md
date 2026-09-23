---
name: sail-and-root-scripts
description: Use before running any PHP, Artisan, Composer, npm, Pest, Pint or PHPStan command in this repo — and before telling the user to run one. This monorepo's commands go through root npm scripts that wrap Sail, so a bare `php artisan`, a bare `composer`, or a `vendor/bin/sail` invoked from the wrong directory either fails or runs against the wrong PHP. Trigger on "run the tests", "run artisan", "migrate", "install a package", "lint", "phpstan", "regenerate types", "boost:update", "sail", "start the containers", or any command a reply is about to put in a code block.
---

# Running commands in this repo

Work from the repo root. `.git`, `.ai/rules`, `.claude` and the npm workspace
list all anchor there, and most changes cross a workspace boundary.

The Laravel base path is `backend/`, so `vendor/bin/sail` is
`backend/vendor/bin/sail` from the root. Boost's own guidelines and most Laravel
documentation print `vendor/bin/sail ...`, which is wrong from the directory
every session starts in. Root scripts exist so nothing needs a manual `cd`.

## The scripts

| Want | Run from the repo root |
| --- | --- |
| Artisan | `npm run artisan -- migrate` |
| Composer | `npm run composer -- require vendor/package` |
| Full suite | `npm run test` |
| Suite, serial | `npm run test:serial` |
| Inner loop | `npm run test:impact` |
| Pint, check only | `npm run lint` |
| Pint, writing fixes | `npm run composer -- lint` |
| PHPStan | `npm run stan` |
| PHP Data to TypeScript | `npm run types:generate` |
| Guidelines and skill mirror | `npm run boost:update` |
| Vite dev server | `npm run web` |
| Sail itself | `npm run sail -- up -d` |

`--` is required: without it npm swallows the arguments and the script runs bare.

## The two host opt-outs

`npm run artisan:host` and `npm run test:host` skip the container. They are for
SQLite-only work when the containers are down, and nothing else.

Anything touching Redis must go through Sail. `REDIS_HOST=redis` is a Compose
network hostname the host cannot resolve, so `queue:work` on the host fails
where the same command inside Sail works. `artisan test` on the host is fine
because `phpunit.xml` pins `CACHE_STORE=array`, `QUEUE_CONNECTION=sync` and
`SESSION_DRIVER=array`.

## Reach for Artisan before reaching for a shell

If Artisan has a command, use it: `route:list` over grepping `routes/`,
`config:show` over reading `config/`, the cache-clear commands over deleting
files, `make:*` over writing a class from scratch. `npm run artisan -- list`
and `--help` when unsure.

Same for Boost's MCP tools: `database-schema` and `database-query` over raw SQL
or inferring schema from migrations, `search-docs` over guessing a package's
API, `read-log-entries` over tailing a log, `get-absolute-url` before sharing a
URL.

## Traps

`npm install` runs at the root and nowhere else. A `package-lock.json` inside a
workspace is not a backup — npm ignores it and the installed tree drifts from
the one CI resolves. Consolidating means
`rm -rf node_modules */node_modules package-lock.json` and one install, never an
incremental fix.

`npm run` executes scripts with `sh`, where Sail's `$UID` fallback is unset, so
generated files land owned by uid 1337 and the host cannot delete them.
`WWWUSER` and `WWWGROUP` are pinned in `.env` and `.env.example` for this
reason — do not remove them.

Changing the PHP runtime is `sail build`, not a restart. A stale image keeps
serving the old PHP while `composer.json` claims otherwise.

Pint does not run Rector. `npm run lint` is Pint only; Rector lives in
`composer refactor` and its rewrites otherwise accumulate until they land,
unrelated, in someone else's diff. Run `composer refactor:check` before
committing and land its changes as their own commit.
