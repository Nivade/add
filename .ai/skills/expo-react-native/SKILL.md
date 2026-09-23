---
name: expo-react-native
description: Use for any work in `mobile/**` — scaffolding the Expo app, adding a screen, wiring Sanctum auth, calling the JSON API, or sharing code with the web client. Also use when deciding whether something belongs in `packages/shared`, and when tempted to reuse an Inertia React component on mobile. Trigger on "mobile", "expo", "react native", "the app", "native screen", "sanctum token", "push notification", "slice 7".
---

# Mobile (Expo / React Native)

Slice 7. Expo joins the root workspace list in the change that scaffolds it and
not before: npm fails the install on a workspace glob matching no
`package.json`, so `mobile` in the root `workspaces` array and `mobile/package.json`
land in the same commit.

## What is shared, and what is written twice

**Shared:** types, domain logic, time math, design tokens — everything in
`packages/shared`.

**Not shared:** components and routing. React Native renders native views and
Inertia renders DOM; Inertia is server-driven while the native client talks to
`backend/routes/api.php`. Every screen is written twice. Do not attempt a shared
component layer — it was considered and declined.

`packages/shared` stays dependency-free and platform-free: no `react`, no
`react-native`, no Node built-ins, no browser globals. Both frontends import it,
so anything platform-specific in there breaks one of them.

Its types come from `npm run types:generate`, which writes
`packages/shared/src/generated.ts` from the PHP Data classes. Never hand-edit
that file.

## Auth differs from the web client

Web auth is Fortify, headless, session-based. The native client uses Sanctum
tokens against the versioned JSON API. Do not reach for the Inertia endpoints
from mobile.

## The API shapes the native client reads

`GET /api/v1/next-action` and `GET /api/v1/sessions/current` return `null`
inside a 200, never a 404. A cold launch reads both, and a 404 there reads as an
error. Handle the null shape rather than treating it as a failure.

Controllers return `Data` objects directly, so the JSON body and the Inertia
prop are the same shape by construction — the generated TypeScript describes
both.

## The product invariants apply to every screen

`.ai/rules/product-invariants.md` is scoped to `mobile/**` as well as the web
client. One thing on screen, skip weighs the same as done, every recommendation
carries its `why`, no streaks, no failure words, zero required fields on
capture. Design for the bad day: open the app, one thing is there, one tap
starts it.

## Push

Push arrives with this client. Note that notification payloads carry
capture-derived titles in cleartext in `notifications.data`; encrypting that
belongs with the ingestion work, where the threat model gets written, and before
push ships.
