# Executive Function OS — build plan

## Skills

`slice-workflow` for choosing and opening a slice. Each slice file lists its
own.

## What this repo is

An external executive-function system, not a task manager. The product answers
"what do I do right now" so the person does not have to. Spec is
[product-spec.md](product-spec.md); the non-negotiables from it are recorded in
`.ai/rules/product-invariants.md`.

## Prior art in the neighbourhood

`~/repos/private/first-move` is the same problem space, narrower: starting and
time blindness, one Session with a visible clock. Its `.ai/rules` are the source
of the **conventions** copied here (laravel-data over API Resources, generated
TypeScript, parallel Pest, no shame mechanics) and of nothing else. Product
scope, domain model and priorities come from this spec, which is the sharper
statement of the intent — decided, not inherited. When the two disagree, the
spec wins and first-move is not consulted.

## Stack, as installed

| Layer | Choice |
| --- | --- |
| Backend | Laravel 13.32, PHP 8.5, SQLite dev, Redis cache/queue |
| Auth | Fortify (starter kit, headless) for web; Sanctum tokens for React Native |
| Web | Inertia 3 + React 19 + TypeScript + Tailwind 4, Wayfinder, vite-plus |
| Mobile | Expo SDK 57 / React Native, expo-router, Sanctum tokens |
| DTO layer | `spatie/laravel-data` + `spatie/laravel-typescript-transformer` |
| AI | `laravel/ai`, behind our own provider contract, fixture driver by default |
| Tooling | `nvade/devtools` (Pint, PHPStan/Larastan, Rector, Sail/Traefik, CI) |

`laravel-patterns` recommends Eloquent API Resources. This repo deliberately
uses laravel-data instead — same reasoning as first-move: one DTO pattern, and
the TypeScript for both frontends is generated from it rather than hand-kept.

§24 suggests `Domain/{Users,Tasks,Intentions,...}` folders. This repo groups by
kind instead — `app/{Actions,Data,Contracts,Support,Models}` — because
`app/Actions/**` is already the unit slice work is organised around; a domain
layer on top of it would be a second axis this codebase's size does not earn.

## Domain model (MVP)

Naming deviates from the spec in one place: the spec's **Next Action** is the
model `Step`. `Action` would collide with `app/Actions/**`, where the use-case
classes live. "Next action" stays the product word and is what
`NextActionResolver` returns.

| Table | Carries | Notes |
| --- | --- | --- |
| `captures` | raw text, source, `intention_id` nullable | immutable, `created_at` only |
| `intentions` | title, why, status, `deadline_at` nullable, `clarifying_question` and its answer, plan assumptions, `recurrence_every_days` and `recurrence_next_at` nullable, `recurrence_template_id` | the thing the person wants handled; a recurring one is a template that spawns a fresh copy |
| `steps` | intention, title, `estimated_seconds`, `place` nullable, position, status | one physical action each; a null place means anywhere |
| `execution_sessions` | intention, `current_step_id`, outcome | a focused stretch, may span steps |
| `execution_events` | session, type, payload | started/done/skipped/stuck/distracted/resumed; a `started` payload names the rung that recommended the step, or says it was not recommended |
| `calendar_events` | source, external id, title, `starts_at`, plan assumptions | read-only; the source owns it, we never write back |
| `reminders` | user, appointment kind and id, `sent_at` | one row per appointment, which is what keeps reminders sparse |
| `devices` | user, Expo push token, platform | the token identifies the device, so registering twice moves it |
| `not_here_reports` | user, place | what the person said about where they are not; pruned after a day |
| `check_ins` | user, topic, answer | the fortnightly question's answers; immutable and kept, because they are the metric |
| `waiting_fors` | user, subject, note, status, `last_answered_at` | something the person is waiting on from someone else; no AI |
| `commitments` | user, description, provenance, `confirmed_at`, status, `intention_id` or `step_id` | provenance is `user_task`, `user_stated` or `system_inferred`; an inference stays unconfirmed until the person says so |
| `future_reminders` | user, message, `trigger_at`, `calendar_event_id` and `offset_seconds` nullable, `sent_at` | a note to a future self, at a time or relative to an event |

Deferred until they earn their place: `Project`, `Task`, `Document`. The spec
lists them; building them before the first journey is polished is the premature
normalisation it warns against. `CalendarEvent` and `Reminder` earned theirs in
slice 6, `WaitingFor` and `Commitment` in slice 9. `Context` earned its place in
slice 10, as a `Place` on a step and a computed `Whereabouts`, not a table.

An appointment is the `Appointment` contract, not a table: a dated intention and
a calendar event both answer it, so backwards planning, the rail and reminders
ask one question rather than branching on which table it came from.

