---
name: slice-workflow
description: Use at the start of any piece of feature work, before planning or writing code, to find which slice it belongs to and what that slice already decided. Also use when asked what to build next, when a design question needs an answer the code does not carry, when something looks unbuilt and might be deliberately unbuilt, and when recording a decision that outlives the task. Trigger on "what's next", "which slice", "start slice", "the plan", "the spec says", "is this in scope", "why isn't this built", `.ai/plans/**`.
---

# Working a slice

The build is sliced, and each slice has a design document. Read the one for the
slice being built, not all of them.

## Reading order

1. `CLAUDE.md`, then `.ai/rules/overview.md` and `.ai/rules/index.md`.
2. `.ai/RESUME.md` — where the build stopped, and nothing else.
3. `.ai/plans/executive-function-os.md` — the slice table, which carries each
   slice's state, the stack as installed, and the domain model.
4. `.ai/plans/slices/<n>-<name>.md` — the design for this slice.
5. `.ai/plans/product-spec.md` — the original prompt, verbatim, cited by section
   number. Authoritative on intent.

`.ai/plans/hardening.md` records what the pass over the earlier slices settled
and what it deliberately left open.

## Each document owns one thing

Putting a fact in the wrong file is how the set goes stale.

| Kind of fact | Where it lives |
| --- | --- |
| Where the build stopped | `.ai/RESUME.md` |
| A slice's state | the table in `executive-function-os.md` |
| Design for a slice | `.ai/plans/slices/` |
| An invariant, trap or standing constraint | `.ai/rules/` |
| What changed | `git log` |
| Something noticed but out of scope | `.ai/findings.md`, via the `note-finding` skill |

A slice document opens by citing its spec sections and the rule files that bind
it. Keep that habit when writing one.

## Deliberately unbuilt is not a gap

The plan records what is not built and why. Before building something that looks
missing, check whether it was declined — named preparation items, push, per-user
AI consent, the deferred spec models (`Project`, `Task`, `Commitment`,
`WaitingFor`, `Context`, `Document`). Reopening one of those needs a new
decision, not a refactor commit.

When the spec and `~/repos/private/first-move` disagree, the spec wins.
first-move contributed conventions only — never scope or product decisions.

## Never write a count or a status sentence

No test counts, error counts or file counts anywhere under `.ai/`, and no "the
suite is green" sentence that a command answers faster and a commit falsifies
silently. State the decision and its reason, which does not move, rather than
the shape, which does.

`tests/Feature/Guards/DocumentationTest.php` holds down the machine-visible part
of this across `.ai/**` and `CLAUDE.md`: relative links resolve, a backticked
path exists, and a documented `npm run` or `composer` script is a real script.
A path the design names before it is built goes in that test's `$planned` list,
which fails once the path exists — so building it forces the document to be
reread.

## Finishing

Run `npm run test`, `npm run stan` and `npm run lint`, then `composer refactor:check`.
Update `.ai/RESUME.md` and the slice table with the `update-resume` skill, which
also routes the session's decisions to the files that own them.
