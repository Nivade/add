# Slice 4 — execution mode

*Spec: [`product-spec.md`](../product-spec.md) §10, §11, §12, §18. Rules:
`product-invariants.md`, `domain-model.md` (the session outlives the step).*

**Scope, not design.** What the spec asks for, placed. The state machine's
shape, the stuck follow-up copy and the event payloads are decided when the
slice starts.

## Shape

The app controls the flow and shows one step. The rest of the intention is
reachable only if the person asks for it.

Six controls, always present, same size and tone: Done, Skip, Pause, I'm stuck,
I got distracted, Stop. Skip advances; it does not accuse.

`SkipStep` already records the skip and leaves the step `pending`, because the
resolver's cool-off needs the step back afterwards. This slice decides the one
thing that is still open about it: what moves a step to `StepStatus::Skipped`,
which is a step the intention finished without.

## I'm stuck (§11)

First-class interaction, not a link to help. Asks what is blocking, from
`StuckReason`'s seven cases, then adapts:

| Answer | Behaviour |
| --- | --- |
| `TooBig`, `DontKnowWhatToDo` | re-decompose this step smaller — AI, queued, with a deterministic "offer the shortest sibling" fallback (`StuckReason::wantsSmallerStep()` already draws this line) |
| `NeedSomething`, `NotEnoughInformation` | record the blocker, move to another step |
| `Tired`, `DontWantTo` | end the session `stopped` — a real answer, not a failure |
| `SomethingElse` | free text, stored, surfaced to nobody |

## I got distracted (§12)

Ends nothing. Returning shows the intention, the count of steps done, and
Continue. The interruption is one `execution_event` and is never scored.

## Progress (§18)

Factual and earned — "you finished the hardest part", "four things today you had
been putting off". Totals, never consecutive days.

## Done when

- The state machine is exhaustively tested, including resume after a day away.
- `execution_events` replay reconstructs the session.
- Every stuck reason has a path that leaves the person with something to do or a
  clean stop.

## Open

Pause versus Stop: whether pausing writes an outcome at all, or is simply the
absence of an open session with a `current_step_id`.