Status enums avoid failure words. Session outcome is
`continued | completed | stopped`. There is no `abandoned`, no `overdue`, no
`due_at` — a real appointment time is `deadline_at` and it is nullable.

`users.timezone` is the zone every relative phrase and every backwards
calculation is read against. Timestamps are stored as the instant they name;
Eloquent writes a Carbon instance in whatever zone it carries, so a value
computed in the person's zone is converted before it reaches a column.

## API boundaries

`routes/web.php` returns Inertia pages, `routes/api.php` returns JSON for React
Native, both thin over `app/Actions/**`. No business logic in either adapter.

```
POST   /api/v1/tokens                     the one route outside the guard
DELETE /api/v1/tokens/current             this device signs out, others stay in
POST   /api/v1/devices                    an Expo push token for this person
GET    /api/v1/home                       the same HomeData the web page renders
POST   /api/v1/reminders/{id}/dismiss     the band, ended by the person
POST   /api/v1/captures                   text in, capture out
POST   /api/v1/captures/{id}/kind         the person changes what it was sorted into
POST   /api/v1/captures/{id}/confirm      the person says the sort was right
POST   /api/v1/intentions                 from a capture or free text
GET    /api/v1/next-action                the one recommendation + why
POST   /api/v1/sessions                   start on a step
GET    /api/v1/sessions/current           null-shaped 200, never 404
POST   /api/v1/sessions/{session}/done
POST   /api/v1/sessions/{session}/skip
POST   /api/v1/sessions/{session}/stuck
POST   /api/v1/sessions/{session}/distracted
POST   /api/v1/sessions/{session}/stop
GET    /api/v1/overwhelmed               collapses the world to one small step
PATCH  /api/v1/intentions/{id}/plan      states a minute count the plan assumed
PATCH  /api/v1/calendar-events/{id}/plan the same, for an event off the calendar
```

Same Data classes serve Inertia props and JSON, so the wire shape cannot drift
between the two frontends.

## Deterministic vs AI

*Spec §26, §27.*

Deterministic, always: next-action selection, time arithmetic (deadline
extraction, leave-by, backwards planning), session state machine, progress
counting, reminder scheduling, overwhelm reduction. These must be explainable and testable, and the
spec's "why this?" line is generated from the ranking inputs, not written by a
model.

AI, behind `App\Support\Ai` contracts and queued where it is not on the critical
path: natural-language capture parsing, intention decomposition into steps,
stuck-reason follow-ups, later document/email interpretation and commitment
detection. Default driver is a fixture/canned pair so the whole app runs with no
API key, and a missing fixture throws rather than inventing an answer.

## Risks

1. **The engine is the product.** A bad recommendation is worse than a list.
   It gets its own test suite with hand-built scenarios before any UI polish.
2. **Decomposition quality decides whether execution mode works.** A ten-step
   plan with a vague first step fails exactly the person it is for. Needs an
   eval corpus, same shape as first-move's. Violations are logged rather than
   rejected, because rejecting leaves the person with nothing. The log is not a
   corpus — it is neither queryable nor durable — but the trigger for building
   one is the first `openai` run against a live key, not a slice number, since
   `canned` and `fake` answers are never worth scoring.
3. **Privacy surface is large and grows with ingestion.** Nothing leaves the
   machine before the AI path is explicit, consented and logged; ingestion is
   scoped out of the MVP for this reason, not only for effort.
4. **Two frontends, one product.** Types and domain logic are shared through
   `packages/shared`; components are not, and attempting to share them is the
   trap that costs a rewrite.
5. **Spec breadth.** Forty sections describe a product several years wide.
   Slices below are ordered so each one is usable on its own.

## Cross-cutting, every slice

These are spec requirements that are not slices. A slice is not done if it
breaks one, and each is a review question on the slice's own diff.

| From | Standing requirement |
| --- | --- |
| §2.1, §2.2, §2.4 | One thing to think about, never a list; execution mode shows one step; a notification earns its interruption. |
| §2.3, §35 | No shame copy, no failure words in enums, UI or notifications. |
| §2.5, §21 | Anything inferred is labelled as inferred and confirmable; nothing consequential happens silently. |
| §9, §26 | Every recommendation carries a `why` built from its ranking inputs, never a model's prose. |
| §28 | Nothing leaves the machine without an explicit, consented, logged AI path. Derived AI data is distinguishable from user data. |
| §31 | Keyboard reachable, screen-reader labelled, focus visible, reduced motion honoured, no colour-only state. |
| §35 | No dashboard, no priority matrix, no mandatory categorisation, no configuration the default cannot cover. |
| §37 | Types, feature test for the workflow, PHPStan clean, queued work off the critical path. |

