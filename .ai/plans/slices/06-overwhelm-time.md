# Slice 6 — overwhelm and time

*Spec: [`product-spec.md`](../product-spec.md) §13, §14, §16, §22, §23. Rules:
`product-invariants.md`, `ai-layer.md` (time arithmetic is computed, never
generated).*

**Scope, not design.** The backwards-planning model and the reminder scheduler
are decided when the slice starts.

## I'm overwhelmed (§16)

Collapses the world to one small step — the **shortest finishable** thing, not
the most useful one — and suppresses everything else until it is done or the
person leaves.

A different question from slice 3's, so it gets its own entry point rather than
a flag on `resolve()`. Same invariant applies: one step, never a list.

It reads rather than writes, so the entry point is a `GET`, not the `POST` the
build plan first sketched. `ReduceToOneStep` ranks the same candidate pool the
resolver uses — shortest first, a timed step ahead of an unestimated one of the
same assumed cost — and honours the skip cool-off, because offering back the
thing just skipped is what the mode exists to avoid. The screen drops the shell:
no rail, no nav, no capture, one way back.

## Time blindness (§13)

Arithmetic, never a model: elapsed time in the session, typical duration for
this kind of step, and backwards planning from an appointment — leave by, get
ready by, find things by.

Every generated assumption is visible and editable. §13 is explicit that the
person can correct them, and a travel-time guess presented as fact is how the
app starts lying.

## Calendar (§22)

Enters read-only. An event stops being a row to display and becomes something
the system reasons about preparation for.

## Reminders (§14, §23)

A reminder carries the preparation, the leave-by time and what is still missing.
A bare "dentist tomorrow" is a bug.

Each notification answers why now, what to do, and what happens if it is
ignored — or it is not sent.

## Done when

- A 14:00 appointment produces an editable backwards plan.
- Overwhelm mode never shows a second item.
- No notification ships that fails the three questions.

## Open

Where estimated travel time comes from, and whether the first version simply
asks the person once and remembers.
