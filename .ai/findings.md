# Findings

Noticed in passing, not this task's job. Triage clears it; nothing here is a rule.

- 2026-09-23: backend/app/Support/Ai/Providers/OpenAiProvider.php:58 chains the provider exception and copies 500 chars of its message, so a laravel/ai error echoing the request body writes the person's own sentences into the log — risk — waiting on the subscription work that reshapes the provider path.
- 2026-09-23: backend/app/Support/Ai/Providers/OpenAiProvider.php:74 rate-limits on one global key, so one person can lock every other person out of the model — risk — per-user limits land with subscription tiers.
- 2026-09-23: mobile/app/appointment/[kind]/[id].tsx:27 resolves a deep-linked appointment by matching it against HomeData.comingUp, so a reminder whose appointment is no longer the next one opens on "that one is not what is next any more" — bug — the fix is a fetch-by-id endpoint, which is a wider change than this cleanup pass.
