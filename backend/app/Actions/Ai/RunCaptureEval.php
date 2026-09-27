<?php

declare(strict_types=1);

namespace App\Actions\Ai;

use App\Contracts\AiProvider;
use App\Support\Ai\AiRequest;
use App\Support\Ai\Exceptions\AiResponseInvalid;
use App\Support\Ai\ParseCaptureExamples;
use App\Support\Ai\Parsers\ParseCaptureParser;
use App\Support\Ai\Providers\LoggingAiProvider;
use App\Support\Ai\Providers\OpenAiProvider;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Lorisleiva\Actions\Concerns\AsCommand;
use Lorisleiva\Actions\Concerns\AsObject;

/** Scores whether the live model asks when it should, and whether what it asks is the prompt's own example. */
final class RunCaptureEval
{
    use AsCommand;
    use AsObject;

    public string $commandSignature = 'ai:eval:capture';

    public string $commandDescription = 'Score the clarifying questions the live capture parser asks against the corpus in storage/ai-eval.';

    private readonly AiProvider $provider;

    public function __construct(OpenAiProvider $provider, private readonly ParseCaptureParser $parser)
    {
        $this->provider = new LoggingAiProvider($provider);
    }

    /** @return list<array{id: string, expects_question: bool, question: ?string, copied: bool, invalid: ?string}> */
    public function handle(): array
    {
        $scored = array_map($this->score(...), $this->corpus());

        File::put(storage_path('ai-eval/capture-baseline.json'), json_encode([
            'scored_at' => now()->toIso8601String(),
            'model' => config('ai.openai.model'),
            'captures' => $scored,
        ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)."\n");

        return $scored;
    }

    public function asCommand(Command $command): int
    {
        if (! $this->provider->isAvailable()) {
            $command->error('No OPENAI_API_KEY configured; nothing to score.');

            return Command::FAILURE;
        }

        $command->table(['id', 'expected a question', 'asked', 'copied from the prompt'], array_map(fn (array $result): array => [
            $result['id'],
            $result['expects_question'] ? 'yes' : 'no',
            $result['question'] ?? '—',
            $result['copied'] ? 'yes' : 'no',
        ], $this->handle()));

        return Command::SUCCESS;
    }

    /** @return list<array{id: string, capture: string, expects_question: bool}> */
    private function corpus(): array
    {
        /** @var list<array{id: string, capture: string, expects_question: bool}> */
        return json_decode(File::get(storage_path('ai-eval/capture-corpus.json')), true, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * @param  array{id: string, capture: string, expects_question: bool}  $entry
     * @return array{id: string, expects_question: bool, question: ?string, copied: bool, invalid: ?string}
     */
    private function score(array $entry): array
    {
        $now = CarbonImmutable::now('Europe/Amsterdam');
        $question = null;
        $invalid = null;

        try {
            $question = $this->parser->parse(
                $this->provider->complete(AiRequest::parseCapture(0, $entry['capture'], $now))->payload,
                $now->getTimezone()->getName(),
            )->clarifyingQuestion;
        } catch (AiResponseInvalid $aiResponseInvalid) {
            $invalid = $aiResponseInvalid->getMessage();
        }

        return [
            'id' => $entry['id'],
            'expects_question' => $entry['expects_question'],
            'question' => $question,
            'copied' => ParseCaptureExamples::isCopiedQuestion($question),
            'invalid' => $invalid,
        ];
    }
}
