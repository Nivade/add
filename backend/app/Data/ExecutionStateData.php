<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\ExecutionSession;
use App\Support\Execution\ProgressLines;
use Illuminate\Http\Request;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Symfony\Component\HttpFoundation\Response;

/** What execution mode renders: one step, the intention it belongs to, and progress nobody wrote by hand. */
#[TypeScript]
class ExecutionStateData extends Data
{
    /** @param  list<string>  $progress */
    public function __construct(
        public ExecutionSessionData $session,
        public IntentionData $intention,
        public array $progress,
    ) {}

    public static function of(ExecutionSession $session): self
    {
        return new self(
            ExecutionSessionData::from($session->refresh()->load('currentStep')),
            IntentionData::from($session->intention),
            ProgressLines::for($session),
        );
    }

    /** Every control but Start mutates something that already exists, so 201 would be a lie. */
    protected function calculateResponseStatus(Request $request): int
    {
        return Response::HTTP_OK;
    }
}
