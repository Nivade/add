# Slice 10 — context: where the person seems to be

**State:** building, 2026-09-30 · [the slice table](../executive-function-os.md#slices)

*Spec: [`product-spec.md`](../product-spec.md) §2.4, §2.5, §4 (Context), §9,
§26, §35. Rules: `product-invariants.md`, `domain-model.md`, `api-and-data.md`,
`ai-layer.md`, `testing.md`, `support-and-concerns.md`, `general.md`.*

§9 asks the engine to weigh location and available tools. Today it cannot:
nothing knows where the person is or where a step has to happen. So on a
Saturday at home, "Buy bin bags" can outrank "Wipe one worktop" just because it
is shorter. This slice teaches the engine that one fact, the way everything
else in it works. It is deterministic, it explains itself, and asking the
person anything is the last resort.

## Skills

Reload these on entry. Each step names the ones it needs inline.

| Skill | Steps |
| --- | --- |
| `slice-workflow` | 1, 15 |
| `sail-and-root-scripts` | every step that runs a command |
| `next-action-resolver` | 5, 6, 7, 14 |
| `ai-layer-changes` | 3 |
| `laravel-actions` | 3, 8, 9 |
| `laravel-data` | 2, 6 |
| `laravel-attributes` | 2, 4, 9 |
| `laravel-best-practices` | 4, 5, 9 |
| `generated-artifacts` | 2, 6, 8, 10, 14 |
| `pest-testing`, `testing-best-practices` | every step that writes a test |
| `phpstan-larastan` | end of every PHP step |
| `wayfinder-development` | 9, 11 |
| `inertia-react-development` | 11 |
| `tailwindcss-development` | 11 |
| `frontend-design` | 11, 12 |
| `expo-react-native` | 12 |
| `note-finding` | anything noticed and out of scope |
| `finish-branch` (runs `code-review`, then `simplify`) | 15 |
| `update-resume` | 15 |

`laravel-patterns` is switched off in `.claude/settings.json` because it
recommends API Resources, which this repo replaced with laravel-data. Do not
load it.

## Decisions

**The spec's Context is not a table.** It is two things. `Place` is an enum on
a step: where the step has to happen. `Whereabouts` is a value object computed
on each resolve: where the person seems to be. Nothing about the person is
stored except what they said themselves (`not_here_reports`).

**`Place` has four cases.** `home`, `work`, `out` (shops, outside, errands) and
`computer`. The first three are *locations* and exclude each other. `computer`
is a tool and can sit alongside any of them. `steps.place` is nullable, and
null means "anywhere", which describes most steps.

**Decomposition infers the place, the person never tags it.** Mandatory
categorisation is a §35 anti-pattern. `DecomposeIntentionSchema` gains `place`,
and the model sets it only when the step cannot happen anywhere else.

**A split inherits.** `SplitStep` writes its children with the parent's `place`
and ignores whatever the model put there. A smaller piece of a step happens
where the step happens.

**Whereabouts come from evidence, never from a question.** There are two
sources. Asking "where are you?" on every open is exactly the noise §2.4
forbids.

- *Likely:* the place of any step the person completed in the last
  `LIKELY_MINUTES = 45`.
- *Unlikely:* any place the person said they are not at, in the last
  `NOT_HERE_HOURS = 2`. They say it through the new stuck answer or the home
  correction.
- The newer of the two wins for a given place.
- Only one location can be likely: the most recently completed one. Once a
  location is likely, the other two locations become unlikely.
- `computer` is judged on its own evidence only.

Both windows are constants on `Whereabouts`. They are not config and not a
setting.

**Place reorders and never filters.** A wrong inference that hid a step could
hide the only thing that needed doing. `FitsWhereYouAre::compare` scores each
candidate's fit as `2` likely, `1` neutral (null place, or nothing known about
that place) and `0` unlikely. With no evidence at all, every candidate scores
`1` and the chain behaves exactly as it does today.

**Chain position: second.** The chain becomes `DeadlineWithinReach`,
`FitsWhereYouAre`, `HasDeadline`, `NotRecentlySkipped`, `PrerequisiteFirst`,
`StartableNow`, `OldestIntention`, `EarliestPosition`.

- A step you cannot do where you are is not really an option, so place
  outranks every preference below it.
- A deadline within reach still outranks place, because its `why` already
  tells the person to go.

**The `why` states the guess as a guess.** When a step fits, the line is
`Place::seemsHere()`:

