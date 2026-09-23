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

**Slice 7, mobile.** Designed in [`07-mobile.md`](plans/slices/07-mobile.md),
nothing of it built. Slice 6 is finished: the overwhelm
entry point, backwards planning over both kinds of appointment, a read-only
calendar behind `CalendarSource`, and one reminder per appointment that answers
§23's four questions and lands on home as a band.

The hardening pass over slices 1–6 is finished too — [`hardening.md`](plans/hardening.md)
carries what it settled and what it deliberately left open.

What slice 6 deliberately left unbuilt, so it is not mistaken for a gap: named
preparation items ("your insurance card"), which need somewhere for objects to
live, and push, which arrives with the mobile client. Reminders are scheduled
every minute in `routes/console.php` and dispatch one job per person, so both a
scheduler and a queue worker have to be running to see one outside a test.

The home slice left one question open: what "Needs attention" offers to resolve
an intention nobody could name. Nothing on that screen acts on it yet.

## Learning the state

Do not trust a sentence here for it. `npm run test`, `npm run stan` and
`npm run lint` answer it in seconds, and `git log` says what moved.
