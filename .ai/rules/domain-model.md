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

`app/Actions/Fortify/**` is exempt — those implement Fortify's contracts and
Fortify calls them, not us.

## Immutability

`captures` have `created_at` only — `public const ?string UPDATED_AT = null`. A
capture is raw input and is never edited; conversion writes an intention and
leaves the capture alone.

Skipped steps are kept, not deleted. The skip history is what stops the resolver
re-offering the same thing and is the eval corpus for decomposition quality.

## Keys

Domain tables use ULIDs (`HasUlids`). `users` is Laravel's default bigint from
the starter kit, so every `user_id` is a `foreignId`, not `foreignUlid`.

## The session outlives the step

`execution_sessions.current_step_id` is nullable and changes as the person moves
through an intention. A session is a stretch of focused work, not a wrapper
around one step, so pausing and resuming does not open a second session.

`StartSession` returns the running session rather than opening a second one.
Actions throw on invalid transitions instead of silently no-opping — a landed
session cannot be landed twice.

## Nothing is denormalised yet

"Current session for a user" joins through `intentions`. At this scale that is
free. Do not add `user_id` to `steps` or `execution_sessions` until a query plan
says otherwise.

## Migrations stay SQLite-compatible

Dev and the suite both run SQLite:

- No `fullText()` / `whereFullText()`, no `UPDATE ... JOIN`.
- No MySQL-only SQL: `CONCAT`, `SUBSTRING_INDEX`, `TIMESTAMPDIFF`, `MATCH()`.
- Dropping a column still covered by a composite index needs the index dropped
  first — SQLite errors where MySQL silently adjusts.
