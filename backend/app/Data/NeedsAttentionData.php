<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\NeedsAttentionKind;
use App\Models\Intention;
use App\Models\WaitingFor;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** One row in the needs-attention band, whichever of the two things it comes from. */
#[TypeScript]
class NeedsAttentionData extends Data
{
    public function __construct(
        public NeedsAttentionKind $kind,
        public string $id,
        public string $title,
        public ?string $detail,
        public ?string $clarifyingQuestion,
    ) {}

    public static function forIntention(Intention $intention): self
    {
        return new self(
            NeedsAttentionKind::Intention,
            $intention->id,
            $intention->title,
            $intention->why,
            $intention->clarifying_question,
        );
    }

    public static function forWaitingFor(WaitingFor $waitingFor): self
    {
        return new self(
            NeedsAttentionKind::WaitingFor,
            $waitingFor->id,
            $waitingFor->subject,
            $waitingFor->note,
            null,
        );
    }
}
