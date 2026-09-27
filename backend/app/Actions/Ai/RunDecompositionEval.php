<?php

declare(strict_types=1);

namespace App\Actions\Ai;

use App\Contracts\AiProvider;
use App\Data\Ai\ParsedStepData;
use App\Support\Ai\AiRequest;
use App\Support\Ai\Exceptions\AiResponseInvalid;
use App\Support\Ai\Parsers\DecomposeParser;
use App\Support\Ai\Providers\LoggingAiProvider;
use App\Support\Ai\Providers\OpenAiProvider;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Lorisleiva\Actions\Concerns\AsCommand;
use Lorisleiva\Actions\Concerns\AsObject;

/** Scores a seed corpus on what `DecomposeParser::violations()` checks, and writes it as a baseline. */
final class RunDecompositionEval
{
    use AsCommand;
    use AsObject;

    public string $commandSignature = 'ai:eval';

    public string $commandDescription = 'Score the live decomposition driver against the seed corpus in storage/ai-eval.';

    private readonly AiProvider $provider;

    public function __construct(OpenAiProvider $provider, private readonly DecomposeParser $parser)
    {
        $this->provider = new LoggingAiProvider($provider);
    }

    /** @return list<array{id: string, shape: string, steps: list<array{title: string, estimated_seconds: int}>, violations: list<string>}> */
    public function handle(): array
    {
        $scored = array_map($this->score(...), $this->corpus());

        File::put(storage_path('ai-eval/baseline.json'), json_encode([
            'scored_at' => now()->toIso8601String(),
            'model' => config('ai.openai.model'),
            'tasks' => $scored,
        ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)."\n");

        return $scored;
    }

    public function asCommand(Command $command): int
    {
        if (! $this->provider->isAvailable()) {
            $command->error('No OPENAI_API_KEY configured; nothing to score.');

            return Command::FAILURE;
        }

        $scored = $this->handle();

        $command->table(['id', 'steps', 'first step (s)', 'violations'], array_map(fn (array $task): array => [
            $task['id'],
            (string) count($task['steps']),
            isset($task['steps'][0]) ? (string) $task['steps'][0]['estimated_seconds'] : '—',
            implode('; ', $task['violations']) ?: 'clean',
        ], $scored));

        $clean = count(array_filter($scored, fn (array $task): bool => $task['violations'] === []));
        $command->info("{$clean}/".count($scored).' tasks scored clean.');

        return Command::SUCCESS;
    }

    /** @return list<array{id: string, shape: string, task: string}> */
    private function corpus(): array
    {
        /** @var list<array{id: string, shape: string, task: string}> */
        return json_decode(File::get(storage_path('ai-eval/corpus.json')), true, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * @param  array{id: string, shape: string, task: string}  $task
     * @return array{id: string, shape: string, steps: list<array{title: string, estimated_seconds: int}>, violations: list<string>}
     */
    private function score(array $task): array
    {
        try {
            $steps = $this->parser->parse($this->provider->complete(AiRequest::decomposeIntention(userId: 0, user: 'Intention: '.$task['task']))->payload);
        } catch (AiResponseInvalid $aiResponseInvalid) {
            return ['id' => $task['id'], 'shape' => $task['shape'], 'steps' => [], 'violations' => ['invalid response: '.$aiResponseInvalid->getMessage()]];
        }

        return [
            'id' => $task['id'],
            'shape' => $task['shape'],
            'steps' => array_map(fn (ParsedStepData $step): array => ['title' => $step->title, 'estimated_seconds' => $step->estimatedSeconds], $steps),
            'violations' => $this->parser->violations($steps),
        ];
    }
}
