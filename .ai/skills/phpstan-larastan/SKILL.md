---
name: phpstan-larastan
description: Use when PHPStan or Larastan reports an error, when adding or changing PHPDoc for static analysis, when `npm run stan` fails, and before finishing any PHP change. Also use when tempted to add `@phpstan-ignore`, a baseline entry, or a cast purely to silence analysis. Trigger on "phpstan", "larastan", "stan", "level 7", "type error", "ignoreErrors", "baseline", "@phpstan-ignore", "never returns", "no value type specified".
---

# Static analysis

`npm run stan` from the repo root. `phpstan.neon` runs Larastan and Carbon's
extension at level 7 over `app/`, `bootstrap/app.php`, `config/`, `database/`
and `routes/` — tests are not analysed.

`composer types:check` is the same thing, and it is part of `composer test`, so
a type error fails the suite.

## Fix the type, do not silence it

There is no baseline file in this repo, and there should not be one. Level 7
errors here are usually real:

- **`no value type specified in iterable type`** — add the array shape. Generic
  `array` is not enough at this level. `@param list<Candidate> $candidates`,
  `@return array<string, mixed>`.
- **`Cannot call method on mixed`** — the value came out of `config()`, a
  decoded JSON payload or a request. Narrow it with `is_string()` / `is_array()`
  and throw on the other branch. In the AI layer that throw is
  `AiResponseInvalid` and the parser is where it belongs.
- **`match` not exhaustive** — a new enum case landed. That error is the design
  working: it is how adding an `AiOperation` reminds you to write its canned
  answer.

`@param`, `@return`, `@var`, `@template` and array shapes are tags, not prose.
The repo's comment rule bans narration, not these — PHPStan reads them and they
stay.

## Where analysis and the framework disagree

Larastan understands Eloquent's magic; it does not always understand a
`#[Bind]` on an interface or a container binding made at runtime. Type against
the contract, and let the binding resolve it.

Dev-only packages are the other seam. `spatie/laravel-typescript-transformer`,
`laravel/boost` and `nvade/devtools` are `require-dev`, and
`bootstrap/providers.php` filters out the provider that extends a class from the
first of them when that class is absent. Do not "simplify" that guard —
`composer install --no-dev` fatals on boot without it.

## Rector is separate and is not optional

Pint does not run Rector, and `npm run lint` is Pint only. Rector's rewrites
accumulate until they land, unrelated, in someone else's diff. Run
`composer refactor:check` before committing and land its changes as their own
commit.

`rector.php` takes the `nvade/devtools` preset and adds `LaravelSetList::LARAVEL_130`,
whose attribute rules are what actually enforce preferring attributes over class
properties. It skips `app/Support/Ai/Prompts.php`, because a byte change there
moves every AI cache key.
