---
paths:
  - 'backend/resources/js/**'
  - 'backend/app/Enums/**'
  - 'mobile/**'
  - 'packages/shared/**'
---
# Product Invariants

Each one exists because the opposite turns the app into another source of shame,
and a shaming app gets deleted by the person who needed it most.

## Never

**No `failed`, no `abandoned`.** Session outcome is
`continued | completed | stopped`. Enum words leak into copy eventually.

**No `overdue`, no `due_at`.** A real appointment or legal deadline is
`deadline_at` and is nullable. Invented deadlines are not modelled at all.

**No streaks, no XP, no punishment for inactivity.** Count totals, never
consecutive days.

**No backlog dumps.** Execution mode shows one step. "I'm overwhelmed" shows one
step. The count of everything else may be stated ("47 things, none of which you
need to think about") but never enumerated in those screens.

**No cheerleading.** No exclamation marks, no willpower, discipline or
motivation talk, no "you've been avoiding this for 11 days" framed as a rebuke.
The same fact stated neutrally is fine and is the point of the progress copy.

**No silent commitments.** Anything the system inferred is marked as inferred
and is confirmable. It never becomes fact on its own.

## Always

**Zero required fields on capture.** A capture is one text column. Everything
else is filled in later, by the app, not the person.

**Skip and stop carry the same weight as done.** Same button size, same tone.

**Recovery, never judgement.** "I got distracted" returns the person to where
they were and says welcome back. The interruption may be recorded; it is never
scored.

**Every recommendation is explainable.** The next action ships with a `why`
built from the inputs that ranked it. If it cannot be explained, it cannot be
recommended.

**Design for the bad day.** The path that must work: open the app, one thing is
there, one tap starts it. Test that path, not the motivated one.

## Success is the person leaving

Measure completed intentions, time from capture to first action, recovery after
a stopped session. Never optimise for time in app.
