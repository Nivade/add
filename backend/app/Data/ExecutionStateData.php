<?php

declare(strict_types=1);

namespace App\Data;

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
        public string $elapsed,
    ) {}

    /** Every control but Start mutates something that already exists, so 201 would be a lie. */
    protected function calculateResponseStatus(Request $request): int
    {
        return Response::HTTP_OK;
    }
}
