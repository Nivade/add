# Support and Concerns boundaries

**State:** designed, 2026-09-24 · a refactor pass, not a slice

*Rules: `domain-model.md`, `api-and-data.md`, `general.md`.*

`app/Support` has no written rule for what belongs in it, and `app/Concerns`
already mixes model, controller and action traits. This pass gives each class the
home Laravel or this repo already has for its kind, then fixes the boundary with
a rule and guard tests so neither folder drifts back.

No new behaviour. Each numbered item is one commit, and the suite passes after
every one.

## Before starting

Another session is working on the AI-consent change in this tree, touching
`User.php`, `AiServiceProvider.php` and the intention actions. Start this pass on
its own branch from `main` after that work merges, not on top of its uncommitted
files.

## What stays in `Support`

| Folder | Why it stays |
| --- | --- |
| `Ai/`, `Calendar/` | A contract in `app/Contracts` with several adapters behind it |
| `NextAction/` | One small interface over the whole ranking engine |
| `Time/` | Backwards planning, the next appointment across two tables, deadline extraction |
| `Database/`, `TypeScript/` | Framework glue |

`DeadlineExtractor` and `NextActionResolver` each have one adapter. They are
noted, not changed.

## Session behaviour moves onto the models

**1. `app/Exceptions/` and `InvalidSessionTransition`.** Move the exception out
of `Support/Execution/Exceptions/` into the new `app/Exceptions/`.

**2. `SessionState` becomes `ExecutionSession` methods.** `assertOpen()` and
`currentStepOrFail()` on the model. Update every session action that calls them,
then delete the class.

**3. `RunningSession` becomes `User::runningSession()`.** A `HasOne` relation,
built as `hasOne(ExecutionSession::class)->ofMany(['started_at' => 'max', 'id' =>
'max'], fn ($query) => $query->running())`, so home and the rail can eager-load
it. `lockKey()` becomes `User::sessionLockKey()`. Callers are
`ChainedNextActionResolver`, `BuildHome`, `BuildRail`, `StartSession`,
`ShowFocusController` and `ShowCurrentSessionController`. Check with `search-docs`
that `ofMany` accepts the constraint closure and the tie-break on `id`.

Done when a test with two running sessions for one person returns the newest
one, including the case where both have the same `started_at`.

**4. `BuildExecutionState` replaces `ExecutionStateData::of`.** A new
`Actions/Sessions/BuildExecutionState` next to `BuildHome` and `BuildRail`.
`ProgressLines` and `ElapsedWords` become its private methods, which removes the
query that `Data` currently runs. After this, `Support/Execution/` is empty and
is deleted.

## Copy goes to its only caller

**5. `ReminderLines` moves into `AppointmentReminder`.** The notification takes
the appointment, the plan and `$now`, and builds its own lines.
`SendDueReminders` stops assembling them.

**6. `EstimateWords` moves into `Support/NextAction/`.** `Candidate` is its only
caller.

**7. `NullAnswer` moves to `app/Http/Responses/`.** It is a `Responsable`, and
that is where Laravel keeps them.

## `Concerns` split by layer

**8. Move the traits to their layer.**

| Trait | New home |
| --- | --- |
| `StoresDatesInUtc`, `PlansBackwards` | `app/Models/Concerns/` |
| `ResolvesOwned`, `ResolvesStartableStep` | `app/Http/Controllers/Concerns/` |
| `QueuesPerUser` | `app/Actions/Concerns/` |
| `PasswordValidationRules`, `ProfileValidationRules` | stay, starter-kit placement |

`domain-model.md` says every class in `app/Actions/**` is an action. Add
`app/Actions/Concerns/**` to the exemption that already covers `Fortify/`. The
model rule text still names `app/Support/Database/`, which is unchanged.

## The boundary, written down and guarded

**9. Add a rule file.** Call it `.ai/rules/support-and-concerns.md`, with globs
`backend/app/Support/**`, `backend/app/Concerns/**` and
`backend/app/Exceptions/**`, and add its row to `index.md`. It records these
decisions:

- `Support` holds adapters behind a contract, framework glue, and pure
  calculation. Anything that enforces a model's own rules goes on the model, and
  copy used in one place lives with that caller.
- No `app/Services`. A service is an adapter behind a contract in
  `app/Contracts`, bound with `#[Bind]` or in a provider.
- Jobs are actions with `AsJob`. There is no `app/Jobs`.
- Laravel events wait until one thing happening needs a second, independent
  reaction. They never reuse the `ExecutionEvent` name.

**10. Guard tests in `ConventionsTest`.** Use `pest-plugin-arch`, which is
already installed, to check that:

- `App\Support` does not use `App\Actions` or `App\Http`
- every class in `App\Support` is final
- `App\Concerns` holds only the validation traits

## After

Run `types:generate` if a `#[TypeScript]` class moved. Run `stan`, `lint` and the
suite, then update the `next-action-resolver` and `ai-layer-changes` skills
wherever they name a moved class.
