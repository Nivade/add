# Repo Role

`add` is an external executive-function system for people with ADHD. Not a task
manager: the product's job is to remove the deciding, so the core question every
screen answers is "what do I do right now", and the answer is one thing.

## Layout

| Workspace | What it is |
| --- | --- |
| `backend/` | Laravel 13 — domain, JSON API, AI layer, Inertia React web |
| `mobile/` | Expo / React Native — Sanctum tokens against the JSON API |
| `packages/shared/` | TypeScript shared by both frontends, generated from PHP |

npm workspaces at root: `backend`, `mobile` and `packages/*`. One lockfile, and
`npm install` runs at the root and nowhere else.

## Where to look first

| You want | Read |
| --- | --- |
| Invariants and traps | `.ai/rules/overview.md`, then `.ai/rules/index.md` |
| What the product is and what is deliberately unbuilt | `.ai/plans/executive-function-os.md` |
| The original spec, verbatim and authoritative on intent | `.ai/plans/product-spec.md` |
| The design for the slice being built | `.ai/plans/slices/` |
| What the UI must never do | `.ai/rules/product-invariants.md` |
| Where the last session stopped | `.ai/RESUME.md` |

Everything an agent reads is authored in `.ai/`. `.claude/` holds settings and
generated mirrors only — `overview.md` explains which is which and why.

## Work from the repo root

`.git`, `.ai/rules`, `.claude` and the workspace list all anchor here, and most
changes cross a workspace boundary. Root scripts exist so nothing needs a `cd`:

Everything below runs inside Sail (PHP 8.5) except the two `:host` opt-outs:

```bash
npm run artisan -- migrate    # through sail
npm run artisan:host -- migrate
npm run composer -- require x
npm run test                  # parallel, memory_limit pinned
npm run test:host             # containers down, SQLite only
npm run test:serial
npm run test:impact
npm run test:browser           # tests/Browser only, real Chromium via pest-plugin-browser
npm run lint                  # pint --test; `npm run composer -- lint` writes the fixes
npm run stan                  # phpstan
npm run types:generate        # PHP Data classes -> packages/shared/src/generated.ts
npm run boost:update          # .ai/guidelines.md + the .claude/skills mirror
npm run skills:restore        # after npm install on a fresh clone: the vendored Expo skills, from skills-lock.json
npm run web                   # vite
npm run mobile                # expo start, outside sail
npm run typecheck             # tsc across every workspace
```

## Sibling repos

`~/repos/private/first-move` is the same problem space, narrower, and is where
these conventions came from. `~/repos/private/tabellio` and `numerosis` are the
older house references for testing and architecture rules.

## Laravel Boost

`npm run boost:update` regenerates `.ai/guidelines.md` and re-mirrors
`.ai/skills/` into `.claude/skills/`. `.mcp.json` is hand-maintained and left out
of that: Boost writes the artisan path relative to the Laravel base path, which
is wrong from the root every client starts in. Its `vendor/bin/sail ...` examples
have the same problem — from the root that is `backend/vendor/bin/sail`, so use
the root scripts above.

@.ai/guidelines.md
