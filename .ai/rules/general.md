---
paths:
  - '**'
---
# General

## Refactor freely — nothing is deployed

No production, no users, no data worth preserving. What is being optimised is
the shape of the codebase, not continuity with its own past.

- Rename, move, merge, delete, and migrate every caller in the same change.
  Never leave an alias or a deprecated wrapper — a shim nobody needs is worse
  than the wrong name, because the next reader has to work out which is live.
- No compatibility layers: no versioned dual paths, no flag protecting an old
  shape.
- `migrate:fresh` beats writing a backfill.

Not relaxed by this: the suite still passes, tests are not deleted without
approval, and a decision recorded in `.ai/rules` is not "legacy" — reopening one
needs a new decision, not a refactor commit.

**Expiry:** the first real user.

## Comments carry one fact, and never cite a rule file

A comment must not narrate what the code does — if it needs explaining, the name
is wrong. One line is the normal maximum, and that applies to plans and rules
too.

A comment must not reference `.ai/rules`, `.ai/plans` or a
skill: a reader of the source cannot see those and they go stale independently.
`tests/` is exempt — a test that exists to hold a rule down may name it, because
the citation is the test's subject.

`@param`, `@return`, `@var`, `@template` and array shapes are tags, not prose.
PHPStan reads them; they stay.

## Prefer attributes over class properties

Laravel 13 has attributes for most of what used to be a property — `#[Table]`,
`#[Fillable]`, `#[Hidden]`, `#[Queue]`, `#[Tries]`, `#[Signature]`,
`#[Singleton]`. Use them, with the `use` import, rather than scattering config
through the class body.

## Rules are held down by tests, not goodwill

`tests/Feature/Guards/ConventionsTest.php` fails the suite when a rule here is
violated. When adding a rule, ask whether it can be a test — a rule nothing can
fail rots. Verify a new guard by breaking the thing it guards and watching it
fail; a guard that cannot fail is worse than none, because it reads like
coverage.

## Documentation that restates code goes stale

State the decision and its reason, which does not move, rather than the shape,
which does. Never write a count into a document, and never write a status
sentence — "the suite is green", "nothing is committed" — that a command answers
faster and a commit falsifies silently.

`tests/Feature/Guards/DocumentationTest.php` holds down the part of this a
machine can see, across `.ai/**`, `docs/**`, `CLAUDE.md` and `GLOSSARY.md`:
relative links resolve, a backticked path exists, and a documented `npm run` or
`composer` script is a real script. `.ai/skills/**` is exempt, because those come
from devtools and the catalog and teach with example paths of their own;
`.ai/plans/legacy/` is exempt, because it is history.

Everything the guard cannot see is the same trade as the rest of this file: the
shape belongs in the code, and the document says why. Where the build stands
belongs in the tracker and `git log`, never in a file here.
