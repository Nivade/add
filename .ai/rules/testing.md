---
paths:
  - 'backend/tests/**'
  - 'backend/phpunit.xml'
  - 'backend/composer.json'
---
# Testing

House convention, matching tabellio, numerosis and first-move:

```bash
npm run test            # composer test -> artisan test --parallel
npm run test:serial     # when parallel output is hiding the failure
npm run test:impact     # pest --tia --filtered, for the inner loop
```

## Pin the memory limit

Every test script carries `-d memory_limit=1G`. The default 128M is not enough:
a runaway recursion OOMs **inside Pest's output cleaner**, which swallows the
real error. `Allowed memory size ... exhausted` in `OutputCleaner.php` is the
symptom, not the bug.

## Never import a global class in a Pest file — it breaks `--parallel`

Pest files have no namespace, so `use RuntimeException;` triggers PHP's "use
statement with non-compound name has no effect" warning. Harmless serially;
paratest's stdout IPC chokes on it and crashes the whole worker. Reference
global classes bare.

```bash
grep -rn "^use [A-Za-z_]*;" backend/tests/
```

## The inverse trap: import every namespaced class you name

`SomeClass::class` without a `use` resolves to `\SomeClass` and nothing errors —
`::class` never requires the class to exist. An `app()->instance()` keyed on that
string binds something nothing resolves, the real implementation runs, and the
assertions fail for reasons that look nothing like the cause.

## Drive failures through the code that produces them

Writing a skipped row straight into the database and asserting the resolver
moved on tests the fixture, not the application. Bind a double for the action
the code calls and let the real path run.

## The engine gets scenario tests, not example tests

`NextActionResolver` is the product. Its tests build a whole world — an
appointment in 40 minutes, a five-minute step, a blocked step, a step skipped
twice — and assert which one comes back *and* what the `why` says. A resolver
test that only asserts "something came back" proves nothing.
