---
paths:
  - 'backend/app/Models/**'
  - 'backend/app/Actions/**'
  - 'backend/database/migrations/**'
---
# Domain Model

The loop: `captures` -> `intentions` -> `steps` -> `execution_sessions`.

## `Step`, not `Action`

The spec's product word is **Next Action**. The model is `Step`, because
`app/Actions/**` already means "use-case class" in this codebase and a model
called `Action` makes every import ambiguous. `NextActionResolver` returns a
`Step`; the product word survives in the API route, the copy and the resolver
name, not in the table.

## Flat Laravel, not `App/Domain/**`

The spec (§24) proposes fifteen domain folders under `App/Domain/`. This repo is
flat — `app/Models`, `app/Actions`, `app/Data`, `app/Support` — on the strength
of §24's own closing line: avoid an elaborate architecture before it is
necessary. Five tables and one journey do not need a module system.

This is a decision, not an oversight. Reopening it needs a new decision: a
domain tree introduced one folder at a time, while the rest of the app stays
flat, is the worst of both.

## Use-case classes are `lorisleiva/laravel-actions`

Every class in `app/Actions/**` is an action: `use AsObject`, one public
`handle()`, called with `::run()`. Callers get `::mock()` and `Action::fake()`
instead of a hand-rolled double — the `laravel-actions` skill covers both.

`AsController`, `AsJob` and `AsCommand` are opt-in per action and are not the
default. Two frontends read the same action and want different shapes, so the
web and API adapters stay separate invokable controllers over `AsObject`; an
action queued off the critical path adds `AsJob` at that point, not before.

`app/Actions/Fortify/**` and `app/Actions/Concerns/**` are exempt — Fortify's
contracts call the first, and the second holds traits shared between actions,
not use-cases themselves.

## Immutability

`captures` have `created_at` only — `public const ?string UPDATED_AT = null`. A
capture is raw input and is never edited; conversion writes an intention and
leaves the capture alone.

Skipped steps are kept, not deleted. The skip history is what stops the resolver
re-offering the same thing and is the eval corpus for decomposition quality.

A split is the one exception, and it pays for itself first: `SplitStep` deletes
the step it replaced, because leaving it pending would offer the person the thing
they just said was too big, and no status word describes "superseded" without
inventing one. What it was — title, skip count, the ids that replaced it — is
written into the session's replay before the row goes, so the history still reads
continuously.

## Keys

Domain tables use ULIDs (`HasUlids`). `users` is Laravel's default bigint from
the starter kit, so every `user_id` is a `foreignId`, not `foreignUlid`.

## Dates are UTC instants, converted below the call site

Columns hold UTC, but the person's clock is where every window starts
(`User::now()`), and Laravel writes a Carbon into SQL as its wall clock. That
shifted every comparison by the person's offset, and three call sites had
already forgotten a hand-written `->utc()`.

So nothing converts by hand. Each driver's connection converts date bindings in
`prepareBindings` (`app/Support/Database/`, registered in `AppServiceProvider`),
and every model converts on write through `StoresDatesInUtc`, which
`ConventionsTest` requires. Supporting a new driver means adding its connection
there too.

The conversion only sees a date object. A date you format into a string yourself,
and `whereDate`, which formats before binding, reach SQL as the wall clock. Pass
the Carbon.

## The session outlives the step

`execution_sessions.current_step_id` is nullable and changes as the person moves
through an intention. A session is a stretch of focused work, not a wrapper
around one step, so pausing and resuming does not open a second session.

`StartSession` returns the running session rather than opening a second one.
Actions throw on invalid transitions instead of silently no-opping — a landed
session cannot be landed twice.

## `execution_sessions` carries `user_id`; nothing else does

Ownership is checked in one place — `ResolvesOwned` reads `user_id` off whatever
model the route bound — so a session that had to join through `intentions` to
answer "whose is this" would need a second ownership path for one table.
`RunningSession` asks the same question on every screen, and the index
`['user_id', 'ended_at']` is what answers it.

`steps` stays un-denormalised, and a step is reached through the session that
offered it. Do not add `user_id` to it; `ConventionsTest` fails if the column
appears.

## Migrations stay SQLite-compatible

Dev and the suite both run SQLite:

- No `fullText()` / `whereFullText()`, no `UPDATE ... JOIN`.
- No MySQL-only SQL: `CONCAT`, `SUBSTRING_INDEX`, `TIMESTAMPDIFF`, `MATCH()`.
- Dropping a column still covered by a composite index needs the index dropped
  first — SQLite errors where MySQL silently adjusts.