| Place | Line |
| --- | --- |
| `home` | "You seem to be at home, where this gets done." |
| `work` | "You seem to be at work, where this gets done." |
| `out` | "You seem to be out, which this needs." |
| `computer` | "You seem to be at a computer, which this needs." |

When a neutral step wins because every other step is unlikely, `decides()`
says "This one does not depend on where you are." for a step with no place,
and "The others need you somewhere you seem not to be." for a placed one, since
an out step after "I'm not at home" does depend on where you are.

**Every guess the `why` states can be corrected in one tap** (§2.5).
`NextActionData` gains `assumedPlace`. It is set only when the `why` contains
that place's `seemsHere()` line. Home then shows a quiet "I'm not at home"
under the `why`, which writes a `not_here_reports` row, and the page
re-resolves.

**A new stuck answer: "I'm not in the right place for this"** (`not_here`). It
resolves to a new `StuckResolution::Elsewhere`:

- It records the step's place as not-here and moves to the next sibling whose
  place differs.
- If there is no such sibling, it lands the session `continued`.
- It is not a skip. `skip_count` is avoidance, and being in the wrong place is
  not avoidance.
- Both frontends offer it only when the step has a place.

**Overwhelm mode honours place.** `SmallestFirst` sorts by cool-off, then
place fit, then size. When place is what kept a shorter step back,
`ReduceToOneStep` says "Nothing shorter can be done where you seem to be."
instead of "Nothing else left is shorter."

**Reports are pruned.** A `not_here_reports` row is worthless after two hours,
and §28 asks for minimal retention. `NotHereReport` is `MassPrunable` after one
day, and `model:prune` runs daily.

**Rejected**, one line each:

- A per-user home/work settings screen: it is the configuration §35 forbids.
- Inferring `computer` from the web client: phone browsers break it.
- Letting the split model choose its children's place: inheritance is
  deterministic and cannot be wrong in a new way.
- Showing a step's place as a tag in the UI: it is noise, and it looks like a
  category the person has to maintain.
- Growing `ResolutionContext` into the Context model: `next-action-resolver`
  forbids it, so it carries `Whereabouts` as one field instead.

## Steps

- [x] **1. Orient.** Invoke skill: `slice-workflow`. Read the rule files listed
  under the spec line. Set this file's state to `building, <date>` and the
  spine's row to `building`. Branch `feature/slice-10-context`.
  *Check:* `npm run artisan -- test --compact tests/Feature/Guards` passes.

- [x] **2. `Place` on steps.** Invoke skills: `laravel-data`,
  `laravel-attributes`, `generated-artifacts`.
  - Create `backend/app/Enums/Place.php`:
    - backed string enum with `#[TypeScript]`, cases `Home`, `Work`, `Out`,
      `Computer`;
    - `isLocation(): bool`, false only for `Computer`;
    - `seemsHere(): string`, an exhaustive `match` returning the four lines in
      Decisions.
  - Migration `npm run artisan -- make:migration add_place_to_steps_table --no-interaction`:
    add `$table->string('place', 16)->nullable()->after('estimated_seconds')`.
    `down()` drops the column.
  - `Step` casts `'place' => Place::class`.
  - `StepData` gains `public ?Place $place` as its last constructor parameter.
    `StepFactory` leaves it null.
  - Run `npm run types:generate`.

  *Check:* `npm run artisan -- migrate` succeeds. `npm run typecheck` passes.
  `packages/shared/src/generated.ts` contains `export type Place`.

- [x] **3. Decomposition infers the place.** Invoke skills: `ai-layer-changes`,
  `laravel-actions`.
  - **Schema.** `DecomposeIntentionSchema`: add `'place' => $schema->string()->enum([...array_column(Place::cases(), 'value'), null])->nullable()->description('Where this has to happen, only when it cannot happen anywhere else: home, work, out (shops, outside, errands) or computer. Null when it can be done anywhere.')->required()`.
    Bump `VERSION` to `'2'`.
    - *Gotcha:* `nullable()` does not add `null` to `enum`, and strict mode
      rejects a null that is missing from the list.
  - **Prompt.** `Prompts::DECOMPOSE` gains one rule, "Set place only when the
    step cannot be done anywhere else; most steps are null." Add places to the
    kitchen example (`home` on each line). Bump `DECOMPOSE_VERSION` to `'2'`.
    Leave `SPLIT_STEP` alone.
  - **Data.** `ParsedStepData` gains `public ?Place $place = null`.
  - **Parser.** `DecomposeParser::step()`:
    - an absent `place` key or `null` becomes null, so existing fakes keep
      passing;
    - a string that `Place::tryFrom` rejects throws
      `AiResponseInvalid('decompose_intention step N has an unknown place.')`.
  - **Writing steps.** `Step::generate()` writes `'place' => $parsed->place`.
    `SplitStep` passes `new ParsedStepData($parsed->title, $parsed->estimatedSeconds, $step->place)`
    to `Step::generate`, so children inherit the parent's place.
  - **Canned answers.** `CannedAiProvider::decompose()` gives its three steps
    `'place' => null`, `'place' => null` and `'place' => 'computer'`.
  - **Tests** in `backend/tests/Feature/Ai/ParserTest.php`: a place is parsed;
    an absent place is null; an unknown place is rejected.
    `backend/tests/Feature/Captures/CaptureToIntentionTest.php`: a decomposed
    step persists its place. `backend/tests/Feature/Execution/StuckTest.php`:
    split children carry the parent's place even when the fake answers a
    different one.

  *Check:*
  `npm run artisan -- test --compact tests/Feature/Ai tests/Feature/Captures tests/Feature/Execution`
  passes, and `npm run stan` is clean. Do not run `ai:eval`: it spends live
  tokens, and place accuracy is not scored this slice (see Open).

