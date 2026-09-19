# Resume here

Last session: repo bootstrapped, slice 1 (foundations) finished, slice 2 started.

Read first: `CLAUDE.md`, `.ai/rules/overview.md`, then
`.ai/plans/executive-function-os.md` for the slice list. The slice being built
has its own plan in `.ai/plans/slices/`; read that one, not all of them.

The product spec is `.ai/plans/product-spec.md`, the long prompt that opened the
repo, verbatim. It supersedes `~/repos/private/first-move` — that repo
contributed conventions only, not scope or product decisions.

## State

Green as of the last command: `pint` clean, `phpstan` 0 errors, full suite
passes parallel. Nothing is committed — `git init` ran, everything is staged in
the working tree. Review and commit before building further.

## Done

**Slice 1 — foundations.** Enums, five migrations, ULID models with generics
docblocks, factories, five Data classes, generated `packages/shared/src/generated.ts`,
and `tests/Feature/Guards/ConventionsTest.php` holding the invariants down (the
`due_at` guard was verified by breaking it).

Infrastructure: Laravel 13.32 + Fortify + Inertia React, `spatie/laravel-data`,
`laravel/ai`, `laravel/sanctum`, `nvade/devtools` (Pint, PHPStan 7, Rector,
Sail/Traefik, CI), typescript-transformer wired through
`app/Providers/TypeScriptTransformerServiceProvider.php`. mysql stripped from
`compose.yaml`; ports 6382 / 1028 / 8028 / 5176.

Repo root was carrying two Laravel copies. `api/` was the pre-scrub Chisel
scaffold, in no workspace and referenced by no rule; it and the empty `apps/`
are gone. `backend/` is the only Laravel tree.

## Half-built: slice 2's AI layer

Written and passing analysis, but nothing calls any of it yet:

```
app/Contracts/AiProvider.php          one method, one call
app/Support/Ai/AiRequest.php          cacheKey() folds in prompt + schema version
app/Support/Ai/Prompts.php            PARSE_CAPTURE, DECOMPOSE — keep byte-stable
app/Support/Ai/StructuredAgent.php    #[Strict] laravel/ai agent
app/Support/Ai/Schemas/{ParseCapture,DecomposeIntention}Schema.php
app/Support/Ai/Exceptions/*.php
app/Data/Ai/AiResponseData.php
app/Enums/Ai/AiOperation.php
config/ai.php                         AI_DRIVER defaults to `canned`
```

Deliberate simplification against first-move: **one schema definition**, the
fluent builder. No raw-array twin, because the parser is the real validator and
the twins drifted there. Do not reintroduce the second copy.

## Next, in order

1. **Parsers** — `app/Support/Ai/Parsers/`: the provider returns raw decoded
   JSON and never judges it; the parser decides usability and throws
   `AiResponseInvalid`. Decomposition parser also enforces the prompt's step
   rules (no "and", no "figure out"), as a quality signal, not a hard reject.
2. **Domain actions** — `RecordCapture`, `ConvertCaptureToIntention` (with a
   deterministic deadline extractor first, AI only for what it cannot read),
   `DecomposeIntention` writing `steps` and stamping `decomposed_at`.
3. **Slice 3, `NextActionResolver`** — deterministic, returns a `Step` plus the
   `why` lines. Scenario tests build a whole world; see `.ai/rules/testing.md`.
4. **Slice 4** execution state machine, **slice 5** Inertia home + focus screen.

## Also done since

**Providers.** All five landed in `app/Support/Ai/Providers/`, bound from
`config('ai.driver')` by `AiServiceProvider`. `phpunit.xml` pins `AI_DRIVER=fake`
so a test reaching the AI path without queueing an answer fails loudly.
`OpenAiProvider` is written but no key is pointed at it. The decisions are
recorded in `.ai/rules/ai-layer.md`, held down by `tests/Feature/Ai/ProviderTest.php`.

**Boost, fully installed.** MCP, guidelines and skills. `.mcp.json` lives at the
repo root, not `backend/`, and calls `php backend/artisan boost:mcp` — the Laravel
tree is a subdirectory but the MCP client's cwd is the root. It is hand-maintained
and left out of `boost:update`, because Boost writes the artisan path relative to
the Laravel base path.

**Agent files moved to `.ai/`.** `.claude/` is settings and generated mirrors
only; skills are authored in `.ai/skills/` and Boost symlinks them into
`.claude/skills/`, which is gitignored. Plans and this file moved too. The
reasoning, and the `backend/.ai` symlink that lets Boost find the root `.ai/`,
are in `.ai/rules/overview.md`.

`backend/boost.json` is Boost's own local state and its own `.gitignore` excludes
it, so a fresh clone has to run `boost:install` once. Its `agents` key is what
`boost:update` checks; an install driven by explicit flags never writes it.

**PHP 8.5 and Sail everywhere.** `compose.yaml` builds the `8.5` Sail runtime as
`sail-8.5/app`, `composer.json` requires `^8.5`, and the mysql CI workflow was
still on 8.4. Every root script now runs in the container except `boost:update`.
`compose.yaml` gained mounts for `../packages` and `../.ai` — both sit above the
app mount and both are written to. `boost.json` has `sail: true`, so the
regenerated guidelines are sail-prefixed.

**Rector config tuned, then applied.** `backend/rector.php` adds
`LaravelSetList::LARAVEL_130` to the shared preset, skips `Prompts.php`, and
skips three rules, each commented where it sits. `composer refactor` then ran
across 31 files — `declare(strict_types=1)` everywhere, the two model scopes are
`#[Scope]` methods, and the factories lost their redundant `$model`. Rector is
clean; this one landed in the working tree rather than its own commit, by
decision, and does not change the rule.

**Tooling top-up.** `lorisleiva/laravel-actions` installed — `app/Actions/**` is
now `AsObject` use-case classes, recorded in `.ai/rules/domain-model.md`. Nothing
has been converted yet; the Fortify actions are exempt. `backend/package.json`
now declares `@add/shared`, so `packages/shared/src/generated.ts` is importable
from `resources/js`. Fourteen skills copied from numerosis and tabellio into
`.ai/skills/` and adapted to this repo's paths (no issue tracker, no remote,
findings go to `.ai/findings.md`, Pest 5, root `npm run` scripts).

**Pre-commit tidy.** Workflows moved from `backend/.github` to the repo root,
where Actions actually reads them, and both gained
`defaults.run.working-directory: backend`. `backend/package-lock.json` and
`backend/node_modules` are gone: the workspace was only half-wired, so the real
install lived under `backend/` while the root lock described nothing. One root
install now hoists all 405 packages, no nesting. Twenty-five transitive versions
moved; no direct dependency did. `backend/pnpm-workspace.yaml` deleted — this is
an npm repo. Root `.editorconfig`, `.gitattributes` and `.npmrc` added, and
`packages/shared/src/typescript-transformer-manifest.json` is now ignored, as in
first-move.

## Still unwired

- `mobile/` does not exist; it is deliberately out of the root workspace list
  until slice 7, or `npm install` breaks.
