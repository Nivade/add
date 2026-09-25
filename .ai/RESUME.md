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

**Slice 8 is done** — see [`08-mvp-real.md`](plans/slices/08-mvp-real.md) for
what each phase built and its one open caveat (phase 1's live-feed round trip,
which needs a person's real ICS URL). Slice 9 is next: it is still only the
paragraph in [`executive-function-os.md`](plans/executive-function-os.md#slices)
(phase 2 — waiting-for, commitments, ingestion, future-self, body doubling) and
has no slice file of its own yet. The hardening pass over slices 1–6 is
finished — [`hardening.md`](plans/hardening.md) carries what it settled and
what it deliberately left open.

What is deliberately unbuilt, so it is not mistaken for a gap: named preparation
items ("your insurance card"), which need somewhere for objects to live; camera
capture, which waits for documents; location triggers, which need a consent
surface; and registration and password reset, which stay on the web. Reminders
are scheduled every minute in `routes/console.php` and dispatch one job per
person, so both a scheduler and a queue worker have to be running to see one
outside a test.

## Learning the state

Do not trust a sentence here for it. `npm run test`, `npm run stan` and
`npm run lint` answer it in seconds, and `git log` says what moved.
