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

**Slice 5, home.** Nothing of it is built: `resources/js/pages/` still holds the
starter kit's `dashboard.tsx` and `welcome.tsx`, no page reads
`GET /api/v1/next-action` or `GET /api/v1/sessions/current`, and no Inertia
controller renders a band. The whole slice is web, over an API that is finished.

Next, in the slice plan's order: the four bands as one page, then Start wired to
the session endpoints, then the journey end to end in a browser, with
accessibility part of it rather than after it. The slice's open question — what
"Needs attention" offers for an intention nobody could name — is unanswered.

Slice 6 is scoped in `.ai/plans/slices/`. Slice 7, mobile, is the point where
Expo joins the root workspace list and not before.

## Learning the state

Do not trust a sentence here for it. `npm run test`, `npm run stan` and
`npm run lint` answer it in seconds, and `git log` says what moved.
