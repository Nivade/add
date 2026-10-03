---
paths:
  - backend/rector.php
gate: rector
---
# Rector

- **Use `withSkipPath()` for a host path, never a raw path inside `withSkip()`.** `withSkipPath()` asserts the path exists, so a file that moves fails the Rector run itself; a raw path in `withSkip()` is checked against nothing, and a moved file just stops being skipped with no warning. The shared preset's own `database/migrations` skip stays a raw `withSkip()` entry, because `withSkipPath()`'s existence assertion would fail for a package, which has no such directory.
- **`withComposerBased(laravel: true)` already version-gates the `LARAVEL_*` upgrade sets** against what the app's `composer.json` actually requires. Adding an explicit `LaravelSetList::LARAVEL_1XX` next to it is redundant, and it pins a version the composer constraint will later outgrow.
- **`withSetProviders()` is a deprecated no-op.** It does nothing on current Rector; drop it rather than carrying it forward as documentation.
