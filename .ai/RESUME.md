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

**Slice 10 is being built** on `feature/slice-10-context`.
[`10-context.md`](plans/slices/10-context.md) ticks its steps as they land;
resume at the first unticked one. Slices 11–14 are recorded in the spine and
have no files.

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
