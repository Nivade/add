# add

An external executive-function system for people with ADHD: every screen answers "what do I do right now", and the answer is one thing. The spec is [`docs/product-spec.md`](docs/product-spec.md).

## Getting things in

**Capture**:
Raw text the person wrote or said, kept exactly as given and never edited.
_Avoid_: Task, note, entry

**Kind**:
What the model sorted a capture into: thought, waiting-for, promise, reminder, or not for you. Marked inferred until the person confirms or changes it.
_Avoid_: Category, type, tag

**Read-back**:
Home showing a freshly sorted capture with its kind, so the person can say "that's right" or change it.

## Doing things

**Intention**:
Something the person wants handled, made from a capture and broken into steps.
_Avoid_: Task, project, goal, todo

**Step**:
One physical action inside an intention, with an estimate and an optional place. The spec's "Next Action" is a step.
_Avoid_: Action (that word means a use-case class in this codebase), subtask

**Next action**:
The single step the resolver recommends right now, always shipped with its why.
_Avoid_: Top task, priority

**Why**:
The one sentence saying why the next action was chosen, built from the rung that decided it and never written by a model.
_Avoid_: Reason, explanation, rationale

**Rung**:
One named comparator in the resolver's ordered chain; the first rung that separates two steps decides between them.
_Avoid_: Score, weight, priority

**Session**:
A stretch of focused work on an intention, which may span several steps and outlives any one of them. Ends continued, completed or stopped.
_Avoid_: Attempt, run; never failed or abandoned

**Overwhelmed**:
The mode that collapses everything to one small step and states, without listing, how much else exists.

**Stuck reason**:
What the person says is in the way of a step; each maps to one resolution: split it, move on to the next step, stop, stay put, or do it elsewhere.

## Time and place

**Appointment**:
Anything with a real time: a dated intention or a calendar event. Not a table, a contract both answer.
_Avoid_: Event, meeting, due date

**Deadline**:
A real appointment time or legal deadline on an intention. Nullable; invented deadlines are not modelled.
_Avoid_: Due date, overdue

**Plan rung**:
A preparation point planned backwards from an appointment: find things, get ready, leave.
_Avoid_: Rung alone, which means a resolver comparator

**Place**:
Where a step has to happen (home, work, out, computer), or nowhere in particular.
_Avoid_: Context, location

**Whereabouts**:
Where the person seems to be, computed on each resolve from recent evidence and never stored.
_Avoid_: Location, position

## Promises and waiting

**Waiting-for**:
Something the person is waiting on from someone else.

**Commitment**:
Something the person promised, with a provenance: their own task, something they stated, or something the system inferred. An inferred one stays unconfirmed until they say so.
_Avoid_: Obligation, promise (the capture kind becomes a commitment)

**Future reminder**:
A note to a future self, at a time or relative to a calendar event.
_Avoid_: Alarm, notification

**Reminder**:
The one notification an appointment earns, sent at most once.

## Measuring

**Check-in**:
The fortnightly question home asks; its answers are immutable and are the user-reported metric.
_Avoid_: Survey, rating
