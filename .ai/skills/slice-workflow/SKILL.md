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
| A slice's state | the table in `executive-function-os.md`, and the plan's own header |
| Design for a slice | `.ai/plans/slices/` |
| An invariant, trap or standing constraint | `.ai/rules/` |
| What changed | `git log` |
| Something noticed but out of scope | `.ai/findings.md`, via the `note-finding` skill |

A slice document opens by citing its spec sections and the rule files that bind
it. Keep that habit when writing one.

## The life of a plan

Every file under `.ai/plans/` except the spec and the spine opens with its state
on the second line, under the title and above the spec citation:

```markdown
**State:** done, 2026-09-20 · [the slice table](../executive-function-os.md#slices)
```

This is the one fact the set repeats on purpose. The spine is an index, and
whoever arrives by following a link into a plan never reads it — a finished plan
that does not say so gets re-executed. `DocumentationTest` fails when the header
and the table disagree, so the copy cannot drift.

Five words, and nothing else is a state:

| Word | Means | Followed by |
| --- | --- | --- |
| `designed` | written, not started | the date it was written |
| `building` | in progress now | the date it started |
| `done` | executed | the date it finished |
| `superseded` | a later plan replaced it | ` by [that plan](link)` |
| `abandoned` | decided against | `, <the reason>` |

`abandoned` and `superseded` matter more than they look. A plan that lost an
argument is the one most likely to be picked up and built by someone who found
the file and assumed it was pending.

## Archiving is routing, not moving

`done` is not dead. A finished plan usually still carries an Open section, and
those are live decisions — `hardening.md` is `done` and four of its decisions
have no other home. Move the file and they are buried.

So a plan leaves `.ai/plans/` only after its open items have somewhere to live:

1. Route each one — a constraint to `.ai/rules/`, a loose end to
   `.ai/findings.md` via `note-finding`, deferred design to the slice that
   inherits it.
2. `git mv` it to `.ai/plans/archive/`, and fix the relative links the extra
   directory broke.
3. `grep -rn '<filename>' .ai CLAUDE.md backend/tests` — rules and skills link
   plans too, and skills are excluded from the link guard, so those break
   silently.
4. Repoint the spine's row at `archive/`. The row stays; the state stays.

`product-spec.md`, `executive-function-os.md`, `RESUME.md` and `findings.md` are
never archived. Neither are the slice files: "why isn't this built" is a
question they answer for as long as the product exists, and their numbering
already puts them in order.

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
path exists, a documented `npm run` or `composer` script is a real script, and
every plan's state matches the spine's.
A path the design names before it is built goes in that test's `$planned` list,
which fails once the path exists — so building it forces the document to be
reread.

## Finishing

Finish with the `finish-branch` skill — it runs `code-review`, then `simplify`,
then the local suite (`npm run test`, `npm run stan`, `npm run lint`,
`composer refactor:check`), then opens the PR. `merge-gate` refuses `gh pr merge`
on a branch that skipped it.
Update `.ai/RESUME.md`, the slice table and the plan's own state header with the
`update-resume` skill, which also routes the session's decisions to the files
that own them.
