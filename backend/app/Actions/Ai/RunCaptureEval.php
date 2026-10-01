<?php

declare(strict_types=1);

namespace App\Actions\Ai;

use App\Actions\Concerns\ScoresAgainstACorpus;
use App\Support\Ai\AiRequests;
use App\Support\Ai\ParseCaptureExamples;
use App\Support\Ai\Parsers\ParseCaptureParser;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Lorisleiva\Actions\Concerns\AsCommand;
use Lorisleiva\Actions\Concerns\AsObject;
use Nvade\AiToolkit\Contracts\AiProvider;
use Nvade\AiToolkit\Exceptions\AiResponseInvalid;
use Nvade\AiToolkit\Exceptions\AiResponseTruncated;

/** Scores whether the live model asks when it should, whether what it asks is the prompt's own example, and which kind it sorts into. */
final class RunCaptureEval
{
    use AsCommand;
    use AsObject;
    use ScoresAgainstACorpus;

    public string $commandSignature = 'ai:eval:capture';

    public string $commandDescription = 'Score the clarifying questions and kinds the live capture parser answers against the corpus in storage/ai-eval.';

    public function __construct(private readonly AiProvider $provider, private readonly ParseCaptureParser $parser) {}

    /** @return list<array{id: string, expects_question: bool, question: ?string, copied: bool, expects_kind: string, kind: ?string, kind_matches: bool, invalid: ?string}> */
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

        $command->table(['id', 'expected a question', 'asked', 'copied from the prompt', 'expected kind', 'kind', 'kind matches'], array_map(fn (array $result): array => [
            $result['id'],
            $result['expects_question'] ? 'yes' : 'no',
            $result['invalid'] !== null ? 'invalid response: '.$result['invalid'] : $result['question'] ?? '—',
            $result['copied'] ? 'yes' : 'no',
            $result['expects_kind'],
            $result['kind'] ?? '—',
            $result['kind_matches'] ? 'yes' : 'no',
        ], $this->handle()));

        return Command::SUCCESS;
    }

    /** @return list<array{id: string, capture: string, expects_question: bool, expects_kind: string}> */
    private function corpus(): array
    {
        /** @var list<array{id: string, capture: string, expects_question: bool, expects_kind: string}> */
        return $this->readCorpus('capture-corpus.json');
    }

    /**
     * @param  array{id: string, capture: string, expects_question: bool, expects_kind: string}  $entry
     * @return array{id: string, expects_question: bool, question: ?string, copied: bool, expects_kind: string, kind: ?string, kind_matches: bool, invalid: ?string}
     */
    private function score(array $entry): array
    {
        $now = CarbonImmutable::now('Europe/Amsterdam');
        $parsed = null;
        $invalid = null;

        try {
            $parsed = $this->parser->parse(
                $this->provider->respond(AiRequests::parseCapture(null, $entry['capture'], $now))->payload,
                $now->getTimezone()->getName(),
            );
        } catch (AiResponseInvalid|AiResponseTruncated $aiResponseInvalid) {
            $invalid = $aiResponseInvalid->getMessage();
        }

        $question = $parsed?->clarifyingQuestion;
        $kind = $parsed?->kind->value;

        return [
            'id' => $entry['id'],
            'expects_question' => $entry['expects_question'],
            'question' => $question,
            'copied' => ParseCaptureExamples::isCopiedQuestion($question),
            'expects_kind' => $entry['expects_kind'],
            'kind' => $kind,
            'kind_matches' => $kind === $entry['expects_kind'],
            'invalid' => $invalid,
        ];
    }
}
