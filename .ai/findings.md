# Findings

Noticed in passing, not this task's job. Triage clears it; nothing here is a rule.

- 2026-09-23: backend/app/Support/Ai/Providers/OpenAiProvider.php:58 chains the provider exception and copies 500 chars of its message, so a laravel/ai error echoing the request body writes the person's own sentences into the log — risk — waiting on the subscription work that reshapes the provider path.
- 2026-09-23: backend/app/Support/Ai/Providers/OpenAiProvider.php:74 rate-limits on one global key, so one person can lock every other person out of the model — risk — per-user limits land with subscription tiers.
- 2026-09-23: mobile/app/appointment/[kind]/[id].tsx:27 resolves a deep-linked appointment by matching it against HomeData.comingUp, so a reminder whose appointment is no longer the next one opens on "that one is not what is next any more" — bug — the fix is a fetch-by-id endpoint, which is a wider change than this cleanup pass.
- 2026-09-23: backend/app/Support/Calendar/Sources/IcsCalendarSource.php fetches any https URL a person pastes, internal hosts included (SSRF) — risk — nothing is deployed; needs a host allowlist or private-range block before the first real user.
- 2026-09-24: mobile/app/index.tsx:81 shows "The app could not reach the server." for any failed home load, a 401 from a stale token included, and never signs out, so the only way back to sign-in is clearing app data — bug — mobile auth handling is not slice 8 phase 2's job.
- 2026-09-24: the live model answered §7's passport with the parse prompt's own example question, word for word — question — the phase 4 corpus should score whether clarifying questions are specific to the capture or copied from the prompt.
- 2026-09-24: backend/vite.traefik.js serves HMR from vite.<slug>.<domain>, which a mkcert *.nvade.dev certificate does not cover, so `npm run web` loads no assets in a browser — tech-debt — local certificate setup, not code.
- 2026-09-24: backend/app/Actions/Intentions/ClarifyIntention.php stores a clarification answer as text only, so a date in it ("Lisbon in March") never becomes an inferred, confirmable `deadline_at` and never reaches ranking — out-of-scope — needs deadline extraction on the answer, which phase 2 did not ask for.
