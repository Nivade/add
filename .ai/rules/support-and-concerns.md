---
paths:
  - 'backend/app/Support/**'
  - 'backend/app/Concerns/**'
  - 'backend/app/Exceptions/**'
---
# Support and Concerns

`Support` holds adapters behind a contract, framework glue, and pure
calculation. Anything that enforces a model's own rules goes on the model, and
copy used in one place lives with that caller.

No `app/Services`. A service is an adapter behind a contract in
`app/Contracts`, bound with `#[Bind]` or in a provider.

Jobs are actions with `AsJob`. There is no `app/Jobs`.

Laravel events wait until one thing happening needs a second, independent
reaction. They never reuse the `ExecutionEvent` name.

`app/Concerns/` holds only the Fortify starter-kit validation traits,
`PasswordValidationRules` and `ProfileValidationRules`. Every other trait
lives with its layer: `app/Models/Concerns/`, `app/Http/Controllers/Concerns/`
or `app/Actions/Concerns/`.
