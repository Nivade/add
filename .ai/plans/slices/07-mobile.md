# Slice 7 — mobile

**State:** done, 2026-09-23 · [the slice table](../executive-function-os.md#slices)

*Spec: [`product-spec.md`](../product-spec.md) §3, §30, and §31's large touch
targets. Rules: `toolchain.md` (the two frontends share types, never components),
`api-and-data.md`, `product-invariants.md`.*

The backend is finished for the journey this slice carries. What is missing is a
second client, a way for it to authenticate, and the three endpoints the web got
as Inertia pages instead.

## Expo joins the workspace here

`mobile` is absent from the root `workspaces` list on purpose: npm fails the
install on a glob matching no `package.json`. The entry and the scaffold land in
one change, and `npm install` runs from the root afterwards, never inside
`mobile/`.

The root `mobile` script already exists and points at a workspace that does not.
It starts working in this change rather than being added by it.

Expo is a managed workflow. `packages/shared` stays dependency-free, so nothing
about React Native reaches it — it gains no `react-native` import in this slice
or any other.

## Sanctum tokens, not the session

Fortify authenticates the web with a session cookie. React Native gets personal
access tokens, which means `User` gains `HasApiTokens` — it does not have it
today, and the `auth:sanctum` group has been served by the session guard alone.

A token endpoint sits outside that group, rate limited, and takes a device name
so a person can revoke one device. Signing out revokes the presented token
rather than all of them. The token is stored in the device keychain, never in
plain storage.

Registration and password reset stay on the web for this slice. A mobile client
that can sign in and capture is the useful product; a second auth surface is
scope, not need.

## Three endpoints the web never needed

The JSON API was built alongside the Inertia pages, so where a page rendered
props directly there is no endpoint:

- `GET /api/v1/home` — the same `HomeData` the Inertia page receives.
- `POST /api/v1/reminders/{notification}/dismiss` — the web's dismiss, over JSON.
- `POST /api/v1/devices` — an Expo push token against the signed-in user.

Same Data classes as the web, so the wire shape cannot drift. Nothing else about
the surface changes: mobile is a client of the API that exists.

## Push is this slice's, not slice 6's

Slice 6 sent one reminder per appointment on the `database` channel and said
push waits for a device to send it to. The device arrives here.

The channel is added to the existing notification; the lines are not rewritten.
§23's four questions are already answered by `ReminderLines`, and a push payload
that shortens them into "dentist tomorrow" is the bug that section names. A
notification too long for a lock screen is truncated by the platform, which is
better than composing a second, thinner set of lines.

The payload carries no intention text beyond what the notification already says,
and encryption waits for ingestion, where the threat model is written.

## Touch-first, which is not the web layout

§30 is explicit that this is not a port. What that decides here:

- Capture is reachable from cold start in one thumb-reach, and it is the screen
  the app opens on when there is nothing running.
- Voice capture goes through the same `POST /api/v1/captures` as text — the
  transcript is text, and the AI layer already parses text. Camera capture
  stores nothing until documents exist, so it waits for phase 2.
- Execution mode's six controls are large targets, not a toolbar.
- Deep links resolve a reminder to the appointment it is about.

Every screen is written twice, web and native. That is the cost `toolchain.md`
already accepted, and a shared component layer is the rewrite it warns about.

## Done when

- A cold start reaches a saved capture in seconds, one-handed.
- A token signs in, survives a restart, and one device can be revoked alone.
- A reminder arrives as a push that answers §23's four questions, and opening it
  lands on the appointment.
- The web and the device render the same home from the same Data classes.

## Open

Location triggers (§30) need an explicit consent surface and a place for "this
place" to live, which is the same gap slice 6 left on travel time. Lock-screen
actions need the session state machine to accept a decision taken outside the
app, which nothing has asked for yet. Camera capture waits for documents.
