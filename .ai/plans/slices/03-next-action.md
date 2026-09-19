# Slice 3 — next action

*Spec: [`product-spec.md`](../product-spec.md) §9, §26. Rules that bind this
slice: `product-invariants.md` (every recommendation is explainable),
`testing.md` (the engine gets scenario tests), `ai-layer.md` (nothing here is
generated).*

The engine is the product. A bad recommendation is worse than a list, because a
list at least does not claim to have thought about it.

## Interface

```php
interface NextActionResolver
{
    public function resolve(User $user, ResolutionContext $context): ?NextActionData;
}
```

One method. Everything the caller needs to know: it returns the single most
useful step or `null`, it never writes, and the `why` is always populated when
the step is.

`ResolutionContext` is a value object — `now`, and later the minutes available
and the place. It is **not** the spec's `Context` model (§4), which stays
deferred; this is the parameter §26 asks for, carrying only what exists. Naming
them apart now avoids a rename when the model lands.

`now` carries the person's timezone, the way the capture pipeline already reads
one. A deadline comparator that plans backwards in UTC for someone who is not in
UTC is wrong by hours in the direction that recommends too late.

Depth check: behind that one method sit candidate gathering, session
continuity, six comparators and the `why` composition. Delete the module and
that logic reappears in the home controller, the API controller, the mobile
client and slice 6's overwhelm path. It earns its keep.

## How it decides

No score. §9 rejects "task #183 has priority 87", and a score is also what makes
`why` unwritable — you cannot explain a number that six inputs went into.

Three stages:

**1. Eligibility.** Hard filters, no ordering: step is `pending`, its intention
is active, its intention has been decomposed, and its intention is not
`needs_clarification`. Ineligible steps are invisible, never ranked low.

Everything decomposed and clear competes — there is no triage gate, because
promoting work by hand is the mandatory categorisation §35 rules out.

An intention the model could not name is held back rather than ranked low.
Offering "Sort the thing out" as the one thing to do now is §8's failure, and
`product-invariants.md` forbids an inference becoming fact on its own. It
surfaces in slice 5's "Needs attention" band instead, which is where §5 puts it.

**2. Continuity.** An open `execution_session` with a `current_step_id` wins
outright. Someone mid-task is not asking what to do — they are asking what is
next in the thing they are already doing, and re-ranking mid-session is how an
app that claims to reduce deciding starts making the person decide again.

**3. Comparator chain.** Candidates sort lexicographically through an ordered
list of named comparators. The first comparator that separates two steps decides
between them; later ones never override it. Ordered:

| # | Comparator | Separates on |
| --- | --- | --- |
| 1 | `DeadlineWithinReach` | a `deadline_at` close enough that the remaining steps barely fit |
| 2 | `HasDeadline` | a real deadline beats no deadline |
| 3 | `NotRecentlySkipped` | a step skipped in the last stretch sinks below one that was not |
| 4 | `StartableNow` | shorter `estimated_seconds` wins; starting is the hard part |
| 5 | `OldestIntention` | the thing that has been waiting longest |
| 6 | `EarliestPosition` | the decomposer's order, then ULID |

Comparator 6 is a total order, so the chain is deterministic and two runs over
the same world return the same step.

Each comparator is a seam of its own — an internal one, private to the
resolver's implementation. The chain's composition is where the product opinion
lives, and reordering it is a product decision, not a refactor.

## `why`, composed not written

`NextActionData::$why` is `list<string>`. It is built from the comparators that
**actually fired**: the one that separated the winner from the runner-up, plus
any that qualified it. Nothing else may write into it, and no model ever does.

> Your appointment is tomorrow and this takes about five minutes.

is comparator 1 and comparator 4 rendered, not prose someone composed. A step
that wins only on comparator 6 gets the honest line — it is simply next — rather
than an invented reason.

The `why` is therefore a test surface. If the chain reorders and the copy does
not change, the copy was decoration.

## Inputs, and when they arrive

§9 lists fourteen. Available now: `deadline_at`, `estimated_seconds`, step
position, open session, skip history, intention age.

| Not yet | Arrives with |
| --- | --- |
| appointments, energy and time budget | slice 6 |
| dependencies, blocked-ness | slice 8 (waiting-for) |
| location, available tools | the deferred `Context` model |

Each one enters as a comparator inserted into the chain, not as a new parameter
on `resolve()`. That is what keeps the interface small while the behaviour grows.

## Skip is not a demotion

`NotRecentlySkipped` is a cool-off, not a penalty. A step skipped twice drops
below its peers for a stretch and then comes back at full standing. It is never
buried, never marked, and the person is never told they have been avoiding it —
`product-invariants.md` covers why, and §18 allows the same fact stated
neutrally as progress copy later.

## Done when

Scenario tests pass. Each builds a whole world and asserts both the step and the
`why` — a test asserting only "something came back" proves nothing:

- Empty world → `null`, and `GET /api/v1/next-action` answers a null-shaped 200.
- Open session → its `current_step_id`, ignoring a more urgent step elsewhere.
- Deadline tomorrow with three steps left beats a bigger deadline-free intention.
- A step skipped twice is not offered a third time in the same stretch, and is
  offered again after it.
- An intention the model could not name is never offered, even when it is the
  only work in the world — the answer is `null`.
- Two identical worlds → the same step, run repeatedly.
- Every non-null answer has a non-empty `why`.
- Skipped state is reached by calling the skip action, never by writing the row
  — `testing.md` has the reasoning.

## Not this slice

Home renders it (slice 5). Starting it opens a session (slice 4). Overwhelm mode
asks a different question — the shortest finishable step, not the most useful —
and gets its own entry point in slice 6 rather than a flag on `resolve()`.
