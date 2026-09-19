# Executive Function OS — build plan

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
| Mobile | Expo / React Native (not scaffolded yet — slice 7) |
| DTO layer | `spatie/laravel-data` + `spatie/laravel-typescript-transformer` |
| AI | `laravel/ai`, behind our own provider contract, fixture driver by default |
| Tooling | `nvade/devtools` (Pint, PHPStan/Larastan, Rector, Sail/Traefik, CI) |

`laravel-patterns` recommends Eloquent API Resources. This repo deliberately
uses laravel-data instead — same reasoning as first-move: one DTO pattern, and
the TypeScript for both frontends is generated from it rather than hand-kept.

## Domain model (MVP)

Naming deviates from the spec in one place: the spec's **Next Action** is the
model `Step`. `Action` would collide with `app/Actions/**`, where the use-case
classes live. "Next action" stays the product word and is what
`NextActionResolver` returns.

| Table | Carries | Notes |
| --- | --- | --- |
| `captures` | raw text, source, `intention_id` nullable | immutable, `created_at` only |
| `intentions` | title, why, status, `deadline_at` nullable, `needs_clarification` | the thing the person wants handled |
| `steps` | intention, title, `estimated_seconds`, position, status | one physical action each |
| `execution_sessions` | intention, `current_step_id`, outcome | a focused stretch, may span steps |
| `execution_events` | session, type, payload | started/done/skipped/stuck/distracted/resumed |

Deferred until they earn their place: `Project`, `Task`, `Commitment`,
`WaitingFor`, `Reminder`, `Context`, `Document`, `CalendarEvent`. The spec lists
them; building them before the first journey is polished is the premature
normalisation it warns against.

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
POST   /api/v1/captures                   text in, capture out
POST   /api/v1/intentions                 from a capture or free text
GET    /api/v1/next-action                the one recommendation + why
POST   /api/v1/sessions                   start on a step
GET    /api/v1/sessions/current           null-shaped 200, never 404
POST   /api/v1/sessions/{session}/done
POST   /api/v1/sessions/{session}/skip
POST   /api/v1/sessions/{session}/stuck
POST   /api/v1/sessions/{session}/distracted
POST   /api/v1/sessions/{session}/stop
POST   /api/v1/overwhelmed                collapses the world to one small step
```

Same Data classes serve Inertia props and JSON, so the wire shape cannot drift
between the two frontends.

## Deterministic vs AI

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

| # | Slice | Spec | State |
| --- | --- | --- | --- |
| 1 | Foundations — enums, migrations, models, Data classes, generated types, guard tests | §25, §37 | done |
| 2 | [Capture → intention](slices/02-capture-intention.md) — one text field, deterministic extraction, queued decomposition | §6, §7, §8, §27 | done |
| 3 | [Next action](slices/03-next-action.md) — `NextActionResolver`, ordered comparators, composed `why` | §9, §26 | done |
| 4 | [Execution mode](slices/04-execution-mode.md) — one step, six controls, stuck and distracted | §10–§12, §18 | next |
| 5 | [Home](slices/05-home.md) — four bands, one action, the first complete journey | §5, §29, §39 | scoped |
| 6 | [Overwhelm and time](slices/06-overwhelm-time.md) — one small step, backwards planning, contextual reminders | §13, §14, §16, §22, §23 | scoped |
| 7 | Mobile — Expo joins the workspace, Sanctum, touch-first capture | §3, §30 | paragraph below |
| 8 | Phase 2 — waiting-for, commitments, ingestion, future-self, body doubling | §15, §17, §19–§21, §33 | paragraph below |
| 9 | Phase 3 — companion, location awareness, bill and subscription detection | §34 | recorded only |

**Slice 7, mobile.** Expo joins the workspace list here and not before, or
`npm install` breaks. Sanctum tokens, the same `/api/v1` surface, the same Data
classes through `packages/shared`. Touch-first rather than a port of the web
layout: one-handed reach, capture in seconds from cold start, voice and camera
capture, push, deep links. Types are shared; components are not, and attempting
that is risk 4.

**Slice 8, phase 2.** Waiting-for with its four responses, commitments with the
three provenance levels kept distinct, email and document ingestion behind a
modular port with no provider coupling, future-self reminders, recurring steps,
body doubling starting with solo. Ingestion is last on purpose: it is where the
privacy surface grows fastest (risk 3), and it is worthless until the core
journey is polished.

**Slice 9** is recorded so it is not reinvented, not planned.

## Measuring it

*Spec §36.* Not DAU, not session count, not time in app. What matters:
captured intentions that get finished, time from capture to first action, share
of sessions that make real progress, recovery rate after distraction. A person
using this well should spend less time in it over time.
