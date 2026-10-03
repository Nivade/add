---
paths:
  - 'backend/app/**/*.php'
  - 'backend/src/**/*.php'
---
# Laravel

- **Never re-throw a caught exception's `getCode()`.** `PDOException::getCode()` returns a string (the SQLSTATE), and every signature expecting an exception code as an `int` — including `new self(..., $e->getCode())` on most built-in exceptions — throws a `TypeError` inside the very catch block meant to handle the failure. Pass `(int) $e->getCode()` or drop the code.
- **Never name a job helper `fail()`.** `Illuminate\Queue\InteractsWithQueue::fail()` is already the trait method a failed job calls to mark itself failed; a same-named helper method shadows it silently, and the job's own failure handling stops firing.
- **Dispatch each fact from one site.** The same event, notification, or job fired from two call sites drifts the moment one of them changes payload or guard conditions without the other — treat a second dispatch site for the same fact as a sign the two call paths should share one, not duplicate it.