## Slices

Each slice is usable on its own. A slice earns its own file in
[`slices/`](slices/) when it is next up; the file carries the design, the
done-when bar and the spec sections it answers. This table is the spine and
stays at one line each.

The State column is repeated in each plan's own header, because whoever follows
a link into a plan never sees this table. `DocumentationTest` fails when the two
disagree, so the copy cannot drift; `slice-workflow` carries the vocabulary and
what moving a plan to `done` requires.

| # | Slice | Spec | State |
| --- | --- | --- | --- |
| 1 | Foundations — enums, migrations, models, Data classes, generated types, guard tests | §25, §37 | done |
| 2 | [Capture → intention](slices/02-capture-intention.md) — one text field, deterministic extraction, queued decomposition | §6, §7, §8, §27 | done |
| 3 | [Next action](slices/03-next-action.md) — `NextActionResolver`, ordered comparators, composed `why` | §9, §26 | done |
| 4 | [Execution mode](slices/04-execution-mode.md) — one step, six controls, stuck and distracted | §10–§12, §18 | done |
| 5 | [Home](slices/05-home.md) — four bands, one action, the first complete journey | §5, §29, §39 | done |
| 6 | [Overwhelm and time](slices/06-overwhelm-time.md) — one small step, backwards planning, contextual reminders | §13, §14, §16, §22, §23 | done |
| — | [Hardening](hardening.md) — where the built slices disagreed with their own decisions | §2.5, §21, §28 | done |
| 7 | [Mobile](slices/07-mobile.md) — Expo joins the workspace, Sanctum, touch-first capture | §3, §30 | done |
| 8 | [Make the MVP real](slices/08-mvp-real.md) — real calendar, live model behind consent, a device, the journey under a browser | §18, §22, §28, §32, §39 | done |
| — | [Agent tooling](agent-tooling.md) — code-review then simplify, gated at merge; vendored skills restored from the lock | — | done |
| — | [Support and Concerns boundaries](archive/support-and-concerns-boundaries.md) — where Support, Concerns and Exceptions decisions land, and the guard tests that hold the line | — | done |
| 9 | [Phase 2](slices/09-phase-2.md) — waiting-for, commitments, future-self, recurring steps, solo body doubling, pasted-text ingestion | §15, §17, §19–§21, §33 | done |
| — | [Custom attributes](custom-attributes.md) — declared intent over repeated code: one-thing screens, exception rendering, job retry policy, per-user commands, drivers, notification kinds | — | done |
| — | [Findings triage](archive/findings-triage.md) — session transitions under the row lock, feed fetches to public hosts only, a dead token signs the phone out, dated clarification answers, a copied-question eval, the local certificate | — | done |
| 10 | [Context](slices/10-context.md) — where a step happens, where the person seems to be, and a rung that reorders on it without ever filtering | §4, §9 | done |
| 11 | [Measuring it](slices/11-measuring.md) — §36's outcomes on demand from recorded rows, the rung behind each start, and a fortnightly check-in | §36 | done |
| — | [UX overhaul](ux-overhaul.md) — the 2026-09-30 audit: trust fixes, a new identity, one capture box the model sorts, focus without chrome, a closing screen, mobile parity | §2, §5, §6, §10–§13, §16, §18, §29–§31, §39 | building |
| 12 | Ingestion — the threat model first, then email, documents, receipts, bank/gov correspondence, bill and subscription detection, and the first `system_inferred` commitment producer | §19, §21, §28, §34 | recorded only |
| 13 | Location — device location as a second whereabouts source behind its own consent, and location-triggered reminders | §14, §15, §34 | recorded only |
| 14 | Body doubling beyond solo — friend, anonymous group, AI companion | §17, §34 | recorded only |

**Slices 10–14** split the spec's Phase 3 by risk. Context comes first: it is
the engine, it is deterministic, and it adds no privacy surface. Measurement is
next, because it is cheap and says whether context helped. Ingestion waits for
its threat model, which also settles notification payload encryption. Location
needs a consent surface, and Friend mode needs a second person in the system,
so both come last. The rest get their files when they are next up.

## Measuring it

*Spec §36.* Not DAU, not session count, not time in app. What matters:
captured intentions that get finished, time from capture to first action, share
of sessions that make real progress, recovery rate after distraction. A person
using this well should spend less time in it over time.

Slice 11 computes it on demand with `metrics:report`, from rows already kept,
for whoever builds the app and never on a screen. The two outcomes the spec
calls user-reported, reduction in overwhelm and in remembering, come from the
fortnightly check-in on home.
