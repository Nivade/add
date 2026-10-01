# add

An executive-function aid for adults with ADHD. Most task managers hand the
person a list and leave the hardest part, deciding what to do, to them. `add`
does the deciding. Every screen answers one question, "what do I do right
now?", and the answer is always one physical step with a plain reason
attached.

A Laravel 13 backend serves an Inertia + React web app and an Expo / React
Native mobile app from the same domain code.

**Status:** in active development. Slices 1–11 of the build plan are done; a UX
overhaul is underway. Not deployed.

## How it works

1. **Capture.** One text box, no required fields. "dentist before the 14th,
   need to find the insurance letter" is enough. Dates and times are extracted
   deterministically; a model sorts the capture and breaks the intention into
   small physical steps, each with a time estimate and an optional place.
2. **Next action.** A deterministic resolver picks one step. There is no
   priority score, because a score cannot explain itself. Instead, candidates
   pass through an ordered chain of named comparators: a deadline within
   reach, whether the step fits where the person is, a real deadline at all,
   not recently skipped, prerequisite order, shortest first, oldest intention.
   The first comparator that separates two steps decides, and its reason
   becomes the `why` shown beside the step. An open session always wins, so
   the app never re-ranks someone mid-task.
3. **Execution.** One step on screen, with done, skip, stop, "I'm stuck" and
   "I got distracted". Skip and stop have the same weight as done. "I got
   distracted" brings the person back to where they were.
4. **Overwhelm.** "I'm overwhelmed" collapses everything to one small step and
   states how much else exists without listing it.
5. **Time.** Appointments from a calendar feed (ICS, read-only) and dated
   intentions are planned backwards into leave-by times. Reminders are sparse,
   one per appointment, and reach the phone as Expo push notifications.

Also built: waiting-for items, commitments (anything the system inferred stays
marked as inferred until the person confirms it), notes to a future self,
recurring intentions, solo body doubling, and a fortnightly check-in.

## Product rules enforced in code

The product spec forbids anything that turns the app into a source of shame,
and the codebase holds that line structurally:

- Status enums carry no failure words. A session ends `continued`, `completed`
  or `stopped`; there is no `failed`, `abandoned` or `overdue`.
- No streaks, points or punishment for inactivity, and no backlog lists in
  execution or overwhelm mode.
- Every recommendation carries a `why` built from its ranking inputs, never
  from model prose. If a step cannot be explained, it is not recommended.
- Success is measured by the person needing the app less: finished
  intentions, time from capture to first action, recovery after a stopped
  session. `metrics:report` computes these in a terminal and never shows them
  to the person.

## Architecture

| Workspace | What it is |
| --- | --- |
| `backend/` | Laravel 13, PHP 8.5: domain, JSON API, AI layer, Inertia React web app |
| `mobile/` | Expo / React Native, expo-router, Sanctum tokens against `/api/v1` |
| `packages/shared/` | TypeScript shared by both frontends, generated from PHP |

- **One wire shape.** `spatie/laravel-data` classes serve as Inertia props and
  as JSON API responses. `spatie/laravel-typescript-transformer` generates the
  TypeScript both frontends import, so web and mobile cannot drift from the
  backend or from each other.
- **Thin adapters.** Web routes and API routes both call the same action
  classes in `backend/app/Actions/`; neither holds business logic.
- **Deterministic where it matters.** Ranking, time arithmetic, the session
  state machine, reminders and overwhelm reduction contain no AI. Models handle
  only language: sorting captures, decomposing intentions, follow-up questions
  when someone is stuck.
- **AI behind consent.** Calls go through
  [`nvade/ai-toolkit`](https://gitlab.com/nvade-packages/ai-toolkit), which I
  extracted from this app. No text leaves the machine without recorded
  consent. The default driver answers from fixtures, so the whole app runs and
  tests without an API key, and a missing fixture throws rather than inventing
  an answer.
- **Session transitions run under a row lock**, and every session control
  refuses a stale tap from a second device or a double press.

## Quality

- About 450 Pest tests, including scenario tests for the resolver and browser
  tests in real Chromium via `pest-plugin-browser`.
- PHPStan (Larastan) level 7, Pint, Rector, and `tsc` across all workspaces.
- GitHub Actions: tests on SQLite and on MySQL 8.4, commit message and branch
  name checks, and a lint for sloppy code patterns.
- Accessibility is part of the definition of done: keyboard reachable, screen
  reader labels, visible focus, reduced motion honoured, no colour-only state.
  Lighthouse scores 100 for accessibility on the main web screens.
- Work is planned as slices in [`.ai/plans/`](.ai/plans/), each usable on its
  own, with design, threat model where needed, and a done-when bar.
  [`.ai/plans/executive-function-os.md`](.ai/plans/executive-function-os.md) is
  the map. Invariants and traps live in [`.ai/rules/`](.ai/rules/).

## Running it

Docker is needed for the full stack (Laravel Sail with Redis); the test suite
also runs on the host against SQLite, given PHP 8.5 and Node 22.

```bash
npm install                 # at the root, for every workspace
cd backend && composer setup && cd ..
npm run test:host           # the suite, no containers
```

With Sail up, everything runs from the repo root:

```bash
npm run test                # parallel suite in Sail
npm run stan                # PHPStan
npm run web                 # Vite for the web app
npm run mobile              # Expo
npm run types:generate      # regenerate packages/shared from PHP Data classes
```
