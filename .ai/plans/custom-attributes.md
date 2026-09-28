# Custom attributes — declared intent the code already repeats by hand

**State:** done, 2026-09-28 · [the slice table](executive-function-os.md#slices)

*Rules: `general.md` (prefer attributes; rules are held down by tests), `support-and-concerns.md`,
`product-invariants.md`, `api-and-data.md`, `domain-model.md`, `ai-layer.md`, `testing.md`.
Skills: `laravel-attributes`, `laravel-actions`, `laravel-data`, `ai-layer-changes`, `pest-testing`,
`testing-best-practices`, `tdd`, `phpstan-larastan`, `sail-and-root-scripts`, `generated-artifacts`,
`split-to-prs`, `finish-branch`, `update-resume`.*

## Skills

- `laravel-attributes`: every phase, before writing an attribute — check the framework hasn't grown one.
- `laravel-data`: phase 1 (`#[OneThing]`, the `DataConfig`/`DataClass` walk).
- `pest-testing`, `testing-best-practices`, `tdd`: every phase's guard and regression tests.
- `laravel-actions`: phases 3 and 4 (`configureJob`, `getJobMiddleware`, command signature/description).
- `ai-layer-changes`: phases 3 and 5 (`AiUnavailable`, provider drivers).
- `phpstan-larastan`: every phase, before finishing.
- `sail-and-root-scripts`: phase 4 (`schedule:list`).
- `generated-artifacts`: closing check, none of this reaches `generated.ts`.
- `split-to-prs`, `finish-branch`, `update-resume`: closing out the branch.

Six hand-rolled attributes, each replacing a fact the code currently writes twice or holds
by goodwill. Laravel 13 already ships attributes for commands, jobs and controllers; this plan
adds ours only where no framework attribute says the thing, and reuses the framework's
wherever one does (`#[Signature]`, `#[Description]`, `#[Tries]`, `#[Backoff]`).

## Shape every attribute follows

- `#[Attribute(Attribute::TARGET_CLASS)]`, `final readonly class`, promoted constructor, no logic.
- Every attribute lives in one shared `backend/app/Attributes/`, regardless of which layer reads
  it — not scattered into a per-layer `Attributes/` folder beside each layer's `Concerns/`.
- Pest's Laravel arch preset reserves that exact namespace for
  `Illuminate\Contracts\Container\ContextualAttribute` classes, a check its own `->ignoring()`
  cannot suppress (the `toImplement()` call fires eagerly, inside `__call`, before `ignoring()` on
  the preset's return value can reach it). `tests/Pest.php` registers a `laravelMinusAttributes`
  custom preset — the framework's `Laravel` preset with that one rule removed — and `ArchTest.php`
  uses it instead of `arch()->preset()->laravel()`.
- One **reader** per attribute, next to it, reading with
  `(new ReflectionClass($class))->getAttributes(X::class)[0] ?? null`. A missing attribute on a
  class that needs one throws a `LogicException` naming the class, never a silent default.
- Each attribute ships with a guard in `tests/Feature/Guards/ConventionsTest.php` wherever the
  convention can fail — `general.md`: a rule nothing can fail rots. Verify each guard by
  breaking the thing and watching it go red.
- Run `laravel-attributes` before each phase: if the framework has grown an attribute for it,
  use that instead.

Phases are independent and ordered by value. Each is one commit, or one PR through
`split-to-prs` if the branch grows past review size. Branch off `main` as
`refactor/custom-attributes`, not off the slice 9 working tree.

## 1. `#[OneThing]` — the no-backlog invariant, enforced

`product-invariants.md`: execution mode and "I'm overwhelmed" show one step, never a list.
Today only the current shape of the Data classes keeps that.

- `backend/app/Attributes/OneThing.php`, no parameters.
- Mark `app/Data/OverwhelmedData.php`, `app/Data/ExecutionStateData.php`,
  `app/Data/NextActionData.php`.
- Guard, "keeps every one-thing screen to one thing": for each class under `app/Data` carrying
  `#[OneThing]`, walk its properties through laravel-data's `DataConfig::getDataClass()`,
  recursing into nested Data. Fail on any property whose iterable item type is a Data class.
  Lists of strings (`why`, `progress`) pass.
- Prove it red: add `/** @var list<StepData> */ public array $others` to `OverwhelmedData`.
- Skills: `laravel-data` (how the iterable item type is resolved from the `@param` tag),
  `pest-testing`.

## 2. `#[RespondsWith]` — one renderer for domain exceptions

`InvalidCommitmentResponse`, `InvalidIntentionTransition` and `InvalidSessionTransition` each carry
the same `render()`; `AiUnavailable` has its own closure in `bootstrap/app.php`.

- `backend/app/Attributes/RespondsWith.php`: `int $status`, `?string $message = null`
  (null answers with the exception's own message).
- Delete the three `render()` methods; annotate them `#[RespondsWith(Response::HTTP_CONFLICT)]`.
  Annotate `AiUnavailable` with 503 and its fixed sentence, and delete its closure.
- One `$exceptions->render(fn (Throwable $e, Request $r) => ...)` in `bootstrap/app.php`: JSON
  only when `expectsJson()`, `null` otherwise so the web keeps its current behaviour.
- `ShouldntReport` stays an interface on the three domain exceptions; reporting is not rendering.
- Guard: no class under `app/Exceptions` or any `Exceptions/` folder under `app/Support` defines
  `render()`.
- Tests already cover the behaviour and must stay green unchanged:
  `tests/Feature/Execution/ExecutionEndpointTest.php`, `tests/Feature/Commitments/CommitmentTest.php`,
  `tests/Feature/Intentions/RecurringIntentionTest.php`, `tests/Feature/Ingestion/ClassifyPastedTextTest.php`.
  Add a web (non-JSON) case only if none exists.
- Skills: `ai-layer-changes` (for `AiUnavailable`), `tdd`.

## 3. `#[FailOn]` — a retry policy on the AI jobs

`DecomposeIntention`, `ConvertCaptureToIntention` and `SplitStep` run queued with no policy, so the
worker default decides. `AiRateLimited` and `AiProviderRequestFailed` are transient;
`AiUnavailable` (consent off, no key) never heals by retrying.

- Framework `#[Tries]` and `#[Backoff]` on each job action; laravel-actions does not read them,
  so `backend/app/Actions/Concerns/ConfiguresJobByAttribute.php` supplies `configureJob()` and
  `getJobBackoff()` from them.
- `backend/app/Attributes/FailOn.php`: `class-string<Throwable> ...$exceptions`. The same
  trait's `getJobMiddleware()` returns `new FailOnException($exceptions)`.
- `#[FailOn(AiUnavailable::class)]` on all three. `AiResponseInvalid` retries: a model answer is not
  deterministic, and the parser still judges the retry.
- Tests, per `testing-best-practices`: one test per action family through `Queue::fake()` is
  not enough — push a real job through the sync driver with `FakeAiProvider` throwing each
  exception, and assert failed-immediately versus released.
- Skills: `laravel-actions` (`configureJob`, `getJobMiddleware`), `ai-layer-changes`.

## 4. `#[PerUserCommand]` — command, description and cadence in one place

The four `QueuesPerUser` actions repeat the `{user? : ...}` argument in a `$commandSignature`
property, and their cadence lives as name strings in `routes/console.php`.

- `backend/app/Enums/Cadence.php`: `EveryMinute`, `Hourly`, with `apply(Event $event): Event`
  as an exhaustive `match`. Not `#[TypeScript]`; it never crosses the API.
- `backend/app/Attributes/PerUserCommand.php`: `string $name`, `string $description`,
  `Cadence $every`.
- `QueuesPerUser` implements `getCommandSignature()` (name plus the `{user?}` argument) and
  `getCommandDescription()` from the attribute; laravel-actions prefers those methods to the
  properties, and `Actions::registerCommands()` still finds the class by the method.
- Delete the `$commandSignature` and `$commandDescription` properties from `SendDueReminders`,
  `SendDueFutureReminders`, `SyncCalendar`, `SendDueRecurringIntentions`.
- `routes/console.php` builds the schedule with `Lody::classes('app/Actions')`, filtered to classes
  carrying the attribute: `$cadence->apply(Schedule::command($name))->withoutOverlapping()`.
  Delete the four hand-written lines.
- `RunDecompositionEval` takes the framework `#[Signature('ai:eval')]` and `#[Description]`
  through `backend/app/Actions/Concerns/ReadsCommandAttributes.php`.
- Replace the "schedules only commands that exist" guard with "schedules every per-user command":
  `Schedule::events()` contains each attributed name. The old guard existed only to catch
  string drift this removes.
- Existing command tests must stay green: `tests/Feature/Reminders/SendDueRemindersTest.php`,
  `tests/Feature/Calendar/SyncCalendarTest.php`.
- Skills: `laravel-actions`, `sail-and-root-scripts` (`npm run artisan -- schedule:list` to eyeball it).

## 5. `#[Driver]` — one name per adapter

Each AI provider and calendar source writes its driver name in `name()`, and the service provider
writes it again in its `match`. Only `IcsCalendarSource::NAME` is shared.

- `backend/app/Attributes/Driver.php`: `string $name`, plus a static
  `classFor(string $configured, list<class-string> $candidates): ?class-string`.
- `backend/app/Support/Concerns/NamedByDriver.php` implements `name()` from the attribute. The
  wrappers (`LoggingAiProvider`, `ConsentGatedAiProvider`) keep delegating and carry no attribute.
- `AiServiceProvider` and `CalendarServiceProvider` pick with `Driver::classFor(config(...), [...])`,
  falling back to the null adapter; the OpenAI class is still wrapped in `ConsentGatedAiProvider`
  after picking.
- Replace `IcsCalendarSource::NAME` in `SyncCalendar` with the attribute's value through one
  accessor. The strings stay byte-identical: `calendar_events.source` stores them.
- Guard: every class implementing `AiProvider` or `CalendarSource` either carries `#[Driver]` or is
  one of the named wrappers, and no two share a name.
- Skills: `ai-layer-changes`, `calendar-sync`.

## 6. `#[NotificationKind]` — the deep-link key, once

`FutureReminderDue` writes `'future_reminder'` in both `toArray()` and `toExpo()`, and the mobile
app routes on it. Both notifications repeat the same `via()`.

- `backend/app/Attributes/NotificationKind.php`: `string $kind`.
- `backend/app/Notifications/Concerns/PushesToDevices.php`: `via()` and a `kind()` read from the
  attribute. `AppointmentReminder` overrides `kind()` with its appointment's kind, so only
  `FutureReminderDue` carries the attribute today.
- Existing notification assertions must stay green; no new test unless one is missing for
  `toExpo()`'s `data.kind`.
- Skills: `expo-react-native` (confirm the mobile side reads `kind` unchanged).

## Done when

- Every phase above is merged, its guard has been seen red once, and nothing it replaced remains:
  no domain `render()`, no `$commandSignature` on a per-user action, no hand-written schedule
  line for one, no duplicated driver string.
- `npm run test`, `npm run stan` and `npm run lint` pass, and `vendor/bin/sloppy diff main --fail-on=high`
  reports nothing new.
- `npm run types:generate` leaves `packages/shared/src/generated.ts` unchanged — no attribute
  here reaches the wire (`generated-artifacts`).
- Finished with `finish-branch`; state moved to `done` here and in the spine with `update-resume`.

## Out of scope

- Ownership on route-bound models: the framework's `#[Authorize]` plus a policy owns that, not
  a hand-rolled attribute. A separate decision.
- Attributes on enum cases: their exhaustive `match` is the point.
- The unlocked `assertOpen()` in the pause, resume and distraction actions — logged in
  `.ai/findings.md`.

## Open

- Whether "custom attributes live in `app/Attributes/`" becomes a line in
  `support-and-concerns.md`. Record it with `record-rule` only when asked.
