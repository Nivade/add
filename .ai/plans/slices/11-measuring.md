# Slice 11 — measuring it: whether the app is helping

**State:** building, 2026-09-30 · [the slice table](../executive-function-os.md#slices)

*Spec: [`product-spec.md`](../product-spec.md) §2.4, §18, §28, §35, §36, §37.
Rules: `product-invariants.md`, `domain-model.md`, `api-and-data.md`,
`testing.md`, `support-and-concerns.md`, `general.md`.*

§36 names eight outcomes and forbids the usual ones: no DAU, no sessions per
day, no time in app. Nothing computes any of them yet. Six can be read from what
is already recorded. Two are user-reported and need one question, asked rarely.
And the spine promised this slice would say whether slice 10's context rung
helped, which nothing records today: a `started` event does not say which rung
picked the step. This slice closes all three gaps. The person's screens gain
exactly one thing, a fortnightly question.

## Skills

Reload these on entry. Each step names the ones it needs inline.

| Skill | Steps |
| --- | --- |
| `slice-workflow` | 1, 13 |
| `sail-and-root-scripts` | every step that runs a command |
| `next-action-resolver` | 2, 12 |
| `laravel-actions` | 2, 3, 5, 6, 7 |
| `laravel-data` | 4, 6 |
| `laravel-attributes` | 4, 5, 7 |
| `laravel-best-practices` | 3, 5, 7 |
| `generated-artifacts` | 4, 6, 8, 12 |
| `pest-testing`, `testing-best-practices` | every step that writes a test |
| `phpstan-larastan` | end of every PHP step |
| `wayfinder-development` | 7, 9 |
| `inertia-react-development` | 9 |
| `tailwindcss-development` | 9 |
| `frontend-design` | 9, 10 |
| `expo-react-native` | 10 |
| `note-finding` | anything noticed and out of scope |
| `finish-branch` (runs `code-review`, then `simplify`) | 13 |
| `update-resume` | 13 |

`laravel-patterns` is switched off in `.claude/settings.json` because it
recommends API Resources. Do not load it.

## Decisions

**The metrics are for whoever builds the app, never a screen.** One artisan
command, `metrics:report`, prints aggregates over a window. Nothing about them
reaches the person's home, focus or settings screens. A metrics page for the
person is the dashboard §35 forbids, and optimising the numbers in front of them
is the productivity theater it names. The person-facing progress lines of §18
already exist in `BuildExecutionState` and are untouched.

**Computed on demand, nothing stored but what was missing.** Every metric is a
query over existing rows at the moment the command runs. No snapshot table, no
counter columns: §28 asks for minimal collection, and the raw rows are already
kept. The two additions are the rung on a `started` event and `check_ins`.

**Numbers only, never content.** The report prints counts, shares and
durations. It never prints a title, a capture, a step or a check-in answer next
to anything identifying. `--user=<id>` narrows the window to one person; without
it the report aggregates everyone.

**§36 mapped onto what exists.** Each outcome and what answers it, all over the
window `[now - days, now)`, `--days` defaulting to 28:

| §36 outcome | Measured as |
| --- | --- |
| captured intentions completed | intentions created in the window: how many are `done`, `set_aside` and still open. The share is `done / created`. `set_aside` is its own count and never folded into a failure. |
| time from intention to first action | for the same cohort, minutes from `intentions.created_at` to the earliest `started` event on any of its sessions, as median and 75th percentile; plus how many have not started yet. |
| sessions with meaningful progress | sessions whose `ended_at` is in the window: the share with `steps_completed >= 1`. |
| abandoned-task recovery | sessions ended in the window with an outcome other than `completed`: the share whose intention got another `started` event within `RECOVERY_DAYS = 7` of `ended_at`, or reached `done`. Also, `distracted` events in the window: the share followed by a `step_completed` in the same session. |
| overdue items recovered | there is no overdue, so this reads as skipped steps picked back up: steps with `skip_count > 0` and `last_skipped_at` in the window, and the share now `done`. |
| commitments fulfilled | the count of commitments that became `kept` in the window, by `updated_at`. A count, never a rate against `released`: both are endings. |
| user-reported reduction in overwhelm | `check_ins` in the window with topic `overwhelm`, counted per answer. |
| user-reported reduction in remembering | the same, topic `remembering`. |

Time in app is not measured. §36 says a person using this well should spend
less time in it, but session length is focused work, not time in the app, and
nothing should make time in the app a number anyone watches.

**The rung that picked a step is recorded when the person starts it.**
`StartSession` asks the resolver what it would recommend *before* it touches
anything, and the `started` event's payload records the answer:

- `{"recommended": true, "rung": "fits_where_you_are"}` when the step started is
  the step the resolver would have offered.
- `{"recommended": false, "rung": null}` otherwise.

The report then shows, per rung, how many recommended starts it decided and the
share of those whose step was completed or skipped in the same session. That is
how `fits_where_you_are` is judged against the others. A rung's key is the snake
case of its class basename, and `continuation` is the key when a running session
short-circuited the chain. Renaming a rung class splits its history, and that is
accepted: nothing is deployed.

**`decide()` joins `resolve()` on the contract.** `NextActionResolver` gains
`decide(User, ResolutionContext): ?Decision`. `Decision` carries the
`NextActionData` and the deciding rung's key. `resolve()` stays, returning
`decide()?->answer`, so its two display callers and every existing test are
unchanged. `NextActionData` does not gain the key: it is on the wire, and the
rung is internal.

**The attribution is computed once, before anything moves.** When a session is
running and the person starts a step on another intention, `StartSession` stops
the old session and opens a new one. Resolving after the stop would rank a world
the person never saw. So the attribution comes first, inside the lock, and both
the `open` and `retarget` paths receive it.

**The user-reported outcomes come from a fortnightly question.** One question on
home every `EVERY_DAYS = 14`, alternating between two topics:

| Topic | Question |
| --- | --- |
| `overwhelm` | "Since you started using this, how much does everything weigh on you?" |
| `remembering` | "Since you started using this, how much do you have to keep in your own head?" |

The answers are "Less", "About the same", "More" and "Not now". They are one row
of equal quiet buttons: "Not now" weighs the same as an answer, and pressing it
is recorded and counts as asked. Each question is measured against when the
person started, not against the fortnight before. The fortnightly deltas would
be noise, while a steady "less" is the claim §36 wants to test.

**When the question is due.** `DueCheckIn` answers a topic only when all of
these hold, and null otherwise:

- the account is at least `EVERY_DAYS` old, because a new person has nothing to
  compare with;
- no check-in was recorded in the last `EVERY_DAYS`;
- a session ended in the last `EVERY_DAYS`. A person who is not using the app is
  not asked anything, since a question to someone who went quiet reads as a nag.

The topic is the opposite of the latest check-in's, and `overwhelm` comes first.

**Where it shows.** It shows only on home, never in a notification (§2.4), and
never in focus or overwhelm mode. It is the last band, after "Needs attention"
and above the rest-count line, so it can never compete with Start. `BuildHome`
leaves it null while a session is running or the reminder band is showing:
those are moments the person has something to go and do.

**Recording is idempotent per fortnight.** Web and mobile can both show the band
before either answers. `RecordCheckIn` returns the existing row when one exists
in the last `EVERY_DAYS`, rather than writing a second one or throwing at a
double tap.

**Check-ins are kept, not pruned.** They are the person's own answers and they
are the metric, so they live until the account is deleted, by cascade.
`not_here_reports` are pruned because they stop meaning anything after two
hours. A check-in does not stop meaning anything.

**Visual brief: the existing language, one band quieter than the rest.** The
brief is already written: `Band`, the mono label, quiet buttons of equal weight
(`Responses`). The question is body text, not `OneThing`, because home has one
thing and this is not it. Under the question sits one muted mono line saying
why it is asked, "Asked every two weeks, to tell whether this app is helping."
It adds no colour, icon, motion, progress or thanks. After an answer the band is
gone on the next render, and that absence is the whole confirmation.

**Rejected**, one line each:

- A per-person "your month" summary: it is a dashboard, and it tempts the
  numbers into goals.
- A weekly snapshot table: every input is already kept, and a second copy is a
  second thing to delete.
- Logging each "I'm overwhelmed" open as a proxy: it is a new stored fact about
  the person, and it measures usage, not relief.
- Asking both questions at once: two questions every fortnight is twice the
  noise for the same trend.
- A notification carrying the question: it is exactly the noise §2.4 forbids.
- Putting the rung key on `NextActionData`: it would leak an internal name onto
  the wire and into `generated.ts`.
- A web page for the operator: it needs a role and gate that nothing else needs.

## Steps

- [x] **1. Orient.** Invoke skill: `slice-workflow`.
  - Read the rule files listed under the spec line.
  - Set this file's state to `building, <date>` and the spine's row to
    `building`.
  - Branch `feature/slice-11-measuring` from an up-to-date `main`.

  *Check:* `npm run artisan -- test --compact tests/Feature/Guards` passes.

- [x] **2. `Decision` and `decide()`.** Invoke skills: `next-action-resolver`,
  `laravel-actions`.
  - Create `backend/app/Support/NextAction/Decision.php`:
    ```php
    final readonly class Decision
    {
        public const string CONTINUATION = 'continuation';

        public function __construct(public NextActionData $answer, public string $decidedBy) {}
    }
    ```
  - `Rung` gains a concrete method
    `public function key(): string { return Str::snake(class_basename(static::class)); }`.
  - `App\Contracts\NextActionResolver` gains
    `public function decide(User $user, ResolutionContext $context): ?Decision;`.
    Change its docblock to say both methods never write.
  - In `ChainedNextActionResolver`, move the body of `resolve()` into
    `decide()`:
    - the continuation branch returns
      `new Decision($this->answer(...), Decision::CONTINUATION)`;
    - the ranked branch returns
      `new Decision($this->answer(...), $this->decidingRung($separator)->key())`;
    - `decidingRung(int $separator): Rung` returns `$this->chain[$separator]`
      when `$separator >= 0`, else the last rung, mirroring the fallback in
      `why()`.
    - `resolve()` becomes `return $this->decide($user, $context)?->answer;`.
  - Add scenarios to `backend/tests/Feature/NextAction/ResolverTest.php`:
    - a running session decides as `continuation`;
    - at home, the home step beats a shorter out step, decided by
      `fits_where_you_are`;
    - a lone intention's first step is decided by `earliest_position`.

  *Check:*
  `npm run artisan -- test --compact tests/Feature/NextAction tests/Feature/Home`
  passes, with every existing scenario unchanged.

- [x] **3. The rung on `started`.** Invoke skills: `laravel-actions`,
  `laravel-best-practices`.
  - `StartSession` gains
    `public function __construct(private readonly NextActionResolver $resolver) {}`.
  - Inside the lock closure, before `runningSession()` is read, compute
    `$attribution = $this->attribution($user, $step);`.
  - Pass `$attribution` into `retarget()` and `open()`. Every
    `RecordExecutionEvent::run(..., ExecutionEventType::Started, ...)` call in
    them passes it as the payload. The cross-intention path in `retarget()`
    hands the same `$attribution` to `open()`.
  - Add the private method:
    ```php
    /** @return array{recommended: bool, rung: string|null} */
    private function attribution(User $user, Step $step): array
    {
        $decision = $this->resolver->decide($user, ResolutionContext::forUser($user));
        $recommended = $decision?->answer->step->id === $step->id;

        return ['recommended' => $recommended, 'rung' => $recommended ? $decision->decidedBy : null];
    }
    ```
  - Update `RecordExecutionEvent`'s docblock: the payload carries only what the
    row cannot, and a `started` event's attribution is one of those things.
  - Add to `backend/tests/Feature/Execution/SessionTest.php`:
    - starting the recommended step records `recommended: true` and the rung;
    - starting another step records `recommended: false, rung: null`;
    - retargeting to another intention records the attribution the person saw,
      which is `continuation` for the running step, so starting anything else
      is `recommended: false`.

  *Check:* `npm run artisan -- test --compact tests/Feature/Execution` passes.

- [x] **4. `check_ins`.** Invoke skills: `laravel-attributes`, `laravel-data`,
  `generated-artifacts`.
  - Create `backend/app/Enums/CheckInTopic.php`: a backed string enum with
    `#[TypeScript]`, cases `Overwhelm = 'overwhelm'` and
    `Remembering = 'remembering'`, and `other(): self`.
  - Create `backend/app/Enums/CheckInAnswer.php`: a backed string enum with
    `#[TypeScript]`, cases `Less = 'less'`, `Same = 'same'`, `More = 'more'` and
    `NotNow = 'not_now'`.
  - Run `npm run artisan -- make:migration create_check_ins_table --no-interaction`
    with these columns:
    - `ulid('id')->primary()`
    - `foreignId('user_id')->constrained()->cascadeOnDelete()`
    - `string('topic', 16)`
    - `string('answer', 16)`
    - `timestamp('created_at')`
    - `index(['user_id', 'created_at'])`
  - Create `backend/app/Models/CheckIn.php` with the same shape as
    `NotHereReport`, minus `MassPrunable` and `prunable()`. It casts `topic`,
    `answer` and `created_at` (immutable).
  - Create `backend/database/factories/CheckInFactory.php`.
  - `User::checkIns(): HasMany`.
  - Run `npm run types:generate`.

  *Check:* `npm run artisan -- migrate` succeeds, and
  `npm run artisan -- test --compact tests/Feature/Guards` passes, because
  `ConventionsTest` covers the new model. `packages/shared/src/generated.ts`
  exports `CheckInTopic` and `CheckInAnswer`.

- [x] **5. When it is due, and recording it.** Invoke skills: `laravel-actions`,
  `laravel-attributes`, `laravel-best-practices`.
  - Create `backend/app/Actions/CheckIns/DueCheckIn.php`: `AsObject`, with
    `public const int EVERY_DAYS = 14;` and
    `handle(User $user, CarbonImmutable $now): ?CheckInTopic`. It applies the
    three rules in Decisions, in that order, with one query each. It passes the
    Carbon instances, never formatted strings.
  - Create `backend/app/Actions/CheckIns/RecordCheckIn.php`: `AsObject`, with
    `handle(User $user, CheckInTopic $topic, CheckInAnswer $answer): CheckIn`.
    It returns the latest row from the last `DueCheckIn::EVERY_DAYS` if there is
    one, and otherwise creates a row.
  - Create `backend/tests/Feature/CheckIns/DueCheckInTest.php`:
    - a new account is not asked;
    - an account with no recent session is not asked;
    - an old, active account is asked `overwhelm` first;
    - the next due check-in is `remembering`, and not before 14 days;
    - "Not now" counts as asked.
  - In the same file, test that a second record inside the fortnight returns
    the first row.

  *Check:* `npm run artisan -- test --compact tests/Feature/CheckIns` passes.

- [x] **6. On home.** Invoke skills: `laravel-data`, `generated-artifacts`.
  - `HomeData` gains `public ?CheckInTopic $checkIn` as its last constructor
    parameter.
  - `BuildHome::handle()` computes `$session` and `$reminder` into locals
    first. It then passes
    `checkIn: $session === null && $reminder === null ? DueCheckIn::run($user, $context->now) : null`.
  - Run `npm run types:generate`.
  - Add to `backend/tests/Feature/Home/HomeTest.php`, which asserts the
    Inertia props:
    - home carries the due topic;
    - it is null while a session runs;
    - it is null while the reminder band shows.

  *Check:* `npm run artisan -- test --compact tests/Feature/Home` passes, and
  `npm run typecheck` passes.

- [x] **7. The endpoint.** Invoke skills: `laravel-actions`,
  `laravel-attributes`, `laravel-best-practices`, `wayfinder-development`.
  - Create `backend/app/Http/Requests/StoreCheckInRequest.php`. It validates
    `'response' => ['required', Rule::enum(CheckInAnswer::class)]` and exposes
    `answer(): CheckInAnswer`. The field is `response` because both clients'
    `Responses` components post that name.
  - Create `backend/app/Http/Controllers/Web/StoreCheckInController.php`:
    `__invoke(StoreCheckInRequest $request, CheckInTopic $topic)` runs
    `RecordCheckIn` and returns `back()`.
  - Create `backend/app/Http/Controllers/Api/V1/StoreCheckInController.php`,
    which does the same and returns `response()->noContent()`.
  - Routes. The enum binds implicitly, so an unknown topic is a 404.
    - `backend/routes/web.php`:
      `Route::post('check-ins/{topic}', ...)->name('check-ins.store')`, inside
      the authenticated group next to `whereabouts.not-here`.
    - `backend/routes/api.php`: the same, as `check-ins.store`, next to
      `whereabouts.not-here`.
  - Create `backend/tests/Feature/CheckIns/CheckInEndpointTest.php`:
    - web and API both write the row, and home then carries no check-in;
    - an invalid answer gets a 422;
    - an unknown topic gets a 404;
    - an unauthenticated request gets a 401 or a redirect.

  *Check:* `npm run artisan -- route:list --path=check-ins` shows both routes,
  and `npm run artisan -- test --compact tests/Feature/CheckIns` passes.

- [x] **8. Shared copy.** Invoke skill: `generated-artifacts`. In
  `packages/shared/src/copy.ts`:
  - `checkInQuestions: Record<CheckInTopic, string>`, with the two questions
    from Decisions, verbatim.
  - `checkInResponses: { value: CheckInAnswer; label: string }[]`, in the
    order Less, About the same, More, Not now.
  - `checkInCopy = { band: 'Looking back', meta: 'Asked every two weeks, to tell whether this app is helping.' } as const`.

  *Check:* `npm run typecheck` passes.

- [x] **9. Web.** Invoke skills: `frontend-design`, `inertia-react-development`,
  `wayfinder-development`, `tailwindcss-development`.
  - Create `backend/resources/js/components/check-in.tsx`, rendering
    `<Band label={checkInCopy.band}>` with, in order:
    - `<p>{checkInQuestions[topic]}</p>`;
    - the meta line, in the classes the `JustFinished` recurrence line uses:
      `text-muted-foreground mt-2 font-mono text-[13px]`;
    - `<div className="mt-3"><Responses action={checkIns.store.form(topic)} responses={checkInResponses} /></div>`.
  - `backend/resources/js/pages/home.tsx` renders
    `{data.checkIn && <CheckIn topic={data.checkIn} />}` after the "Needs
    attention" band and before the rest-count row.
  - Critique the result with `frontend-design`, against the visual brief in
    Decisions:
    - the band must read as the quietest thing on the page;
    - the buttons must be the existing `quietButtonClassName`, all four the
      same;
    - it adds no toast and no thank-you.
  - Screenshot home with and without the band through the `run` skill, and
    compare.

  *Check:* `npm run typecheck` passes, and the band renders only for an account
  where `DueCheckIn` answers a topic.

- [x] **10. Mobile.** Invoke skills: `expo-react-native`, `frontend-design`.
  - `mobile/src/api/endpoints.ts` gains
    `checkIn: (token: string, topic: CheckInTopic, response: CheckInAnswer) => request<void>(\`/check-ins/${topic}\`, { method: 'POST', token, body: { response } })`.
    Copy the shape of `respondToWaitingFor`.
  - `mobile/app/index.tsx` renders the band after "Needs attention" and before
    the rest-count `Meta`. It contains the question, the meta line and
    `<Responses responses={checkInResponses} onRespond={async (answer) => { await api.checkIn(token as string, data.checkIn, answer); await reload(); }} />`.
    Use the mobile `Band` from `@/components/screen`.
  - The same critique applies as on web.

  *Check:* `npm run typecheck` passes.

- [x] **11. `metrics:report`.** Invoke skills: `laravel-actions`,
  `laravel-data`, `laravel-attributes`, `laravel-best-practices`.
  - Create `backend/app/Support/Metrics/MetricsWindow.php`, a `final readonly`
    class:
    - `__construct(public CarbonImmutable $from, public CarbonImmutable $to, public ?int $userId = null)`;
    - `static lastDays(int $days, CarbonImmutable $now, ?int $userId = null): self`;
    - generic `scope(Builder $query, string $column = 'user_id'): Builder`,
      which adds `where($column, $userId)` only when `$userId` is set. Type it
      with `@template TModel of Model`.
  - Create `backend/app/Support/Metrics/Percentile.php`:
    `final class Percentile { /** @param list<int> $values */ public static function of(array $values, int $percent): ?int }`.
    It uses the nearest-rank method and returns null for an empty list.
  - Under `backend/app/Data/Metrics/`, create one Data class per group. None
    carries `#[TypeScript]`, because the frontends never see them. Build each
    one with `new`.
    - `IntentionOutcomesData`: `created`, `done`, `setAside`, `open`,
      `?int medianMinutesToStart`, `?int p75MinutesToStart`, `notStarted`.
    - `SessionOutcomesData`: `ended`, `withProgress`, `distractions`,
      `backAfterDistraction`.
    - `RecoveryOutcomesData`: `landedUnfinished`, `pickedBackUp`,
      `skippedSteps`, `skippedThenDone`.
    - `RungOutcomeData`: `?string rung` (null for not recommended), `starts`,
      `doneInSession`, `skippedInSession`.
    - `CheckInOutcomeData`: `CheckInTopic topic`, `less`, `same`, `more`,
      `notNow`.
    - `OutcomesData`: the three above, `int commitmentsKept`,
      `list<RungOutcomeData> rungs` and `list<CheckInOutcomeData> checkIns`.
  - Under `backend/app/Actions/Metrics/`, create one `AsObject` action per
    group. Each has `handle(MetricsWindow $window)` and follows the definitions
    in Decisions exactly.
    - `MeasureIntentions`: fetch the cohort's `id`, `status` and `created_at`.
      Get the first start per intention in one grouped query:
      `min(execution_events.created_at)` joined through `execution_sessions`
      and grouped by `intention_id`, over `type = started`. Do the minute
      arithmetic in PHP, never in SQL, so it stays SQLite-compatible.
    - `MeasureSessions`: count the ended sessions and those with progress in
      SQL. Load the `distracted` events, then answer "a `step_completed`
      later in the same session" with one `whereIn` query over their session
      ids.
    - `MeasureRecovery`:
      - Take the unfinished landed sessions, then one query for later
        `started` events on their intention ids.
      - Treat an intention whose status is `done` as picked back up.
      - For steps, use `whereHas('intention', fn ($query) => $window->scope($query))`.
    - `MeasureRungs`:
      - Load the `started` events in the window, with their `payload`,
        `execution_session_id`, `step_id` and `created_at`.
      - One query fetches the `step_completed` and `step_skipped` events for
        those session ids.
      - Match them in PHP: same session, same step, created later.
      - Group by `payload['rung']`. An event without a payload predates this
        slice and is left out.
    - `MeasureCheckIns`: one grouped count by `topic` and `answer`.
    - The commitments count is small enough to live in `ReportOutcomes`.
  - Create `backend/app/Actions/Metrics/ReportOutcomes.php`:
    - uses `AsObject`, `AsCommand` and `ReadsCommandAttributes`;
    - `#[Signature('metrics:report {--days=28 : How many days back the window reaches} {--user= : One person by id, instead of everyone}')]`;
    - `#[Description('Report the outcomes spec §36 asks for, as numbers only.')]`.
    - `handle(MetricsWindow $window): OutcomesData` runs the five measures and
      counts the kept commitments.
    - `asCommand(Command $command): int`:
      - build the window from the options, and fail when `--user` names no
        user;
      - print one `$command->table()` per group;
      - write each share as `12 of 20 (60%)`, and as `—` when its denominator
        is zero.
    - Copy `RunDecompositionEval` for how the attributes and `asCommand` are
      laid out.
  - Tests:
    - `backend/tests/Unit/Metrics/PercentileTest.php` covers an empty list, a
      single value and an even count.
    - `backend/tests/Feature/Metrics/ReportOutcomesTest.php` lays the world
      down through the real actions (`StartSession`, `CompleteStep`,
      `SkipCurrentStep`, `RecordDistraction`, `StopSession`, `RecordCheckIn`),
      with `$this->travelTo()` between them. It asserts each group's numbers,
      and covers:
      - rows outside the window are left out;
      - `--user` narrows the report to one person;
      - a `set_aside` intention is not counted as done;
      - a started event with no payload is ignored.
    - Also in `ReportOutcomesTest.php`: the command exits 0 and its output
      contains no step or intention title.

  *Check:*
  `npm run artisan -- test --compact tests/Feature/Metrics tests/Unit/Metrics`
  passes, `npm run artisan -- metrics:report` prints the tables on a seeded
  database, and `npm run stan` is clean.

- [x] **12. Documents.** Invoke skills: `next-action-resolver`,
  `generated-artifacts`.
  - `.ai/skills/next-action-resolver/SKILL.md`:
    - the contract has `decide()` beside `resolve()`, and `decide()` names the
      deciding rung;
    - rung keys are persisted in `started` payloads, so renaming a rung class
      splits its history in `metrics:report`.
    - Run `npm run boost:update`.
  - `.ai/rules/product-invariants.md`: under "Success is the person leaving",
    add that `metrics:report` is the only place outcomes are shown, that it
    never reaches the person's screens, and that the check-in is the only
    question home asks unprompted.
  - `.ai/rules/domain-model.md`: add one line under "Immutability", saying
    `check_ins` are immutable and kept until the account goes.
  - `.ai/plans/executive-function-os.md`:
    - add a `check_ins` row to the domain model table;
    - add to the `execution_events` row that `started` carries the deciding
      rung;
    - change the "Measuring it" section to say slice 11 computes it with
      `metrics:report`, and name the two outcomes it answers from check-ins.
  - Empty `$planned` in `backend/tests/Feature/Guards/DocumentationTest.php` of
    every path this slice built.

  *Check:* `npm run artisan -- test --compact tests/Feature/Guards` passes.

- [ ] **13. Finish.** Invoke skills: `finish-branch` (it runs `code-review`,
  then `simplify`, then `npm run test`, `npm run stan`, `npm run lint` and
  `composer refactor:check`), then `update-resume`.
  - Run `(cd backend && ./vendor/bin/sail php vendor/bin/sloppy diff main --fail-on=high)`
    and fix what this branch introduced. `ReportOutcomes` and the measures are
    the likeliest to trip SL101 and SL102; split by metric rather than
    suppress.
  - Set this file's state and the spine's row to `done, <date>`.
  - `.ai/RESUME.md` no longer says §36 has no instrumentation.

  *Check:* the PR is open, CI is green, and `DocumentationTest` agrees with the
  spine.

## Open

- Nothing compares the check-in trend with the behavioural outcomes. With one
  person that comparison is an anecdote, so it waits for a second person.
- The eval corpus still scores no `expected_place` (slice 10's Open), so
  `fits_where_you_are` can be judged by what the person did, but not by whether
  the place was right.
- `metrics:report` prints to a terminal. If the numbers are ever wanted
  elsewhere, a `--json` flag is the whole change. It is not built before
  someone asks.
