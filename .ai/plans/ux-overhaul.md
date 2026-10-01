# UX overhaul — the audit of 2026-09-30

**State:** building, 2026-09-30 · [the slice table](executive-function-os.md#slices)

*Spec: [`product-spec.md`](product-spec.md) §2.1–§2.3, §5, §6, §10–§13, §16, §18, §21, §29–§31, §35, §39, §40.
Rules: `product-invariants.md`, `domain-model.md`, `api-and-data.md`, `ai-layer.md`, `testing.md`, `toolchain.md`, `general.md`.*

## Skills

- `slice-workflow`: step 0.1, and closing step 6.3.
- `sail-and-root-scripts`: before the first command of every phase.
- `laravel-actions`, `laravel-attributes`, `laravel-best-practices`: every step touching `backend/app/Actions/**` or adding a PHP class (1.1, 1.3, 1.5, 1.6, 3.2–3.4, 4.3–4.6, 4.10).
- `laravel-data`: every step adding or changing a Data class (1.4, 1.5, 3.1, 3.2, 3.4, 4.3–4.6, 4.10).
- `generated-artifacts`: every step that changes a Data class, enum or route (then `npm run types:generate`).
- `wayfinder-development`: every step adding a web route used from React (1.1, 3.3, 4.3, 4.5, 4.6).
- `ai-layer-changes`: 3.1, 3.2, 3.8.
- `next-action-resolver`: 1.6, 4.7.
- `fortify-development`: 2.5 (login, register), 3.8 (consent at signup).
- `inertia-react-development`, `tailwindcss-development`: every web UI step.
- `frontend-design`: phase 2, and every step that draws a screen.
- `expo-react-native`, `expo-router`, `expo-data-fetching`: phase 5, and 1.1, 1.3, 1.5, 3.5, 4.10 where they touch `mobile/**`.
- `pest-testing`, `testing-best-practices`: every step that writes a test.
- `phpstan-larastan`: every phase, before its finish step.
- `chrome-devtools-mcp:a11y-debugging`: 4.8 and 6.1.
- `security-review`: 1.8 and 3.9.
- `code-review`, `simplify`, `finish-branch`: the finish step of every phase (`finish-branch` runs the other two).
- `split-to-prs`: 0.1 (the branch per phase below is the approved split).
- `update-resume`: 6.3.

## What the person decided, 2026-09-30

- Every audit point is in scope; nothing is optional.
- Capture becomes one box. The model sorts each capture into a kind, reversing slice 9's settled capture question; a read-back marks the kind as inferred and one tap changes it.
- Long text (over 280 characters, or containing a blank line) goes through the existing ingestion classifier from the same box; there is no Paste button anywhere.
- The visual identity is reopened in full, superseding slice 5's visual direction. The identity below is the decision.
- New mobile dependencies approved as part of the audit: `expo-haptics`, `expo-file-system`, `expo-font`, `@expo-google-fonts/atkinson-hyperlegible-next`, `@expo-google-fonts/atkinson-hyperlegible-mono`.
- Only Start and Continue are filled with `now`; every other button is outlined or quiet, and shadcn's `primary` is ink (phase 2 review).
- Deleting the feature tests of the four explicit-entry endpoints (3.5) and of the home "Just finished" band (4.4): approved by the person.
- AI consent is a requirement, 2026-10-01: asked at signup and required to register (3.8), reversing slice 8's opt-in; withdrawing it stays possible, and a capture the model cannot sort is marked failed and home says so. There is no model-free path.
- Mobile's explicit-entry screens go in 3.5, with the API routes they call, 2026-10-01.
- Every session transition refuses a stale tap, not only Done, Skip and I'm stuck (step 4.10), 2026-10-01.
- Removing `@radix-ui/react-navigation-menu`, `@radix-ui/react-toggle` and `@radix-ui/react-toggle-group` from `backend/package.json`: approved, in 3.5, 2026-10-01.

## Rejected, one line each

- Kind chips in the capture dialog: too busy (the person).
- A deterministic phrase router for kinds: the person chose the model.
- A second model call to ask the kind: one payload is never sent twice; `kind` joins the `parse_capture` answer.
- The model deciding whether text is pasted: the length rule is deterministic and free.
- A stale step id as a silent no-op: `domain-model.md` says actions throw; the web renders the throw as a redirect back.
- A celebration on Done: Skip must weigh the same; the motion beat plays on every step change, and celebration waits for the intention's closing screen.
- Hiding the day strip in focus: it is time, not navigation.
- An identity rule file under `.ai/rules/`: rules are recorded only on an explicit ask; the design lives here and slice 5 points here.
- Tokens generated into CSS at build time: hand-mirrored CSS plus a guard test is simpler and needs no build step.
- A second read-back for inferred promises in Needs attention: that band skips commitments whose capture read-back is still open.
- A model-free sort for people without consent: the app works because of the model (the person).
- A response cache so a kind change can re-run the parse: nothing caches answers; the parse is stored on the capture instead.
- Parsing long text after classifying it: the classification already carries the title, why and deadline, and a second call re-sends the payload.
- Checked and dropped: "You have just started" a minute into a session is the 90-second floor in `BuildExecutionState`, not a stale line.

## Before any step

- [x] **0.1 Branch.** Invoke skills: `slice-workflow`, `split-to-prs`, `sail-and-root-scripts`.
  - `git fetch origin && git log origin/main --oneline -30 | grep -i "slice 11\|metrics"` must print the slice 11 merge. If it prints nothing, stop and tell the person slice 11 is unmerged.
  - This plan, its spine row and the `$planned` list in `backend/tests/Feature/Guards/DocumentationTest.php` were written uncommitted on `feature/slice-11-measuring`. Move them: `git stash push -- .ai/plans/ux-overhaul.md .ai/plans/executive-function-os.md backend/tests/Feature/Guards/DocumentationTest.php`, `git switch -c fix/ux-trust origin/main`, `git stash pop`.
  - Commit `docs: plan the ux overhaul` with only those three files.
  - Check: `npm run artisan -- test --compact tests/Feature/Guards/DocumentationTest.php` passes.
- One branch per phase, each off `origin/main` after the previous phase's PR merges: `fix/ux-trust`, `feature/visual-identity`, `feature/unified-capture`, `feature/ux-journey`, `feature/mobile-parity`.
- Set State to `building, <date>` in this file and the spine row in the first commit of phase 1. Tick each step here in the same commit that lands it.
- Test one file with `npm run artisan -- test --compact <path>`; browser tests with `npm run test:browser`; TypeScript with `npm run typecheck`.

## Phase 1 — trust (`fix/ux-trust`)

- [x] **1.1 A double tap cannot finish the next step.** Invoke skills: `laravel-actions`, `wayfinder-development`, `expo-react-native`.
  - `ExecutionSession::currentStepOrFail(?string $expectedStepId = null): Step` throws `InvalidSessionTransition("Session {$this->id} has moved past step {$expectedStepId}.")` when the current step id differs.
  - `CompleteStep::handle(ExecutionSession $session, string $stepId)`, `SkipCurrentStep::handle(ExecutionSession $session, string $stepId)`, `ReportStuck::handle(ExecutionSession $session, string $stepId, StuckReason $reason, ?string $note = null)` pass it through. Update every caller (`grep -rn "CompleteStep::run\|SkipCurrentStep::run\|ReportStuck::run" backend`).
  - New `backend/app/Http/Requests/StepControlRequest.php`: `step_id` required string; `stepId(): string`. The web and API `CompleteStepController` and `SkipStepController` take it. `ReportStuckRequest` gains the same `step_id` rule and `stepId()`.
  - `backend/bootstrap/app.php`: after the `RespondsWith` renderer, `$exceptions->render(fn (OutOfDateTransition $e, Request $request) => $request->expectsJson() ? null : back())`.
  - Web `backend/resources/js/pages/focus.tsx`: every `Control` form posts a hidden `step_id` and its button is `disabled={processing}`; the stuck `router.post` sends `step_id`.
  - Mobile `mobile/src/api/endpoints.ts`: `control()` sends `{ step_id }` for `complete-step` and `skip-step`; `stuck()` sends `step_id`. `mobile/app/focus.tsx` holds a `busy` flag that disables all six controls while a request runs, and an `ApiError` with status 409 calls `reload()`.
  - Tests in `backend/tests/Feature/Execution/SessionTest.php` and `backend/tests/Feature/Execution/ExecutionEndpointTest.php`: a second complete with the old step id throws and leaves the next step pending; the API answers 409; the web answers a redirect and changes nothing; a missing `step_id` is 422.
  - Check: `npm run artisan -- test --compact tests/Feature/Execution` and `npm run typecheck` pass.
- [x] **1.2 The phone layout fits the phone.** Invoke skills: `tailwindcss-development`, `pest-testing`.
  - `backend/resources/js/layouts/shell.tsx`: the header and its button group get `flex-wrap`. Phase 3 removes four of the buttons; this is the interim fix.
  - New `backend/tests/Browser/LayoutTest.php`: at a 390px-wide viewport (find the device method with `search-docs` query `browser testing device viewport`), `/home` has `document.documentElement.scrollWidth <= window.innerWidth`, and `assertNoJavaScriptErrors()`.
  - Check: `npm run test:browser` passes.
- [x] **1.3 Every clock reads in the person's zone.** Invoke skills: `laravel-best-practices`, `expo-react-native`.
  - New `backend/app/Http/Middleware/RecordTimezone.php`: reads `X-Timezone` header, else the `tz` cookie; when the value is in `DateTimeZone::listIdentifiers()` and differs from `users.timezone`, saves it. Nothing else.
  - Register it on the authenticated group in `backend/routes/web.php` (after `verified`) and on the `auth:sanctum` group in `backend/routes/api.php`.
  - `backend/bootstrap/app.php`: add `tz` to `encryptCookies(except: [...])`.
  - `backend/resources/views/app.blade.php`: the inline script also sets `document.cookie = 'tz=' + encodeURIComponent(Intl.DateTimeFormat().resolvedOptions().timeZone) + '; path=/; max-age=31536000; samesite=lax'`.
  - `mobile/src/api/client.ts`: every request sends `X-Timezone: Intl.DateTimeFormat().resolvedOptions().timeZone`.
  - `backend/resources/js/pages/settings/profile.tsx` shows "Times are read in {zone}, taken from this device." from `auth.user.timezone` (add `timezone: string` to the `User` type in `backend/resources/js/types/auth.ts`). `mobile/app/settings.tsx` shows the same line from `Intl`.
  - New `backend/tests/Feature/Auth/TimezoneTest.php`: the cookie updates the zone; the header updates it on the API; an unknown zone is ignored; a guest request is untouched.
  - Check: the test file passes; after login in the browser, the rail clock shows local time.
- [x] **1.4 A capture says it landed.** Invoke skills: `inertia-react-development`, `laravel-data`, `expo-data-fetching`.
  - Web `StoreCaptureController`: replace `->with('captured', true)` with `Inertia::flash('toast', ['type' => 'success', 'message' => 'Got it. Sorting it out.'])` and `back()`.
  - `HomeData` gains `int $sortingCount`: the person's captures with `processed_at` null created in the last 24 hours, counted in `BuildHome`.
  - `packages/shared/src/copy.ts`: `sortingLine(count: number): string` → "Sorting the thought you just wrote down." for one, "Sorting the {n} thoughts you just wrote down." for more.
  - Web `backend/resources/js/pages/home.tsx`: when `sortingCount > 0`, a `<p role="status">` with `sortingLine`, and `usePoll(3000, { only: ['home'] }, { autoStart: false })` started while the count is above zero and stopped when it reaches zero.
  - Mobile `mobile/app/index.tsx`: the same line; reload on screen focus with `useFocusEffect`, and every 3 seconds while the count is above zero.
  - Tests: `backend/tests/Feature/Home/HomeTest.php` counts unprocessed captures only; `backend/tests/Feature/Captures/SortCaptureTest.php` asserts the flashed toast (find the assertion with `search-docs` query `inertia flash data testing`).
  - Check: both test files pass; `npm run types:generate` then `npm run typecheck`.
- [x] **1.5 "I got distracted" welcomes the person back.** Invoke skills: `laravel-actions`, `laravel-data`, `expo-react-native`.
  - `RecordDistraction`: inside the transition, sets `paused_at` when it is null, then records `Distracted`.
  - `ResumeSession`: a paused session clears `paused_at` and records `Resumed`; a running session records `Resumed` and changes nothing else (acknowledging a return is a valid transition). Replace its docblock with one line saying so.
  - `ExecutionStateData` gains `bool $returning` and `int $stepsDone` (the intention's done steps). `BuildExecutionState` constants `RETURNING_AFTER_PAUSE_MINUTES = 5` and `RETURNING_AFTER_IDLE_MINUTES = 20`: returning is paused with the latest event `Distracted` or `paused_at` older than 5 minutes, or running with the latest event older than 20 minutes.
  - `packages/shared/src/copy.ts`: `returnCopy` with `welcome: 'Welcome back.'`, `paused: 'Paused.'`, `pausedMeta: 'Continue whenever you are ready.'`, `workingOn(title)` → "You were working on {title}.", `meta(title, n)` → `workingOn` plus "You had done {n} step(s)." (omitted when 0).
  - Web `focus.tsx` and mobile `focus.tsx`: returning shows `welcome`, `workingOn`, `stepsDone` and Continue (posts `resume`); paused and not returning shows `paused`, `pausedMeta` and Continue.
  - Web `home.tsx` and mobile `index.tsx`: a returning session shows `welcome` and `workingOn` with Continue; otherwise the step title and "Part-way through {intention title}."
  - Tests in `SessionTest.php`: distracted pauses; returning after distracted; not returning one minute into a pause; returning six minutes in (`$this->travel(6)->minutes()`); returning after 21 idle minutes; resume on a running session records `Resumed` and clears returning.
  - `backend/tests/Browser/JourneyTest.php`: after "I got distracted", assert "Welcome back." and "You were working on"; Continue; then Pause asserts "Paused." and Continue.
  - Check: `npm run artisan -- test --compact tests/Feature/Execution` and `npm run test:browser` pass.
- [x] **1.6 One count, one meaning.** Invoke skills: `next-action-resolver`, `laravel-actions`.
  - New `backend/app/Actions/Home/CountOpenThings.php`: `handle(User $user): int` = open intentions + open waiting-fors + open commitments with neither `intention_id` nor `step_id`.
  - `BuildHome::restCount` = `CountOpenThings` minus the needs-attention items minus one for the right-now or session intention when it is not already among them, never below zero. Drop `openBesidesIntentions` from `NeedsAttentionBand`.
  - `ReduceToOneStep` rest count = `CountOpenThings` minus one when a step is shown.
  - Test in `HomeTest.php`: three intentions and one waiting-for, one shown right now → home and `/overwhelmed` both answer 3.
  - Check: `npm run artisan -- test --compact tests/Feature/Home tests/Feature/Overwhelm` passes.
- [x] **1.7 Plan state.** Tick 1.1–1.6 here.
- [x] **1.8 Finish.** Invoke skills: `security-review` (the timezone cookie and middleware), `phpstan-larastan`, `finish-branch`.

## Visual identity

Supersedes slice 5's visual direction. The subject is a calm, competent person beside you on a bad day, so every choice aims at legibility first and one memorable thing: the day, drawn.

**Type.** Atkinson Hyperlegible Next for everything; Atkinson Hyperlegible Mono only for clocks, durations and counts. Designed for legibility, which is this product's promise. Never uppercase, never tracked-out labels.

| Role | Size | Line height | Weight |
| --- | --- | --- | --- |
| The one thing | `clamp(2.25rem, 1.6rem + 2.6vw, 3.75rem)` | 1.1 | 600, tracking -0.015em, `text-balance` |
| Lead (why lines, read-backs) | 1.125rem | 1.55 | 400 |
| Body | 1rem | 1.55 | 400 |
| Band heading (a sentence-case question) | 1rem | 1.4 | 600, muted |
| Small (the smallest size anywhere) | 0.875rem | 1.4 | 400 |
| Numeric (mono, `tabular-nums`) | 0.9375rem | 1.4 | 500 |

**Colour.** Guard-tested for contrast in step 2.2.

| Token | Light | Dark | Use |
| --- | --- | --- | --- |
| paper | `#F2F4F5` | `#0F1B22` | page |
| surface | `#FFFFFF` | `#162530` | dialogs, fields |
| ink | `#172330` | `#E3EBEE` | text |
| muted | `#4F5D6B` | `#9AADB5` | secondary text |
| line | `#D7DDE1` | `#27363F` | decorative hairlines only, never a control's edge |
| field | `#6F808C` | `#6F8793` | control and field borders |
| now | `#0A6B80` | `#5BC3D9` | the thing to do now, and nothing else |
| on-now | `#FFFFFF` | `#0F1B22` | text on `now` |

Day-strip stops, decorative: light dawn `#F4D8C8`, morning `#F6EBC8`, noon `#F4F2E6`, afternoon `#EADFC2`, dusk `#C9C1E6`, night `#2E3A63`; dark dawn `#3B2B31`, morning `#3A3527`, noon `#25313A`, afternoon `#3A3326`, dusk `#2E2946`, night `#121A2E`. Anchored at 06:00, 09:00, 13:00, 17:00, 20:00, 23:00.

**Shape.** Buttons 0.75rem radius, fields 0.5rem, dialogs 1rem. No cards, no shadows, no gradients except the day strip. Bands are separated by space (3rem desktop, 2.5rem phone), not rules.

**Layout.**

```
desktop                                          phone
┌────────┬──────────────────────────────────┐    ┌──────────────────────┐
│06 ▒    │ add        I'm overwhelmed  Capture│   │ add          Capture │
│   ▒    │                                   │    │ ▒▒▒▒▒▓▓░░░░░░░░░ now │
│12:50 ━━│ Find the old passport.            │    │                      │
│   ░░ ~ │ About 4 minutes, so done around   │    │ Find the old         │
│12:54   │ 12:54 if you start now.           │    │ passport.            │
│        │ [ Start ↵ ]   suggested step      │    │ About 4 minutes …    │
│13:30 ─ │                                   │    │ [      Start       ] │
│ leave  │ Why this one?                     │    │ Why this one?        │
└────────┴──────────────────────────────────┘    └──────────────────────┘
```

- Desktop: the day strip is a sticky full-height column, 7.5rem wide; the content column is left-aligned, `max-w-[40rem]`, `ml-[clamp(1.5rem,6vw,6rem)]`; the header sits inside the same column width, so the eye never crosses the screen.
- Phone: the day strip turns horizontal, 2.5rem tall, under the header; content is full width with `px-5`.

**The day strip** (the signature; the rail rebuilt). 06:00 to 24:00. A 0.75rem gradient band at its inner edge; hour labels every two hours in mono small on paper; now as a 2px `now` line with the clock in mono 600; the step block from now to now plus the step's estimate in `now` at 18% with "done ~12:54"; the session block from its start to now in ink at 12%; prep rungs and leave as ink ticks with labels ("find things 13:00", "get ready 13:10", "leave 13:30"); the appointment as a heavier tick with its title. Everything drawn is `aria-hidden`; one visually hidden sentence from `railSummary()` says it all.

**Controls.** Start and Continue are the only filled buttons: `now` background, `on-now` text, 3.5rem tall, lead size, weight 600, with a key hint. Focus controls are six equal outlined buttons, 4rem tall, body 600, each with a one-line hint and a key. Quiet actions are underlined-on-hover text buttons, at least 2.75rem tall.

**Motion.** One neutral beat whenever the step changes, whatever caused it: the new step fades and rises 0.5rem over 300ms. One orchestrated moment on the closing screen: a check that draws itself, then the lines in sequence. Both are off under `prefers-reduced-motion`.

**Voice.** Sentences, not labels joined by middle dots. Titles appear as the person wrote them, never lower-cased.

## Phase 2 — identity (`feature/visual-identity`)

- [x] **2.1 Tokens in one place.** Invoke skills: `frontend-design`, `expo-react-native`.
  - New `packages/shared/src/tokens.ts`: `export const lightColors = {...} as const`, `export const darkColors = {...} as const`, `export const dayStripLight`, `export const dayStripDark` (the stops above, each `{ minute, color }`), `export const radius = { control: 12, field: 8, panel: 16 } as const` (px, for React Native), `export const typeScale` (the table above, in px at a 16px root). One flat `key: '#RRGGBB'` per line, which the guard test parses. Export it from `packages/shared/src/index.ts`.
  - Check: `npm run typecheck` passes.
- [x] **2.2 A guard holds the palette.** Invoke skills: `pest-testing`, `testing-best-practices`.
  - New `backend/tests/Feature/Guards/DesignTokensTest.php`: parses `lightColors` and `darkColors` from `packages/shared/src/tokens.ts`; asserts ink/paper, ink/surface ≥ 7; muted/paper, muted/surface, now/paper, now/surface, on-now/now ≥ 4.5; field/paper, field/surface ≥ 3, in both themes (WCAG relative luminance, written in the test file as `designTokenContrast()`); asserts `backend/resources/css/app.css` declares `--paper`, `--surface`, `--ink`, `--muted`, `--line`, `--field`, `--now`, `--on-now` with the same hex in `:root` and `.dark`; asserts no `uppercase` class and no `text-[<n>px]` under `backend/resources/js` outside `components/ui/`.
  - Verify the guard by breaking one hex in `app.css` and watching it fail.
  - Check: the test fails until 2.3 and 2.4 land, then passes.
- [x] **2.3 The stylesheet and the fonts.** Invoke skills: `tailwindcss-development`, `vite`.
  - `backend/vite.config.ts`: replace the two Plex `bunny()` calls with `bunny('Atkinson Hyperlegible Next', { weights: [400, 500, 600, 700] })` and `bunny('Atkinson Hyperlegible Mono', { weights: [400, 500, 600] })`.
  - `backend/resources/css/app.css`: the eight tokens as CSS variables in `:root` and `.dark`; map the shadcn variables onto them (`--background: var(--paper)`, `--foreground: var(--ink)`, `--muted-foreground: var(--muted)`, `--border: var(--line)`, `--input: var(--field)`, `--ring: var(--now)`, `--primary: var(--ink)`, `--primary-foreground: var(--paper)`, `--card`/`--popover: var(--surface)`); `--radius: 0.5rem`; `@theme` text sizes `--text-one-thing`, `--text-lead`, `--text-body`, `--text-small`, `--text-numeric` with their line heights; the day-strip stops as `--strip-*`. Remove `--chart-*` and `--sidebar-*` and their uses in `backend/resources/js/types/ui.ts` and `backend/resources/js/layouts/settings/layout.tsx`.
  - `backend/resources/views/app.blade.php`: the inline `html` background becomes `#F2F4F5` and dark `#0F1B22`; add `<meta name="theme-color">` for both schemes.
  - `backend/resources/js/app.tsx`: the progress colour becomes `#0A6B80`.
  - Check: `npm run artisan -- test --compact tests/Feature/Guards/DesignTokensTest.php` passes except the class checks 2.4 clears.
- [x] **2.4 Every web component in the new identity.** Invoke skills: `frontend-design`, `inertia-react-development`, `tailwindcss-development`.
  - `backend/resources/js/components/one-thing.tsx`: `OneThing` is an `h1` with `tabIndex={-1}`, the one-thing size, no border stripe; `Meta` becomes lead-size muted sentences; `StartStep` uses the filled Start. `stepMeta` already lives in `packages/shared/src/copy.ts`; 4.7 replaces it.
  - `backend/resources/js/components/band.tsx`: no rule; an `h2` in the band-heading style; labels become questions: "Why this one?", "What's coming up", "Needs you", "Before you go", "Sorted for you", "A question for you" (the check-in).
  - `backend/resources/js/components/ui/button.tsx`: `default` is outlined ink, and a `now` variant, the filled `now` style, is used by Start and Continue alone; a `quiet` variant is the text button; add `size: 'action'` (3.5rem) and `size: 'control'` (4rem); outline uses `border-field`; every size at least 2.75rem.
  - `backend/resources/js/components/responses.tsx`: `quietButtonClassName` and `quietLineClassName` become body-size text buttons, `min-h-11`, no mono, no uppercase.
  - `git mv backend/resources/js/components/rail.tsx backend/resources/js/components/day-strip.tsx`, export `DayStrip`, and draw it as specified above. `RailData` gains `?int $stepSeconds` and `list<RailMarkData> $marks`, replacing `leaveByMinute`/`leaveByClock`; new Data `RailMarkData(?PlanRung $rung, int $minute)` in `backend/app/Data/RailMarkData.php`, where a null rung is the appointment itself. `BuildRail::handle(User $user, ?ResolutionContext $context = null, ?int $stepSeconds = null)` fills them; `ShowHomeController` passes the right-now step's estimate as the page prop `rail`, which overrides the shared one. Shared `railSummary(rail): string` in `packages/shared/src/copy.ts`.
  - `backend/resources/js/layouts/shell.tsx`: new layout per the wireframe; the strip is horizontal under `sm`.
  - New `backend/resources/js/components/wordmark.tsx` ("add", weight 700, links home); the app logo and its icon component are deleted, and the three auth layouts use the wordmark.
  - Every `text-[11px]`/`text-[13px]`/`font-mono`/`uppercase`/`tracking-[...]` under `backend/resources/js` outside `components/ui/` goes; mono stays only on clocks, durations and counts.
  - Tests: `backend/tests/Feature/Home/RailTest.php` covers `stepSeconds` and `marks`.
  - Check: `DesignTokensTest.php` passes; `npm run composer -- ci:check` passes; screenshots of `/home`, `/focus`, `/overwhelmed` in light and dark at 1440 and 390 match the wireframe.
- [x] **2.5 The doors in.** Invoke skills: `fortify-development`, `frontend-design`.
  - `backend/resources/js/pages/welcome.tsx`: headline kept; beside it, a `<figure>` with a static, non-interactive sample of home (the strip, "Put the laundry in the washing machine.", "About 3 minutes, so done around 19:08 if you start now.", a disabled Start with `tabIndex={-1}`, "Why this one? Your parents arrive Saturday."), captioned "What opening the app looks like."; the footer line becomes "Built for the days when starting is the hard part."
  - `backend/resources/js/pages/auth/login.tsx`: title "Log in", description "Pick up where you left off.", the remember checkbox `defaultChecked`.
  - `backend/resources/js/pages/auth/register.tsx`: description "It takes a minute. Nothing else is asked of you."
  - `backend/resources/js/layouts/settings/layout.tsx`: heading "Settings" with no description; the nav is text links, the current one ink weight 600 with `aria-current="page"`.
  - Check: `npm run test:browser` passes; screenshots of `/`, `/login`, `/settings/profile` in both themes.
- [x] **2.6 Slice 5 points here.** Add one line under slice 5's "The visual direction" heading in [`slices/05-home.md`](slices/05-home.md): "Superseded 2026-09-30 by the visual identity in the UX overhaul plan."
  - Check: `npm run artisan -- test --compact tests/Feature/Guards/DocumentationTest.php` passes.
- [x] **2.7 Finish.** Invoke skills: `phpstan-larastan`, `finish-branch`.

## Phase 3 — one capture box (`feature/unified-capture`)

- [x] **3.1 The capture parse answers a kind.** Invoke skills: `ai-layer-changes`, `laravel-data`.
  - New `backend/app/Enums/CaptureKind.php`, `#[TypeScript]`: `Thought = 'thought'`, `WaitingFor = 'waiting_for'`, `Promise = 'promise'`, `Reminder = 'reminder'`, `NotForYou = 'not_for_you'`.
  - `ParseCaptureSchema`: `VERSION = '4'`; adds `kind` (string enum of the first four values, required) and `waiting_on` (nullable, required: the person or organisation waited on).
  - `Prompts::PARSE_CAPTURE_VERSION = '4'`; add rules: `thought` is the default; `waiting_for` only when someone else owes them something; `promise` only when they state they will do something for someone ("I'll", "I told X I'd"); `reminder` only when they ask to be reminded; hedged intent ("I'll probably") is a thought. Add one example per kind.
  - `ParsedCaptureData` gains `CaptureKind $kind` and `?string $waitingOn`; `ParseCaptureParser` throws `AiResponseInvalid` on a kind outside the four.
  - `CannedAiProvider::parseCapture` picks the kind from the text's start ("waiting for/on" → waiting_for, "I'll"/"I will" → promise, "remind me" → reminder, else thought) so the UI can be clicked through without a key.
  - `storage/ai-eval/capture-corpus.json`: every entry gains `expects_kind`; add entries "waiting for John to send the contract", "waiting on the insurance company about the claim", "I'll send Sarah the photos tomorrow", "told mum I'd call her on Sunday", "remind me tomorrow at 9 to call the dentist", "remind me on Friday to water the plants", "need to call the dentist", "I'll probably need a new phone at some point" (thought). `RunCaptureEval` scores the kind and prints expected, actual and match.
  - Tests: `backend/tests/Feature/Ai/CaptureEvalTest.php` covers kind scoring; parser tests for each kind and an invalid kind.
  - Check: `npm run artisan -- test --compact tests/Feature/Ai tests/Feature/Captures` passes.
- [x] **3.2 Sorting replaces converting.** Invoke skills: `laravel-actions`, `ai-layer-changes`.
  - Migration: `npm run artisan -- make:migration add_kind_to_captures_table --table=captures --no-interaction`: `kind` string(32) nullable, `routed_id` ulid nullable (no foreign key; the kind says which table), `kind_confirmed_at` timestamp nullable, `parsed` json nullable, `failed_at` timestamp nullable. `Capture` casts `kind` to `CaptureKind` and `parsed` to `ParsedCaptureData`. The body stays immutable; the routing columns are written by sorting, as `intention_id` is today.
  - `git mv backend/app/Actions/Intentions/ConvertCaptureToIntention.php backend/app/Actions/Captures/SortCapture.php`; class `SortCapture`, `handle(Capture $capture, ?CaptureKind $chosen = null): Capture`, same job attributes.
    - Returns early when `kind` is set and nothing was chosen.
    - Long text (`mb_strlen($body) > 280` or a blank line) with nothing chosen runs `ClassifyPastedText`; either way it stores a `ParsedCaptureData` built from the classification in `parsed` (`title`, or `Str::limit($body, 80)` when not actionable; `why`; `deadlineAt`; kind `Thought`; `waitingOn` and `clarifyingQuestion` null), and there is no parse call. Not actionable sets `NotForYou` and `processed_at`, and stops; actionable routes as a Thought.
    - Otherwise the existing extractor-then-model parse, stored in `parsed` before routing; when `parsed` is already set, it is reused and the model is not asked. The kind is `$chosen ?? $parsed->kind`.
    - Thought: the existing intention path, `DecomposeIntention` dispatched after commit.
    - WaitingFor: `CreateWaitingFor::run($user, $parsed->waitingOn ?? $parsed->title, $parsed->waitingOn === null ? null : $parsed->title)`.
    - Promise: `CreateCommitment::run($user, $parsed->title, $chosen === null ? CommitmentProvenance::SystemInferred : CommitmentProvenance::UserStated)`.
    - Reminder: `CreateFutureReminder::run($user, $capture->body)`; a `ValidationException` (no time found) routes it as a Thought.
    - Writes `kind`, `routed_id`, `processed_at`, and `kind_confirmed_at = now()` when chosen.
    - `jobFailed(Throwable $e, Capture $capture): void` sets `failed_at` (no consent is `AiConsentRequired`, an `AiUnavailable`, so `FailOn` fails it at once). `BuildHome`'s `sortingCount` leaves out captures with `failed_at` set, so polling stops.
  - `UpdateAiConsent`: turning consent on clears `failed_at` on the person's unprocessed captures and dispatches `SortCapture` for each.
  - `RecordCapture` dispatches `SortCapture`. Update every reference (`grep -rn ConvertCaptureToIntention backend .ai`), and `git mv backend/tests/Feature/Captures/CaptureToIntentionTest.php backend/tests/Feature/Captures/SortCaptureTest.php` rather than creating it.
  - `backend/tests/Feature/Captures/SortCaptureTest.php` gains: one test per kind with `fakeAi()->respondFor()`; long text through the classifier, both answers, with no parse call; a reminder with no time becomes a thought; a chosen promise is `user_stated` and confirmed; sorting twice does nothing; a second sort with `parsed` set asks the model nothing; no consent sets `failed_at` and drops it from `sortingCount`; turning consent on sorts it.
  - Check: `npm run artisan -- test --compact tests/Feature/Captures` passes.
- [x] **3.3 Change it or confirm it.** Invoke skills: `laravel-actions`, `wayfinder-development`.
  - New `backend/app/Exceptions/CaptureAlreadyActedOn.php`, extends `OutOfDateTransition`, `#[RespondsWith(Response::HTTP_CONFLICT)]`.
  - New `backend/app/Actions/Captures/ChangeCaptureKind.php`: `handle(Capture $capture, CaptureKind $kind): Capture`. Throws `CaptureAlreadyActedOn` when the routed record was acted on: an intention with any execution session, a waiting-for no longer waiting, a commitment no longer open, a future reminder already sent. Otherwise deletes the routed record (an intention with its steps), clears `intention_id`, `routed_id` and `kind`, and runs `SortCapture` synchronously with `$kind`, which reuses `parsed`, so no change of kind asks the model again. `NotForYou` is not a choice.
  - New `backend/app/Actions/Captures/ConfirmCaptureKind.php`: sets `kind_confirmed_at`; for a promise, also runs `RespondToCommitment` with `CommitmentResponse::Confirm`.
  - New `backend/app/Http/Requests/ChangeCaptureKindRequest.php`: `kind` required, `Rule::enum(CaptureKind::class)->except([CaptureKind::NotForYou])`.
  - Web: `POST captures/{capture}/kind` named `captures.kind` → `backend/app/Http/Controllers/Web/ChangeCaptureKindController.php`; `POST captures/{capture}/confirm` named `captures.confirm` → `backend/app/Http/Controllers/Web/ConfirmCaptureKindController.php`. Both `back()` with `preserveScroll` on the client.
  - API: same paths under `/api/v1` → `backend/app/Http/Controllers/Api/V1/ChangeCaptureKindController.php` and `backend/app/Http/Controllers/Api/V1/ConfirmCaptureKindController.php`, returning `CaptureData`, which gains `?CaptureKind $kind` and `?string $kindConfirmedAt`.
  - New `backend/tests/Feature/Captures/CaptureKindTest.php`: change each way; "Keep it anyway" turns not-for-you into a thought; an acted-on record answers 409; another person's capture is refused; confirm sets the stamp and confirms a promise.
  - Check: the test file passes; `npm run types:generate`.
- [x] **3.4 Home reads back what it sorted.** Invoke skills: `laravel-data`, `inertia-react-development`, `frontend-design`.
  - New `backend/app/Data/SortedCaptureData.php`, `#[TypeScript]`: `id`, `excerpt` (`Str::limit($body, 80)`), `CaptureKind $kind`, `?string $detail` (the waiting-for subject; the reminder's trigger in words, the same way `ComingUpData::$inWords` is built).
  - New `backend/app/Actions/Home/BuildSortedCaptures.php`: `handle(User $user): array{items: list<SortedCaptureData>, more: int}`; unconfirmed captures whose kind is set and not `Thought`, newest first, three shown, the rest counted. They stay until answered.
  - `HomeData` gains `array $sorted`, `int $sortedMore`, `int $unsortedCount` (the person's captures with `failed_at` set) and `bool $aiConsented` (`hasConsentedToAi()`). `BuildNeedsAttention` takes the ids of commitments routed from an unconfirmed capture and leaves them out.
  - `packages/shared/src/copy.ts`: `unsortedLine(count: number, consented: boolean)` → without consent "Sorting what you write needs AI turned on. Nothing you wrote is lost." with a "Turn it on" link to `ai.edit`; with consent "{n} thing(s) you wrote could not be sorted. Nothing is lost." Home shows it as `role="status"` when `unsortedCount > 0`.
  - `packages/shared/src/copy.ts`: `sortedLine(kind, detail)` ("Saved as something you are waiting on from {detail}.", "Saved as something you said you would do.", "Saved as a reminder for {detail}.", "Nothing in this looks like it needs you."); `captureKindChoices` ("It's a thought", "I'm waiting on it", "I said I'd do it", "Remind me"); `sortedMoreLine(n)`.
  - New `backend/resources/js/components/sorted-band.tsx`, placed on home after "Why this one?": each item is the excerpt in quotes, its `sortedLine`, then "That's right" and "Not right?"; "Not right?" reveals the other kinds as quiet buttons. A not-for-you item offers "Keep it anyway" and "That's right". `sortedMore` shows `sortedMoreLine` and no list.
  - Tests in `HomeTest.php`: the band shows unconfirmed non-thought captures, three at most, with the rest counted; an inferred promise is absent from Needs attention while its read-back is open; a failed capture counts in `unsortedCount` and not in `sortingCount`.
  - Check: `npm run artisan -- test --compact tests/Feature/Home` passes.
- [x] **3.5 One box, and the other doors go.** Invoke skills: `inertia-react-development`, `wayfinder-development`, `pest-testing`.
  - `backend/resources/js/components/quick-capture.tsx`: exports `CaptureHost` (the `c` key and the dialog, no trigger) and `QuickCapture` (host plus the header button). The dialog: "What's on your mind?", "Write it however it comes out. Sorting it out is the app's job.", a four-row textarea, Save with a `⌘↵`/`Ctrl ↵` hint by platform, Cmd/Ctrl+Enter submits.
  - Delete the waiting-for, commitment, future-reminder and paste capture components under `backend/resources/js/components/`, and `CaptureFormDialog` (the dialog shell now lives in `backend/resources/js/components/quick-capture.tsx`).
  - Header: wordmark; "I'm overwhelmed" (a plain link to `overwhelmed()`; 4.9 styles it and adds its key), Capture, the user menu. Nothing else.
  - Delete the web routes `waiting-fors.store`, `commitments.store`, `future-reminders.store`, `ingestion.classify` and the API `POST` routes `waiting-fors`, `commitments`, `future-reminders`, `ingestion/classify`, their controllers, and `StoreWaitingForRequest`, `StoreCommitmentRequest`, `StoreFutureReminderRequest`, `ClassifyPastedTextRequest`. The actions stay; `SortCapture` calls them. Remove `entryCopy` from `packages/shared/src/copy.ts`.
  - Mobile, so it never calls a deleted route: delete the add, waiting-for, commitment, remind and paste screens under `mobile/app/`, the entry component under `mobile/src/components/`, their functions in `mobile/src/api/endpoints.ts`, and the `/add` button in `mobile/app/index.tsx`. `mobile/app/capture.tsx` stays the one way in.
  - `npm uninstall --workspace backend @radix-ui/react-navigation-menu @radix-ui/react-toggle @radix-ui/react-toggle-group` at the root; `grep -rn "react-navigation-menu\|react-toggle" backend/resources` prints nothing first.
  - Tests: delete the endpoint tests for the removed routes; everything they proved about the actions is now in `SortCaptureTest.php`.
  - Check: `npm run test` and `npm run typecheck` pass; `npm run artisan -- route:list --path=api` shows none of the removed routes.
- [x] **3.6 The browser journey captures through the box.** `backend/tests/Browser/JourneyTest.php`: capture "waiting for John to send the contract" and assert the read-back and "That's right"; capture "clean the kitchen before my parents arrive" and continue the existing journey. Check: `npm run test:browser` passes.
- [x] **3.7 The recorded decisions move.**
  - Under "The capture question, settled" in [`slices/09-phase-2.md`](slices/09-phase-2.md), add: "Reversed 2026-09-30 by the UX overhaul plan: the model sorts, the read-back marks it inferred, and one tap changes it."
  - In `.ai/rules/domain-model.md`, the captures sentence becomes: a capture's body is never edited; sorting writes its routing columns once, and changing the kind rewrites them.
  - `.ai/plans/executive-function-os.md` API boundaries: add `POST /api/v1/captures/{id}/kind` and `POST /api/v1/captures/{id}/confirm`.
  - Check: `DocumentationTest.php` passes.
- [x] **3.8 Consent at the door.** Invoke skills: `fortify-development`, `ai-layer-changes`, `pest-testing`.
  - `backend/app/Actions/Fortify/CreateNewUser.php`: validates `'ai_consent' => ['accepted']` and creates the user with `ai_consented_at = now()`.
  - `backend/resources/js/pages/auth/register.tsx`: a required checkbox `ai_consent`, unchecked by default, labelled from shared `registerConsentLabel` = "Send what I write to a model outside this server, so it can be sorted for me.", with its `InputError`.
  - `aiConsentCopy(false).line` becomes "Nothing you write leaves this server, so nothing you capture gets sorted." The settings switch stays on web and mobile: consent can be withdrawn, and 3.4's `unsortedLine` says what that stops. The gate in `AiConsent` is unchanged.
  - `backend/database/factories/UserFactory.php`: `ai_consented_at` defaults to `now()`; new state `withoutAiConsent()`. Tests that assert a refusal or the off state (`grep -rln "ai_consented_at\|hasConsentedToAi\|AiConsentRequired" backend/tests`) use the state.
  - Tests in `backend/tests/Feature/Auth/RegistrationTest.php`: registering without `ai_consent` fails validation and creates no user; with it, `ai_consented_at` is set.
  - Records: under item 1 of Phase 4 in [`slices/08-mvp-real.md`](slices/08-mvp-real.md) add "Amended 2026-10-01 by the UX overhaul plan: consent is asked at signup and required; the settings switch withdraws it."; in `.ai/rules/ai-layer.md`, "Consent is per person" gains: registration requires it, and withdrawing it stops sorting, which home says.
  - Check: `npm run artisan -- test --compact tests/Feature/Auth tests/Feature/Settings tests/Feature/Ai` and `DocumentationTest.php` pass.
- [ ] **3.9 Finish.** Invoke skills: `security-review` (ownership on the new routes, model output driving writes), `phpstan-larastan`, `finish-branch`.

## Phase 4 — the journey (`feature/ux-journey`)

- [ ] **4.1 Focus drops the chrome.** Invoke skills: `inertia-react-development`.
  - New `backend/resources/js/layouts/focus-frame.tsx`: the day strip and the page, no header; mounts `CaptureHost`.
  - One clock per page: move `useMinuteOfDay(from, live)` out of `day-strip.tsx` into `backend/resources/js/hooks/use-minute-of-day.ts`; `shell.tsx` and `focus-frame.tsx` call it once and pass `nowMinute` to both `DayStrip`s (column and row), which stop running their own interval.
  - `backend/resources/js/app.tsx`: `focus` and `finished` use `FocusFrame`.
  - Check: `/focus` shows no header; `c` still opens capture.
- [ ] **4.2 A keyboard layer.** Invoke skills: `inertia-react-development`.
  - New `backend/resources/js/hooks/use-shortcuts.ts`: `useShortcuts(map: Record<string, () => void>, enabled = true)`; ignores typing targets, modifier keys, and any open `[role="dialog"]` other than the one that registered it.
  - Keys: shell `c` capture, `o` overwhelmed; home `Enter` starts when `document.activeElement === document.body`; focus `d` Done, `s` Skip, `?` and `h` I'm stuck, `p` Pause, `r` I got distracted, `x` Stop; stuck dialog `1`–`8`; overwhelmed `Enter` start, `Escape` home; finished `Enter` start next, `Escape` home.
  - Every button with a key shows a `<kbd>` hint and carries `aria-keyshortcuts`.
  - New `NowButton` in `backend/resources/js/components/now-button.tsx`: `variant="now" size="action"`, its key hint and `aria-keyshortcuts`; Start and Continue in `one-thing.tsx`, `home.tsx` and `focus.tsx` use it and nothing else does.
  - `JourneyTest.php` drives Done and Continue by these keys.
  - Check: `npm run test:browser` passes.
- [ ] **4.3 Six controls in two honest rows.** Invoke skills: `frontend-design`.
  - Focus controls as two `role="group"` rows, all six equal (`size="control"`): "This step" — Done, Skip, I'm stuck; "Step away" — Pause, I got distracted, Stop.
  - `packages/shared/src/copy.ts` `focusCopy` gains `hints`: Done "Finished it", Skip "Not this one now", I'm stuck "Tell me what's in the way", Pause "Back in a bit", I got distracted "I drifted off", Stop "Done for now".
  - After any control, focus moves to the `h1`; the step container is keyed by step id for the motion beat.
  - Check: screenshots at 1440 and 390; `JourneyTest.php` passes.
- [ ] **4.4 Finishing is a moment.** Invoke skills: `laravel-actions`, `laravel-data`, `frontend-design`, `pest-testing`.
  - New `backend/app/Data/FinishedData.php`, `#[TypeScript]`: `IntentionData $intention`, `list<string> $lines`, `?NextActionData $next`, `?int $recurrenceEveryDays`.
  - New `backend/app/Actions/Sessions/BuildFinished.php`: `handle(ExecutionSession $session): FinishedData`. Lines, each only when true: "{n} steps done." or "{n} steps done, {m} skipped."; "About {duration} of work." (the sum of the intention's sessions, paused time excluded); "{words} before the deadline."; "It had been on your list for {d} days." (two days or more); "{t} things finished today." (intentions). `next` from `NextActionResolver::resolve`.
  - Routes: web `GET focus/{session}/finished` named `focus.finished` → `backend/app/Http/Controllers/Web/ShowFinishedController.php`; API `GET /api/v1/sessions/{session}/finished` → `backend/app/Http/Controllers/Api/V1/ShowFinishedController.php`. Both owned, both 404 unless the outcome is `Completed`.
  - Web `CompleteStepController`, `SkipStepController`, `ReportStuckController`: a `Completed` outcome redirects to `focus.finished`.
  - New `backend/resources/js/pages/finished.tsx`: `OneThing` "{title} is handled."; the lines; the drawn check (the one orchestrated moment); when `next` exists, "Next, if you want:" its title, Start (filled) and "Leave it there" (outline, same size, to home); a quiet "Does this come back?" revealing "Every [7] days" and Repeat (the existing `intentions.recurrence` route); `recurrenceLine` when it already repeats.
  - Remove the "Just finished" band: `JustFinishedData`, `HomeData::$justFinished`, `BuildHome::justFinished`, `homeBands.justFinished`, `JustFinished` in `home.tsx` and in `mobile/app/index.tsx`, and their tests. The recurrence assertions in `backend/tests/Feature/Intentions/RecurringIntentionTest.php` that read `home.justFinished` move to `FinishedTest.php` against `recurrenceEveryDays`.
  - New `backend/tests/Feature/Execution/FinishedTest.php`: the last Done redirects there; each line appears only when true; the API answers `FinishedData`; another person's session and a running session are refused.
  - `JourneyTest.php` ends on "is handled." and "Leave it there".
  - Check: `npm run artisan -- test --compact tests/Feature/Execution tests/Feature/Home` and `npm run test:browser` pass.
- [ ] **4.5 Stuck answers back, and does not bounce.** Invoke skills: `laravel-actions`.
  - `StuckReason::acknowledgement(string $intentionTitle): ?string`: TooBig and DontKnowWhatToDo "Let's make it smaller. Forget the rest of {title} for now."; NeedSomething and NotEnoughInformation "That one can wait until you have what it needs. Here is something you can do now."; NotHere "That one waits until you are there. Here is one you can do here."; SomethingElse "Noted. This one is still here when you want it."; Tired and DontWantTo null.
  - `ExecutionStateData` gains `?string $notice`, set when the session's latest event is `Stuck`. Web and mobile render it as `role="status"` above the step.
  - A stuck answer that stops the session: web flashes the toast "Stopped for now. It will be here later." and redirects home; mobile routes home with the same line.
  - Something else: the dialog reveals a textarea ("What's in the way? You can leave this empty.") and "Tell it", which posts `note`.
  - `AdvanceSession::handle` also passes over steps this session reported stuck with a reason whose `resolution()` is `StuckResolution::Split` (`$session->events()->where('type', ExecutionEventType::Stuck)->whereIn('payload->reason', [...])->pluck('step_id')`), combined with the caller's `$passOver` in the same `reject()`; such steps still count as left. A split's new steps have new ids and are offered.
  - Tests in `backend/tests/Feature/Execution/StuckTest.php`: each acknowledgement; the notice clears after the next Done; a too-big step is not offered again in the same session after its sibling is done, and the session ends `Continued` when only it is left; a note is stored.
  - Check: `npm run artisan -- test --compact tests/Feature/Execution` passes.
- [ ] **4.6 Home stops asking for configuration.** Invoke skills: `laravel-actions`, `inertia-react-development`.
  - `ComingUpData` gains `string $localAt` (`Y-m-d\TH:i` in the person's zone).
  - New `backend/app/Actions/Intentions/CorrectDeadline.php`: `handle(Intention $intention, ?CarbonImmutable $deadlineAt): Intention`; sets `deadline_at` (null clears it) and `deadline_confirmed_at = now()` when not null.
  - New `backend/app/Http/Requests/CorrectDeadlineRequest.php`: `deadline_at` nullable `date_format:Y-m-d\TH:i`; `deadlineAt(User $user): ?CarbonImmutable` reads it in the person's zone.
  - Web `POST intentions/{intention}/deadline/correct` named `intentions.deadline.correct` → `backend/app/Http/Controllers/Web/CorrectDeadlineController.php`; API `PATCH /api/v1/intentions/{intention}/deadline/correct` (the bare `deadline` path is the existing confirm) → `backend/app/Http/Controllers/Api/V1/CorrectDeadlineController.php`, returning `IntentionData`.
  - Web `GET appointments/{kind}/{id}` named `appointments.show` → `backend/app/Http/Controllers/Web/ShowAppointmentController.php` (the API controller's lookup) → new `backend/resources/js/pages/appointment.tsx`: the title and when; for an inferred deadline, "Read from what you wrote.", That's right, a `datetime-local` prefilled from `localAt` with Save, and "There's no deadline"; the backwards-plan editor; for a calendar event, the "Remind me after" form; back to home.
  - Home's "What's coming up": "{title}, {inWords}." and "Leave at {clock}." when planned; "Plan for it" links to the appointment page; an inferred deadline shows "Read from what you wrote." with That's right and "Change" (to the page). The future-reminder form and the plan editor leave home.
  - New `backend/tests/Feature/Intentions/CorrectDeadlineTest.php`: set, clear, zone, ownership.
  - Check: `npm run artisan -- test --compact tests/Feature/Intentions tests/Feature/Home` passes.
- [ ] **4.7 Say it plainly.** Invoke skills: `next-action-resolver`.
  - Remove every `.toLowerCase()` applied to titles or server lines in `backend/resources/js` and `mobile/`.
  - `BuildExecutionState` progress: "{done} of {n} steps done."; the sitting line only when `steps_completed` differs from the intention's done count; the today line reads "{t} steps finished today." and only when above the sitting count.
  - `PrerequisiteFirst::decides` → "It comes first in “{title}”." `HasDeadline::qualifies` and `DeadlineWithinReach::decides`, when `$candidate->intention->deadline_inferred`: "Going by what you wrote, the deadline is {d}." / "…was {d}." / "Going by what you wrote, the deadline is {d} and what is left only just fits."
  - `packages/shared/src/copy.ts`: `estimateLine(seconds, nowMinute)` → "About 4 minutes, so done around 12:54 if you start now." or "Nobody has estimated this one."; `suggestedLabel = 'suggested step'`, rendered as a bordered pill beside Start; `partOfLine(title)` → "Part of {title}." Web and mobile both use them, so mobile shows "suggested step" too. Delete `stepMeta`, `rightNowMeta` and `smallestStepMeta` from `packages/shared/src/copy.ts`, and fold `partWayLine` into `returnCopy` as `partWay`; `restCountLine` becomes sentences ("Nothing else is waiting.", "{n} other thing(s), none of which you need to think about.").
  - No middle-dot joins in UI copy: waiting-for rows read "{subject}: {note}", coming-up reads as a sentence.
  - Tests: update strings in `backend/tests/Feature/NextAction/ResolverTest.php`, `HomeTest.php`, `backend/tests/Feature/Intentions/ClarifyIntentionTest.php`, `SessionTest.php`; add a scenario for the inferred-deadline wording.
  - Check: `npm run test` passes.
- [ ] **4.8 Accessibility pass.** Invoke skills: `chrome-devtools-mcp:a11y-debugging`.
  - Each page has one `h1` (the one thing, or the page's question) before any `h2`; bands are `h2`.
  - After the step changes, focus moves to the `h1`; the notice is `role="status"`.
  - The day strip's drawing is `aria-hidden`; `railSummary()` is its one visually hidden sentence.
  - Every tap target is at least 2.75rem; the overwhelmed page, Esc and Enter work without a mouse.
  - Check: Lighthouse accessibility 100 on `/home`, `/focus`, `/overwhelmed`, `/focus/{session}/finished` in both themes; the snapshot shows the heading order; `DesignTokensTest.php` passes.
- [ ] **4.9 The overwhelmed screen is the calmest one.** Invoke skills: `frontend-design`.
  - `packages/shared/src/copy.ts` `overwhelmedCopy` gains `lines`: "You've got a lot going on.", "Ignore everything else for now.", "Let's do one thing."
  - `backend/resources/js/pages/overwhelmed.tsx`: those three lines, then the step, its why, Start; the rest line; "Back to home" (Esc). More space, no motion.
  - Header "I'm overwhelmed" is visible ink text, body size, key `o`; the footer link on home goes.
  - Check: screenshot at 1440 and 390.
- [ ] **4.10 No stale tap lands.** Invoke skills: `laravel-actions`, `laravel-data`, `expo-react-native`, `pest-testing`.
  - `ExecutionSession::assertSeen(string $eventId): void` throws `InvalidSessionTransition("Session {$this->id} has moved on since event {$eventId}.")` when the session's latest event id (ulid, `events()->latest('id')->value('id')`) differs.
  - `PauseSession`, `ResumeSession`, `RecordDistraction`, `StopSession` gain `?string $seenEventId = null`, checked first inside their `transition()`; null is only for internal callers (`ReportStuck`, `StartSession` stopping a session), never a controller.
  - New `backend/app/Http/Requests/SessionControlRequest.php`: `seen_event_id` required string; `seenEventId(): string`. The web `PauseFocusController`, `ResumeFocusController`, `RecordDistractionController`, `StopFocusController` and API `PauseSessionController`, `ResumeSessionController`, `RecordDistractionController`, `StopSessionController` take it and pass it through.
  - `PromoteStepToCommitment::handle(ExecutionSession $session, string $stepId)`; both `PromoteCurrentStepToCommitmentController`s take `StepControlRequest`. `currentStepOrFail(string $expectedStepId)` loses its default.
  - `backend/resources/js/components/one-tap-form.tsx`: the `stepId` prop becomes `fields: Record<string, string>`, rendered as hidden inputs; every caller passes `{ step_id }` or `{ seen_event_id }`.
  - `ExecutionStateData` gains `string $seenEventId`. Web `focus.tsx` posts it as a hidden `seen_event_id`. Home's Continue for a returning or paused session becomes a `OneTapForm` posting `resume` with it (today it only links to focus, so focus says "Welcome back." a second time), and `ResumeFocusController` redirects to `focus` rather than `back()`; the promise form posts `step_id`. Mobile `mobile/src/api/endpoints.ts` sends `seen_event_id` for pause, resume, distracted and stop, and `step_id` for commitment.
  - Tests in `SessionTest.php` and `ExecutionEndpointTest.php`: a second Pause, Stop and I got distracted with the old event id is refused (API 409, web redirect, nothing recorded); a missing `seen_event_id` is 422; `ReportStuck` with a stop resolution still stops.
  - Check: `npm run artisan -- test --compact tests/Feature/Execution tests/Feature/Commitments` and `npm run typecheck` pass.
- [ ] **4.11 Finish.** Invoke skills: `phpstan-larastan`, `finish-branch`.

## Phase 5 — mobile (`feature/mobile-parity`)

- [ ] **5.1 Dependencies, fonts and tokens.** Invoke skills: `expo-react-native`, `sail-and-root-scripts`.
  - `npm install --workspace mobile expo-haptics@~57.0.3 expo-file-system@~57.0.7 expo-font@~57.0.4 @expo-google-fonts/atkinson-hyperlegible-next@^0.4.1 @expo-google-fonts/atkinson-hyperlegible-mono@^0.4.1` at the root, then `npm exec --workspace mobile -- expo install --check`.
  - `mobile/app/_layout.tsx`: `useFonts` for both families (400, 600, 700; mono 400, 500); `SplashScreen.preventAutoHideAsync()` until loaded; `StatusBar style="auto"`.
  - `mobile/src/theme.ts`: `useTheme()` returns colours from `lightColors`/`darkColors` by `useColorScheme()`, plus `typeScale`, `radius`, `space`; `makeStyles(fn)` returns a hook that memoises `StyleSheet.create(fn(theme))`. Every screen and component moves to it; `TOUCH_TARGET` stays 56.
  - `mobile/src/components/screen.tsx`: `Band` loses its box (no surface, border or radius); the label is sentence case, body size 600, muted; `Screen` gains a `footer` rendered below the scroll view inside the safe area.
  - Check: `npm run typecheck`; `npm run mobile`, then the app renders in both system themes.
- [ ] **5.2 Home.** Invoke skills: `expo-react-native`, `expo-router`, `frontend-design`.
  - New `mobile/src/components/day-strip.tsx`: the horizontal strip from the identity, with an accessibility label from `railSummary()`.
  - `mobile/app/index.tsx`: the strip; the one thing, `estimateLine`, the suggested pill, `partOfLine`, Start; "Why this one?"; the sorted band (new `mobile/src/components/sorted-band.tsx`, same behaviour as 3.4); "What's coming up" with "Plan for it"; "Needs you"; the sorting line; the rest line.
  - New `mobile/src/components/bottom-bar.tsx` as the `Screen` footer: Capture (filled, wide) and I'm overwhelmed. A "Settings" text button top right; Sign out moves to `mobile/app/settings.tsx`.
  - Check: `npm run typecheck`; on a device, Capture and Start are reachable with one thumb without scrolling.
- [ ] **5.3 Focus.** Invoke skills: `expo-react-native`.
  - `mobile/app/focus.tsx`: controls pinned in the footer as the two rows of three from 4.3, with hints; `Haptics.impactAsync(Haptics.ImpactFeedbackStyle.Light)` on every one of the six, the same for each; the notice; returning and paused views from 1.5; the stuck modal's something-else note; `step_id` or `seen_event_id` on every control, as 4.10 set; a `Completed` outcome replaces the route with `/finished/{id}`.
  - New `mobile/app/finished/[session].tsx`: the same content as the web closing screen, `Haptics.notificationAsync(Haptics.NotificationFeedbackType.Success)` once on mount; `api.finished()` in `mobile/src/api/endpoints.ts`.
  - Check: `npm run typecheck`; on a device, walk start → done → distracted → welcome back → finish.
- [ ] **5.4 Capture, and nothing but capture.** Invoke skills: `expo-react-native`, `expo-data-fetching`.
  - `mobile/app/capture.tsx`: speech `lang` from `Intl.DateTimeFormat().resolvedOptions().locale`; the listening label "Listening. Tap to stop."; a failed save shows `writeProblem`.
  - New `mobile/src/capture/pending-captures.ts` on `expo-file-system`'s `File` and `Paths.document` (`pending-captures.json`): `queuePending(body, source)`, `flushPending(token)`. A network failure (not an `ApiError`) queues the capture and shows "Saved on this phone. It goes through when you are back online."; home flushes on load and when `AppState` becomes active.
  - Check: `npm run typecheck`; on a device in airplane mode, a capture queues, then lands once online.
- [ ] **5.5 Every write answers.** Invoke skills: `expo-data-fetching`.
  - New `mobile/src/api/use-write.ts`: `useWrite(reload: () => void)` returns `{ run(write: () => Promise<unknown>): Promise<void>, busy: boolean, problem: string | null }`; a ref drops a second tap in the same frame, `busy` disables the controls, an `ApiError` with status 409 calls `reload()`, and every other error sets `problem` from `writeProblem` and never rethrows.
  - Every write in `mobile/app/**` goes through `useWrite`, `mobile/app/focus.tsx`'s `send()` included (its ref and busy flag move into the hook); `problem` is shown in place.
  - `mobile/app/overwhelmed.tsx` uses `overwhelmedCopy.lines`; `mobile/app/appointment/[kind]/[id].tsx` gains confirm and correct for an inferred deadline (the API from 4.6), and "Plan for it" is its title.
  - Check: `npm run typecheck`; on a device with the server stopped, every button says why nothing happened.
- [ ] **5.6 Finish.** Invoke skills: `finish-branch`.

## Closing

- [ ] **6.1 Audit again.** Invoke skills: `chrome-devtools-mcp:a11y-debugging`, `frontend-design`. Re-walk the audit's path live: `/`, `/login`, `/home`, capture, start, distracted, stuck "This is too much", done to finished, `/overwhelmed`, in both themes at 1440 and 390. Every audit finding is gone, Lighthouse accessibility is 100, and nothing scrolls sideways.
- [ ] **6.2 Plan state.** Set State to `done, <date>` here and in the spine row.
- [ ] **6.3 Resume and archive.** Invoke skills: `update-resume`, `slice-workflow`. This plan has no Open section, so it moves to `archive/` by the checklist in `slice-workflow`.
