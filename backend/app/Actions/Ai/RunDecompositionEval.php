<?php

declare(strict_types=1);

namespace App\Actions\Ai;

use App\Support\Ai\AiRequest;
use App\Support\Ai\Exceptions\AiResponseInvalid;
use App\Support\Ai\Parsers\DecomposeParser;
use App\Support\Ai\Providers\OpenAiProvider;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Lorisleiva\Actions\Concerns\AsCommand;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * risk 2's trigger: the first `openai` run against a live key. Scores the same
 * three things `DecomposeParser::violations()` already checks in production —
 * step count, first-step size, first-step phrasing — against a small seed
 * corpus, and writes what it found as the first baseline to compare against.
 */
final class RunDecompositionEval
{
    use AsCommand;
    use AsObject;

    public string $commandSignature = 'ai:eval';

    public string $commandDescription = 'Score the live decomposition driver against the seed corpus in storage/ai-eval.';

    public function __construct(
        private readonly OpenAiProvider $provider,
        private readonly DecomposeParser $parser,
    ) {}

    public function asCommand(Command $command): int
    {
        if (! $this->provider->isAvailable()) {
            $command->error('No OPENAI_API_KEY configured; nothing to score.');

            return Command::FAILURE;
        }

        $corpus = $this->corpus();
        $rows = [];
        $scored = [];
        $passed = 0;

        foreach ($corpus as $task) {
            [$row, $result] = $this->score($task);
            $rows[] = $row;
            $scored[] = $result;
            $passed += $result['violations'] === [] ? 1 : 0;
        }

        $command->table(['id', 'steps', 'first step (s)', 'violations'], $rows);
        $command->info("{$passed}/".count($corpus).' tasks scored clean.');

        $this->writeBaseline($scored);

        return Command::SUCCESS;
    }

    /** @return list<array{id: string, shape: string, task: string}> */
    private function corpus(): array
    {
        /** @var list<array{id: string, shape: string, task: string}> */
        return json_decode(File::get($this->corpusPath()), true, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * @param  array{id: string, shape: string, task: string}  $task
     * @return array{0: list<string>, 1: array<string, mixed>}
     */
    private function score(array $task): array
    {
        $request = AiRequest::decomposeIntention(userId: 0, user: 'Intention: '.$task['task']);

        try {
            $steps = $this->parser->parse($this->provider->complete($request)->payload);
        } catch (AiResponseInvalid $exception) {
            return [
                [$task['id'], '—', '—', 'invalid response: '.$exception->getMessage()],
                ['id' => $task['id'], 'shape' => $task['shape'], 'steps' => [], 'violations' => [$exception->getMessage()]],
            ];
        }

        $violations = $this->parser->violations($steps);

        return [
            [$task['id'], (string) count($steps), (string) $steps[0]->estimatedSeconds, implode('; ', $violations) ?: 'clean'],
            [
                'id' => $task['id'],
                'shape' => $task['shape'],
                'steps' => array_map(fn ($step): array => ['title' => $step->title, 'estimated_seconds' => $step->estimatedSeconds], $steps),
                'violations' => $violations,
            ],
        ];
    }

    /** @param  list<array<string, mixed>>  $scored */
    private function writeBaseline(array $scored): void
    {
        File::put($this->baselinePath(), json_encode([
            'scored_at' => now()->toIso8601String(),
            'model' => config('ai.openai.model'),
            'tasks' => $scored,
        ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)."\n");
    }

    private function corpusPath(): string
    {
        return storage_path('ai-eval/corpus.json');
    }

    private function baselinePath(): string
    {
        return storage_path('ai-eval/baseline.json');
    }
}
