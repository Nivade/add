# Slice 8 — make the MVP real

**State:** done, 2026-09-25 · [the slice table](../executive-function-os.md#slices)

*Spec: [`product-spec.md`](../product-spec.md) §5, §7, §18, §22, §28, §30–§32,
§37, §39. Rules: `product-invariants.md`, `domain-model.md`, `api-and-data.md`,
`ai-layer.md`, `testing.md`, `toolchain.md`.*

Slices 1–7 built every MVP surface §32 names, but the calendar is a fixture, the
model is canned, and neither client has met a real person. §32 says build these
exceptionally well before adding integrations, and §39 says the first journey is
polished before the product widens. This slice closes that gap and adds no domain
model the spec defers.

Each phase is usable on its own. Phases 1–4 run in order; phase 5 needs a person
at a device and runs alongside; phase 6 fits anywhere.

## Before any phase

Invoke `slice-workflow`, then `sail-and-root-scripts` before the first command.
Read every rule file whose globs cover the paths in scope, per `.ai/rules/index.md`.
Every phase that writes a test invokes `pest-testing` and `testing-best-practices`;
every phase that touches PHP invokes `phpstan-larastan` before finishing, and
`generated-artifacts` whenever a Data class or route changes what is generated.
Something noticed and out of scope goes through `note-finding`.

## Phase 1 — a real calendar, read-only

*Skills: `calendar-sync`, `laravel-actions`, `laravel-data`, `laravel-attributes`,
`inertia-react-development`, `wayfinder-development`.*

An ICS source behind the existing `CalendarSource` contract. A person pastes the
private feed URL their calendar already publishes: no OAuth, no write scope, and
nothing a provider SDK couples us to. The URL is a credential, so it is stored
encrypted and never serialised into a Data class.

The driver is chosen once, as today; the feed is per person. A person without a
feed reads as an empty answer, which `SyncCalendar` already treats as "delete
nothing". Connecting is opted into from settings, never assumed — the config
comment on `calendar.driver` already says why.

The parser is `sabre/vobject`, approved: recurrence overrides and Windows zone
names are where a hand-written parser is quietly wrong, and its `expand()` reads
exactly the window a sync asks for. 5.0 kept the 4.x `expand()` shape, so it stays
on `^5.0`.

All-day and cancelled entries are left out: neither has an instant to plan
backwards from. A feed that cannot be fetched or parsed throws rather than
answering an empty day, and nothing it throws carries the URL. `ics` is the
example environment's driver, because the per-person opt-in is now the gate.

**Done when** a real feed puts an appointment on home with its leave-by line, a
reminder fires for it, and removing the feed leaves nothing orphaned.

## Phase 2 — answering "Needs attention"

*Skills: `laravel-actions`, `laravel-data`, `next-action-resolver`,
`inertia-react-development`, `wayfinder-development`, `expo-react-native`.*

Closes the open question in [`05-home.md`](05-home.md). An intention held back
for clarification shows one question and one answer field in its band. The
answer confirms the existing intention rather than recording a fresh capture,
so where it came from survives, the same shape as confirming a deadline. Once
answered it becomes eligible for the resolver and decomposition is queued.

The model asks the question while parsing a hedged or unnamed capture, and decomposition waits for the answer.

Both clients get it; the endpoint serves both.

**Done when** "I should probably renew my passport" (§7) can be answered from
home and its first step becomes the right-now action.

## Phase 3 — the §39 journey, under a browser

*Skills: `pest-testing`, `testing-best-practices`.*

One browser test walks the whole journey on the web against the canned AI
driver: capture, one action, start, done, the next step, distracted, welcome
back, finish, progress lines. It drives the journey by keyboard only and checks
focus is visible at each control (§31). `pestphp/pest-plugin-browser` is
approved; it drives Playwright, so the browsers and `ext-sockets` have to exist
inside Sail, not only on the host.

This is the test that says the journey is polished. It is not a place for
per-control assertions the feature tests already make.

**Done when** the test passes in CI.

## Phase 4 — consent, then a live model

*Skills: `ai-layer-changes`, `laravel-actions`, `inertia-react-development`,
`expo-react-native`.*

Three parts, in order, and the third does not start until the first two land.

1. **Per-user consent.** A column and a settings screen. Without consent the
   person's text never reaches an external provider, and the app falls back to
   the answer it gives with no key. §28's "intentional architectural path" is
   this switch, and it is per person, not per deployment.
2. **The two provider findings.** The log copying a person's sentences out of a
   provider error, and the one global rate-limit key. Both are in
   `.ai/findings.md`; remove each entry in the change that fixes it.
3. **An eval corpus for decomposition** (risk 2). A small scored set run against
   a live key: is the first step a physical action, is it small, is the step
   count sane. The plan names this run as the corpus trigger.

**Done when** a consenting person gets a live decomposition, a person without
consent provably does not, and the corpus has a first baseline to compare against.

## Phase 5 — the phone, for real

*Skills: `expo-react-native`, `expo-dev-client`, `expo-router`,
`expo-data-fetching`, `eas-workflows`.*

An EAS project id in `mobile/app.json`, a dev build, and one push sent end to
end: a reminder arrives on a device, opening it lands on its appointment. The
deep link resolves through a fetch-by-id endpoint instead of matching against
`comingUp`, which closes that finding.

This phase needs a person holding a phone or running a simulator; an agent can
prepare it but not finish it.

**Done when** a push has arrived on a real device and its deep link opened the
right appointment. Done against an Android emulator, not a physical device —
no device is available for the foreseeable future, and Expo push behaves the
same either way. The EAS project, its FCM V1 credentials and
`mobile/google-services.json` (gitignored, per-account) are all set up; iOS
stays unverified, no simulator was used.

## Phase 6 — progress that means something

*Skills: `laravel-actions`, `next-action-resolver`, `pest-testing`.*

§18's two strongest lines, added to `ProgressLines`: "you started this after
putting it off for 11 days", counted from when the step was first offered and
skipped; and "you finished the hardest part", where hardest is the largest
estimate in the intention, decided in code. Stated without praise, never as a
streak, and absent rather than zero when it does not apply.

**Done when** both lines appear on finishing and neither appears where it would
be false. `ProgressLines` no longer exists as its own class — the earlier
Support/Concerns refactor folded it into `BuildExecutionState::progressLines()`
— so these two lines were added there instead.

## Finishing

Per phase: `npm run test`, `npm run stan`, `npm run lint`, then `composer
refactor:check`. At the end of the slice, `update-resume` moves this header, the
spine's row and `.ai/RESUME.md` together.

## Open

Named preparation items, step editing and location triggers stay out: each needs
a model the spec defers, and slice 9 is where the first of them earns one.

Phase 1's done-when — a real feed puts an appointment on home, a reminder
fires, removing the feed orphans nothing — is unverified end to end: it needs
a person's real private ICS URL, which an agent cannot supply. The code
shipped and is covered by `SyncCalendarTest`; only the live-feed round trip
is open.
