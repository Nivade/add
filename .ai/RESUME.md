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

**Slice 8, phase 2.** Phase 1 of [`08-mvp-real.md`](plans/slices/08-mvp-real.md)
is built and has not yet read a real person's feed. Phase 2 of the product is now slice 9 and stays a paragraph until 8 is done.
The hardening pass over slices 1–6 is finished — [`hardening.md`](plans/hardening.md)
carries what it settled and what it deliberately left open.

The mobile client exists but has never run against a device or a simulator: the
bundle builds and `expo-doctor` passes, and that is the whole of what has been
checked. There is no EAS project id in `mobile/app.json`, so
`getExpoPushTokenAsync` has nothing to ask for a token with, and no push has been
sent end to end. There are no native folders — `ios/` and `android/` are
generated, and the speech-recognition and secure-store modules need a dev build
rather than Expo Go.

What is deliberately unbuilt, so it is not mistaken for a gap: named preparation
items ("your insurance card"), which need somewhere for objects to live; camera
capture, which waits for documents; location triggers, which need a consent
surface; and registration and password reset, which stay on the web. Reminders
are scheduled every minute in `routes/console.php` and dispatch one job per
person, so both a scheduler and a queue worker have to be running to see one
outside a test.

The home slice's open question — what "Needs attention" offers to resolve an
intention nobody could name — is slice 8's phase 2.

## Learning the state

Do not trust a sentence here for it. `npm run test`, `npm run stan` and
`npm run lint` answer it in seconds, and `git log` says what moved.
