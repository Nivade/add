---
name: generated-artifacts
description: Read before editing, creating or "fixing" any file that a command owns — `packages/shared/src/generated.ts`, `backend/resources/js/actions/**`, `backend/resources/js/routes/**`, `backend/resources/js/wayfinder/**`, `.ai/guidelines.md`, `.claude/skills/**`, `backend/compose.yaml`. Also use when a type, route helper or guideline looks wrong or missing and the instinct is to hand-write it, when a TypeScript import from `@add/shared` or `@/actions` does not resolve, or when a date field arrives as `undefined` on the frontend. Trigger on "generated", "regenerate", "types:generate", "wayfinder", "boost:update", "the type is wrong", "add this to generated.ts".
---

# Files a command owns

Editing one of these looks like it works, then the next run of its generator
silently reverts it. Change the source, run the generator.

| File or tree | Generator | Source of truth |
| --- | --- | --- |
| `packages/shared/src/generated.ts` | `npm run types:generate` | PHP classes in `backend/app/Data/**` and `backend/app/Enums/**` |
| `backend/resources/js/actions/**`, `routes/**`, `wayfinder/**` | Wayfinder, via the Vite plugin | `backend/routes/*.php` and the controllers |
| `.ai/guidelines.md` | `npm run boost:update` | Boost's package guidelines plus `backend/config/boost.php` |
| `.claude/skills/**` | `npm run boost:update` | `.ai/skills/**` |
| `packages/shared/src/typescript-transformer-manifest.json` | `npm run types:generate` | nothing — it is a hash |

The three Wayfinder trees and `.claude/skills/` are gitignored. That is the
tell: a gitignored path under version control's nose is generated.

## Publishing a type to the frontends

`#[TypeScript]` on the class or enum is the only mechanism, enums included —
`AttributedEnumTransformer` gates on that attribute. Add it, then
`npm run types:generate`.

Config lives in `backend/app/Providers/TypeScriptTransformerServiceProvider.php`;
transformer v3 has no publishable config file. Three things it must keep doing:

- Replace `CarbonImmutable` with `string`. Without it every date silently
  becomes `undefined` on the frontend while the command still prints its
  success line.
- Write through `FlatModuleWriter`. The global writer emits an ambient
  namespace that cannot be imported from a package.
- Route enums through `AttributedEnumTransformer`.

## Skills are authored in `.ai/`, never in `.claude/`

`.claude/` holds settings and generated mirrors. Write a skill in
`.ai/skills/<name>/SKILL.md` and run `npm run boost:update`; the copy under
`.claude/skills/` is overwritten.

`SkillComposer` merges `.ai/skills/` last, so a repo skill silently masks a
Boost skill of the same name. `inertia-react-development` is deliberately one of
those — Boost ships it for Inertia 1 and 2 only and the installed major is 3. On
a Boost upgrade, check `boost:list-skills` and delete the local copy if a v3
version appears upstream.

To drop a Boost-bundled skill or guideline instead of masking it, add its name
to `skills.exclude` or `guidelines.exclude` in `backend/config/boost.php`.

## `compose.yaml` is hand-edited, and sync would revert it

Generated once by `devtools:install`, then edited to drop the `mysql` service,
its `depends_on` entry and the `sail-mysql` volume. `devtools:sync` is therefore
never run. Removing a service means removing its named volume **and** that
volume's `driver: local` line; orphaning one breaks the YAML in a way the error
does not point at. Validate with `docker compose config --quiet`.

`.mcp.json` is hand-maintained for the same reason: Boost writes the Artisan
path relative to the Laravel base path, which is wrong from the root every
client starts in.
