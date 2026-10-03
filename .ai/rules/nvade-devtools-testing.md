---
paths:
  - 'backend/tests/**'
---
# Testing

- **Never `use` a global class at the top of a Pest file.** Under `--parallel` (the `test`/`test:serial` split this component's scripts run), each worker's warning about it crashes the worker instead of merely printing. Import inside the closure, or reference the fully qualified name.
- **Import every namespaced class passed to `::class`.** An unimported `Some\Class::class` resolves to a string under the current namespace, not the class it names — the test can pass while asserting against a name nothing implements.
- **Pin `memory_limit`** (`-d memory_limit=1G` or higher) on the test invocation. The default CLI limit is tuned for a request, not a whole suite booting the framework once per test class.
- **Drive a failure through the code that produces it, not through a hand-built double of its output.** A double drifts from what the real code actually does the moment either one changes without the other; a test built on real behaviour catches that drift instead of hiding it.
- **`putenv()` only takes effect on the first application boot in a process.** A test that changes an env var with `putenv()` after Testbench has already booted once in that process is asserting against the old value; use `Config::set()` or the app's own config, not the environment, once boot has happened.
- **`$this->artisan(...)` assertions (`assertExitCode`, `expectsOutput`, ...) run on destruct, not on the call.** A failure surfaces on the *next* assertion or at the end of the test, with a misleading location. Chain the assertion the framework gives you rather than assigning the pending command and inspecting it later.
- **`RefreshDatabase` fires its after-commit callbacks one transaction level early.** Code that queues work via `DB::afterCommit()` and expects it to run only once the outermost transaction closes sees it fire inside the test's own wrapping transaction instead — a real difference from production, not a flake.
- **A guard that memoizes the current user caches it for the rest of that test.** Switching actors mid-test (`actingAs()` a second time) does not invalidate a guard's own per-request memoization inside a single Pest test, since Pest does not tear down between assertions the way separate requests would.
- **A notification is written on the notifiable's own database connection**, not the app's default one. A multi-connection test asserting a notification landed on the wrong connection is asserting against the notifiable, not against config.
