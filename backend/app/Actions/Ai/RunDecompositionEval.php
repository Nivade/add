<?php

declare(strict_types=1);

namespace App\Actions\Ai;

use App\Actions\Concerns\ReadsCommandAttributes;
use App\Actions\Concerns\ScoresAgainstACorpus;
use App\Data\Ai\ParsedStepData;
use App\Support\Ai\AiRequests;
use App\Support\Ai\Parsers\DecomposeParser;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Contracts\Container\Container;
use Lorisleiva\Actions\Concerns\AsCommand;
use Lorisleiva\Actions\Concerns\AsObject;
use Nvade\AiToolkit\Contracts\AiProvider;
use Nvade\AiToolkit\Exceptions\AiResponseInvalid;
use Nvade\AiToolkit\Exceptions\AiResponseTruncated;
use Nvade\AiToolkit\Providers\DispatchingAiProvider;
use Nvade\AiToolkit\Providers\OpenAiProvider;

/** Scores a seed corpus on what `DecomposeParser::violations()` checks, and writes it as a baseline. */
#[Signature('ai:eval')]
#[Description('Score the live decomposition driver against the seed corpus in storage/ai-eval.')]
final class RunDecompositionEval
{
    use AsCommand;
    use AsObject;
    use ReadsCommandAttributes;
    use ScoresAgainstACorpus;

    private readonly AiProvider $provider;

    /** The corpus is nobody's words, so no consent gate; the dispatcher keeps each call in the log. */
    public function __construct(OpenAiProvider $provider, Container $container, private readonly DecomposeParser $parser)
    {
        $this->provider = new DispatchingAiProvider($provider, $container);
    }

    /** @return list<array{id: string, shape: string, steps: list<array{title: string, estimated_seconds: int}>, violations: list<string>}> */
    public function handle(): array
    {
        $scored = array_map($this->score(...), $this->corpus());

        $this->writeBaseline('baseline.json', 'tasks', $scored);

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
        return $this->readCorpus('corpus.json');
    }

    /**
     * @param  array{id: string, shape: string, task: string}  $task
     * @return array{id: string, shape: string, steps: list<array{title: string, estimated_seconds: int}>, violations: list<string>}
     */
    private function score(array $task): array
    {
        try {
            $steps = $this->parser->parse($this->provider->respond(AiRequests::decomposeIntention(userId: null, user: 'Intention: '.$task['task']))->payload);
        } catch (AiResponseInvalid|AiResponseTruncated $aiResponseInvalid) {
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
