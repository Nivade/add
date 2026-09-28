<?php

declare(strict_types=1);

namespace App\Actions\Intentions;

use App\Actions\Concerns\ConfiguresJobByAttribute;
use App\Attributes\FailOn;
use App\Contracts\AiProvider;
use App\Contracts\DeadlineExtractor;
use App\Enums\IntentionStatus;
use App\Models\Capture;
use App\Models\Intention;
use App\Support\Ai\AiRequest;
use App\Support\Ai\Exceptions\AiUnavailable;
use App\Support\Ai\Parsers\ParseCaptureParser;
use App\Support\Time\ExtractedDeadline;
use Carbon\CarbonImmutable;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsJob;
use Lorisleiva\Actions\Concerns\AsObject;

/** The extractor answers first and its answer wins; the model is asked only about what it left behind. */
#[Tries(3)]
#[Backoff(30, 120, 300)]
#[FailOn(AiUnavailable::class)]
final class ConvertCaptureToIntention
{
    use AsJob;
    use AsObject;
    use ConfiguresJobByAttribute;

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
                $capture->user_id,
                $extracted?->remainderOf($capture->body) ?? $capture->body,
                $now,
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
}
