# Findings

Noticed in passing, not this task's job. Triage clears it; nothing here is a rule.

- 2026-09-27: the live model answered the passport capture with the parse prompt's own example question — todo — run `npm run artisan -- ai:eval:capture` against a live key; its baseline says whether questions for other captures are copied too, and decides whether the prompt changes.
- 2026-09-24: backend/vite.traefik.js serves HMR from vite.<slug>.<domain>, which a mkcert *.nvade.dev certificate does not cover, so `npm run web` loads no assets in a browser — tech-debt — local certificate setup, not code.
