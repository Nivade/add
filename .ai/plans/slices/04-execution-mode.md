# Slice 4 — execution mode

*Spec: [`product-spec.md`](../product-spec.md) §10, §11, §12, §18. Rules:
`product-invariants.md`, `domain-model.md` (the session outlives the step).*

## Shape

The app controls the flow and shows one step. The rest of the intention is
reachable only if the person asks for it.

Six controls, always present, same size and tone: Done, Skip, Pause, I'm stuck,
I got distracted, Stop. Skip advances; it does not accuse.

`SkipStep` records the skip and leaves the step `pending`, because the resolver's
cool-off needs the step back afterwards. Nothing writes `StepStatus::Skipped`:
it belongs to a step the intention finished around, and finishing an intention
by hand is a control this slice does not have. It arrives with the screen that
does.

## The state machine

Three states, and only one of them is terminal:

| State | Row |
| --- | --- |
| running | `ended_at` null, `paused_at` null |
| paused | `ended_at` null, `paused_at` set |
| ended | `ended_at` set, `outcome` set |

Pausing writes no outcome. An outcome is a claim about how a stretch of work
finished, and someone who paused has not finished it — they are coming back, and
the resolver's continuity rule should still return them to the step they left.
Resuming clears `paused_at` on the same session, so the stretch stays one row
and "welcome back" is a read, not a reconstruction.

Stop is the only control that ends a session by choice. `Completed` is written
when the last remaining step is done, and `Continued` when the session runs out
of steps it can offer but the intention is not finished.

`Completed` also finishes the intention itself. An intention whose last step is
done is done, and leaving it `Active` would have the resolver offer it forever —
this is the loop closing on its own, not the by-hand control above.

Ending a session is one action, so the guard that refuses a second landing
cannot hold on one path and be missing from another. Advancing asserts the same
thing before it moves: a landed session pointed at a fresh step is a resurrected
one.

Every transition writes one `execution_event`, and a transition that cannot be
made throws rather than silently no-opping — a landed session cannot be landed
twice. The events are the replay: `started`, `step_completed`, `step_skipped`,
`paused`, `resumed`, `stuck`, `distracted`, `stopped`, each carrying the step it
happened to and nothing the row does not already hold.

## Advancing

`current_step_id` moves to the next `pending` step of the intention by position,
wrapping to the front so a skip early in the list does not strand the tail. When
nothing is left to point at, the session ends: `Completed` if no pending steps
remain, `Continued` if the only ones left are being held back.

## I'm stuck (§11)

First-class interaction, not a link to help. Asks what is blocking, from
`StuckReason`'s seven cases, then adapts:

| Answer | Behaviour |
| --- | --- |
| `TooBig`, `DontKnowWhatToDo` | move to the shortest sibling now, deterministically, and queue the AI split of the step that was too big (`StuckReason::wantsSmallerStep()` already draws this line) |
| `NeedSomething`, `NotEnoughInformation` | record the blocker in the event payload, move to another step |
| `Tired`, `DontWantTo` | end the session `stopped` — a real answer, not a failure |
| `SomethingElse` | free text, stored in the payload, surfaced to nobody |

The deterministic move happens first and is what the response is built from. The
queued split replaces the step with smaller ones when the model answers, and the
person is never left waiting on it — `ai-layer.md` is the reason.

## I got distracted (§12)

Ends nothing. Returning shows the intention, the count of steps done, and
Continue. The interruption is one `execution_event` and is never scored.

## Progress (§18)

Composed from counts the rows already hold — steps done in this session, things
finished today — and never from consecutive days. It is built the way the
resolver's `why` is built, from facts, so no copy can claim something that did
not happen.

## Done when

- The state machine is exhaustively tested, including resume after a day away.
- `execution_events` replay reconstructs the session.
- Every stuck reason has a path that leaves the person with something to do or a
  clean stop.
- `GET /api/v1/sessions/current` answers a null-shaped 200.