- [x] **4. `not_here_reports`.** Invoke skills: `laravel-attributes`,
  `laravel-best-practices`.
  - Migration `create_not_here_reports_table` with these columns:
    - `ulid('id')->primary()`
    - `foreignId('user_id')->constrained()->cascadeOnDelete()`
    - `string('place', 16)`
    - `timestamp('created_at')`
    - `index(['user_id', 'created_at'])`
  - `backend/app/Models/NotHereReport.php` copies `Capture`'s shape:
    `#[Unguarded]`, `#[UseFactory]`, `HasUlids`, `StoresDatesInUtc`,
    `UPDATED_AT = null`, casts `place` and `created_at` (immutable). It also
    uses `MassPrunable`, and `prunable()` returns `static::where('created_at', '<', now()->subDay())`.
  - `User::notHereReports(): HasMany`.
  - `backend/database/factories/NotHereReportFactory.php`.
  - `backend/routes/console.php`:
    `Schedule::command('model:prune', ['--model' => [NotHereReport::class]])->daily();`.

  *Check:* `npm run artisan -- test --compact tests/Feature/Guards` passes,
  because `ConventionsTest` covers the new model.

- [x] **5. `Whereabouts`.** Invoke skills: `next-action-resolver`,
  `laravel-best-practices`.
  - Create `backend/app/Support/NextAction/Whereabouts.php`, a `final readonly`
    class with:
    - `public function __construct(public array $likely = [], public array $unlikely = [])`
      (both `list<Place>`);
    - `public static function forUser(User $user, CarbonImmutable $now): self`;
    - `public function fit(?Place $place): int` (2, 1 or 0, as in Decisions);
    - `public function isKnown(): bool`.
  - `forUser()` runs two queries, then applies the rules from Decisions:
    - the user's steps with a place and `completed_at >= $now->subMinutes(self::LIKELY_MINUTES)`,
      through `whereHas('intention', user_id)`;
    - `$user->notHereReports()` with `created_at >= $now->subHours(self::NOT_HERE_HOURS)`.
    - Pass Carbon instances, never formatted strings. `domain-model.md`
      explains why: the connection converts only date objects.
  - `ResolutionContext` gains `public Whereabouts $whereabouts = new Whereabouts`
    as its last parameter, and `forUser()` passes
    `Whereabouts::forUser($user, $now)`.
  - Create `backend/tests/Feature/NextAction/WhereaboutsTest.php`, with
    scenarios driven through `CompleteStep` and `ReportNotHere`, not raw rows.
    Cover:
    - a home step completed 10 minutes ago makes home likely, and work and out
      unlikely;
    - that evidence expires after 45 minutes;
    - a newer report beats an older completion, and the other way round;
    - `computer` is independent of location;
    - for a user in `Pacific/Auckland`, the window is measured in instants, not
      wall-clock times.

  *Check:*
  `npm run artisan -- test --compact tests/Feature/NextAction tests/Feature/Time`
  passes.

