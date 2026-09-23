---
paths:
  - 'backend/app/Data/**'
  - 'backend/app/Http/**'
  - 'backend/routes/api.php'
---
# API and Data Objects

## laravel-data, not API Resources

The generic `laravel-patterns` skill and Boost's guidelines both say "default to
Eloquent API Resources". This repo deliberately does not:

1. `spatie/laravel-data` is the DTO convention (`app/Data/**`); Resources would
   be a second pattern for the same job.
2. `spatie/typescript-transformer` generates `packages/shared/src/generated.ts`
   from these classes. Resources cannot, which leaves both frontends' types
   hand-maintained and drifting.
3. `Data implements Responsable` — return it straight from a controller, and the
   Inertia prop and the JSON body are then the same shape by construction.

Do not "fix" this by reintroducing Resources.

## Casing

Properties are camelCase, so the JSON is camelCase with no output mapper.
`#[MapInputName(SnakeCaseMapper::class)]` lets `Data::from($model)` read
snake_case columns. Never hand-write output keys.

## Trap: every `from*` method is magic

`DataClassFactory` registers **any** static method whose name starts with `from`
as a creation method, so `fromModel()` calling `self::from($model)` recurses
until the process dies — and the OOM lands inside Pest's output cleaner, which
hides the real error behind a 700k-frame trace. Call `Data::from($model)` at the
call site. Define a `from*` method only when it does real mapping and never
re-enters `from()`.

## Status codes and null shapes

`ResponsableData::calculateResponseStatus()` returns 201 for every POST. That is
right for creating a capture, wrong for completing a step — and every session
control returns `ExecutionStateData`, so the override lives on the Data class
(`calculateResponseStatus()` returning `HTTP_OK`) rather than in each controller.

A controller overrides locally only when the status depends on what happened:
`StoreSessionController` answers 201 or 200 on `wasRecentlyCreated`, because
starting a session that is already running creates nothing.

`GET /api/v1/next-action` and `GET /api/v1/sessions/current` return `null` in a
200, never a 404. A cold launch reads both, and a 404 there reads as an error to
every client.

## Controllers

Invokable, one per verb, thin over an action in `app/Actions/**`. Route-model
binding everywhere. Do not route straight at an action's `AsController` — see
`.ai/rules/domain-model.md`; the web and API adapters return different shapes.

## Generated TypeScript is never hand-edited

`packages/shared/src/generated.ts` comes from the PHP Data classes and enums.
Regenerate with `npm run types:generate`. To publish a new type, add
`#[TypeScript]` to the class or enum — that attribute is the only mechanism,
including for enums, because `AttributedEnumTransformer` gates on it.

Config lives in `app/Providers/TypeScriptTransformerServiceProvider.php`; v3 has
no publishable config file. Three things it must keep doing: replace
`CarbonImmutable` with `string` (otherwise every date silently becomes
`undefined` while the command still prints "All done!"), write through
`FlatModuleWriter` (the global writer emits an ambient namespace that cannot be
imported from a package), and route enums through `AttributedEnumTransformer`.
