<?php

declare(strict_types=1);

namespace App\Actions\Sessions;

use App\Enums\ExecutionEventType;
use App\Models\ExecutionEvent;
use App\Models\ExecutionSession;
use Lorisleiva\Actions\Concerns\AsObject;

/** The events are the replay: one per transition, its payload only what the row cannot hold, such as the rung behind a start. */
final class RecordExecutionEvent
{
    use AsObject;

    /** @param  array<string, mixed>|null  $payload */
    public function handle(
        ExecutionSession $session,
        ExecutionEventType $type,
        ?string $stepId = null,
        ?array $payload = null,
    ): ExecutionEvent {
        return $session->events()->create([
            'step_id' => $stepId ?? $session->current_step_id,
            'type' => $type,
            'payload' => $payload,
        ]);
    }
}