- [x] **6. The rung.** Invoke skills: `next-action-resolver`, `laravel-data`,
  `generated-artifacts`.
  - Create `backend/app/Support/NextAction/Comparators/FitsWhereYouAre.php`
    extending `Rung`:
    - `compare` returns `fit(b) <=> fit(a)`;
    - `decides` returns `seemsHere()` when the winner fits, otherwise "This one
      does not depend on where you are.";
    - `qualifies` returns `seemsHere()` when the winner fits, otherwise null.
  - Insert it second in `ChainedNextActionResolver::$chain`.
  - `NextActionData` gains `public ?Place $assumedPlace = null` after `$why`.
    `resolve()` sets it to the winner's place when
    `in_array($place->seemsHere(), $why, true)`. A continuation answer leaves
    it null.
  - Run `npm run types:generate`.
  - Add scenarios to `backend/tests/Feature/NextAction/ResolverTest.php`.
    Assert the step *and* the full `why`:
    - at home, the home step beats a shorter out step;
    - a deadline within reach still beats place;
    - after "not at home", an undated out step beats a home step with a
      deadline two weeks away;
    - with no evidence, the existing ordering is unchanged;
    - `assumedPlace` is set only when the line is present.

  *Check:*
  `npm run artisan -- test --compact tests/Feature/NextAction tests/Feature/Home`
  passes, including every existing scenario unchanged.

- [ ] **7. Overwhelm mode.** Invoke skill: `next-action-resolver`.
  - `SmallestFirst::sort` compares cool-off, then `FitsWhereYouAre::compare`,
    then size.
  - `ReduceToOneStep::why()` replaces the second line with "Nothing shorter can
    be done where you seem to be." when `$context->whereabouts->isKnown()` and
    another candidate costs less than the chosen one.
  - Add scenarios to
    `backend/tests/Feature/Overwhelm/ReduceToOneStepTest.php`.

  *Check:*
  `npm run artisan -- test --compact tests/Feature/Overwhelm tests/Feature/Execution`
  passes.

- [ ] **8. The stuck answer.** Invoke skills: `laravel-actions`,
  `generated-artifacts`.
  - `StuckReason::NotHere = 'not_here'` goes after `NeedSomething`, and
    `resolution()` maps it to the new `StuckResolution::Elsewhere`.
  - Create `backend/app/Actions/Whereabouts/ReportNotHere.php`: `AsObject`,
    `handle(User $user, Place $place): NotHereReport`, which creates the row.
  - `ReportStuck` matches `Elsewhere` to a private `elsewhere($session, $step)`:
    - if the step's place is null, it runs `AdvanceSession::run($session, $step->id)`;
    - otherwise it runs `ReportNotHere::run($session->user, $step->place)`,
      then takes the first remaining sibling by position with a different
      place (null counts as different);
    - with no such sibling, it runs
      `LandSession::run($session, SessionOutcome::Continued)`.
  - Add tests to `backend/tests/Feature/Execution/StuckTest.php`:
    - the report is written;
    - the sibling is chosen, or the session lands `continued`;
    - `skip_count` is unchanged;
    - a step with no place advances.

  *Check:* `npm run artisan -- test --compact tests/Feature/Execution` passes.

- [ ] **9. The correction endpoint.** Invoke skills: `laravel-actions`,
  `laravel-attributes`, `laravel-best-practices`, `wayfinder-development`.
  - Create `backend/app/Http/Requests/ReportNotHereRequest.php`. It validates
    `'place' => ['required', Rule::enum(Place::class)]` and exposes
    `place(): Place`.
  - Create `backend/app/Http/Controllers/Web/ReportNotHereController.php`,
    which returns `back()`. Route it in `backend/routes/web.php`:
    `Route::post('whereabouts/not-here', ...)->name('whereabouts.not-here')`.
  - Create `backend/app/Http/Controllers/Api/V1/ReportNotHereController.php`,
    which returns `response()->noContent()`. Route it in
    `backend/routes/api.php` as `whereabouts.not-here`.
  - Create `backend/tests/Feature/NextAction/NotHereEndpointTest.php`:
    - web and API both write the row and re-rank `GET /api/v1/next-action`;
    - an invalid place gets a 422;
    - an unauthenticated request gets a 401 or a redirect.

  *Check:* `npm run artisan -- route:list --path=whereabouts` shows both routes,
  and `npm run artisan -- test --compact tests/Feature/NextAction` passes.

- [ ] **10. Shared copy.** Invoke skill: `generated-artifacts`. In
  `packages/shared/src/copy.ts`:
  - add `{ value: 'not_here', label: "I'm not in the right place for this" }`
    to `stuckReasons`, after `need_something`;
  - add `stuckReasonsFor(step: { place: Place | null })`, which drops
    `not_here` when `place` is null;
  - add `notHereLabels: Record<Place, string>` = home "I'm not at home", work
    "I'm not at work", out "I'm not out", computer "I'm not at a computer".

  *Check:* `npm run typecheck` passes.

