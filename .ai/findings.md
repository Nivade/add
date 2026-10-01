# Findings

Noticed in passing, not this task's job. Triage clears it; nothing here is a rule.

- 2026-09-30: the `returning ? … : …` headline and meta ternaries repeat across web and mobile focus and home — tech-debt — phases 4 and 5 redraw those screens
- 2026-09-30: `Commitment::isStandalone()` and the `standalone` scope spell the same predicate twice (`backend/app/Models/Commitment.php`) — tech-debt — the usual Laravel pairing of a query and an instance check
- 2026-09-30: web "I'm stuck" posts through `router.post` rather than `OneTapForm` (`backend/resources/js/pages/focus.tsx`) — tech-debt — the dialog closes on the first tap and `step_id` refuses a stale second
- 2026-09-30: `api/*` JSON is decided per renderer through the `$wantsJson` closure in `backend/bootstrap/app.php`; a new renderer or controller that calls `expectsJson()` brings back the redirect or 500 for a client without an Accept header — risk — an api-group middleware setting `Accept: application/json` would fix it at the entry point, but an unmatched api route never reaches middleware, so `shouldRenderJsonWhen` stays
- 2026-09-30: welcome's `SampleHome` draws home by hand (a `text-2xl` headline, its own "Why this one?" line) instead of using `OneThing`/`Band` (`backend/resources/js/pages/welcome.tsx`) — tech-debt — both render headings, which would add a second h1 and a stray h2 to the welcome outline; a heading-less variant is the fix if the sample drifts from home
- 2026-10-01: a sorted waiting-for still being read back can also show in Needs you once it is four days stale; only promises are filtered (`backend/app/Actions/Home/BuildNeedsAttention.php`) — bug — a read-back left unanswered that long is the trigger
- 2026-10-01: `captures.intention_id` mirrors `routed_id` for a thought, so sorting and changing a kind write both (`backend/app/Actions/Captures/SortCapture.php`) — tech-debt — `CaptureData::$intentionId` and the `intention` relation still read it
- 2026-10-01: `SortCapture::handle(?CaptureKind $chosen)` switches classify, provenance and `kind_confirmed_at` on one flag; a `RouteCapture` action taking kind and provenance would split it (`backend/app/Actions/Captures/SortCapture.php`) — tech-debt — the plan fixed this signature
- 2026-10-01: nothing over HTTP renders `AiConsentRequired`'s sentence any more, since the classify endpoint went; no test holds its `#[RespondsWith]` message (`backend/app/Exceptions/AiConsentRequired.php`) — risk — a future synchronous model call surfaces it untested
- 2026-10-01: home runs three count queries over `captures` per render, polled every 3s while sorting (`backend/app/Actions/Home/BuildHome.php`) — perf — one conditional aggregate would answer `sortingCount`, `unsortedCount` and the read-back total
