<?php

declare(strict_types=1);

namespace App\Actions\Ai;

use App\Actions\Intentions\ConvertCaptureToIntention;
use App\Support\Ai\AiRequest;
use App\Support\Ai\Exceptions\AiResponseInvalid;
use App\Support\Ai\Parsers\ParseCaptureParser;
use App\Support\Ai\Prompts;
use App\Support\Ai\Providers\OpenAiProvider;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsCommand;
use Lorisleiva\Actions\Concerns\AsObject;

/** Scores whether the live model asks when it should, and whether what it asks is the prompt's own example. */
final class RunCaptureEval
{
    use AsCommand;
    use AsObject;

    public string $commandSignature = 'ai:eval:capture';

    public string $commandDescription = 'Score the clarifying questions the live capture parser asks against the corpus in storage/ai-eval.';

    public function __construct(
        private readonly OpenAiProvider $provider,
        private readonly ParseCaptureParser $parser,
    ) {}

    public function asCommand(Command $command): int
    {
        if (! $this->provider->isAvailable()) {
            $command->error('No OPENAI_API_KEY configured; nothing to score.');

            return Command::FAILURE;
        }

        $scored = array_map($this->score(...), $this->corpus());

        $command->table(['id', 'expected a question', 'asked', 'copied from the prompt'], array_map(fn (array $result): array => [
            $result['id'],
            $result['expects_question'] ? 'yes' : 'no',
            $result['question'] ?? '—',
            $result['copied'] ? 'yes' : 'no',
        ], $scored));

        File::put(storage_path('ai-eval/capture-baseline.json'), json_encode([
            'scored_at' => now()->toIso8601String(),
            'model' => config('ai.openai.model'),
            'captures' => $scored,
        ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)."\n");

        return Command::SUCCESS;
    }

    /** Case and punctuation are ignored, so a copy with a changed comma still counts as one. */
    public static function copiesPrompt(?string $question): bool
    {
        if ($question === null) {
            return false;
        }

        preg_match_all('/clarifying_question "([^"]+)"/', Prompts::PARSE_CAPTURE, $examples);

        return in_array(self::normalise($question), array_map(self::normalise(...), $examples[1]), true);
    }

    private static function normalise(string $text): string
    {
        return Str::squish((string) preg_replace('/[^a-z0-9 ]/', '', mb_strtolower($text)));
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
        $request = AiRequest::parseCapture(userId: 0, user: ConvertCaptureToIntention::describe($entry['capture'], $now));

        try {
            $question = $this->parser->parse($this->provider->complete($request)->payload, $now->getTimezone()->getName())->clarifyingQuestion;
        } catch (AiResponseInvalid $aiResponseInvalid) {
            return ['id' => $entry['id'], 'expects_question' => $entry['expects_question'], 'question' => null, 'copied' => false, 'invalid' => $aiResponseInvalid->getMessage()];
        }

        return ['id' => $entry['id'], 'expects_question' => $entry['expects_question'], 'question' => $question, 'copied' => self::copiesPrompt($question), 'invalid' => null];
    }
}
