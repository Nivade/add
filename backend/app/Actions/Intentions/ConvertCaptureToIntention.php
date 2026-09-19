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

        $extracted = $this->extractor->extract(
            $capture->body,
            CarbonImmutable::now($capture->user()->sole()->timezone)
        );

        $parsed = $this->parser->parse(
            $this->provider->complete(AiRequest::parseCapture($this->unclaimed($capture->body, $extracted)))->payload
        );

        // Read in the person's zone, stored as the instant it names.
        $deadlineAt = ($extracted instanceof ExtractedDeadline ? $extracted->at : $parsed->deadlineAt)?->utc();

        $intention = DB::transaction(function () use ($capture, $parsed, $deadlineAt): Intention {
            $intention = Intention::query()->create([
                'user_id' => $capture->user_id,
                'title' => $parsed->title,
                'why' => $parsed->why,
                'status' => IntentionStatus::Captured,
                'deadline_at' => $deadlineAt,
                'needs_clarification' => $parsed->needsClarification,
            ]);

            $capture->update(['intention_id' => $intention->id, 'processed_at' => now()]);

            return $intention;
        });

        DecomposeIntention::dispatch($intention);

        return $intention;
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
