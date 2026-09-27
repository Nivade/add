# Resume here

Where the build stopped, and nothing else. Decisions belong in `.ai/rules/`,
design belongs in `.ai/plans/slices/`, and what changed belongs in `git log` —
this file restating any of them is how it goes stale.

Read first: `CLAUDE.md`, `.ai/rules/overview.md`, then
`.ai/plans/executive-function-os.md` for the slice table, which carries each
slice's state. Read the file in `.ai/plans/slices/` for the slice being built,
not all of them.

The product spec is `.ai/plans/product-spec.md`, the prompt that opened the
repo, verbatim. It supersedes `~/repos/private/first-move` — that repo
contributed conventions only, not scope or product decisions.

## Stopped at

**Slice 9 is done** — see [`09-phase-2.md`](plans/slices/09-phase-2.md) for
what each phase built. Phase 5 (solo body doubling) closed with no code:
execution mode (slice 4) already answers §17's solo requirement. Slice 10
(Phase 3) is recorded only, not planned — it carries §17's Friend mode, the
`Context` model, and §19's real ingestion sources (email, documents, receipts,
bank/gov correspondence), none of which any built slice claims. The hardening
pass over slices 1–6 is finished — [`hardening.md`](plans/hardening.md) carries
what it settled and what it deliberately left open.

What is deliberately unbuilt, so it is not mistaken for a gap: named preparation
items ("your insurance card"), which need somewhere for objects to live; camera
capture, which waits for documents; location triggers, which need a consent
surface; registration and password reset, which stay on the web; and §36's
success metrics, which have no instrumentation anywhere yet. Reminders are
scheduled every minute in `routes/console.php` and dispatch one job per
person, so both a scheduler and a queue worker have to be running to see one
outside a test. The same is now true of `future-reminders:dispatch` and
`intentions:recur`.

## Learning the state

Do not trust a sentence here for it. `npm run test`, `npm run stan` and
`npm run lint` answer it in seconds, and `git log` says what moved.
