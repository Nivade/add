---
name: next-action-resolver
description: Use when touching how the single next step is chosen or explained — `backend/app/Support/NextAction/**`, `App\Contracts\NextActionResolver`, any comparator or rung, `ResolutionContext`, `Candidate`, `CandidatePool`, `NextActionData`, or the `why` string. Also use when the recommendation or its explanation is wrong, when adding a ranking input, when writing tests for the engine, and before changing the `GET /api/v1/next-action` response. Trigger on "next action", "which step comes first", "ranking", "the why is wrong", "resolver", "prioritise", "comparator", "rung".
---

# The next-action engine

The engine is the product. A bad recommendation is worse than a list, because a
list at least does not claim to have thought about it.

`NextActionResolver` has one method. It returns the single most useful step or
`null`, it never writes, and the `why` is populated whenever a step is. The
interface is bound to `ChainedNextActionResolver` with `#[Bind]` on the
contract itself — there is no service-provider registration to hunt for.

## A chain of comparators, not a score

Ranking is a `usort` over an ordered chain, each rung returning an int. The
first rung that returns non-zero decides. There is deliberately no numeric
score: a number six inputs went into cannot be explained, and every
recommendation has to be.

The chain runs highest-priority first: `DeadlineWithinReach`, `HasDeadline`,
`NotRecentlySkipped`, `PrerequisiteFirst`, `StartableNow`, `OldestIntention`,
`EarliestPosition`. Read the classes for the current order rather than trusting
this line.

A running session's `current_step_id` short-circuits the whole chain before
ranking — being part-way through beats every other consideration.

## Adding a ranking input

Extend `Rung`, which no-ops `decides()` and `qualifies()` so only the rungs that
speak override them. Implement:

- `compare()` — the ordering.
- `decides()` — the sentence for when *this* rung is what separated the winner
  from the field.
- `qualifies()` — the supporting clause for when a rung above it already
  decided.

Then insert it into `ChainedNextActionResolver::$chain` at the position its
priority demands. Position is the whole design; a rung added at the end changes
nothing until everything above it ties.

## How the `why` is built

`separator()` finds the highest rung that put anything below the winner — not
the runner-up alone, because a sibling step of the same intention ties all the
way down and would hide the real reason. That rung's `decides()` leads, every
rung below it contributes a `qualifies()`, and nulls are filtered out. If
nothing separated anything, the last rung's `decides()` is the fallback.

Never return an empty `why`. A recommendation that cannot be explained cannot be
recommended.

## `ResolutionContext` is a value object

`now` carries the person's timezone, so planning backwards from a deadline lands
on their day rather than UTC's. `availableSeconds` is the time until the next
leave-by rung, or `null` when the day is open. `ResolutionContext::forUser()`
assembles it from `NextAppointment` and `BackwardsPlan`.

It is **not** the spec's `Context` model. Do not grow it into one.

## Candidate cost

A step nobody estimated is costed at a fixed assumption rather than treated as
free or as unknown, and "only just fits" means doing the work at half speed
would still run past the deadline. Both constants live on `Candidate`; change
them there, not at a call site.

A deadline already behind us is not a reach — there is no stretch of day left to
fit the work into. `deadline_at` is the only deadline word in this codebase, it
is nullable, and there is no `overdue`.

## Testing it

The engine gets scenario tests, not example tests. A test builds a whole world —
an appointment in forty minutes, a five-minute step, a blocked step, a step
skipped twice — and asserts which step comes back **and** what the `why` says. A
test that only asserts something came back proves nothing.

Drive failures through the code that produces them. Writing a skipped row
straight into the database and asserting the resolver moved on tests the
fixture, not the application; bind a double for the action the code calls and
let the real path run.

`GET /api/v1/next-action` returns `null` inside a 200, never a 404. A cold
launch reads it and a 404 there reads as an error to every client.
