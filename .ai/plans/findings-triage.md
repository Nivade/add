# Findings triage — clearing `.ai/findings.md`

**State:** designed, 2026-09-27 · [the slice table](executive-function-os.md#slices)

*Rules: `domain-model.md`, `support-and-concerns.md`, `api-and-data.md`, `ai-layer.md`,
`product-invariants.md`, `toolchain.md`, `testing.md`, `general.md`.
Skills: `note-finding` (triage), `laravel-actions`, `calendar-sync`, `laravel-security`,
`expo-react-native`, `expo-data-fetching`, `ai-layer-changes`, `next-action-resolver`, `tdd`,
`pest-testing`, `testing-best-practices`, `phpstan-larastan`, `sail-and-root-scripts`,
`split-to-prs`, `finish-branch`, `update-resume`.*

Every entry in [`findings.md`](../findings.md) was re-checked against the code on the date above
and still holds. Each phase below clears one entry: the change that fixes it deletes its line,
as `note-finding`'s triage asks. Phases are independent, one branch and one PR each, in the
order listed — risk to data first, then the traps a person can hit, then measurement, then local
tooling.

## Phase 1 — session transitions take the row lock

*Finding 2026-09-27. Skills: `laravel-actions`, `tdd`, `pest-testing`. Branch
`fix/session-transition-lock`.*

Wider than the finding says. `PauseSession`, `ResumeSession` and `RecordDistraction` check
`assertOpen()` on the unlocked model, and so do `CompleteStep`, `SkipCurrentStep` and
`ReportStuck` through `currentStepOrFail()` before their transaction opens. A double-tapped
"done" can mark the step done, bump `steps_completed` twice and advance twice; a pause racing a
stop writes `paused_at` and a `Paused` event on an ended session.

- Lift `LandSession`'s re-read into one model method, `ExecutionSession::lockOpen()`: re-reads
  its own row under `lockForUpdate()`, asserts open, returns the locked instance. Only valid
  inside a transaction — it throws a `LogicException` when `DB::transactionLevel()` is 0.
- Every session action opens `DB::transaction`, calls `lockOpen()` first, and does all its
  checks and writes on the locked instance: `paused_at`, `current_step_id` and the current step
  are read from it, never from the caller's copy. Nested calls (`CompleteStep` into
  `AdvanceSession` into `LandSession`) re-lock the same row inside the same transaction, which
  is free.
- `assertOpen()` becomes private to the model; a guard in `ConventionsTest` fails on any
  `->assertOpen()` or `->currentStepOrFail()` call from `app/Actions/Sessions`, so a new
  transition cannot skip the lock.

**Tests.** SQLite ignores `lockForUpdate`, so the race itself is not reproducible in the suite.
The deterministic proxy is the stale copy: load a session, end or pause it through a second
instance, then run each action on the first copy — it throws `InvalidSessionTransition` and
writes no `execution_events` row. One dataset over the seven actions, plus the double-complete
case asserting `steps_completed` moved once.

**Done when** no session action reads open/paused/current-step state off an unlocked model, and
the guard proves it.

## Phase 2 — the calendar feed only reaches public hosts

*Finding 2026-09-23. Skills: `calendar-sync`, `laravel-security`, `tdd`, `pest-testing`. Branch
`fix/calendar-feed-ssrf`.*

`IcsCalendarSource::fetch()` sends a GET to whatever URL a person saved, redirects followed, so
a feed of `https://169.254.169.254/...` or a hostname resolving to `10.x` reaches the internal
network from the queue worker. An allowlist of calendar providers was considered and declined:
self-hosted calendars (Nextcloud, Fastmail) are legitimate feeds, and the risk is the address
class, not the provider.

- A `PublicFeedHost` class in `app/Support/Calendar/`: resolves the host's A and AAAA records
  and refuses when there are none or any of them is private, reserved, loopback, link-local or
  an IPv4-mapped form of those (`FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE`, plus
  explicit checks for `::ffff:` and `fc00::/7`). IP-literal hosts go through the same test.
  Resolution sits behind a `HostResolver` bound in the container, the seam tests swap.
- `fetch()` stops letting the client redirect. It follows up to three redirects itself, checking
  each hop with `PublicFeedHost`, and pins every request to the address it checked with
  `CURLOPT_RESOLVE`, so a DNS answer that changes between the check and the connect (rebinding)
  cannot move the request. Only `https` is followed; `webcal` is already rewritten before this.
- A refused host throws `CalendarFeedUnreadable` with a message that carries no URL and no
  address — the URL is the credential. `SyncCalendar`'s empty-read rule already keeps the stored
  events.
- `CalendarFeedUpdateRequest` runs the same check, so a person pasting an unreachable address
  hears it at connect time: "That address is not a public calendar feed."

**Tests.** `Http::fake` with `preventStrayRequests()` and a fake `HostResolver`: public host
fetches; private, loopback, metadata, IPv6 ULA and mapped addresses refuse; a redirect from a
public host to a private one refuses at the hop and sends no request to it; more than three
hops refuse; the thrown message contains neither the URL nor the address; the form request
rejects a private host with the message above.

**Done when** no feed request leaves for an address outside public unicast space, at connect or
at sync.

## Phase 3 — a rejected token signs the phone out

*Finding 2026-09-24. Skills: `expo-react-native`, `expo-data-fetching`, `expo-router`. Branch
`fix/mobile-stale-token`.*

