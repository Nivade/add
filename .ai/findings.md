# Findings

Noticed in passing, not this task's job. Triage clears it; nothing here is a rule.

- 2026-09-24: the live model answered §7's passport with the parse prompt's own example question, word for word — question — the phase 4 corpus should score whether clarifying questions are specific to the capture or copied from the prompt.
- 2026-09-24: backend/vite.traefik.js serves HMR from vite.<slug>.<domain>, which a mkcert *.nvade.dev certificate does not cover, so `npm run web` loads no assets in a browser — tech-debt — local certificate setup, not code.
