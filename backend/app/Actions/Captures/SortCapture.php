<?php

declare(strict_types=1);

namespace App\Actions\Captures;

use App\Actions\Commitments\CreateCommitment;
use App\Actions\Concerns\ConfiguresJobByAttribute;
use App\Actions\FutureReminders\CreateFutureReminder;
use App\Actions\Ingestion\ClassifyPastedText;
use App\Actions\Intentions\DecomposeIntention;
use App\Actions\WaitingFor\CreateWaitingFor;
use App\Attributes\FailOn;
use App\Contracts\DeadlineExtractor;
use App\Data\Ai\ParsedCaptureData;
use App\Enums\CaptureKind;
use App\Enums\CommitmentProvenance;
use App\Enums\IntentionStatus;
use App\Models\Capture;
use App\Models\Intention;
use App\Models\User;
use App\Support\Ai\AiRequests;
use App\Support\Ai\Parsers\ParseCaptureParser;
use Carbon\CarbonImmutable;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsJob;
use Lorisleiva\Actions\Concerns\AsObject;
use Nvade\AiToolkit\Contracts\AiProvider;
use Nvade\AiToolkit\Exceptions\AiUnavailable;
use Throwable;

/** The model is asked once per capture: its answer is stored, so a changed kind routes again without asking. */
#[Tries(3)]
#[Backoff(30, 120, 300)]
#[FailOn(AiUnavailable::class)]
final class SortCapture
{
    use AsJob;
    use AsObject;
    use ConfiguresJobByAttribute;

    private const int LONG_TEXT_CHARACTERS = 280;

    public function __construct(
        private readonly AiProvider $provider,
        private readonly DeadlineExtractor $extractor,
        private readonly ParseCaptureParser $parser,
    ) {}

    public function handle(Capture $capture, ?CaptureKind $chosen = null): Capture
    {
        if ($capture->kind !== null && ! $chosen instanceof CaptureKind) {
            return $capture;
        }

        $user = $capture->user()->sole();

        if (! $capture->parsed instanceof ParsedCaptureData && ! $chosen instanceof CaptureKind && $this->isLong($capture->body)) {
            $capture->parsed = $this->classify($capture, $user);
            $capture->save();

            if ($capture->kind === CaptureKind::NotForYou) {
                return $capture;
            }
        }

        if (! $capture->parsed instanceof ParsedCaptureData) {
            $capture->parsed = $this->parse($capture, $user);
            $capture->save();
        }

        $this->route($capture, $user, $capture->parsed, $chosen);

        return $capture;
    }

    public function jobFailed(Throwable $e, Capture $capture): void
    {
        $capture->update(['failed_at' => now()]);
    }

    /** A blank line means pasted paragraphs, which is what the classifier reads. */
    private function isLong(string $body): bool
    {
        return mb_strlen($body) > self::LONG_TEXT_CHARACTERS || preg_match('/\R\s*\R/', $body) === 1;
    }

    private function classify(Capture $capture, User $user): ParsedCaptureData
    {
        $classified = ClassifyPastedText::run($user, $capture->body);

        if (! $classified->actionable) {
            $capture->kind = CaptureKind::NotForYou;
            $capture->processed_at = now()->toImmutable();
        }

        return new ParsedCaptureData(
            title: $classified->title ?? Str::limit($capture->body, 80),
            why: $classified->why,
            deadlineAt: $classified->deadlineAt,
            clarifyingQuestion: null,
            kind: CaptureKind::Thought,
            waitingOn: null,
        );
    }

    /** The extractor answers first and its answer wins; the model is asked only about what it left behind. */
    private function parse(Capture $capture, User $user): ParsedCaptureData
    {
        $now = CarbonImmutable::now($user->timezone);
        $extracted = $this->extractor->extract($capture->body, $now);

        $parsed = $this->parser->parse(
            $this->provider->respond(AiRequests::parseCapture(
                $capture->user_id,
                $extracted?->remainderOf($capture->body) ?? $capture->body,
                $now,
            ))->payload,
            $user->timezone,
        );

        $parsed->deadlineAt = $extracted->at ?? $parsed->deadlineAt;

        return $parsed;
    }

    private function route(Capture $capture, User $user, ParsedCaptureData $parsed, ?CaptureKind $chosen): void
    {
        DB::transaction(function () use ($capture, $user, $parsed, $chosen): void {
            $kind = $chosen ?? $parsed->kind;

            $routedId = match ($kind) {
                CaptureKind::WaitingFor => CreateWaitingFor::run($user, $parsed->waitingOn ?? $parsed->title, $parsed->waitingOn === null ? null : $parsed->title)->id,
                CaptureKind::Promise => CreateCommitment::run($user, $parsed->title, $chosen instanceof CaptureKind ? CommitmentProvenance::UserStated : CommitmentProvenance::SystemInferred)->id,
                CaptureKind::Reminder => $this->remind($user, $capture),
                default => null,
            };

            if ($routedId === null) {
                $kind = CaptureKind::Thought;
                $routedId = $this->intend($capture, $parsed)->id;
            }

            $capture->update([
                'kind' => $kind,
                'routed_id' => $routedId,
                'processed_at' => now(),
                'failed_at' => null,
                'kind_confirmed_at' => $chosen instanceof CaptureKind ? now() : null,
            ]);
        });
    }

    /** No time in the words means nothing to remind at, so it is kept as a thought instead. */
    private function remind(User $user, Capture $capture): ?string
    {
        try {
            return CreateFutureReminder::run($user, $capture->body)->id;
        } catch (ValidationException) {
            return null;
        }
    }

    private function intend(Capture $capture, ParsedCaptureData $parsed): Intention
    {
        $intention = Intention::query()->create([
            'user_id' => $capture->user_id,
            'title' => $parsed->title,
            'why' => $parsed->why,
            'status' => IntentionStatus::Captured,
            'deadline_at' => $parsed->deadlineAt,
            'clarifying_question' => $parsed->clarifyingQuestion,
        ]);

        $capture->intention_id = $intention->id;

        DecomposeIntention::dispatch($intention)->afterCommit();

        return $intention;
    }
}
