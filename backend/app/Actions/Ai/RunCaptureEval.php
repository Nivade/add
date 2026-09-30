<?php

declare(strict_types=1);

namespace App\Actions\Ai;

use App\Actions\Concerns\ScoresAgainstACorpus;
use App\Support\Ai\AiRequests;
use App\Support\Ai\ParseCaptureExamples;
use App\Support\Ai\Parsers\ParseCaptureParser;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Contracts\Container\Container;
use Lorisleiva\Actions\Concerns\AsCommand;
use Lorisleiva\Actions\Concerns\AsObject;
use Nvade\AiToolkit\Contracts\AiProvider;
use Nvade\AiToolkit\Exceptions\AiResponseInvalid;
use Nvade\AiToolkit\Exceptions\AiResponseTruncated;
use Nvade\AiToolkit\Providers\DispatchingAiProvider;
use Nvade\AiToolkit\Providers\OpenAiProvider;

/** Scores whether the live model asks when it should, and whether what it asks is the prompt's own example. */
final class RunCaptureEval
{
    use AsCommand;
    use AsObject;
    use ScoresAgainstACorpus;

    public string $commandSignature = 'ai:eval:capture';

    public string $commandDescription = 'Score the clarifying questions the live capture parser asks against the corpus in storage/ai-eval.';

    private readonly AiProvider $provider;

    /** The corpus is nobody's words, so no consent gate; the dispatcher keeps each call in the log. */
    public function __construct(OpenAiProvider $provider, Container $container, private readonly ParseCaptureParser $parser)
    {
        $this->provider = new DispatchingAiProvider($provider, $container);
    }

    /** @return list<array{id: string, expects_question: bool, question: ?string, copied: bool, invalid: ?string}> */
    public function handle(): array
    {
        $scored = array_map($this->score(...), $this->corpus());

        $this->writeBaseline('capture-baseline.json', 'captures', $scored);

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
            $result['invalid'] !== null ? 'invalid response: '.$result['invalid'] : $result['question'] ?? '—',
            $result['copied'] ? 'yes' : 'no',
        ], $this->handle()));

        return Command::SUCCESS;
    }

    /** @return list<array{id: string, capture: string, expects_question: bool}> */
    private function corpus(): array
    {
        /** @var list<array{id: string, capture: string, expects_question: bool}> */
        return $this->readCorpus('capture-corpus.json');
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
                $this->provider->respond(AiRequests::parseCapture(null, $entry['capture'], $now))->payload,
                $now->getTimezone()->getName(),
            )->clarifyingQuestion;
        } catch (AiResponseInvalid|AiResponseTruncated $aiResponseInvalid) {
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
