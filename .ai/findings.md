# Findings

Noticed in passing, not this task's job. Triage clears it; nothing here is a rule.

- 2026-09-28: mobile/src/components/resource-state.tsx — the Pending retry screen and the StaleNote line, on every `mobile/app` screen that loads through `useResource`, were typechecked only, never run on the emulator — todo — no emulator was running.
