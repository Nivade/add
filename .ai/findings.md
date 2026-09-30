# Findings

Noticed in passing, not this task's job. Triage clears it; nothing here is a rule.

- 2026-09-30: a capture whose `ConvertCaptureToIntention` job fails (`FailOn(AiUnavailable)`) keeps `processed_at` null, so home shows the sorting line and polls a full `BuildHome` every 3s for up to 24h (`backend/app/Actions/Home/BuildHome.php` `sortingCount`) — bug — phase 3's `SortCapture` replaces the job; mark the failure there
- 2026-09-30: home and mobile meta lines mix sentences with main's lower-cased fragments (`rightNowMeta`, `restCountLine`, `stepMeta` in `packages/shared/src/copy.ts`) — tech-debt — ux-overhaul step 4.7 removes every lower-casing
- 2026-09-30: `partWayLine` sits apart from `returnCopy`, which holds the rest of the returning copy (`packages/shared/src/copy.ts`) — tech-debt — pure churn inside phase 1
- 2026-09-30: the `returning ? … : …` headline and meta ternaries repeat across web and mobile focus and home — tech-debt — phases 4 and 5 redraw those screens
- 2026-09-30: `PromoteStepToCommitment` calls `currentStepOrFail()` without a step id, the only reason the parameter stays nullable (`backend/app/Actions/Commitments/PromoteStepToCommitment.php`) — risk — the plan's step-id guard covered Done, Skip and Stuck only
- 2026-09-30: `Commitment::isStandalone()` and the `standalone` scope spell the same predicate twice (`backend/app/Models/Commitment.php`) — tech-debt — the usual Laravel pairing of a query and an instance check
- 2026-09-30: web "I'm stuck" posts through `router.post` rather than `OneTapForm` (`backend/resources/js/pages/focus.tsx`) — tech-debt — the dialog closes on the first tap and `step_id` refuses a stale second
- 2026-09-30: `OneTapForm` takes a domain `stepId` prop rather than a caller-supplied hidden input (`backend/resources/js/components/one-tap-form.tsx`) — tech-debt — only focus forms use it
- 2026-09-30: mobile focus `send()` rethrows every non-409 error into a `void` call, an unhandled rejection (`mobile/app/focus.tsx`) — bug — ux-overhaul step 5.5 wraps every mobile write
- 2026-09-30: `returnCopy.stepsDone` from ux-overhaul 1.5 lives inside `returnCopy.meta` rather than as its own key — question — decided in the phase 1 review; the plan text still names the key
- 2026-09-30: a returning session says "Welcome back" on home, then again on focus after Continue — question — faithful to 1.5; worth settling before phase 4 lays out focus
- 2026-09-30: `api/*` JSON is decided per renderer through the `$wantsJson` closure in `backend/bootstrap/app.php`; a new renderer or controller that calls `expectsJson()` brings back the redirect or 500 for a client without an Accept header — risk — an api-group middleware setting `Accept: application/json` would fix it at the entry point, but an unmatched api route never reaches middleware, so `shouldRenderJsonWhen` stays
- 2026-09-30: `step_id` guards only Done, Skip and Stuck; Continue, Pause, I got distracted and Stop post it but nothing reads it, so a stale tap on those still lands — risk — a session-wide stale-state token checked in `ResolvesOwned::owned` would guard every transition; a new design, not a phase 1 fix
- 2026-09-30: only mobile focus guards two taps in one frame with a ref; capture, paste, entry, settings, appointment and home still let both through `saving` (`mobile/app/focus.tsx` `send`) — todo — ux-overhaul step 5.5 should build one mobile write hook owning the ref, busy flag and 409 reload
