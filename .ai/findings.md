# Findings

Noticed in passing, not this task's job. Triage clears it; nothing here is a rule.

- 2026-09-19: StartableNow outranks EarliestPosition, so a later step of the same intention is offered before its prerequisite — backend/app/Support/NextAction/ChainedNextActionResolver.php:29 — bug — the chain order is a product decision in .ai/plans/slices/03-next-action.md; changing it needs a new decision, not a patch

