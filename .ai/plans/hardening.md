# Hardening pass — what the audit found

**State:** done, 2026-09-20 · [the slice table](executive-function-os.md#slices)

*Spec: [`product-spec.md`](product-spec.md) §2.5, §9, §13, §21, §22, §28, §31.
Rules: `product-invariants.md`, `ai-layer.md`, `domain-model.md`.*

Sits between slice 6 and slice 7. Nothing here is a new feature: it is the set of
places where the built slices disagree with their own decisions. Mobile ships on
top of this surface, so the disagreements get settled before a second client
copies them.

Ordered by what breaks the person's trust soonest, not by effort. Each numbered
item is one commit.

## Correctness of the arithmetic

**1. The model's deadline is read in the person's zone, or not at all.**
`ParseCaptureParser` parses a naive datetime as the app zone, which is UTC, so a
model-supplied "09:00" lands two hours off for an Amsterdam user, and the request
never tells the model what day it is. Fix both ends: the parse takes the person's
zone as an argument, and the user message carries today's date and their zone
alongside the text. The prompt already says ISO 8601; it starts saying which zone
to answer in when the text names no offset. Bumps `PARSE_CAPTURE_VERSION`, which
invalidates every cached and fixture answer by design.

Done when a capture whose only deadline the model found lands on the person's
clock, asserted for a non-UTC user with a naive model answer.

**2. A deadline that has passed stops being a reach signal.**
`Candidate::onlyJustFits()` is true forever once the deadline is behind us, so
`DeadlineWithinReach` pins that step to the top of every answer and the `why`
says "your deadline is 2 days ago and what is left only just fits". Two changes:
the fit test requires the deadline to be ahead, and `HasDeadline` gains an
earliest-deadline-first tie-break so the soonest real constraint leads among
several. Wording for a passed deadline is neutral and past tense — a fact, not a
rebuke — and the step stays a candidate, because dropping it would hide work the
person still wants done.

Done when a scenario test fixes a deadline in the past and asserts both the
ranking and the sentence.

**3. The rail is one clock.**
`Rail` parses ISO strings with `new Date()`, which is the browser's zone, while
`minuteOfDay` arrived already computed in the profile zone; a traveller gets a
rail whose marks and "now" are on different scales. The server sends minutes past
midnight for every mark it already sends a timestamp for, and the component does
no zone arithmetic at all. While there: the mark is the leave-by time rather than
the appointment instant, it is labelled with the appointment's own title — which
`RailData` already carries and nothing renders — and the word `due` leaves the
UI, because `product-invariants.md` bans it. The clock ticks from the server
offset on an interval, so an open tab stops lying about what time it is.

Done when the rail renders correctly for a user whose profile zone is not the
browser's, and no component parses a timestamp.

**4. A calendar read that returns nothing deletes nothing.**
`SyncCalendar` reaps events the source stopped reporting with a `whereNotIn` over
the draft list, so an empty read — expired token, provider down — clears the
window and takes the person's stated travel and preparation minutes with it. An
empty read is treated as no information: nothing is reaped. A source that
legitimately has no events in the window is indistinguishable from a failure at
this seam, and keeping two stale rows costs less than deleting six real ones.

Done when a sync returning no drafts leaves the stored window intact.

## Invariants the UI is not keeping

**5. Anything the system inferred says so.**
`product-invariants.md` requires inferred data to be marked and confirmable, and
only the backwards plan keeps it. `StepData` omits the `generated` column, so no
client can tell a model-written step from the person's own words, and a
model-read deadline is shown as fact. `StepData` carries `generated`, both
frontends label a generated step as suggested, and the deadline gets the one
affordance that matters: a confirm, because the deadline is what ranks the step
and fires the reminder. Copy stays flat — "suggested", not a warning.

Editing a step's text is not in scope and stays out: the confirmation for a bad
step is already "I'm stuck", which reaches the split path. Recorded under Open.

Done when a generated step is distinguishable in the props and on the screen, and
an inferred deadline can be confirmed.

**6. The reminder band survives a refresh.**
`BuildHome` marks the notification read while rendering, so a reload loses the
band and, once mobile lands, whichever client renders first eats it. Reading
stops mutating: the band shows while the appointment is ahead, and a dismiss
endpoint marks it read when the person says so.

Done when two consecutive loads both show the band, and a dismiss ends it.

**7. Starting on a step starts on that step.**
`StartSession` returns the running session whatever step it was handed, so
pressing Start on a different thing silently continues the old one — the "nothing
consequential happens silently" line. A running session is retargeted at the
requested step and records an event for it; the person asked, so the app does it,
and one session per stretch still holds. `StoreSessionRequest` also accepts a
step that is done, skipped, or on a finished intention: the controller checks the
step is pending and its intention is active, and answers 404 the way the
ownership checks do.

Done when starting on a second step moves the session, and a done step is
refused.

## The AI seam

**8. The privacy claim is code or it is deleted.**
`config/ai.php` carries `redact_input`, which nothing reads, and no AI call is
logged anywhere, while the build plan claims the path is consented and logged.
The dead flag goes. A logging decorator wraps whichever provider the driver
resolves and records operation, provider, model and token counts per call —
never the payload, because the point is an auditable path, not a copy of the
person's thoughts. Consent is the driver choice until there are real users, and
that is written down in `ai-layer.md` rather than implied.

Done when every provider call leaves one structured log line with no payload in
it, asserted through the fake provider.

**9. Confirm the model id before the first live run.** Confirmed as real, so
`config/ai.php` keeps its default and no code moved.

**10. The chain offers a prerequisite before what follows it.**
The open entry in [`findings.md`](../findings.md): `StartableNow` sits above
`EarliestPosition`, so a cheap later step is offered before the step it depends
on. Decided here rather than patched: within one intention, position wins, because
the model was asked for a sequence and a sequence that can be taken out of order
is not one. Across intentions, cheapest still wins. That is a comparator that
reads both candidates' intentions rather than a reordering of the chain, so
`03-next-action.md` records the new rung and the findings entry clears.

Done when a two-step intention offers step one first even when step two is
shorter, and the cross-intention ordering is unchanged.

**11. Stuck on "too big" moves to a real step.**
`ReportStuck` picks the replacement with `orderBy('estimated_seconds')`, and NULL
sorts first on both engines, so the person is handed an unestimated step — often
a bigger one — and the skip cool-off is ignored. It reuses `ReduceToOneStep`'s
ordering instead, which already answers "shortest finishable thing" and already
honours the cool-off. One definition of smallest, in one place.

Done when the replacement is the shortest estimated step and a step skipped
minutes ago is not offered.

## Concurrency and history

**12. One running session, resolved the same way everywhere.**
Nothing enforces one running session per user, and the three readers disagree
when there are two: `BuildHome` and `ShowFocusController` take an unordered
`first()`, the resolver takes `latest('started_at')`. No database constraint —
a partial unique index is SQLite-only and CI runs MySQL too. Instead one finder
that every reader calls, ordered, and a lock around the create in
`StartSession`. `steps_completed` becomes an atomic `increment()` rather than a
read-modify-write.

Done when a concurrent double start produces one session, and all three readers
return the same row.

**13. Splitting a step does not erase it.**
`SplitStep` deletes the original, dropping its `skip_count` and leaving
`execution_events` rows pointing at an id that no longer resolves, while
`domain-model.md` says skipped steps are kept. No new status word: the split
records an event carrying the replaced step's title and skip count before the
delete, so the history reads continuously. The decision goes in
`domain-model.md`, because "we delete this one row" looks wrong without it.

Done when a split leaves the replaced step's title recoverable from the event
stream.

**14. The schedulers stop loading every user into memory.**
`reminders:dispatch` runs every minute and `calendar:sync` hourly, both over
`User::all()` with queries per user. Chunked, and each person's work dispatched
as a job rather than done inline, so one slow calendar cannot delay everyone
else's reminders. The shape is the fix; the volume is hypothetical.

Done when both commands chunk and neither holds the whole table.

## Around the edges

**15. Extractor edges that produce a confidently wrong date.**
`PhraseDeadlineExtractor` overflows an impossible ISO date silently — `setDate`
turns the 31st of February into March — reads "this monday" on a Monday as next
week, and sends "by the 20th" on the 20th to next month. All three return a date
nobody meant, which is worse than returning null and letting the model try.
Validate the ISO date before accepting it, and treat today as satisfying both
"this <weekday>" and "the <n>th".

Done when each of the three cases is a dataset row in the extractor's unit test.

**16. The model reasons in the person's zone.**
`DecomposeIntention` interpolates a UTC deadline into the prompt. Same treatment
as item 1: the person's zone, formatted, or nothing.

**17. Starter cruft and CI gaps.**
The two `ExampleTest` files assert the starter kit, not this product; the welcome
route deserves a real test and the `assertTrue(true)` does not exist. CI runs
Pint, PHPStan, Pest and the backend `tsc`, but not Rector's dry run, not
`packages/shared`'s typecheck, and nothing fails when `generated.ts` drifts from
the PHP Data classes — the drift that silently breaks both frontends at once.
Root `npm run typecheck` already covers both workspaces; CI calls it, adds
`composer refactor:check`, and regenerates the types to fail on a diff.

Done when a stale `generated.ts` fails CI.

**18. Notification payloads are the privacy surface, stated.**
`notifications.data` holds capture-derived titles in cleartext. Encrypting the
column is not this pass — it is risk 3's ingestion work, where the threat model
gets written — but the exposure is recorded in `ai-layer.md` so the next person
finds it before shipping push.

## Done when

- `npm run test`, `npm run stan`, `npm run lint` and `npm run typecheck` pass.
- No screen shows an inferred value as a fact.
- Two clients reading the same state get the same answer.
- Every item above is either code or a recorded decision, and
  [`findings.md`](../findings.md) has no open entry left from the audit.

## Found while executing it

The schedule had been naming two commands that did not exist: laravel-actions
only turns a `commandSignature` into a command once `registerCommands()` runs,
and nothing called it, so `reminders:dispatch` and `calendar:sync` failed every
minute while the tests — which call the actions directly — stayed green. Fixed
with item 14, and a guard now fails when the schedule names a command nothing
answers.

## Open

Confirming a generated step's wording needs step editing, which nothing has
asked for yet. Overwhelm mode does not label a suggested step: its `why` lines
already say what the step costs, and a second label is the noise that screen
exists to remove. Per-user AI consent is built — slice 8 phase 4 added the
column and both a web and mobile settings screen. Encrypting notification
payloads waits for ingestion, where the threat model is written.
