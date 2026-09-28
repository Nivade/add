# Findings

Noticed in passing, not this task's job. Triage clears it; nothing here is a rule.

- 2026-09-28: mobile/src/components/resource-state.tsx — the Pending retry screen and the StaleNote line on focus, overwhelmed, commitments, settings and the appointment screen were typechecked only, never run on the emulator — todo — the change merged in #18 without a running emulator.
- 2026-09-28: .ai/skills/finish-branch/SKILL.md step 6 runs no `vp check`, which CI's `composer ci:check` does, so JSON and JS formatting under backend/ first fails in CI — tech-debt — a skill change, not the triage's job.
