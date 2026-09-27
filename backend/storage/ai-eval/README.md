# Decomposition eval corpus

`corpus.json` — nine tasks someone might avoid, across six shapes: admin
dread, vague creative, physical mess, social cost, long horizon, already
abandoned, undefined. Seeds, not real ones; replace with real avoided tasks
when there are any, per `.ai/plans/executive-function-os.md` risk 2.

`baseline.json` — the score from the run that triggered this corpus, per risk
2: the first `openai` run against a live key. Regenerate with `ai:eval`
(`npm run artisan -- ai:eval`), which needs `OPENAI_API_KEY` and spends real
tokens on every run, so it is a deliberate command, never wired into CI or the
suite.

The scorer is `DecomposeParser::violations()`, the same one production runs on
every decomposition — step count, first-step size, first-step phrasing. This
corpus does not add a second definition of what a good answer looks like.

## Clarifying questions

`capture-corpus.json` — captures that need a clarifying question and are not
the prompt's examples, the prompt's two examples marked `-control`, and three
that need none. `ai:eval:capture` (`npm run artisan -- ai:eval:capture`) scores
whether a question was asked when one was expected and whether it is one of the
prompt's example questions, and writes `capture-baseline.json`. Same live key,
same cost, same rule: never in CI.

## Known weak spots

Recorded when the baseline was scored, still unfixed:

- `garage` — "Stand up and walk to the garage door" trips the banned-word
  check on "and". Two physical actions in one step, joined instead of split.
