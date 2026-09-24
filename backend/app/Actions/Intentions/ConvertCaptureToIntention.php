<?php

declare(strict_types=1);

namespace App\Actions\Intentions;

use App\Contracts\AiProvider;
use App\Contracts\DeadlineExtractor;
use App\Enums\IntentionStatus;
use App\Models\Capture;
use App\Models\Intention;
use App\Support\Ai\AiRequest;
use App\Support\Ai\Parsers\ParseCaptureParser;
use App\Support\Time\ExtractedDeadline;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsJob;
use Lorisleiva\Actions\Concerns\AsObject;

/** The extractor answers first and its answer wins; the model is asked only about what it left behind. */
final class ConvertCaptureToIntention
{
    use AsJob;
    use AsObject;

    public function __construct(
        private readonly AiProvider $provider,
        private readonly DeadlineExtractor $extractor,
        private readonly ParseCaptureParser $parser,
    ) {}

    public function handle(Capture $capture): Intention
    {
        if ($capture->intention_id !== null) {
            return $capture->intention()->firstOrFail();
        }

        $timezone = $capture->user()->sole()->timezone;
        $now = CarbonImmutable::now($timezone);

        $extracted = $this->extractor->extract($capture->body, $now);

        $parsed = $this->parser->parse(
            $this->provider->complete(AiRequest::parseCapture(
                $this->describe($this->unclaimed($capture->body, $extracted), $now)
            ))->payload,
            $timezone,
        );

        $deadlineAt = $extracted instanceof ExtractedDeadline ? $extracted->at : $parsed->deadlineAt;

        $intention = DB::transaction(function () use ($capture, $parsed, $deadlineAt): Intention {
            $intention = Intention::query()->create([
                'user_id' => $capture->user_id,
                'title' => $parsed->title,
                'why' => $parsed->why,
                'status' => IntentionStatus::Captured,
                'deadline_at' => $deadlineAt,
                'clarifying_question' => $parsed->clarifyingQuestion,
            ]);

            $capture->update(['intention_id' => $intention->id, 'processed_at' => now()]);

            return $intention;
        });

        DecomposeIntention::dispatch($intention);

        return $intention;
    }

    /** A model with no date cannot resolve "Saturday", and one with no zone answers on the wrong clock. */
    private function describe(string $text, CarbonImmutable $now): string
    {
        return implode("\n", [
            'Today is '.$now->format('l j F Y').' in '.$now->getTimezone()->getName().'.',
            '',
            $text,
        ]);
    }

    private function unclaimed(string $body, ?ExtractedDeadline $extracted): string
    {
        if (! $extracted instanceof ExtractedDeadline) {
            return $body;
        }

        $remainder = Str::squish(str_replace($extracted->phrase, ' ', $body));

        return $remainder === '' ? $body : $remainder;
    }
}