- [ ] **11. Web.** Invoke skills: `frontend-design`, `inertia-react-development`,
  `wayfinder-development`, `tailwindcss-development`.
  - Create `backend/resources/js/components/not-here.tsx`. Copy
    `SaidIdDoThis`'s quiet-line shape: an Inertia `<Form>` from the Wayfinder
    `whereabouts.notHere.form()` with a hidden `place` input and
    `preserveScroll`. The submit button reads `notHereLabels[place]`.
  - `backend/resources/js/pages/home.tsx` renders it inside the "Why this one"
    band, above `SaidIdDoThis`, when `rightNow.assumedPlace` is set.
  - `backend/resources/js/pages/focus.tsx` maps `stuckReasonsFor(session.step)`
    instead of `stuckReasons`. Read the `ExecutionStateData` shape for the step
    field's name.
  - `frontend-design` drives the critique pass. The existing visual language is
    the brief: `OneThing`, the `Band`, the mono quiet line. The correction must
    read as a quiet line, never compete with Start, and add no colour, icon or
    motion.
  - Place the correction directly under the `why` lines, because it answers
    the guess they state.
  - The response to pressing it is the re-ranked one thing and a `why` without
    the guess. Add no toast and no confirmation text.
  - Screenshot home with and without the line through the `run` skill and
    compare.

  *Check:* `npm run typecheck` passes, and the home page renders the line only
  after a completed placed step.

- [ ] **12. Mobile.** Invoke skills: `expo-react-native`, `frontend-design`.
  - `mobile/src/api/endpoints.ts` gains
    `notHere: (token: string, place: Place) => request<void>('/whereabouts/not-here', { method: 'POST', body: { place } })`.
    Copy the shape of the existing `promoteToCommitment` call.
  - `mobile/app/index.tsx` renders
    `<QuietAction label={notHereLabels[rightNow.assumedPlace]} … />` in the
    "Why this one" band, above the commitment action. Its press reloads the
    home resource, the same way `promote()` does.
  - `mobile/app/focus.tsx` maps `stuckReasonsFor(...)`.
  - The same quiet-line critique applies as on web.

  *Check:* `npm run typecheck` passes.

- [ ] **13. Browser journey.** Invoke skills: `pest-testing`,
  `testing-best-practices`. Add one test to
  `backend/tests/Browser/JourneyTest.php`:
  - complete a home step;
  - home shows "You seem to be at home";
  - press "I'm not at home";
  - the out step is now the one thing;
  - `assertNoJavaScriptErrors()`.

  *Check:* `npm run test:browser` passes.

- [ ] **14. Documents.** Invoke skills: `next-action-resolver`,
  `generated-artifacts`.
  - `.ai/skills/next-action-resolver/SKILL.md`: add `FitsWhereYouAre` to the
    chain line. Under `ResolutionContext`, add that `Whereabouts` is the spec's
    Context and rides on it as one field. Run `npm run boost:update`.
  - `.ai/rules/domain-model.md`: add a short section. The spec's Context is
    `Place` plus `Whereabouts`, not a table, and nothing about the person is
    stored except `not_here_reports`, which are pruned after a day.
  - `.ai/plans/executive-function-os.md`:
    - in the domain model table, add `place` to `steps` and a
      `not_here_reports` row;
    - take `Context` out of the deferred list, with one line saying it earned
      its place here.
  - Empty `$planned` in `backend/tests/Feature/Guards/DocumentationTest.php` of
    every path this slice built. A path still listed once it exists fails the
    test.

  *Check:* `npm run artisan -- test --compact tests/Feature/Guards` passes.

- [ ] **15. Finish.** Invoke skills: `finish-branch` (it runs `code-review`,
  then `simplify`, then `npm run test`, `npm run stan`, `npm run lint` and
  `composer refactor:check`), then `update-resume`.
  - Run `(cd backend && ./vendor/bin/sail php vendor/bin/sloppy diff main --fail-on=high)`
    and fix what this branch introduced.
  - Set this file's state and the spine's row to `done, <date>`.

  *Check:* the PR is open, CI is green, and `DocumentationTest` agrees with the
  spine.

## Open

- A wrong `place` on a step cannot be corrected, only worked around with "I'm
  not at home". Correcting it depends on step editing, which `hardening.md`
  leaves open.
- `place` accuracy is not scored in `backend/storage/ai-eval`. Add
  `expected_place` to the corpus with the next deliberate `ai:eval` run.
- Slice 13 adds device location as a third `Whereabouts` source, behind its own
  consent.