`request()` throws `ApiError(401)`, `useResource` flattens every throw into `failed`, and every
screen that loads through it shows "The app could not reach the server." with a retry that
fails forever. The home screen is where it was seen; `focus`, `overwhelmed`, `settings`,
`commitments` and the appointment screen have the same path.

- `mobile/src/api/client.ts` gains one module-level unauthorized handler, set by
  `SessionProvider` on mount. `request()` calls it on a 401 to any request that sent a token,
  then throws as before. Handling it once in the client covers every screen and every write,
  rather than a check per screen.
- The handler clears the stored token without calling `api.signOut` — the token is already dead,
  and calling the API with it would 401 back into the handler. `Gate` in `mobile/app/_layout.tsx`
  already routes a null token to sign-in.
- `useResource` keeps the error it caught, and screens word the two failures differently: no
  answer at all keeps "The app could not reach the server."; a server that answered with an error
  says so and offers the retry. No failure words from `product-invariants.md`.

**Verification.** The mobile workspace has no test runner, and adding one is a dependency change
this plan does not take. `npm run typecheck`, then by hand on the emulator: sign in, revoke the
token from web settings, bring the app forward — it lands on sign-in with no dead end.

**Done when** a revoked or expired token returns the phone to sign-in from any screen.

## Phase 4 — a date in a clarification answer becomes an inferred deadline

*Finding 2026-09-24. Skills: `laravel-actions`, `next-action-resolver`, `ai-layer-changes`,
`tdd`, `pest-testing`. Branch `feat/clarification-deadline`.*

`ClarifyIntention` stores the answer as text and dispatches decomposition; the date in "Lisbon
in March" never reaches `deadline_at`, so ranking and backwards planning never see it. Asking the
model for it was considered and declined: `ai-layer-changes` keeps anything deterministic out of
the AI layer, and `PhraseDeadlineExtractor` already reads dates out of capture text.

- `ClarifyIntention` runs the `DeadlineExtractor` over the answer, on the person's clock, inside
  the same conditional update. It writes `deadline_at` only when the intention has none — an
  answer never overwrites a date already captured or confirmed. `deadline_confirmed_at` stays
  null, so the date arrives inferred and the existing confirm surface asks about it.
- The extractor does not read month names today, so "in March" extracts nothing. Add
  `in|by|before <month>` — resolved to the start of that month's next occurrence, because the
  date is when the thing is needed by, and the first day is the only safe reading of a month.
  "end of <month>" resolves to its last day. This also applies to captures, which is wanted.
- Order stays as is: the deadline is written before `DecomposeIntention` is dispatched, so the
  decomposition prompt already sees it.

**Tests.** Extractor unit cases for month names: this year versus next, the current month, "end
of"; `ClarifyIntention` writes an inferred deadline from a dated answer, leaves an existing
deadline alone, and writes nothing from an undated one; one resolver scenario test showing the
clarified intention now ranks on its deadline.

**Done when** a dated clarification answer shows up as a confirmable deadline and moves the
ranking.

## Phase 5 — score whether a clarifying question is the capture's own

*Finding 2026-09-24, tagged `question`. Skills: `ai-layer-changes`, `testing-best-practices`.
Branch `feat/parse-capture-eval`.*

The live model answered the passport capture with the prompt's own example question, word for
word. That capture is the prompt's example, so the copy may simply be the right answer; one
data point cannot tell copying from correctness. Measure before touching the prompt.

- Extend `ai:eval` with a second corpus in `backend/storage/ai-eval`, for `parse_capture`:
  captures that need a question and are not any of the prompt's examples, plus the examples
  themselves as a control.
- Score two things per answer: whether a question was asked when one was expected, and whether
  it matches one of the prompt's example questions after normalising case and punctuation. The
  examples are read out of `Prompts` at run time rather than copied into the eval, so the check
  cannot drift from the prompt.
- Write the result into the baseline beside the decomposition scores.

The run needs a live key, so a person runs it. If non-example captures come back with copied
questions, the follow-up is a prompt edit with a `_VERSION` bump — its own change, judged
against this baseline. If they do not, the finding closes with no prompt change.

**Done when** the baseline answers whether questions are copied, and the finding line is
replaced by that answer or removed.

## Phase 6 — the local certificate covers this repo's subdomains

*Finding 2026-09-24. Skills: `sail-and-root-scripts`. No branch for the host step.*

Confirmed on the host: the SAN list behind `../traefik/certs/local.cert.pem` has `*.nvade.dev`
and `*.tabellio.nvade.dev` but no `*.add.nvade.dev`, and a single-level wildcard does not cover
`vite.add.nvade.dev` — nor `mailpit.add.nvade.dev`, which has the same problem unreported.
Renaming the hosts to one level (`add-vite.nvade.dev`) was considered and declined: tabellio
already solved it with a nested SAN, and the sibling repos share one pattern.

- Host step, done by the person because the certificate is shared by every sibling repo:
  regenerate with mkcert, keeping every existing SAN and adding `*.add.nvade.dev`, then restart
  Traefik.
- Repo step: add `*.add.nvade.dev` to the SAN list the `toolchain.md` section already documents
  for `*.nvade.debug`, and delete the finding.

**Done when** `npm run web` serves assets and HMR in a browser without a certificate warning.

## After the last phase

`findings.md` keeps its header and whatever was noted since. Mark this plan `done` with
`update-resume`; nothing here stays open, so it can be archived per `slice-workflow`.
