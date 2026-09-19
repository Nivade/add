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

**Slice 3, next action.** Nothing is built: there is no `NextActionResolver`, no
`ResolutionContext`, no comparators, and `GET /api/v1/next-action` does not
exist. `routes/api.php` carries the capture endpoint only.

Next, in the slice plan's order: `ResolutionContext`, then the comparator chain,
then the composed `why`, then the endpoint.

Slices 4–6 are scoped in `.ai/plans/slices/`. Slice 7, mobile, is the point
where Expo joins the root workspace list and not before.

## Learning the state

Do not trust a sentence here for it. `npm run test`, `npm run stan` and
`npm run lint` answer it in seconds, and `git log` says what moved.
