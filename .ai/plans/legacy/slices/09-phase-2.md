# Slice 9 — phase 2: waiting-for, commitments, future-self, and the rest

**State:** done, 2026-09-27 · [the slice table](../executive-function-os.md#slices)

*Spec: [`product-spec.md`](../product-spec.md) §15, §17, §19–§21, §33. Rules:
`product-invariants.md`, `domain-model.md`, `api-and-data.md`, `testing.md`.*

Slices 1–8 built and hardened the first journey: capture, decompose, execute,
recover. This slice is the spec's own "Phase 2" (§33) — the things that go wrong
between journeys, not during one. Someone else owes you something. You said
you'd do something. You'll remember later, not now. All three are the same
failure: something true and unfinished, sitting outside the one-thing screen
because it isn't actionable *yet*.

Six phases, ordered by how much they lean on something risky or unbuilt.
Ingestion is last because it is where the privacy surface grows fastest (risk
3) and needs its own consent story the way AI consent did in slice 8. Phases
1–3 need nothing but a new table and an explicit entry point each.

## The capture question, settled

Reversed 2026-09-30 by the UX overhaul plan: the model sorts, the read-back marks it inferred, and one tap changes it.

Captures stay intention-only. `ParseCaptureSchema` does not grow a `kind`
field, and `ConvertCaptureToIntention` does not branch. Classifying a sentence
as an intention, a waiting-for, or a commitment automatically is itself a form
of the mandatory categorisation §35 warns against — it just moves the decision
from the person to the model. Waiting-for and commitments each get their own
explicit action instead: a person taps "Waiting for" the way they tap "Capture
a thought," and it is a different row from the moment it is typed.

This also settles §21's three-way distinction for commitments cleanly.
`Commitment::provenance` is `user_task | user_stated | system_inferred` from
day one — the enum exists because the spec asks the system to *distinguish*
the three, not because this slice builds a detector. Automatic detection is
§34's "proactive identification of forgotten commitments," which is Phase 3,
not Phase 2. `system_inferred` is a real value with no producer yet, the same
shape `Appointment::appointmentInferred()` already uses for calendar times.

## Before any phase

Invoke `slice-workflow`, then `sail-and-root-scripts` before the first command.
Read every rule file whose globs cover the paths in scope, per `.ai/rules/index.md`.
Every phase that adds a model, migration, controller, queued command or cache
touches invokes `laravel-patterns` — with one override already on record:
`executive-function-os.md`'s own "Stack, as installed" section says this repo
uses `laravel-data` where `laravel-patterns` would recommend Eloquent API
Resources, and that stands. Every phase that writes a test invokes
`pest-testing` and `testing-best-practices`; every phase that touches PHP
invokes `phpstan-larastan` before finishing, and `generated-artifacts`
whenever a Data class or route changes what is generated. Something noticed
and out of scope goes through `note-finding`.

## Phase 1 — waiting-for

*Skills: `laravel-patterns`, `laravel-actions`, `laravel-data`, `laravel-attributes`,
`inertia-react-development`, `wayfinder-development`, `expo-react-native`.*

§20. A `waiting_fors` table: `user_id`, `subject` (who or what — "John",
"the contract"), `note` (what for), `status`
(`waiting | followed_up | cancelled | received`), timestamps. No AI: a person
types who and what, the same zero-friction shape as a capture, from a
"Waiting for" action next to "Capture a thought."

Surfacing reuses the mechanism slice 8 phase 2 already built rather than
inventing a second one: `HomeData.needsAttention` is currently
`list<IntentionData>` (an intention held back for clarification). It becomes a
union the way `ComingUpData` already unions `Intention` and `CalendarEvent` —
a waiting-for old enough to be worth a nudge shows there with "John hasn't
sent the contract yet" and the four responses as buttons. "Old enough" is a
fixed constant (days since `created_at` or the last answer, which
`last_answered_at` holds so an unrelated edit never resets it), not a
model call — deterministic, same as everything in `next-action-resolver`.

No backlog dump: at most one stale waiting-for competes for the band's
attention, same one-thing-at-a-time rule the clarifying-question slot already
follows.

**Done when** a waiting-for can be created, goes quiet, and reappears in
`needsAttention` past the threshold with all four responses wired; marking it
received or cancelled retires it for good.

## Phase 2 — commitments

*Skills: `laravel-patterns`, `laravel-actions`, `laravel-data`, `laravel-attributes`,
`inertia-react-development`, `wayfinder-development`.*

§21. A `commitments` table: `user_id`, `description`, `provenance`
(`user_task | user_stated | system_inferred`), `confirmed_at` nullable,
timestamps. Two provenance levels are reachable this slice:

- `user_task` — promoted from an existing intention or step, a confirm-only
  action that copies the description and stamps `confirmed_at` immediately,
  since the person is looking at the thing they are promoting.
- `user_stated` — typed directly ("I'll call Sarah Friday"), same immediate
  `confirmed_at`, since the person just said it themselves.

`system_inferred` rows need `confirmed_at` nullable and a confirm action per
product-invariants' "no silent commitments". Nothing produces one yet, but the
answers are built anyway: a commitment nothing displays is not tracked at all.

**Revised, 2026-09-27:** a commitment carries `status` (`open | kept | released`)
and an optional `intention_id`. Home's needs-attention band shows the one most
pressing open commitment — an unconfirmed inference first — with confirm, keep
and release; the full list is `/commitments`, reached only from that row.
An intention or the step on screen in focus can be promoted, once each:
promoting again reopens the one commitment, finishing the intention or step
keeps it, and a finished intention cannot be promoted.

**Done when** a commitment can be created from either reachable provenance,
carries the right label, and an inferred one — created directly in a test,
since nothing creates one in the product yet — renders on home as inferred and
unconfirmed rather than as fact, and can be confirmed, kept or released.

## Phase 3 — future-self reminders

*Skills: `laravel-patterns`, `laravel-actions`, `laravel-data`, `ai-layer-changes`.*

§15, time and calendar-context triggers only. Location triggers ("when I
leave work") stay deferred, the same call already made for calendar-event
preparation in slice 8's open items — they need a consent surface this slice
does not build. That rules out the spec's own headline example; the two
triggers actually in scope are "tomorrow at 5, remind me to buy dishwasher
tablets" (a timestamp) and "after the dentist, remind me to call the
pharmacy" (relative to a `calendar_events` row's `starts_at`).

A `future_reminders` table: `user_id`, `message`, `trigger_at` nullable,
`calendar_event_id` nullable with an `offset_seconds` when set, `sent_at`
nullable. Exactly one of `trigger_at` / `calendar_event_id` is set — a
database constraint, not just a validation rule, so a bad row cannot exist
even from a future bug.

**Revised, 2026-09-27:** every reminder stores `trigger_at`, which is `NOT NULL`.
A calendar-relative one also keeps `calendar_event_id` and `offset_seconds`, and
`SyncCalendar` moves its `trigger_at` when the event moves. The instant is then
one indexed column, and no driver needs its own constraint. Dispatch is a new scheduled command alongside
`reminders:dispatch`, not a branch inside it — appointments and future-self
notes are different shapes answering different questions, and
`SendDueReminders` is already at the size that earned `BuildExecutionState`
its own file in the Support/Concerns pass.

Parsing "tomorrow at 5" out of free text is the one place this slice's
deterministic-first rule bends: a relative time phrase needs the same
zone-aware extraction `ParseCaptureParser` already does for deadlines. Reuse
that parser's approach rather than a new AI operation — one capability, two
callers.

**Done when** a time-triggered note and a calendar-relative one both fire at
the right instant (a non-UTC user, asserted per `domain-model.md`'s existing
UTC-instant discipline) and read the way §15's example does: what you wanted,
stated plainly.

## Phase 4 — recurring steps

*Skills: `laravel-patterns`, `laravel-actions`, `domain-model.md` (which this phase is deciding).*

§33 names "recurring tasks" as one bullet with no example anywhere in the
spec — the smallest-specified phase here. Proposed minimal shape: a
`recurrence` on `Intention` (`every_days` nullable int, or a small
`RecurrenceRule` value object if a single interval proves too narrow once
drafted), set from a finished intention's own "repeat this" action rather
than at capture time. A scheduled command creates a fresh intention from the
template when due, the same `AsCommand` pattern `SendDueReminders` already
uses.

**Decided:** re-decompose each time the recurrence fires, through the same
`DecomposeIntention` path a fresh capture uses. No step template is stored or
copied forward; a stale step set is worse than a fresh decomposition pass.
Missed runs collapse into one fresh intention and the schedule keeps its
original rhythm, so a stalled scheduler never produces a backlog of copies.
"Repeat this" lives in home's just-finished band, shown for an hour after the
session that finished the intention. A copy points at its template and shows the
template's rhythm there, so repeating a copy can never start a second schedule.

## Phase 5 — body doubling, solo

*Skills: `pest-testing`, `inertia-react-development`, `expo-react-native`.*

§17 lists four modes; the slice-table paragraph commits to solo only. Solo is
described as "a guided execution session" — which is what execution mode
(slice 4) already is. **Closed, no code:** revisited after phases 1–4 and 6
shipped, per the earlier open question — execution mode answers §17's solo
requirement as built. Nothing added here.

## Phase 6 — email and document ingestion

*Skills: `laravel-patterns`, `ai-layer-changes`.*

§19. Risk 3 is explicit that ingestion was scoped out of the MVP "for this
reason, not only for effort," and slice 8's consent work (per-user, explicit,
logged) is the precedent this phase has to match before any provider is
wired. Scope for this slice is the port only: an `IngestionSource` contract
in `app/Contracts`, mirroring `CalendarSource`'s shape — no email provider,
no document parser, no automatic reading of anything. A manual source (paste
text, the same box as capture, routed through a distinct classification
prompt that only this phase adds) is the one adapter built, so the port has a
real caller without touching a mailbox.

**Revised, 2026-09-27:** the port was dropped. A contract, one adapter and a
provider around a single call was indirection with nothing on the other side;
`ClassifyPastedText` calls the AI layer directly, and the contract arrives with
the second source that needs it, in slice 10.

**Done when** pasted text is classified through the classification prompt, and
nothing in this phase reads external data without the same explicit-consent gate
slice 8 built for the AI path generally. The web paste dialog and the native paste screen
are its callers; an actionable result becomes an ordinary capture of the pasted
text, so deadlines and clarification go through the capture path unchanged.

§19's actual sources — email, documents, receipts, bank and government
correspondence — are not this phase. They are recorded under slice 10 with
§19 alongside §34, since automatic detection is the same "proactive
identification" work §34 already names.

## Finishing

Per phase: `npm run test`, `npm run stan`, `npm run lint`, then `composer
refactor:check`. At the end of the slice, `update-resume` moves this header,
the spine's row and `.ai/RESUME.md` together.

## Open

Nothing. All six phases resolved — five built, phase 5 closed as answered by
existing execution mode.
