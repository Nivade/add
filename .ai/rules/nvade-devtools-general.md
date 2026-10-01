---
paths:
  - 'backend/**/*.php'
---
# General

- **A comment carries one fact and never cites a rule file, a skill, or a plan.** State the fact where the reader can see it; a citation to `.ai/rules/`, a skill, or `.claude/plans/` goes stale independently of the code and is invisible to a reader who only has the source (a Composer dist install has no `.ai/` or `.claude/` at all). `tests/` is exempt: a test that exists to hold a rule down is allowed to name it, because the citation is the test's subject.
- **A document states decisions, not counts or status.** A baseline size, an error count, a percentage — anything that changes on the next run — reads as current for exactly as long as nobody re-runs the check that produced it, and then it is a lie nobody notices. Say what changed and why; let the tool that counts things report the count.
- **A rule nothing can fail rots.** Writing a fact down does not keep it true. Hold it down with a test that fails when the fact stops holding, the same way a guard test enforces the citation rule above.
- **Pint does not run Rector.** `pint --dirty` and `composer lint` (Pint alone) leave Rector's rewrites unapplied; only `composer refactor`/`composer lint:check` reach it, and nothing else triggers it. Its rewrites then accumulate until they land, unrelated, in whoever's diff runs it next. Run `composer refactor` once before committing, and split any rewrite Rector makes that your change did not ask for into its own commit.
