# Rules Overview

Read this before `index.md`.

**`index.md` is hand-maintained.** Boost rewrites it only when it has scoped
rules of its own under `.ai/rules/boost/`, and that is switched off in
`backend/config/boost.php` — Boost scopes its rules to `app/**`, which never
matches this repo's `backend/app/**`, so they stay inline in `.ai/guidelines.md`
instead. Add the row yourself when you add a rule file.

**These are traps, not orientation.** Readable straight from the code? It does
not belong here. What belongs: invariants, cross-file constraints, and decisions
that look wrong without their reason.

## Where the surprising decisions live

| About to... | Read first |
| --- | --- |
| Build any UI, or name a status | `product-invariants.md` — the product is defined by what it never does |
| Write a migration or model | `domain-model.md` — the spec's "Next Action" is the model `Step` |
| Add an endpoint or DTO | `api-and-data.md` — laravel-data, not API Resources |
| Ask a model anything | `ai-layer.md` — deterministic first, and failures are loud |
| Write a test | `testing.md` — parallel by default, and it has sharp edges |
| Touch the stack or containers | `toolchain.md` |

## Everything for an agent lives in `.ai/`

`.claude/` holds settings and whatever a tool insists on generating there.
`settings.json` and `.claude/hooks/` come from devtools' `claude` component;
`.claude/rules/` links each rule here so Claude Code loads it by its `paths`.
Skills are written in `.ai/skills/` and Boost mirrors them into `.claude/skills/`,
which is gitignored, because that is the only place Claude Code discovers project
skills. Edit the copy in `.ai/`; the other one is overwritten. Third-party skills
live in `.agents/skills/`, restored from `skills-lock.json` and linked by
`npm run skills:link`.

## Rule, skill, or hook

The three carry different kinds of knowledge and are not interchangeable.

| Kind | Where | Reaches an agent when |
| --- | --- | --- |
| A constraint that is always true | `.ai/rules/` | the path matches |
| A procedure with steps | `.ai/skills/` | the task starts |
| The reminder that either exists | `.claude/hooks/` | the harness fires it |

A standing constraint does not become a skill because it keeps getting missed.
`.claude/hooks/skill-hook.sh` names a skill when a prompt matches its
`metadata.keywords` or an edited file matches its `paths`, so a skill this repo
owns carries both in its frontmatter. `.claude/hooks/lifecycle.sh` gates merges
on the review line `finish-branch` writes into the MR. `.claude/settings.json`
wires them, and switches off installed skills whose advice contradicts a rule
here — `laravel-patterns` recommending API Resources is the reason that list
exists.

Prose asking an agent to remember something is the weakest of the three. Prefer
a rule a guard test can fail, then a hook that fires at the moment of the
mistake, then a skill.

`.ai/guidelines.md` is Boost's output — regenerate it with `boost:update`, never
hand-edit it. `CLAUDE.md` pulls it in, and stays hand-written itself.

Boost resolves its paths from the Laravel base path, which is `backend/`, so
`backend/.ai` is a committed symlink to the real `.ai/` at the root. Deleting it
sends the next `boost:update` into `backend/.ai/`, and breaks the `.claude/rules/`
links, which point through it.

## Product in one line

The person should never have to work out what to do next when the application
can work it out for them. Every screen answers "what do I need to think about
right now", and the answer is usually one thing.

The spec is [`docs/product-spec.md`](../../docs/product-spec.md), verbatim and
cited by section number. [`docs/adr/`](../../docs/adr/) records where the build
deviates from it and what is deliberately not built; the work itself is in the
tracker.

## Counts and dates

Never write a test count, an error count or a file count into a document here.
They go stale within a few commits and a stale count invites trusting a rule
that has moved underneath it.
